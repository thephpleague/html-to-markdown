<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown;

/**
 * Markdown isn't parsed inside of a block of raw HTML, so code can't be protected by Markdown delimiters there.
 *
 * @internal
 */
final class RawHtml
{
    /**
     * Tags which start an HTML block no matter what follows them on the line, in any version of the spec
     *
     * @see https://spec.commonmark.org/0.31.2/#html-blocks
     */
    private const BLOCK_TAGS = 'address|article|aside|base|basefont|blockquote|body|caption|center|col|colgroup|dd|details|dialog|dir|div|dl|dt|fieldset|figcaption|figure|footer|form|frame|frameset|h[1-6]|head|header|hr|html|iframe|legend|li|link|main|menu|menuitem|meta|nav|noframes|ol|optgroup|option|p|param|pre|script|search|section|source|style|summary|table|tbody|td|textarea|tfoot|th|thead|title|tr|track|ul';

    /**
     * Set on the configuration during a conversion: whether table cells have a converter of their own,
     * which puts each row on one line
     */
    public const FLATTENS_TABLE_CELLS = '__INTERNAL_flattens_table_cells';

    /**
     * Set on the configuration during a conversion: whether no code can be left as Markdown anywhere in the document
     */
    public const KEEPS_ALL_CODE_AS_HTML = '__INTERNAL_keeps_all_code_as_html';

    private const TABLE_TAGS = ['caption', 'col', 'colgroup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr'];

    public static function isInsideTable(ElementInterface $element): bool
    {
        return $element->isDescendantOf(self::TABLE_TAGS);
    }

    public static function isBlockTag(string $tagName): bool
    {
        return \preg_match('~^(?:' . self::BLOCK_TAGS . ')$~i', $tagName) === 1;
    }

    /**
     * Whether the given output would start an HTML block when at the start of a line
     */
    public static function startsBlock(string $markdown): bool
    {
        return \preg_match('~^[ \t]*+<(?:[!?]|/?(?:' . self::BLOCK_TAGS . ')(?:[\s/>]|$))~i', $markdown) !== 0;
    }

    /**
     * Whether a fenced code block for the element wouldn't reliably be recognized as one
     */
    public static function cannotBeFenced(ElementInterface $element, ?Configuration $config): bool
    {
        if ($config === null) {
            return self::isInsideInlineMarkdown($element, false);
        }

        return $config->getOption(self::KEEPS_ALL_CODE_AS_HTML) === true
            || self::isInsideInlineMarkdown($element, $config->getOption(self::FLATTENS_TABLE_CELLS) === true);
    }

    /**
     * Whether the element is inside of something which puts its contents on the line it starts on,
     * where nothing which has to start a line of its own would be recognized
     *
     * @param bool $flattensTableCells Whether table cells are converted to Markdown, which puts each row on one line
     */
    public static function isInsideInlineMarkdown(ElementInterface $element, bool $flattensTableCells): bool
    {
        for ($node = $element->getParent(); $node !== null; $node = $parent) {
            $parent = $node->getParent();
            $tag    = $node->getTagName();

            if (\in_array($tag, ['a', 'b', 'em', 'i', 'strong', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
                return true;
            }

            // As HTML, these make up a block which a blank line doesn't end
            if (\in_array($tag, ['script', 'style', 'textarea'], true)) {
                return true;
            }

            if ($flattensTableCells && \in_array($tag, self::TABLE_TAGS, true)) {
                return true;
            }

            // A list item anywhere else isn't output as part of a list which starts on its own line
            if ($tag === 'li' && ($parent === null || ! \in_array($parent->getTagName(), ['ol', 'ul'], true))) {
                return true;
            }

            // Nor is one which follows something other than a list item
            if ($tag === 'li' && ! self::startsLine($node)) {
                return true;
            }

            // A list at the very start of a list item shares its line, which leaves the rest of it misaligned
            if (($tag === 'ol' || $tag === 'ul') && self::startsListItem($node)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a list item comes first in its list, or after something which ended its line, as a list item does
     */
    public static function startsLine(ElementInterface $listItem): bool
    {
        $end = PrecedingMarkdown::getEndOfSiblings($listItem);

        return \trim($end, " \t") === '' || \substr($end, -1) === "\n";
    }

    private static function startsListItem(ElementInterface $element): bool
    {
        for ($node = $element; ($parent = $node->getParent()) !== null; $node = $parent) {
            if (PrecedingMarkdown::hasContent($node)) {
                return false;
            }

            if ($parent->getTagName() === 'li') {
                return true;
            }
        }

        return false;
    }

    /**
     * Keeps the element as HTML around the Markdown which its children were converted to
     */
    public static function fromElement(ElementInterface $element): string
    {
        $html = $element->getChildrenAsString();

        // The attributes must stay encoded, otherwise their values could break out of their quotes
        if (\preg_match('/^<([^\s>\/!?=]++)(?:\s++[^\s=>]++="[^"]*+")*+\s*+>/', $html, $matches) === 1) {
            return Backticks::escapeText($matches[0]) . $element->getValue() . '</' . $matches[1] . '>';
        }

        // Nodes which aren't elements have no tag to keep
        if (\preg_match('/^<[^!?]/', $html) !== 1) {
            return \html_entity_decode($html);
        }

        $tag = $element->getTagName();

        return '<' . $tag . '>' . $element->getValue() . '</' . $tag . '>';
    }

    public static function getCode(ElementInterface $element): string
    {
        $code = \html_entity_decode($element->getChildrenAsString());

        $code = \preg_replace('/<code\b[^>]*>/', '', $code);
        \assert($code !== null);

        // A carriage return alone also ends a line, and would be left out of any indentation added later
        $code = \preg_replace('/\r\n?/', "\n", $code);
        \assert($code !== null);

        return \str_replace('</code>', '', $code);
    }

    /**
     * Returns an element holding the code which is safe both as raw HTML and as inline HTML surrounded by Markdown
     */
    public static function fromCode(string $code, string $tag = 'code'): string
    {
        $code = \preg_replace_callback('/[!-\/:-@\[-`{-~]/', static function (array $matches): string {
            return '&#' . \ord($matches[0]) . ';';
        }, $code);
        \assert($code !== null);

        // An actual line break could end the HTML block which this is in
        $code = \preg_replace('/\r\n|\r|\n/', $tag === 'pre' ? '&#10;' : '', $code);
        \assert($code !== null);

        return $code === '' ? '' : '<' . $tag . '>' . $code . '</' . $tag . '>';
    }
}
