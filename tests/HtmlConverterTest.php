<?php

namespace League\HTMLToMarkdown\Test;

use League\HTMLToMarkdown\Environment;
use League\HTMLToMarkdown\HtmlConverter;

class HtmlConverterTest extends \PHPUnit_Framework_TestCase
{
    private function html_gives_markdown($html, $expected_markdown, array $options = array())
    {
        $markdown = new HtmlConverter($options);
        $result = $markdown->convert($html);
        $this->assertEquals($expected_markdown, $result);
    }

    public function test_empty_input()
    {
        $this->html_gives_markdown('', '');
        $this->html_gives_markdown('     ', '');
    }

    public function test_plain_text()
    {
        $this->html_gives_markdown('test', 'test');
        $this->html_gives_markdown('<p>test</p>', 'test');

        //expected result is in the comment for better readability
        $this->html_gives_markdown('<p>*test*</p>', '\\*test\\*'); // \*test\*
        $this->html_gives_markdown('<p>_test_</p>', '\\_test\\_'); // \_test\_
        $this->html_gives_markdown('<p>\\*test\\*</p>', '\\\\\\*test\\\\\\*'); // \\\*test\\\*
        $this->html_gives_markdown('<p>test[test]</p>', 'test\\[test\\]'); // test\[test\]
    }

    public function test_line_breaks()
    {
        $this->html_gives_markdown("test\nanother line", 'test another line');
        $this->html_gives_markdown("<p>test\nanother line</p>", 'test another line');
        $this->html_gives_markdown("<p>test<br>\nanother line</p>", "test  \nanother line");
        $this->html_gives_markdown("<p>test<br>\n another line</p>", "test  \n another line");
        $this->html_gives_markdown("<p>test<br>\n<em>another</em> line</p>", "test  \n_another_ line");
        $this->html_gives_markdown('<p>test<br>another line</p>', "test  \nanother line");
        $this->html_gives_markdown('<p>test<br/>another line</p>', "test  \nanother line");
        $this->html_gives_markdown('<p>test<br />another line</p>', "test  \nanother line");
        $this->html_gives_markdown('<p>test<br  />another line</p>', "test  \nanother line");
        $this->html_gives_markdown('<p>test<br>another line</p>', "test\nanother line", array('hard_break' => true));
        $this->html_gives_markdown('<p>test<br/>another line</p>', "test\nanother line", array('hard_break' => true));
        $this->html_gives_markdown('<p>test<br />another line</p>', "test\nanother line", array('hard_break' => true));
        $this->html_gives_markdown('<p>test<br  />another line</p>', "test\nanother line", array('hard_break' => true));
    }

    public function test_headers()
    {
        $this->html_gives_markdown('<h1>Test</h1>', "Test\n====");
        $this->html_gives_markdown('<h2>Test</h2>', "Test\n----");
        $this->html_gives_markdown('<blockquote><h1>Test</h1></blockquote>', '> # Test');
        $this->html_gives_markdown('<blockquote><h2>Test</h2></blockquote>', '> ## Test');
        $this->html_gives_markdown('<h3>Test</h3>', '### Test');
        $this->html_gives_markdown('<h4>Test</h4>', '#### Test');
        $this->html_gives_markdown('<h5>Test</h5>', '##### Test');
        $this->html_gives_markdown('<h6>Test</h6>', '###### Test');
    }

    public function test_spans()
    {
        $this->html_gives_markdown('<em>Test</em>', '_Test_');
        $this->html_gives_markdown('<i>Test</i>', '_Test_');
        $this->html_gives_markdown('<strong>Test</strong>', '**Test**');
        $this->html_gives_markdown('<b>Test</b>', '**Test**');
        $this->html_gives_markdown('<em>Test</em>', '*Test*', array('italic_style' => '*'));
        $this->html_gives_markdown('<em>Italic</em> and a <strong>bold</strong>', '*Italic* and a __bold__', array('italic_style' => '*', 'bold_style' => '__'));
        $this->html_gives_markdown('<i>Test</i>', '_Test_', array('italic_style' => '_'));
        $this->html_gives_markdown('<strong>Test</strong>', '__Test__', array('bold_style' => '__'));
        $this->html_gives_markdown('<b>Test</b>', '__Test__', array('bold_style' => '__'));
        $this->html_gives_markdown('<span>Test</span>', '<span>Test</span>');
        $this->html_gives_markdown('<b>Bold</b> <i>Italic</i>', '**Bold** _Italic_');
        $this->html_gives_markdown('<b>Bold</b><i>Italic</i>', '**Bold**_Italic_');
        $this->html_gives_markdown('<em>This is <strong>a test</strong></em>', '_This is **a test**_');
        $this->html_gives_markdown('<em>This is </em><strong>a </strong>test', '_This is_ **a** test');
        $this->html_gives_markdown('Emphasis with no<em> </em>text<strong> perserves</strong> spaces.', 'Emphasis with no text **perserves** spaces.');
        $this->html_gives_markdown("Emphasis discards<em> \n</em>line breaks", "Emphasis discards line breaks");
        $this->html_gives_markdown("Emphasis preserves<em><br/></em>HTML breaks", "Emphasis preserves  \nHTML breaks");
    }

    public function test_nesting()
    {
        $this->html_gives_markdown('<span><span>Test</span></span>', '<span><span>Test</span></span>');
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

        // Autolinking
        $this->html_gives_markdown('<a href="test">test</a>', '[test](test)');
        $this->html_gives_markdown('<a href="google.com">google.com</a>', '[google.com](google.com)');
        $this->html_gives_markdown('<a href="https://www.google.com">https://www.google.com</a>', '<https://www.google.com>');
        $this->html_gives_markdown('<a href="ftp://files.example.com">ftp://files.example.com</a>', '<ftp://files.example.com>');
    }

    public function test_horizontal_rule()
    {
        $this->html_gives_markdown('<hr>', '- - - - - -');
        $this->html_gives_markdown('<hr/>', '- - - - - -');
        $this->html_gives_markdown('<hr />', '- - - - - -');
        $this->html_gives_markdown('<hr  />', '- - - - - -');
    }

    public function test_lists()
    {
        $this->html_gives_markdown('<ul><li>Item A</li><li>Item B</li><li>Item C</li></ul>', "- Item A\n- Item B\n- Item C");
        $this->html_gives_markdown('<ul><li>   Item A</li><li>   Item B</li></ul>', "- Item A\n- Item B");
        $this->html_gives_markdown('<ul><li>  <h3> Item A</h3><p>Description</p></li><li>   Item B</li></ul>', "- ###  Item A\n  \n  Description\n- Item B");
        $this->html_gives_markdown('<ul><li>First</li><li>Second</li></ul>', "* First\n* Second", array('list_item_style' => '*'));
        $this->html_gives_markdown('<ol><li>Item A</li><li>Item B</li></ol>', "1. Item A\n2. Item B");
        $this->html_gives_markdown("<ol>\n    <li>Item A</li>\n    <li>Item B</li>\n</ol>", "1. Item A\n2. Item B");
        $this->html_gives_markdown('<ol><li>   Item A</li><li>   Item B</li></ol>', "1. Item A\n2. Item B");
        $this->html_gives_markdown('<ol><li>  <h3> Item A</h3><p>Description</p></li><li>   Item B</li></ol>', "1. ###  Item A\n  \n  Description\n2. Item B");
        $this->html_gives_markdown('<ol start="120"><li>Item A</li><li>Item B</li></ol>', "120. Item A\n121. Item B");
    }

    public function test_nested_lists()
    {
        $this->html_gives_markdown('<ul><li>Item A</li><li>Item B<ul><li>Nested A</li><li>Nested B</li></ul></li><li>Item C</li></ul>', "- Item A\n- Item B\n  - Nested A\n  - Nested B\n- Item C");
        $this->html_gives_markdown('<ul><li>   Item A<ol><li>Nested A</li></ol></li><li>   Item B</li></ul>', "- Item A\n  1. Nested A\n- Item B");
        $this->html_gives_markdown('<ol><li>Item A<ul><li>Nested A</li></ul></li><li>Item B</li></ol>', "1. Item A\n  - Nested A\n2. Item B");
    }

    public function test_list_like_things_which_arent_lists()
    {
        $this->html_gives_markdown('<p>120.<p>', '120\.');
        $this->html_gives_markdown('<p>120. <p>', '120\.');
        $this->html_gives_markdown('<p>120.00<p>', '120.00');
        $this->html_gives_markdown('<p>120.00 USD<p>', '120.00 USD');
    }

    public function test_code_samples()
    {
        $this->html_gives_markdown('<code>&lt;p&gt;Some sample HTML&lt;/p&gt;</code>', '`<p>Some sample HTML</p>`');
        $this->html_gives_markdown("<code>\n&lt;p&gt;Some sample HTML&lt;/p&gt;\n&lt;p&gt;And another line&lt;/p&gt;\n</code>", "```\n\n<p>Some sample HTML</p>\n<p>And another line</p>\n\n```");
        $this->html_gives_markdown("<p><code>\n#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n</code></p>", "```\n\n#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n\n```");
        $this->html_gives_markdown("<p><code>#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n</code></p>", "```\n#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n\n```");
        $this->html_gives_markdown('<pre><code>&lt;p&gt;Some sample HTML&lt;/p&gt;</code></pre>', '`<p>Some sample HTML</p>`');
        $this->html_gives_markdown('<pre><code class="language-php">&lt;?php //Some php code ?&gt;</code></pre>', '`php <?php //Some php code ?>`');
        $this->html_gives_markdown("<pre><code class=\"language-php\">&lt;?php //Some multiline php code\n\$myVar = 2; ?&gt;</code></pre>", "```php \n<?php //Some multiline php code\n\$myVar = 2; ?>\n```");
        $this->html_gives_markdown("<pre><code>&lt;p&gt;Multiline HTML&lt;/p&gt;\n&lt;p&gt;Here's the second line&lt;/p&gt;</code></pre>", "```\n<p>Multiline HTML</p>\n<p>Here's the second line</p>\n```");
    }

    public function test_preformat()
    {
        $this->html_gives_markdown("<pre>test\ntest\r\ntest</pre>", "```\ntest" . PHP_EOL .'test'. PHP_EOL .'test'. PHP_EOL .'```');
        $this->html_gives_markdown("<pre>test\ntest\r\ntest\n</pre>", "```\ntest" . PHP_EOL .'test'. PHP_EOL .'test'. PHP_EOL .'```');
        $this->html_gives_markdown("<pre>test\n\ttab\r\n</pre>", "```\ntest" . PHP_EOL . "\ttab" . PHP_EOL . '```');
        $this->html_gives_markdown('<pre>  one line with spaces  </pre>', '`  one line with spaces  `');
        $this->html_gives_markdown("<pre></pre>", "```\n```");
        $this->html_gives_markdown("<pre></pre><pre></pre>", "```\n```\n```\n```");
        $this->html_gives_markdown("<pre>\n</pre>", "```\n" . PHP_EOL . '```');
        $this->html_gives_markdown("<pre>foo\n</pre>", "```\nfoo" . PHP_EOL . '```');
        $this->html_gives_markdown("<pre>\nfoo</pre>", "```\n" . PHP_EOL . 'foo' . PHP_EOL . '```');
        $this->html_gives_markdown("<pre>\nfoo\n</pre>", "```\n" . PHP_EOL . 'foo' . PHP_EOL . '```');
        $this->html_gives_markdown("<pre>\n\n</pre>", "```\n" . PHP_EOL . PHP_EOL . '```');
        $this->html_gives_markdown("<pre>\n\n\n</pre>", "```\n" . PHP_EOL . PHP_EOL . PHP_EOL . '```');
        $this->html_gives_markdown("<pre>\n</pre><pre>\n</pre>", "```\n" . PHP_EOL. "```\n```\n" . PHP_EOL . '```');
    }

    public function test_blockquotes()
    {
        $this->html_gives_markdown('<blockquote>Something I said?</blockquote>', '> Something I said?');
        $this->html_gives_markdown('<blockquote><blockquote>Something I said?</blockquote></blockquote>', '> > Something I said?');
        $this->html_gives_markdown('<blockquote><p>Something I said?</p><p>Why, yes it was!</p></blockquote>', "> Something I said?\n> \n> Why, yes it was!");
    }

    public function test_malformed_html()
    {
        $this->html_gives_markdown('<code><p>Some sample HTML</p></code>', '`<p>Some sample HTML</p>`'); // Invalid HTML, but should still work
        $this->html_gives_markdown('<strong><em>Strong italic</strong> Regular text', '**_Strong italic_** Regular text'); // Missing closing </em>
    }

    public function test_html5_tags_are_preserved()
    {
        $this->html_gives_markdown('<article>Some stuff</article>', '<article>Some stuff</article>');
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

    public function test_html_entities()
    {
        $this->html_gives_markdown('<p>&amp;euro;</p>', '&euro;');
        $this->html_gives_markdown('<code>&lt;p&gt;Some sample HTML&lt;/p&gt;</code>', '`<p>Some sample HTML</p>`');
    }

    public function test_set_option()
    {
        $markdown = new HtmlConverter();
        $markdown->getConfig()->setOption('strip_tags', true);
        $result = $markdown->convert('<span>Strip</span>');

        $this->assertEquals('Strip', $result);
    }

    public function test_invoke()
    {
        $markdown = new HtmlConverter();
        $markdown->getConfig()->setOption('strip_tags', true);
        $result = $markdown('<span>Strip</span>');

        $this->assertEquals('Strip', $result);
    }

    public function test_sanitization()
    {
        $html = '<pre><code>&lt;script type = "text/javascript"&gt; function startTimer() { var tim = window.setTimeout("hideMessage()", 5000) } &lt;/head&gt; &lt;body&gt;</code></pre>';
        $markdown = '`<script type = "text/javascript"> function startTimer() { var tim = window.setTimeout("hideMessage()", 5000) } </head> <body>`';
        $this->html_gives_markdown($html, $markdown);
        $this->html_gives_markdown('<p>&gt; &gt; Look at me! &lt; &lt;</p>', '\> > Look at me! < <');
        $this->html_gives_markdown('<p>&gt; &gt; <b>Look</b> at me! &lt; &lt;<br />&gt; Just look at me!</p>', "\\> > **Look** at me! < <  \n\\> Just look at me!");
        $this->html_gives_markdown('<p>Foo<br>--<br>Bar<br>Foo--</p>', "Foo  \n\\--  \nBar  \nFoo--");
        $this->html_gives_markdown("<p>123456789) Foo and 1234567890) Bar!</p>\n<p>1. Platz in 'Das große Backen'</p>", "123456789\\) Foo and 1234567890) Bar!\n\n1\\. Platz in 'Das große Backen'");
        $this->html_gives_markdown("<p>\n+ Siri works well for TV and movies<br>\n- No 4K support\n</p>", "\+ Siri works well for TV and movies  \n\- No 4K support");
        $this->html_gives_markdown('<p>You forgot the &lt;!--more--&gt; tag!</p>', 'You forgot the \<!--more--> tag!');
    }

    public function test_instatiation_with_environment()
    {
        $markdown = new HtmlConverter(new Environment(array()));

        $htmlH3 = '<h3>Test</h3>';
        $result = $markdown->convert($htmlH3);
        $this->assertEquals($htmlH3, $result);

        $htmlH4 = '<h4>Test</h4>';
        $result = $markdown->convert($htmlH4);
        $this->assertEquals($htmlH4, $result);
    }
}
