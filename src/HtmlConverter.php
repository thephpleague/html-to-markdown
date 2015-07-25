<?php

namespace League\HTMLToMarkdown;

/**
 * Class HtmlConverter
 *
 * A helper class to convert HTML to Markdown.
 *
 * @author Colin O'Dell <colinodell@gmail.com>
 * @author Nick Cernis <nick@cern.is>
 *
 * @link https://github.com/thephpleague/html-to-markdown/ Latest version on GitHub.
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class HtmlConverter
{
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * Constructor
     *
     * @param array $options Configuration options
     */
    public function __construct(array $options = array())
    {
        $defaults = array(
            'header_style'    => 'setext', // Set to 'atx' to output H1 and H2 headers as # Header1 and ## Header2
            'suppress_errors' => true, // Set to false to show warnings when loading malformed HTML
            'strip_tags'      => false, // Set to true to strip tags that don't have markdown equivalents. N.B. Strips tags, not their content. Useful to clean MS Word HTML output.
            'bold_style'      => '**', // Set to '__' if you prefer the underlined style
            'italic_style'    => '*', // Set to '_' if you prefer the underlined style
            'remove_nodes'    => '', // space-separated list of dom nodes that should be removed. example: 'meta style script'
        );

        $this->environment = Environment::createDefaultEnvironment($defaults);

        $this->environment->getConfig()->merge($options);
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->environment->getConfig();
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
        if (trim($html) === '') {
            return '';
        }

        $document = $this->createDOMDocument($html);

        // Work on the entire DOM tree (including head and body)
        if (!($root = $document->getElementsByTagName('html')->item(0))) {
            throw new \InvalidArgumentException('Invalid HTML was provided');
        }

        $rootElement = new Element($root);
        $this->convertChildren($rootElement);

        // Store the now-modified DOMDocument as a string
        $markdown = $document->saveHTML();

        $markdown = $this->sanitize($markdown);

        return $markdown;
    }

    /**
     * @param string $html
     *
     * @return \DOMDocument
     */
    private function createDOMDocument($html)
    {
        $document = new \DOMDocument();

        if ($this->getConfig()->getOption('suppress_errors')) {
            // Suppress conversion errors (from http://bit.ly/pCCRSX)
            libxml_use_internal_errors(true);
        }

        // Hack to load utf-8 HTML (from http://bit.ly/pVDyCt)
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        $document->encoding = 'UTF-8';

        if ($this->getConfig()->getOption('suppress_errors')) {
            libxml_clear_errors();
        }

        return $document;
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
    protected function convertToMarkdown(ElementInterface $element)
    {
        $tag = $element->getTagName();

        // Strip nodes named in remove_nodes
        $tags_to_remove = explode(' ', $this->getConfig()->getOption('remove_nodes'));
        if (in_array($tag, $tags_to_remove)) {
            return false;
        }

        $converter = $this->environment->getConverterByTag($tag);

        return $converter->convert($element);
    }

    /**
     * @param string $markdown
     *
     * @return string
     */
    protected function sanitize($markdown)
    {
        $markdown = html_entity_decode($markdown, ENT_QUOTES, 'UTF-8');
        $markdown = html_entity_decode($markdown, ENT_QUOTES, 'UTF-8'); // Double decode to cover cases like &amp;nbsp; http://www.php.net/manual/en/function.htmlentities.php#99984
        $markdown = preg_replace('/<!DOCTYPE [^>]+>/', '', $markdown); // Strip doctype declaration
        $unwanted = array('<html>', '</html>', '<body>', '</body>', '<head>', '</head>', '<?xml encoding="UTF-8">', '&#xD;');
        $markdown = str_replace($unwanted, '', $markdown); // Strip unwanted tags
        $markdown = trim($markdown, "\n\r\0\x0B");

        return $markdown;
    }
}
