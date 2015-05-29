<?php

namespace HTMLToMarkdown;

use HTMLToMarkdown\Converter\BlockquoteConverter;
use HTMLToMarkdown\Converter\CommentConverter;
use HTMLToMarkdown\Converter\ConverterInterface;
use HTMLToMarkdown\Converter\DivConverter;
use HTMLToMarkdown\Converter\EmphasisConverter;
use HTMLToMarkdown\Converter\HardBreakConverter;
use HTMLToMarkdown\Converter\HeaderConverter;
use HTMLToMarkdown\Converter\HorizontalRuleConverter;
use HTMLToMarkdown\Converter\ImageConverter;
use HTMLToMarkdown\Converter\LinkConverter;
use HTMLToMarkdown\Converter\ListBlockConverter;
use HTMLToMarkdown\Converter\ListItemConverter;
use HTMLToMarkdown\Converter\ParagraphConverter;
use HTMLToMarkdown\Converter\PreformattedConverter;
use HTMLToMarkdown\Converter\TextConverter;

/**
 * Class HtmlConverter
 *
 * A helper class to convert HTML to Markdown.
 *
 * @version 2.2.2
 * @author Colin O'Dell <colinodell@gmail.com>
 * @author Nick Cernis <nick@cern.is>
 * @link https://github.com/nickcernis/html2markdown/ Latest version on GitHub.
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class HtmlConverter
{
    /**
     * @var \DOMDocument The root of the document tree that holds our HTML.
     */
    private $document;

    /**
     * @var array Class-wide options users can override.
     */
    private $options = array(
        'header_style'    => 'setext', // Set to 'atx' to output H1 and H2 headers as # Header1 and ## Header2
        'suppress_errors' => true, // Set to false to show warnings when loading malformed HTML
        'strip_tags'      => false, // Set to true to strip tags that don't have markdown equivalents. N.B. Strips tags, not their content. Useful to clean MS Word HTML output.
        'bold_style'      => '**', // Set to '__' if you prefer the underlined style
        'italic_style'    => '*', // Set to '_' if you prefer the underlined style
        'remove_nodes'    => '', // space-separated list of dom nodes that should be removed. example: 'meta style script'
    );

    /**
     * @var ConverterInterface[]
     */
    protected $converters = array();

    /**
     * Constructor
     *
     * @param array $options Configuration options
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge($this->options, $options);

        $this->addConverter(new BlockquoteConverter());
        $this->addConverter(new HardBreakConverter());
        $this->addConverter(new ParagraphConverter());
        $this->addConverter(new HeaderConverter($this->options['header_style']));
        $this->addConverter(new EmphasisConverter($this->options['italic_style'], $this->options['bold_style']));
        $this->addConverter(new CommentConverter());
        $this->addConverter(new ListBlockConverter());
        $this->addConverter(new HorizontalRuleConverter());
        $this->addConverter(new ListItemConverter());
        $this->addConverter(new TextConverter());
        $this->addConverter(new PreformattedConverter());
        $this->addConverter(new LinkConverter());
        $this->addConverter(new ImageConverter());
        $this->addConverter(new DivConverter($this->options['strip_tags']));
    }

    /**
     * @param ConverterInterface $converter
     */
    protected function addConverter(ConverterInterface $converter)
    {
        foreach ($converter->getSupportedTags() as $tag) {
            $this->converters[$tag] = $converter;
        }
    }

    /**
     * Setter for conversion options
     *
     * @param $name
     * @param $value
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }


    /**
     * Convert
     *
     * Loads HTML and passes to getMarkdown()
     *
     * @param $html
     *
     * @return string The Markdown version of the html
     */
    public function convert($html)
    {
        $this->document = new \DOMDocument();

        if ($this->options['suppress_errors']) {
            // Suppress conversion errors (from http://bit.ly/pCCRSX)
            libxml_use_internal_errors(true);
        }

        // Hack to load utf-8 HTML (from http://bit.ly/pVDyCt)
        $this->document->loadHTML('<?xml encoding="UTF-8">' . $html);
        $this->document->encoding = 'UTF-8';

        if ($this->options['suppress_errors']) {
            libxml_clear_errors();
        }

        return $this->getMarkdown($html);
    }


    /**
     * Convert Children
     *
     * Recursive function to drill into the DOM and convert each node into Markdown from the inside out.
     *
     * Finds children of each node and convert those to #text nodes containing their Markdown equivalent,
     * starting with the innermost element and working up to the outermost element.
     *
     * @param ElementInterface $element
     */
    private function convertChildren(ElementInterface $element)
    {
        // Don't convert HTML code inside <code> and <pre> blocks to Markdown - that should stay as HTML
        if ($element->isDescendantOf(array('pre', 'code'))) {
            return;
        }

        // If the node has children, convert those to Markdown first
        if ($element->hasChildren()) {
            foreach ($element->getChildren() as $child) {
                $this->convertChildren($child);
            }
        }

        // Now that child nodes have been converted, convert the original node
        $markdown = $this->convertToMarkdown($element);

        // Create a DOM text node containing the Markdown equivalent of the original node

        // Replace the old $node e.g. '<h3>Title</h3>' with the new $markdown_node e.g. '### Title'
        $element->setFinalMarkdown($markdown);
    }


    /**
     * Get Markdown
     *
     * Sends the body node to convertChildren() to change inner nodes to Markdown #text nodes, then saves and
     * returns the resulting converted document as a string in Markdown format.
     *
     * @return string|boolean The converted HTML as Markdown, or false if conversion failed
     */
    private function getMarkdown()
    {
        // Work on the entire DOM tree (including head and body)
        $input = $this->document->getElementsByTagName('html')->item(0);

        if (!$input) {
            return false;
        }

        // Convert all children of this root element. The DOMDocument stored in $this->doc will
        // then consist of #text nodes, each containing a Markdown version of the original node
        // that it replaced.
        $element =  new Element($input);
        $this->convertChildren($element);

        // Sanitize and return the body contents as a string.
        $markdown = $this->document->saveHTML(); // stores the DOMDocument as a string
        $markdown = html_entity_decode($markdown, ENT_QUOTES, 'UTF-8');
        $markdown = html_entity_decode($markdown, ENT_QUOTES, 'UTF-8'); // Double decode to cover cases like &amp;nbsp; http://www.php.net/manual/en/function.htmlentities.php#99984
        $markdown = preg_replace('/<!DOCTYPE [^>]+>/', '', $markdown); // Strip doctype declaration
        $unwanted = array('<html>', '</html>', '<body>', '</body>', '<head>', '</head>', '<?xml encoding="UTF-8">', '&#xD;');
        $markdown = str_replace($unwanted, '', $markdown); // Strip unwanted tags
        $markdown = trim($markdown, "\n\r\0\x0B");

        return $markdown;
    }


    /**
     * Convert to Markdown
     *
     * Converts an individual node into a #text node containing a string of its Markdown equivalent.
     *
     * Example: An <h3> node with text content of 'Title' becomes a text node with content of '### Title'
     *
     * @param ElementInterface $element
     *
     * @return string The converted HTML as Markdown
     */
    private function convertToMarkdown(ElementInterface $element)
    {
        $tag = $element->getTagName();
        $value = $element->getValue();

        // Strip nodes named in remove_nodes
        $tags_to_remove = explode(' ', $this->options['remove_nodes']);
        if (in_array($tag, $tags_to_remove)) {
            return false;
        }

        if (isset($this->converters[$tag])) {
            return $this->converters[$tag]->convert($element);
        }

        // If strip_tags is false (the default), preserve tags that don't have Markdown equivalents,
        // such as <span> nodes on their own. C14N() canonicalizes the node to a string.
        // See: http://www.php.net/manual/en/domnode.c14n.php
        if ($this->options['strip_tags']) {
            return $value;
        }

        return html_entity_decode($element->getChildrenAsString());
    }
}
