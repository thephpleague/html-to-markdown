<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown;

use League\HTMLToMarkdown\Converter\ConverterInterface;
use League\HTMLToMarkdown\Converter\DefaultConverter;
use League\HTMLToMarkdown\Converter\DivConverter;
use League\HTMLToMarkdown\Converter\LinkConverter;

/**
 * A helper class to convert HTML to Markdown.
 *
 * @author Colin O'Dell <colinodell@gmail.com>
 * @author Nick Cernis <nick@cern.is>
 *
 * @link https://github.com/thephpleague/html-to-markdown/ Latest version on GitHub.
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class HtmlConverter implements HtmlConverterInterface
{
    /** @var Environment */
    protected $environment;

    /**
     * Whether raw HTML has been output which could make up an HTML block reaching the element being converted.
     *
     * Markdown isn't parsed inside of one, so nothing there can be protected by Markdown syntax.
     *
     * @var bool
     */
    private $isAfterRawHtml = false;

    /**
     * Whether the element being converted is inside of a table which gets converted without any HTML being kept in it.
     *
     * Any other table's rows can end up next to HTML which was output in a different place within the table.
     *
     * @var bool
     */
    private $isInsideCleanTable = false;

    /**
     * Constructor
     *
     * @param Environment|array<string, mixed> $options Environment object or configuration options
     */
    public function __construct($options = [])
    {
        if ($options instanceof Environment) {
            $this->environment = $options;
        } elseif (\is_array($options)) {
            $defaults = [
                'header_style' => 'setext', // Set to 'atx' to output H1 and H2 headers as # Header1 and ## Header2
                'suppress_errors' => true, // Set to false to show warnings when loading malformed HTML
                'strip_tags' => false, // Set to true to strip tags that don't have markdown equivalents. N.B. Strips tags, not their content. Useful to clean MS Word HTML output.
                'strip_placeholder_links' => false, // Set to true to remove <a> that doesn't have href.
                'bold_style' => '**', // DEPRECATED: Set to '__' if you prefer the underlined style
                'italic_style' => '*', // DEPRECATED: Set to '_' if you prefer the underlined style
                'remove_nodes' => '', // space-separated list of dom nodes that should be removed. example: 'meta style script'
                'hard_break' => false, // Set to true to turn <br> into `\n` instead of `  \n`
                'list_item_style' => '-', // Set the default character for each <li> in a <ul>. Can be '-', '*', or '+'
                'preserve_comments' => false, // Set to true to preserve comments, or set to an array of strings to preserve specific comments
                'use_autolinks' => true, // Set to true to use simple link syntax if possible. Will always use []() if set to false
                'table_pipe_escape' => '\|', // Replacement string for pipe characters inside markdown table cells
                'table_caption_side' => 'top', // Set to 'top' or 'bottom' to show <caption> content before or after table, null to suppress
            ];

            $this->environment = Environment::createDefaultEnvironment($defaults);

            $this->environment->getConfig()->merge($options);
        }
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getConfig(): Configuration
    {
        return $this->environment->getConfig();
    }

    /**
     * Convert
     *
     * @see HtmlConverter::convert
     *
     * @return string The Markdown version of the html
     */
    public function __invoke(string $html): string
    {
        return $this->convert($html);
    }

    /**
     * Convert
     *
     * Loads HTML and passes to getMarkdown()
     *
     * @return string The Markdown version of the html
     *
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function convert(string $html): string
    {
        if (\trim($html) === '') {
            return '';
        }

        $document = $this->createDOMDocument($html);

        // Work on the entire DOM tree (including head and body)
        if (! ($root = $document->getElementsByTagName('html')->item(0))) {
            throw new \InvalidArgumentException('Invalid HTML was provided');
        }

        $this->isAfterRawHtml     = false;
        $this->isInsideCleanTable = false;

        $flattensTableCells = ! $this->environment->getConverterByTag('td') instanceof DefaultConverter;
        $this->getConfig()->setOption(RawHtml::FLATTENS_TABLE_CELLS, $flattensTableCells);
        $this->getConfig()->setOption(RawHtml::KEEPS_ALL_CODE_AS_HTML, $flattensTableCells && $this->hasMalformedTable($document));

        $rootElement = new Element($root);
        $this->convertChildren($rootElement);

        // Store the now-modified DOMDocument as a string
        $markdown = $document->saveHTML();

        if ($markdown === false) {
            throw new \RuntimeException('Unknown error occurred during HTML to Markdown conversion');
        }

        return $this->sanitize($markdown);
    }

    private function createDOMDocument(string $html): \DOMDocument
    {
        $document = new \DOMDocument();

        if ($this->getConfig()->getOption('suppress_errors')) {
            // Suppress conversion errors (from http://bit.ly/pCCRSX)
            \libxml_use_internal_errors(true);
        }

        // Hack to load utf-8 HTML (from http://bit.ly/pVDyCt)
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        $document->encoding = 'UTF-8';

        $this->replaceMisplacedComments($document);

        if ($this->getConfig()->getOption('suppress_errors')) {
            \libxml_clear_errors();
        }

        return $document;
    }

    /**
     * Finds any comment nodes outside <html> element and moves them into <body>.
     *
     * The same goes for processing instructions, which would otherwise be output before the root elements.
     *
     * @see https://github.com/thephpleague/html-to-markdown/issues/212
     * @see https://3v4l.org/7bC33
     */
    private function replaceMisplacedComments(\DOMDocument $document): void
    {
        // Find ny comment nodes at the root of the document.
        $misplacedComments = (new \DOMXPath($document))->query('/comment() | /processing-instruction()[name() != "xml"]');
        if ($misplacedComments === false) {
            return;
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return;
        }

        // Loop over comment nodes in reverse so we put them inside <body> in
        // their original order.
        for ($index = $misplacedComments->length - 1; $index >= 0; $index--) {
            // DOMNameSpaceNode, which item() can also return, isn't a DOMNode and can't be inserted.
            $comment = $misplacedComments->item($index);
            if (! $comment instanceof \DOMNode) {
                continue;
            }

            if ($body->firstChild === null) {
                $body->insertBefore($comment);
            } else {
                $body->insertBefore($comment, $body->firstChild);
            }
        }
    }

    /**
     * Convert Children
     *
     * Recursive function to drill into the DOM and convert each node into Markdown from the inside out.
     *
     * Finds children of each node and convert those to #text nodes containing their Markdown equivalent,
     * starting with the innermost element and working up to the outermost element.
     */
    private function convertChildren(ElementInterface $element): void
    {
        // Don't convert HTML code inside <code> and <pre> blocks to Markdown - that should stay as HTML
        // except if the current node is a code tag, which needs to be converted by the CodeConverter.
        if ($element->isDescendantOf(['pre', 'code']) && $element->getTagName() !== 'code') {
            return;
        }

        // Give converter a chance to inspect/modify the DOM before children are converted
        $tag       = $element->getTagName();
        $converter = $this->environment->getConverterByTag($tag);
        if ($converter instanceof PreConverterInterface) {
            $converter->preConvert($element);
        }

        $wasAfterRawHtml = $this->isAfterRawHtml;

        // This has to be worked out first, since it depends on what came before the element
        $leadingBlankLine = $this->getLeadingBlankLine($element, $converter);
        $startsHtmlBlock  = $this->isKeptAsHtml($element, $converter) && $this->canStartHtmlBlock($element);

        if ($startsHtmlBlock) {
            $this->isAfterRawHtml = true;
        } elseif ($this->startsOnOwnLine($element, $converter)) {
            $this->isAfterRawHtml = false;
        }

        $isCleanTable = $tag === 'table' && $this->isCleanTable($element, $converter);
        if ($isCleanTable) {
            $this->isInsideCleanTable = true;
        }

        // If the node has children, convert those to Markdown first
        if ($element->hasChildren()) {
            foreach ($element->getChildren() as $child) {
                $this->convertChildren($child);
            }
        }

        if ($isCleanTable) {
            $this->isInsideCleanTable = false;
        }

        // Now that child nodes have been converted, convert the original node.
        // Whatever converter it has, inline code can't be left as Markdown where that might not get parsed.
        if ($tag === 'code' && ! $element->isDescendantOf('pre') && $this->isWhereMarkdownMightNotBeParsed($element)) {
            $markdown = $this->isRemoved($tag) ? '' : RawHtml::fromCode(RawHtml::getCode($element));
        } else {
            $markdown = $this->convertToMarkdown($element);
        }

        if ($startsHtmlBlock) {
            // Its closing tag can start one too
            $this->isAfterRawHtml = true;
        } elseif ($this->endsOnBlankLine($element, $converter, $markdown)) {
            $this->isAfterRawHtml = false;
        } elseif (RawHtml::startsBlock($markdown) || $this->isTableRows($element, $converter)) {
            // Anything else output as HTML, such as a comment, as well as table rows which no blank line follows,
            // since whatever comes next would be taken as another row, cells and all
            $this->isAfterRawHtml = true;
        }

        // Nothing inside of a removed element was output at all
        if ($this->isRemoved($tag)) {
            $this->isAfterRawHtml = $wasAfterRawHtml;
        }

        if ($markdown !== '') {
            $markdown = $leadingBlankLine . $markdown;

            // Otherwise whatever came next could be taken as part of it
            if ($this->isOutsideOfItsContainer($element, $converter)) {
                $markdown .= "\n";
            }
        }

        // Create a DOM text node containing the Markdown equivalent of the original node

        // Replace the old $node e.g. '<h3>Title</h3>' with the new $markdown_node e.g. '### Title'
        $element->setFinalMarkdown($markdown);
    }

    /**
     * Convert to Markdown
     *
     * Converts an individual node into a #text node containing a string of its Markdown equivalent.
     *
     * Example: An <h3> node with text content of 'Title' becomes a text node with content of '### Title'
     *
     * @return string The converted HTML as Markdown
     */
    protected function convertToMarkdown(ElementInterface $element): string
    {
        $tag = $element->getTagName();

        // Strip nodes named in remove_nodes
        if ($this->isRemoved($tag)) {
            return '';
        }

        $converter = $this->environment->getConverterByTag($tag);

        return $converter->convert($element);
    }

    private function isRemoved(string $tag): bool
    {
        $tagsToRemove = \explode(' ', Coerce::toString($this->getConfig()->getOption('remove_nodes') ?? ''));

        return \in_array($tag, $tagsToRemove, true);
    }

    /**
     * Whether any part of a table is somewhere other than where the table converter expects it.
     *
     * Rows and cells are then output wherever they happen to be, which nothing else here accounts for.
     */
    private function hasMalformedTable(\DOMDocument $document): bool
    {
        $notRow     = 'not(self::tr)';
        $notSection = 'not(self::caption or self::colgroup or self::thead or self::tbody or self::tfoot or self::tr)';
        $other      = $this->getConfig()->getOption('preserve_comments') ? 'node()[self::processing-instruction() or self::comment()]' : 'processing-instruction()';

        $query = \implode(' | ', [
            '//table//table',
            '//tr[not(ancestor::table)] | //td[not(parent::tr)] | //th[not(parent::tr)] | //col[not(ancestor::table)]',
            '//caption[not(parent::table)] | //colgroup[not(parent::table)]',
            '//thead[not(parent::table)] | //tbody[not(parent::table)] | //tfoot[not(parent::table)]',
            '//table/*[' . $notSection . '] | //thead/*[' . $notRow . '] | //tbody/*[' . $notRow . '] | //tfoot/*[' . $notRow . ']',
            '//tr/*[not(self::td or self::th)]',
            \str_replace('X', $other, '//table/X | //thead/X | //tbody/X | //tfoot/X | //tr/X'),
        ]);

        $nodes = (new \DOMXPath($document))->query($query);

        return $nodes === false || $nodes->length > 0;
    }

    private function isWhereMarkdownMightNotBeParsed(ElementInterface $element): bool
    {
        if ($this->isAfterRawHtml || $this->getConfig()->getOption(RawHtml::KEEPS_ALL_CODE_AS_HTML) === true) {
            return true;
        }

        return $this->getConfig()->getOption(RawHtml::FLATTENS_TABLE_CELLS) === true
            && RawHtml::isInsideTable($element)
            && ! $this->isInsideCleanTable;
    }

    /**
     * Whether a table is converted to rows which start on their own lines, with no HTML output anywhere among them
     */
    private function isCleanTable(ElementInterface $element, ConverterInterface $converter): bool
    {
        return ! $converter instanceof DefaultConverter
            && ! $this->isInsideInlineMarkdown($element)
            && ! $this->containsHtml($element);
    }

    private function containsHtml(ElementInterface $element): bool
    {
        if (! $element->hasChildren()) {
            return false;
        }

        foreach ($element->getChildren() as $child) {
            if ($child->isText()) {
                continue;
            }

            // A caption is output apart from the rows, and anything which isn't an element could be output as it is
            $tag = $child->getTagName();
            if ($tag[0] === '#' || \in_array($tag, ['caption', 'pre', 'table'], true)) {
                return true;
            }

            if ($this->isKeptAsHtml($child, $this->environment->getConverterByTag($tag)) || $this->containsHtml($child)) {
                return true;
            }
        }

        return false;
    }

    private function isInsideInlineMarkdown(ElementInterface $element): bool
    {
        return RawHtml::isInsideInlineMarkdown($element, $this->getConfig()->getOption(RawHtml::FLATTENS_TABLE_CELLS) === true);
    }

    private function isKeptAsHtml(ElementInterface $element, ConverterInterface $converter): bool
    {
        $tag = $element->getTagName();
        if ($tag[0] === '#' || $this->isRemoved($tag)) {
            return false;
        }

        if ($converter instanceof LinkConverter) {
            return ! $element->getAttribute('href') && ! $this->getConfig()->getOption('strip_placeholder_links');
        }

        if (! $converter instanceof DefaultConverter && ! $converter instanceof DivConverter) {
            return false;
        }

        if ($this->getConfig()->getOption('strip_tags', false)) {
            return false;
        }

        // sanitize() removes these from the start of the output when they have no attributes
        if ($tag === 'html' || $tag === 'body') {
            return ! $element instanceof Element || $element->hasAttributes();
        }

        return true;
    }

    /**
     * Whether either tag of an element which is kept as HTML could start an HTML block
     */
    private function canStartHtmlBlock(ElementInterface $element): bool
    {
        // Any other tag only starts one when it's alone on its line
        return RawHtml::isBlockTag($element->getTagName()) || $this->canOutputLineBreak($element);
    }

    private function canOutputLineBreak(ElementInterface $element): bool
    {
        // Nodes without any have no child list at all on older versions of PHP
        if (! $element->hasChildren()) {
            return false;
        }

        foreach ($element->getChildren() as $child) {
            if ($child->isText()) {
                continue;
            }

            $tag = $child->getTagName();
            if (\in_array($tag, ['code', 'img'], true)) {
                continue;
            }

            $isInline = \in_array($tag, ['a', 'b', 'em', 'i', 'strong'], true)
                || ($tag[0] !== '#' && ! RawHtml::isBlockTag($tag) && $this->environment->getConverterByTag($tag) instanceof DefaultConverter);

            if (! $isInline || $this->canOutputLineBreak($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the element's Markdown is certain to start on a line of its own where Markdown is parsed
     */
    private function startsOnOwnLine(ElementInterface $element, ConverterInterface $converter): bool
    {
        if ($converter instanceof DefaultConverter || $this->isInsideInlineMarkdown($element)) {
            return false;
        }

        $parent = $element->getParent();

        switch ($element->getTagName()) {
            case 'blockquote':
            case 'ol':
            case 'pre':
            case 'table':
            case 'ul':
                // These get a blank line put before them
                return true;
            case 'li':
                return $parent !== null && \in_array($parent->getTagName(), ['ol', 'ul'], true) && RawHtml::startsLine($element);
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            case 'p':
                // Which only matters when there is something to reset
                return $this->isAfterRawHtml && $this->followsBlankLine($element);
            default:
                return false;
        }
    }

    /**
     * These aren't recognized unless they start on their own line, outside of any HTML block
     */
    private function getLeadingBlankLine(ElementInterface $element, ConverterInterface $converter): string
    {
        $tag = $element->getTagName();
        if (! \in_array($tag, ['blockquote', 'ol', 'pre', 'table', 'ul'], true) && ! $this->isOutsideOfItsContainer($element, $converter)) {
            return '';
        }

        if ($converter instanceof DefaultConverter || $this->isInsideInlineMarkdown($element) || $this->followsBlankLine($element)) {
            return '';
        }

        // A list inside of another is already put on a line of its own, which is enough to continue the outer one
        $parent   = $element->getParent();
        $isNested = $parent !== null && ($parent->getTagName() === 'li' || (\in_array($parent->getTagName(), ['ol', 'ul'], true) && RawHtml::startsLine($element)));
        if (! $this->isAfterRawHtml && $tag !== 'pre' && $tag !== 'blockquote' && $tag !== 'table' && $isNested) {
            return '';
        }

        return \substr(PrecedingMarkdown::getEndOfSiblings($element), -1) === "\n" ? "\n" : "\n\n";
    }

    /**
     * Whether it's a table row or list item with no table or list around it to keep it apart from what's next to it
     */
    private function isOutsideOfItsContainer(ElementInterface $element, ConverterInterface $converter): bool
    {
        if ($converter instanceof DefaultConverter) {
            return false;
        }

        $tag = $element->getTagName();
        if ($tag === 'tr') {
            return ! $element->isDescendantOf('table');
        }

        $parent = $element->getParent();

        return $tag === 'li' && ($parent === null || ! \in_array($parent->getTagName(), ['ol', 'ul'], true));
    }

    private function isTableRows(ElementInterface $element, ConverterInterface $converter): bool
    {
        if ($converter instanceof DefaultConverter) {
            return false;
        }

        return $element->getTagName() === 'table' || $this->isOutsideOfItsContainer($element, $converter);
    }

    private function followsBlankLine(ElementInterface $element): bool
    {
        $node = $element;
        while (true) {
            $end = PrecedingMarkdown::getEndOfSiblings($node);
            if ($end !== '') {
                return $end === "\n\n";
            }

            $parent = $node->getParent();
            if ($parent === null) {
                return true;
            }

            // Only those which output nothing before their contents can be looked past
            $converter = $this->environment->getConverterByTag($parent->getTagName());
            if ($this->isKeptAsHtml($parent, $converter) || ! ($converter instanceof DefaultConverter || $converter instanceof DivConverter)) {
                return false;
            }

            $node = $parent;
        }
    }

    private function endsOnBlankLine(ElementInterface $element, ConverterInterface $converter, string $markdown): bool
    {
        $tags = ['blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'ol', 'p', 'pre', 'table', 'ul'];

        return \in_array($element->getTagName(), $tags, true)
            && ! $converter instanceof DefaultConverter
            && ! $this->isInsideInlineMarkdown($element)
            && \substr($markdown, -2) === "\n\n";
    }

    protected function sanitize(string $markdown): string
    {
        $markdown = \html_entity_decode($markdown, ENT_QUOTES, 'UTF-8');
        $markdown = \preg_replace('/<!DOCTYPE [^>]+>/', '', $markdown); // Strip doctype declaration
        \assert($markdown !== null);
        $markdown = \trim($markdown); // Remove blank spaces at the beggining of the html

        /*
         * Removing unwanted tags. Tags should be added to the array in the order they are expected.
         * XML, html and body opening tags should be in that order. Same case with closing tags
         */
        $unwanted = ['<?xml encoding="UTF-8">', '<html>', '</html>', '<body>', '</body>', '<head>', '</head>', '&#xD;'];

        foreach ($unwanted as $tag) {
            if (\strpos($tag, '/') === false) {
                // Opening tags
                if (\strpos($markdown, $tag) === 0) {
                    $markdown = \substr($markdown, \strlen($tag));
                }
            } else {
                // Closing tags
                if (\strpos($markdown, $tag) === \strlen($markdown) - \strlen($tag)) {
                    $markdown = \substr($markdown, 0, -\strlen($tag));
                }
            }
        }

        return \trim($markdown, "\n\r\0\x0B");
    }

    /**
     * Pass a series of key-value pairs in an array; these will be passed
     * through the config and set.
     * The advantage of this is that it can allow for static use (IE in Laravel).
     * An example being:
     *
     * HtmlConverter::setOptions(['strip_tags' => true])->convert('<h1>test</h1>');
     *
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $config = $this->getConfig();

        foreach ($options as $key => $option) {
            $config->setOption($key, $option);
        }

        return $this;
    }
}
