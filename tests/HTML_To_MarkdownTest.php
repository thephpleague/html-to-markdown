<?php
require_once dirname(__FILE__) . '/../HTML_To_Markdown.php';

class HTML_To_MarkdownTest extends PHPUnit_Framework_TestCase
{
    private function html_gives_markdown($html, $expected_markdown, $options=null)
    {
        $markdown = new HTML_To_Markdown($html, $options);
        $this->assertEquals($expected_markdown, $markdown->__toString());
    }

    public function test_plain_text()
    {
        $this->html_gives_markdown("test", "test");
        $this->html_gives_markdown("<p>test</p>", "test");
    }

    public function test_line_breaks()
    {
        $this->html_gives_markdown("test\nanother line", "test another line");
        $this->html_gives_markdown("<p>test\nanother line</p>", "test another line");
        $this->html_gives_markdown("<p>test<br>another line</p>", "test  \nanother line");
    }

    public function test_headers()
    {
        $this->html_gives_markdown("<h1>Test</h1>", "Test\n====");
        $this->html_gives_markdown("<h2>Test</h2>", "Test\n----");
        $this->html_gives_markdown("<blockquote><h1>Test</h1></blockquote>", "> # Test");
        $this->html_gives_markdown("<blockquote><h2>Test</h2></blockquote>", "> ## Test");
        $this->html_gives_markdown("<h3>Test</h3>", "### Test");
        $this->html_gives_markdown("<h4>Test</h4>", "#### Test");
        $this->html_gives_markdown("<h5>Test</h5>", "##### Test");
        $this->html_gives_markdown("<h6>Test</h6>", "###### Test");
    }

    public function test_spans()
    {
        $this->html_gives_markdown("<em>Test</em>", "*Test*");
        $this->html_gives_markdown("<i>Test</i>", "*Test*");
        $this->html_gives_markdown("<strong>Test</strong>", "**Test**");
        $this->html_gives_markdown("<b>Test</b>", "**Test**");
        $this->html_gives_markdown("<em>Test</em>", "_Test_", array('italic_style' => '_'));
        $this->html_gives_markdown("<em>Italic</em> and a <strong>bold</strong>", "_Italic_ and a __bold__", array('italic_style' => '_', 'bold_style' => '__'));
        $this->html_gives_markdown("<i>Test</i>", "_Test_", array('italic_style' => '_'));
        $this->html_gives_markdown("<strong>Test</strong>", "__Test__", array('bold_style' => '__'));
        $this->html_gives_markdown("<b>Test</b>", "__Test__", array('bold_style' => '__'));        
        $this->html_gives_markdown("<span>Test</span>", "<span>Test</span>");
        $this->html_gives_markdown("<b>Bold</b> <i>Italic</i>", "**Bold** *Italic*");
        $this->html_gives_markdown("<b>Bold</b><i>Italic</i>", "**Bold***Italic*");
    }

    public function test_nesting()
    {
        $this->html_gives_markdown("<span><span>Test</span></span>", "<span><span>Test</span></span>");
    }

    public function test_script()
    {
        $this->html_gives_markdown("<script>alert('test');</script>", "<script>alert('test');</script>");
    }

    public function test_image()
    {
        $this->html_gives_markdown('<img src="/path/img.jpg" alt="alt text" title="Title" />', '![alt text](/path/img.jpg "Title")');
    }

    public function test_anchor()
    {
        $this->html_gives_markdown('<a href="http://modernnerd.net">http://modernnerd.net</a>', '<http://modernnerd.net>');
        $this->html_gives_markdown('<a href="http://modernnerd.net" title="Title">Modern Nerd</a>', '[Modern Nerd](http://modernnerd.net "Title")');
        $this->html_gives_markdown('<a href="http://modernnerd.net" title="Title">Modern Nerd</a> <a href="http://modernnerd.net" title="Title">Modern Nerd</a>', '[Modern Nerd](http://modernnerd.net "Title") [Modern Nerd](http://modernnerd.net "Title")');
        $this->html_gives_markdown('<a href="http://modernnerd.net/" title="Title"><img src="/path/img.jpg" alt="alt text" title="Title"/></a>', '[![alt text](/path/img.jpg "Title")](http://modernnerd.net/ "Title")');
        $this->html_gives_markdown('<a href="http://modernnerd.net/" title="Title"><img src="/path/img.jpg" alt="alt text" title="Title"/> Test</a>', '[![alt text](/path/img.jpg "Title") Test](http://modernnerd.net/ "Title")');

        // Placeholder links and fragment identifiers
        $this->html_gives_markdown('<a>Test</a>', '<a>Test</a>');
        $this->html_gives_markdown('<a href="">Test</a>', '<a href="">Test</a>');
        $this->html_gives_markdown('<a href="#nerd" title="Title">Test</a>', '[Test](#nerd "Title")');
        $this->html_gives_markdown('<a href="#nerd">Test</a>', '[Test](#nerd)');
    }

    public function test_lists()
    {
        $this->html_gives_markdown("<ul><li>Item A</li><li>Item B</li></ul>", "- Item A\n- Item B");
        $this->html_gives_markdown("<ul><li>   Item A</li><li>   Item B</li></ul>", "- Item A\n- Item B");
        $this->html_gives_markdown("<ol><li>Item A</li><li>Item B</li></ol>", "1. Item A\n2. Item B");
        $this->html_gives_markdown("<ol>\n    <li>Item A</li>\n    <li>Item B</li>\n</ol>", "1. Item A\n2. Item B");
        $this->html_gives_markdown("<ol><li>   Item A</li><li>   Item B</li></ol>", "1. Item A\n2. Item B");
    }

    public function test_code_samples()
    {
        $this->html_gives_markdown("<code>&lt;p&gt;Some sample HTML&lt;/p&gt;</code>", "`<p>Some sample HTML</p>`");
        $this->html_gives_markdown("<code>\n&lt;p&gt;Some sample HTML&lt;/p&gt;\n&lt;p&gt;And another line&lt;/p&gt;\n</code>", "    <p>Some sample HTML</p>\n    <p>And another line</p>");
        $this->html_gives_markdown("<p><code>\n#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n</code></p>", "    #sidebar h1 {\n        font-size: 1.5em;\n        font-weight: bold;\n    }");
        $this->html_gives_markdown("<p><code>#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n</code></p>", "    #sidebar h1 {\n        font-size: 1.5em;\n        font-weight: bold;\n    }");
    }

    public function test_preformat()
    {
        $this->html_gives_markdown("<pre>test\ntest\r\ntest</pre>", '    test' . PHP_EOL . '    test' . PHP_EOL . '    test');
        $this->html_gives_markdown("<pre>test\n\ttab\r\n</pre>", '    test' . PHP_EOL . "    \ttab");
        $this->html_gives_markdown("<pre>  one line with spaces  </pre>", '    ' . '  one line with spaces  ');
    }

    public function test_blockquotes()
    {
        $this->html_gives_markdown("<blockquote>Something I said?</blockquote>", "> Something I said?");
        $this->html_gives_markdown("<blockquote><blockquote>Something I said?</blockquote></blockquote>", "> > Something I said?");
        $this->html_gives_markdown("<blockquote><p>Something I said?</p><p>Why, yes it was!</p></blockquote>", "> Something I said?\n> \n> Why, yes it was!");
    }

    public function test_malformed_html()
    {
        $this->html_gives_markdown("<code><p>Some sample HTML</p></code>", "`<p>Some sample HTML</p>`"); // Invalid HTML, but should still work
        $this->html_gives_markdown("<strong><em>Strong italic</strong> Regular text", "***Strong italic*** Regular text"); // Missing closing </em>
    }

    public function test_html5_tags_are_preserved()
    {
        $this->html_gives_markdown("<article>Some stuff</article>", "<article>Some stuff</article>");
    }

    public function test_strip_unmarkdownable()
    {
        $this->html_gives_markdown('<span>Span</span>', 'Span', array('strip_tags' => true));
    }

    public function test_strip_comments()
    {
        $this->html_gives_markdown('<p>Test</p><!-- Test comment -->', 'Test');
        $this->html_gives_markdown('<p>Test</p><!-- Test comment -->', 'Test', array('strip_tags' => true));
    }

    public function test_delete_blank_p()
    {
        $this->html_gives_markdown('<p></p>', '');
        $this->html_gives_markdown('<p></p>', '', array('strip_tags' => true));
    }


    public function test_divs()
    {
        $this->html_gives_markdown('<div>Hello</div><div>World</div>', '<div>Hello</div><div>World</div>');
        $this->html_gives_markdown('<div>Hello</div><div>World</div>', "Hello\n\nWorld", array('strip_tags' => true));
        $this->html_gives_markdown("<div>Hello</div>\n<div>World</div>", "Hello\n\nWorld", array('strip_tags' => true));
        $this->html_gives_markdown('<p>Paragraph</p><div>Hello</div><div>World</div>', "Paragraph\n\nHello\n\nWorld", array('strip_tags' => true));
    }

    public function test_remove_nodes()
    {
        $this->html_gives_markdown('<div>Hello</div><div>World</div>', '', array('remove_nodes' => 'div'));
        $this->html_gives_markdown('<p>Hello</p><span>World</span>', '', array('remove_nodes' => 'p span'));
    }

    public function test_set_option()
    {
        $markdown = new HTML_To_Markdown();
        $markdown->set_option('strip_tags', true);
        $markdown->convert('<span>Strip</span>');

        $this->assertEquals('Strip', $markdown->__toString());
    }

}