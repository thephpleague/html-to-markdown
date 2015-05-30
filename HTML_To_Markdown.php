<?php
/**
 * Class HTML_To_Markdown
 *
 * A helper class to convert HTML to Markdown.
 *
 * @version 2.2.2
 * @author Nick Cernis <nick@cern.is>
 * @link https://github.com/nickcernis/html2markdown/ Latest version on GitHub.
 * @link http://twitter.com/nickcernis Nick on twitter.
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class HTML_To_Markdown
{
    /**
     * @var DOMDocument The root of the document tree that holds our HTML.
     */
    private $document;

    /**
     * @var string|boolean The Markdown version of the original HTML, or false if conversion failed
     */
    private $output;

    /**
     * @var array Class-wide options users can override.
     */
    private $options = array(
        'header_style'    => 'setext', // Set to "atx" to output H1 and H2 headers as # Header1 and ## Header2
        'suppress_errors' => true, // Set to false to show warnings when loading malformed HTML
        'strip_tags'      => false, // Set to true to strip tags that don't have markdown equivalents. N.B. Strips tags, not their content. Useful to clean MS Word HTML output.
        'bold_style'      => '**', // Set to '__' if you prefer the underlined style
        'italic_style'    => '*', // Set to '_' if you prefer the underlined style
        'remove_nodes'    => '', // space-separated list of dom nodes that should be removed. example: "meta style script"
    );


    /**
     * Constructor
     *
     * Set up a new DOMDocument from the supplied HTML, convert it to Markdown, and store it in $this->$output.
     *
     * @param string $html The HTML to convert to Markdown.
     * @param array $overrides [optional] List of style and error display overrides.
     */
    public function __construct($html = null, $overrides = null)
    {
        if ($overrides)
            $this->options = array_merge($this->options, $overrides);

        if ($html)
            $this->convert($html);
    }


    /**
     * Setter for conversion options
     *
     * @param $name
     * @param $value
     */
    public function set_option($name, $value)
    {
        $this->options[$name] = $value;
    }


    /**
     * Convert
     *
     * Loads HTML and passes to get_markdown()
     *
     * @param $html
     * @return string The Markdown version of the html
     */
    public function convert($html)
    {
        $this->document = new DOMDocument();

        if ($this->options['suppress_errors'])
            libxml_use_internal_errors(true); // Suppress conversion errors (from http://bit.ly/pCCRSX )

        $this->document->loadHTML('<?xml encoding="UTF-8">' . $html); // Hack to load utf-8 HTML (from http://bit.ly/pVDyCt )
        $this->document->encoding = 'UTF-8';

        if ($this->options['suppress_errors'])
            libxml_clear_errors();

        return $this->get_markdown($html);
    }


    /**
     * Is Child Of?
     *
     * Is the node a child of the given parent tag?
     *
     * @param $parent_name string|array The name of the parent node(s) to search for e.g. 'code' or array('pre', 'code')
     * @param $node
     * @return bool
     */
    private static function is_child_of($parent_name, $node)
    {
        for ($p = $node->parentNode; $p != false; $p = $p->parentNode) {
            if (is_null($p))
                return false;

            if ( is_array($parent_name) && in_array($p->nodeName, $parent_name) )
                return true;
            
            if ($p->nodeName == $parent_name)
                return true;
        }
        return false;
    }


    /**
     * Convert Children
     *
     * Recursive function to drill into the DOM and convert each node into Markdown from the inside out.
     *
     * Finds children of each node and convert those to #text nodes containing their Markdown equivalent,
     * starting with the innermost element and working up to the outermost element.
     *
     * @param $node
     */
    private function convert_children($node)
    {
        // Don't convert HTML code inside <code> and <pre> blocks to Markdown - that should stay as HTML
        if (self::is_child_of(array('pre', 'code'), $node))
            return;

        // If the node has children, convert those to Markdown first
        if ($node->hasChildNodes()) {
            $length = $node->childNodes->length;

            for ($i = 0; $i < $length; $i++) {
                $child = $node->childNodes->item($i);
                $this->convert_children($child);
            }
        }

        // Now that child nodes have been converted, convert the original node
        $markdown = $this->convert_to_markdown($node);

        // Create a DOM text node containing the Markdown equivalent of the original node
        $markdown_node = $this->document->createTextNode($markdown);

        // Replace the old $node e.g. "<h3>Title</h3>" with the new $markdown_node e.g. "### Title"
        $node->parentNode->replaceChild($markdown_node, $node);
    }


    /**
     * Get Markdown
     *
     * Sends the body node to convert_children() to change inner nodes to Markdown #text nodes, then saves and
     * returns the resulting converted document as a string in Markdown format.
     *
     * @return string|boolean The converted HTML as Markdown, or false if conversion failed
     */
    private function get_markdown()
    {
        // Work on the entire DOM tree (including head and body)
        $input = $this->document->getElementsByTagName("html")->item(0);

        if (!$input)
            return false;

        // Convert all children of this root element. The DOMDocument stored in $this->doc will
        // then consist of #text nodes, each containing a Markdown version of the original node
        // that it replaced.
        $this->convert_children($input);

        // Sanitize and return the body contents as a string.
        $markdown = $this->document->saveHTML(); // stores the DOMDocument as a string
        $markdown = html_entity_decode($markdown, ENT_QUOTES, 'UTF-8');
        $markdown = html_entity_decode($markdown, ENT_QUOTES, 'UTF-8'); // Double decode to cover cases like &amp;nbsp; http://www.php.net/manual/en/function.htmlentities.php#99984
        $markdown = preg_replace("/<!DOCTYPE [^>]+>/", "", $markdown); // Strip doctype declaration
        $unwanted = array('<html>', '</html>', '<body>', '</body>', '<head>', '</head>', '<?xml encoding="UTF-8">', '&#xD;');
        $markdown = str_replace($unwanted, '', $markdown); // Strip unwanted tags
        $markdown = trim($markdown, "\n\r\0\x0B");

        $this->output = $markdown;

        return $markdown;
    }


    /**
     * Convert to Markdown
     *
     * Converts an individual node into a #text node containing a string of its Markdown equivalent.
     *
     * Example: An <h3> node with text content of "Title" becomes a text node with content of "### Title"
     *
     * @param $node
     * @return string The converted HTML as Markdown
     */
    private function convert_to_markdown($node)
    {
        $tag = $node->nodeName; // the type of element, e.g. h1
        $value = $node->nodeValue; // the value of that element, e.g. The Title
        
        // Strip nodes named in remove_nodes
        $tags_to_remove = explode(' ', $this->options['remove_nodes']);
        if ( in_array($tag, $tags_to_remove) )
            return false;

        switch ($tag) {
            case "p":
                $markdown = (trim($value)) ? rtrim($value) . PHP_EOL . PHP_EOL : '';
                break;
            case "pre":
                $markdown = PHP_EOL . $this->convert_code($node) . PHP_EOL;
                break;
            case "h1":
            case "h2":
                $markdown = $this->convert_header($tag, $node);
                break;
            case "h3":
                $markdown = "### " . $value . PHP_EOL . PHP_EOL;
                break;
            case "h4":
                $markdown = "#### " . $value . PHP_EOL . PHP_EOL;
                break;
            case "h5":
                $markdown = "##### " . $value . PHP_EOL . PHP_EOL;
                break;
            case "h6":
                $markdown = "###### " . $value . PHP_EOL . PHP_EOL;
                break;
            case "em":
            case "i":
            case "strong":
            case "b":
                $markdown = $this->convert_emphasis($tag, $value);
                break;
            case "hr":
                $markdown = "- - - - - -" . PHP_EOL . PHP_EOL;
                break;
            case "br":
                $markdown = "  " . PHP_EOL;
                break;
            case "blockquote":
                $markdown = $this->convert_blockquote($node);
                break;
            case "code":
                $markdown = $this->convert_code($node);
                break;
            case "ol":
            case "ul":
                $markdown = $value . PHP_EOL;
                break;
            case "li":
                $markdown = $this->convert_list($node);
                break;
            case "img":
                $markdown = $this->convert_image($node);
                break;
            case "a":
                $markdown = $this->convert_anchor($node);
                break;
            case "#text":
                $markdown = $this->convert_text($node);
                break;
            case "#comment":
                $markdown = '';
                break;
            case "div":
                $markdown = ($this->options['strip_tags']) ? $value . PHP_EOL . PHP_EOL : html_entity_decode($node->C14N());
                break;
            default:
                // If strip_tags is false (the default), preserve tags that don't have Markdown equivalents,
                // such as <span> nodes on their own. C14N() canonicalizes the node to a string.
                // See: http://www.php.net/manual/en/domnode.c14n.php
                $markdown = ($this->options['strip_tags']) ? $value : html_entity_decode($node->C14N());
        }

        return $markdown;
    }


    /**
     * Convert Header
     *
     * Converts h1 and h2 headers to Markdown-style headers in setext style,
     * matching the number of underscores with the length of the title.
     *
     * e.g.     Header 1    Header Two
     *          ========    ----------
     *
     * Returns atx headers instead if $this->options['header_style'] is "atx"
     *
     * e.g.    # Header 1   ## Header Two
     *
     * @param string $level The header level, including the "h". e.g. h1
     * @param string $node The node to convert.
     * @return string The Markdown version of the header.
     */
    private function convert_header($level, $node)
    {
        $content = $node->nodeValue;

        if (!$this->is_child_of('blockquote', $node) && $this->options['header_style'] == "setext") {
            $length = (function_exists('mb_strlen')) ? mb_strlen($content, 'utf-8') : strlen($content);
            $underline = ($level == "h1") ? "=" : "-";
            $markdown = $content . PHP_EOL . str_repeat($underline, $length) . PHP_EOL . PHP_EOL; // setext style
        } else {
            $prefix = ($level == "h1") ? "# " : "## ";
            $markdown = $prefix . $content . PHP_EOL . PHP_EOL; // atx style
        }

        return $markdown;
    }


    /**
     * Converts inline styles
     * This function is used to render strong and em tags
     * 
     * eg <strong>bold text</strong> becomes **bold text** or __bold text__
     * 
     * @param string $tag
     * @param string $value
     * @return string
     */
     private function convert_emphasis($tag, $value)
     {
        if ($tag == 'i' || $tag == 'em') {
            $markdown = $this->options['italic_style'] . $value . $this->options['italic_style'];
        } else {
            $markdown = $this->options['bold_style'] . $value . $this->options['bold_style'];
        }
        
        return $markdown;
     }


    /**
     * Convert Image
     *
     * Converts <img /> tags to Markdown.
     *
     * e.g.     <img src="/path/img.jpg" alt="alt text" title="Title" />
     * becomes  ![alt text](/path/img.jpg "Title")
     *
     * @param $node
     * @return string
     */
    private function convert_image($node)
    {
        $src = $node->getAttribute('src');
        $alt = $node->getAttribute('alt');
        $title = $node->getAttribute('title');

        if ($title != "") {
            $markdown = '![' . $alt . '](' . $src . ' "' . $title . '")'; // No newlines added. <img> should be in a block-level element.
        } else {
            $markdown = '![' . $alt . '](' . $src . ')';
        }

        return $markdown;
    }


    /**
     * Convert Anchor
     *
     * Converts <a> tags to Markdown.
     *
     * e.g.     <a href="http://modernnerd.net" title="Title">Modern Nerd</a>
     * becomes  [Modern Nerd](http://modernnerd.net "Title")
     *
     * @param $node
     * @return string
     */
    private function convert_anchor($node)
    {
        $href = $node->getAttribute('href');
        $title = $node->getAttribute('title');
        $text = $node->nodeValue;

        if ($title != "") {
            $markdown = '[' . $text . '](' . $href . ' "' . $title . '")';
        } elseif ($href === $text) {
            $markdown = '<' . $href . '>';
        } else {
            $markdown = '[' . $text . '](' . $href . ')';
        }

        if (! $href)
            $markdown = html_entity_decode($node->C14N());

        return $markdown;
    }


    /**
     * Convert List
     *
     * Converts <ul> and <ol> lists to Markdown.
     *
     * @param $node
     * @return string
     */
    private function convert_list($node)
    {
        // If parent is an ol, use numbers, otherwise, use dashes
        $list_type = $node->parentNode->nodeName;
        $value = $node->nodeValue;

        if ($list_type == "ul") {
            $markdown = "- " . trim($value) . PHP_EOL;
        } else {
            $number = $this->get_position($node);
            $markdown = $number . ". " . trim($value) . PHP_EOL;
        }

        return $markdown;
    }


    /**
     * Convert Code
     *
     * Convert code tags by indenting blocks of code and wrapping single lines in backticks.
     *
     * @param DOMNode $node
     * @return string
     */
    private function convert_code($node)
    {
        // Store the content of the code block in an array, one entry for each line

        $markdown = '';

        $code_content = html_entity_decode($node->C14N());
        $code_content = str_replace(array("<code>", "</code>"), "", $code_content);
        $code_content = str_replace(array("<pre>", "</pre>"), "", $code_content);

        $lines = preg_split('/\r\n|\r|\n/', $code_content);
        $total = count($lines);

        // If there's more than one line of code, prepend each line with four spaces and no backticks.
        if ($total > 1 || $node->nodeName === 'pre') {

            // Remove the first and last line if they're empty
            $first_line = trim($lines[0]);
            $last_line = trim($lines[$total - 1]);
            $first_line = trim($first_line, "&#xD;"); //trim XML style carriage returns too
            $last_line = trim($last_line, "&#xD;");

            if (empty($first_line))
                array_shift($lines);

            if (empty($last_line))
                array_pop($lines);

            $count = 1;
            foreach ($lines as $line) {
                $line = str_replace('&#xD;', '', $line);
                $markdown .= "    " . $line;
                // Add newlines, except final line of the code
                if ($count != $total)
                    $markdown .= PHP_EOL;
                $count++;
            }
            $markdown .= PHP_EOL;

        } else { // There's only one line of code. It's a code span, not a block. Just wrap it with backticks.

            $markdown .= "`" . $lines[0] . "`";

        }
        
        return $markdown;
    }


    /**
     * Convert blockquote
     *
     * Prepend blockquotes with > chars.
     *
     * @param $node
     * @return string
     */
    private function convert_blockquote($node)
    {
        // Contents should have already been converted to Markdown by this point,
        // so we just need to add ">" symbols to each line.

        $markdown = '';

        $quote_content = trim($node->nodeValue);

        $lines = preg_split('/\r\n|\r|\n/', $quote_content);

        $total_lines = count($lines);

        foreach ($lines as $i => $line) {
            $markdown .= "> " . $line . PHP_EOL;
            if ($i + 1 == $total_lines)
                $markdown .= PHP_EOL;
        }

        return $markdown;
    }


    /**
     * Get Position
     *
     * Returns the numbered position of a node inside its parent, excluding empty text nodes
     *
     * @param $node
     * @return int The numbered position of the node, starting at 1.
     */
    private function get_position($node)
    {
        // Get all of the nodes inside the parent
        $list_nodes = $node->parentNode->childNodes;

        $position = 0;

        // Loop through all nodes and find the given $node
        foreach ($list_nodes as $current_node) {
            if (!$this->is_whitespace($current_node)) {
                $position++;
            }

            if ($current_node->isSameNode($node)) {
                break;
            }
        }

        return $position;
    }

    /**
     * @param \DomNode $node
     *
     * @return bool
     */
    private function is_whitespace($node)
    {
        return $node->nodeName === '#text' && trim($node->nodeValue) === '';
    }


    /**
     * To String
     *
     * Magic method to return Markdown output when HTML_To_Markdown instance is treated as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->output();
    }


    /**
     * Output
     *
     * Getter for the converted Markdown contents stored in $this->output
     *
     * @return string
     */
    public function output()
    {
        if (!$this->output) {
            return '';
        } else {
            return $this->output;
        }
    }

    /**
     * @param \DomNode $node
     *
     * @return string
     */
    private function convert_text($node)
    {
        $value = $node->nodeValue;

        $markdown = preg_replace('~\s+~', ' ', $value);
        $markdown = preg_replace('~^#~', '\\\\#', $markdown);

        if ($markdown === ' ') {
            $next = $this->get_next($node);
            if (!$next || $this->is_block($next)) {
                $markdown = '';
            }
        }

        return $markdown;
    }

    /**
     * @param \DomNode $node
     *
     * @return \DomNode|null
     */
    private function get_next($node, $checkChildren = true)
    {
        if ($checkChildren && $node->firstChild) {
            return $node->firstChild;
        } elseif ($node->nextSibling) {
            return $node->nextSibling;
        } elseif ($node->parentNode) {
            return $this->get_next($node->parentNode, false);
        } else {
            return null;
        }
    }

    /**
     * @param \DomNode $node
     *
     * @return bool
     */
    private function is_block($node)
    {
        switch ($node->nodeName) {
            case "blockquote":
            case "body":
            case "code":
            case "div":
            case "h1":
            case "h2":
            case "h3":
            case "h4":
            case "h5":
            case "h6":
            case "hr":
            case "html":
            case "li":
            case "p":
            case "ol":
            case "ul":
                return true;
            default:
                return false;
        }
    }
}
