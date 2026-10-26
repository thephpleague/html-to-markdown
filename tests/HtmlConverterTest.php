<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Test;

use League\HTMLToMarkdown\Converter\ConverterInterface;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\Environment;
use League\HTMLToMarkdown\HtmlConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlConverterTest extends TestCase
{
    /**
     * @param array<string, mixed> $options
     * @param ConverterInterface[] $converters
     */
    private function assertHtmlGivesMarkdown(string $html, string $expectedMarkdown, array $options = [], array $converters = []): void
    {
        $markdown = new HtmlConverter($options);
        foreach ($converters as $converter) {
            $markdown->getEnvironment()->addConverter($converter);
        }

        $result = $markdown->convert($html);
        $this->assertSame($expectedMarkdown, $result);
    }

    public function testEmptyInput(): void
    {
        $this->assertHtmlGivesMarkdown('', '');
        $this->assertHtmlGivesMarkdown('     ', '');
    }

    public function testPlainText(): void
    {
        $this->assertHtmlGivesMarkdown('test', 'test');
        $this->assertHtmlGivesMarkdown('<p>test</p>', 'test');

        //expected result is in the comment for better readability
        $this->assertHtmlGivesMarkdown('<p>*test*</p>', '\\*test\\*'); // \*test\*
        $this->assertHtmlGivesMarkdown('<p>_test_</p>', '\\_test\\_'); // \_test\_
        $this->assertHtmlGivesMarkdown('<p>\\*test\\*</p>', '\\\\\\*test\\\\\\*'); // \\\*test\\\*
        $this->assertHtmlGivesMarkdown('<p>test[test]</p>', 'test\\[test\\]'); // test\[test\]

        // Markdown-like syntax in <div> text should be preserved as-is - no escaping
        $this->assertHtmlGivesMarkdown('<div>_test_</div>', '<div>_test_</div>');
        $this->assertHtmlGivesMarkdown('<div>*test*</div>', '<div>*test*</div>');

        $this->assertHtmlGivesMarkdown('<p>\ ` * _ { } [ ] ( ) &gt; > # + - . !</p>', '\\\\ &#96; \* \_ { } \[ \] ( ) &gt; &gt; # + - . !');
    }

    public function testLineBreaks(): void
    {
        $this->assertHtmlGivesMarkdown("test\nanother line", 'test another line');
        $this->assertHtmlGivesMarkdown("<p>test\nanother line</p>", 'test another line');
        $this->assertHtmlGivesMarkdown("<p>test<br>\nanother line</p>", "test  \nanother line");
        $this->assertHtmlGivesMarkdown("<p>test<br>\n another line</p>", "test  \n another line");
        $this->assertHtmlGivesMarkdown("<p>test<br>\n<em>another</em> line</p>", "test  \n*another* line");
        $this->assertHtmlGivesMarkdown('<p>test<br>another line</p>', "test  \nanother line");
        $this->assertHtmlGivesMarkdown('<p>test<br/>another line</p>', "test  \nanother line");
        $this->assertHtmlGivesMarkdown('<p>test<br />another line</p>', "test  \nanother line");
        $this->assertHtmlGivesMarkdown('<p>test<br  />another line</p>', "test  \nanother line");
        $this->assertHtmlGivesMarkdown('<p>test<br>another line</p>', "test\nanother line", ['hard_break' => true]);
        $this->assertHtmlGivesMarkdown('<p>test<br/>another line</p>', "test\nanother line", ['hard_break' => true]);
        $this->assertHtmlGivesMarkdown('<p>test<br />another line</p>', "test\nanother line", ['hard_break' => true]);
        $this->assertHtmlGivesMarkdown('<p>test<br  />another line</p>', "test\nanother line", ['hard_break' => true]);
        $this->assertHtmlGivesMarkdown('<p>foo</p><table><tr><td>bar</td></tr></table><p>baz</p>', "foo\n\n<table><tr><td>bar</td></tr></table>\n\nbaz");
    }

    public function testHeaders(): void
    {
        $this->assertHtmlGivesMarkdown('<h1>Test</h1>', "Test\n====");
        $this->assertHtmlGivesMarkdown('<h2>Test</h2>', "Test\n----");
        $this->assertHtmlGivesMarkdown('<blockquote><h1>Test</h1></blockquote>', '> # Test');
        $this->assertHtmlGivesMarkdown('<blockquote><h2>Test</h2></blockquote>', '> ## Test');
        $this->assertHtmlGivesMarkdown('<h3>Test</h3>', '### Test');
        $this->assertHtmlGivesMarkdown('<h4>Test</h4>', '#### Test');
        $this->assertHtmlGivesMarkdown('<h5>Test</h5>', '##### Test');
        $this->assertHtmlGivesMarkdown('<h6>Test</h6>', '###### Test');
        $this->assertHtmlGivesMarkdown('<h1></h1>', '');
        $this->assertHtmlGivesMarkdown('<h2></h2>', '');
        $this->assertHtmlGivesMarkdown('<h3></h3>', '');
        $this->assertHtmlGivesMarkdown('<h1># Test</h1>', "\# Test\n=======");
        $this->assertHtmlGivesMarkdown('<h1># Test #</h1>', "\# Test #\n=========");
        $this->assertHtmlGivesMarkdown('<h3>Mismatched Tags</h4>', '### Mismatched Tags');
    }

    public function testSpans(): void
    {
        $this->assertHtmlGivesMarkdown('<em>Test</em>', '*Test*');
        $this->assertHtmlGivesMarkdown('<i>Test</i>', '*Test*');
        $this->assertHtmlGivesMarkdown('<strong>Test</strong>', '**Test**');
        $this->assertHtmlGivesMarkdown('<b>Test</b>', '**Test**');
        $this->assertHtmlGivesMarkdown('<em>Test</em>', '*Test*', ['italic_style' => '*']);
        $this->assertHtmlGivesMarkdown('<em>Italic</em> and a <strong>bold</strong>', '*Italic* and a __bold__', ['italic_style' => '*', 'bold_style' => '__']);
        $this->assertHtmlGivesMarkdown('<i>Test</i>', '_Test_', ['italic_style' => '_']);
        $this->assertHtmlGivesMarkdown('<strong>Test</strong>', '__Test__', ['bold_style' => '__']);
        $this->assertHtmlGivesMarkdown('<b>Test</b>', '__Test__', ['bold_style' => '__']);
        $this->assertHtmlGivesMarkdown('<span>Test</span>', '<span>Test</span>');
        $this->assertHtmlGivesMarkdown('<b>Bold</b> <i>Italic</i>', '**Bold** *Italic*');
        $this->assertHtmlGivesMarkdown('<b>Bold</b><i>Italic</i>', '**Bold***Italic*');
        $this->assertHtmlGivesMarkdown('<em>This is <strong>a test</strong></em>', '*This is **a test***');
        $this->assertHtmlGivesMarkdown('<em>This is </em><strong>a </strong>test', '*This is* **a** test');
        $this->assertHtmlGivesMarkdown('Emphasis with no<em> </em>text<strong> preserves</strong> spaces.', 'Emphasis with no text **preserves** spaces.');
        $this->assertHtmlGivesMarkdown("Emphasis discards<em> \n</em>line breaks", 'Emphasis discards line breaks');
        $this->assertHtmlGivesMarkdown('Emphasis preserves<em><br/></em>HTML breaks', "Emphasis preserves  \nHTML breaks");
    }

    public function testConsecutiveSpans(): void
    {
        $this->assertHtmlGivesMarkdown('<em>Foo</em><em>Bar</em>', '*FooBar*');
        $this->assertHtmlGivesMarkdown('<i>Foo</i><i>Bar</i>', '*FooBar*');
        $this->assertHtmlGivesMarkdown('<em>Foo</em><i>Bar</i><em>Foo</em>', '*FooBarFoo*');
        $this->assertHtmlGivesMarkdown('<strong>Foo</strong><strong>Bar</strong>', '**FooBar**');
        $this->assertHtmlGivesMarkdown('<b>Foo</b><b>Bar</b>', '**FooBar**');
        $this->assertHtmlGivesMarkdown('<strong>Foo</strong><b>Bar</b><strong>Foo</strong>', '**FooBarFoo**');
        $this->assertHtmlGivesMarkdown('<em>Foo</em> <em>Bar</em>', '*Foo* *Bar*');
        $this->assertHtmlGivesMarkdown('<strong>Foo</strong> <strong>Bar</strong>', '**Foo** **Bar**');
        $this->assertHtmlGivesMarkdown('<strong>Foo</strong><b>Bar</b><em>Foo</em>', '**FooBar***Foo*');
        $this->assertHtmlGivesMarkdown('<strong> </strong><strong>hello</strong>', ' **hello**');
        $this->assertHtmlGivesMarkdown('<strong>hello</strong><strong> </strong>', '**hello**');
        $this->assertHtmlGivesMarkdown('<strong>a</strong><strong></strong><strong>b</strong>', '**ab**');
        $this->assertHtmlGivesMarkdown('<b></b><b>a</b><b>b</b>', '**ab**');
        $this->assertHtmlGivesMarkdown('<strong>a</strong><strong><img src="b.png"></strong><strong>c</strong>', '**a![](b.png)c**');
        $this->assertHtmlGivesMarkdown('<em>Foo</em><em><br></em><em>Bar</em>', "*Foo  \nBar*");
        $this->assertHtmlGivesMarkdown('<b>a</b><b><i>b</i></b><b>c</b>', '**a*b*c**');
        $this->assertHtmlGivesMarkdown('<b><i>a</i></b><b><i>b</i></b>', '***ab***');
        $this->assertHtmlGivesMarkdown('<em> </em><i>a</i> <b></b><b>b</b>', ' _a_ __b__', ['italic_style' => '_', 'bold_style' => '__']);
        $this->assertHtmlGivesMarkdown('<ul><li><b>a</b><b></b><b>b</b></li></ul>', '- **ab**');
        $this->assertHtmlGivesMarkdown('<strong>a</strong><b>b</b><strong>c</strong>', '**ac**', ['remove_nodes' => 'b']);
        $this->assertHtmlGivesMarkdown('<b>a</b><strong>b</strong><strong>c</strong>', '**bc**', ['remove_nodes' => 'b']);
        $this->assertHtmlGivesMarkdown('<p><code><b>a</b><b>b</b></code></p>', '`<b>a</b><b>b</b>`');
    }

    public function testNesting(): void
    {
        $this->assertHtmlGivesMarkdown('<span><span>Test</span></span>', '<span><span>Test</span></span>');
    }

    public function testScript(): void
    {
        $this->assertHtmlGivesMarkdown("<script>alert('test');</script>", "<script>alert('test');</script>");
    }

    public function testImage(): void
    {
        $this->assertHtmlGivesMarkdown('<img src="/path/img.jpg" alt="alt text" title="Title" />', '![alt text](/path/img.jpg "Title")');
    }

    public function testAnchor(): void
    {
        $this->assertHtmlGivesMarkdown('<a href="http://modernnerd.net">http://modernnerd.net</a>', '<http://modernnerd.net>');
        $this->assertHtmlGivesMarkdown('<a href="http://modernnerd.net" title="Title">Modern Nerd</a>', '[Modern Nerd](http://modernnerd.net "Title")');
        $this->assertHtmlGivesMarkdown('<a href="http://modernnerd.net" title="Title">Modern Nerd</a> <a href="http://modernnerd.net" title="Title">Modern Nerd</a>', '[Modern Nerd](http://modernnerd.net "Title") [Modern Nerd](http://modernnerd.net "Title")');
        $this->assertHtmlGivesMarkdown('<a href="http://modernnerd.net"><h3>Modern Nerd</h3></a>', '[### Modern Nerd](http://modernnerd.net)');
        $this->assertHtmlGivesMarkdown('The <a href="http://modernnerd.net">Modern Nerd </a>(MN)', 'The [Modern Nerd ](http://modernnerd.net)(MN)');
        $this->assertHtmlGivesMarkdown('<a href="http://modernnerd.net/" title="Title"><img src="/path/img.jpg" alt="alt text" title="Title"/></a>', '[![alt text](/path/img.jpg "Title")](http://modernnerd.net/ "Title")');
        $this->assertHtmlGivesMarkdown('<a href="http://modernnerd.net/" title="Title"><img src="/path/img.jpg" alt="alt text" title="Title"/> Test</a>', '[![alt text](/path/img.jpg "Title") Test](http://modernnerd.net/ "Title")');

        // Placeholder links and fragment identifiers
        $this->assertHtmlGivesMarkdown('<a>Test</a>', '<a>Test</a>');
        $this->assertHtmlGivesMarkdown('<a href="">Test</a>', '<a href="">Test</a>');
        $this->assertHtmlGivesMarkdown('<a href="#nerd" title="Title">Test</a>', '[Test](#nerd "Title")');
        $this->assertHtmlGivesMarkdown('<a href="#nerd">Test</a>', '[Test](#nerd)');

        // Strip placeholder links
        $this->assertHtmlGivesMarkdown('<a>Test</a>', 'Test', ['strip_placeholder_links' => true]);
        $this->assertHtmlGivesMarkdown('<a href="">Test</a>', 'Test', ['strip_placeholder_links' => true]);
        $this->assertHtmlGivesMarkdown('<a href="#nerd" title="Title">Test</a>', '[Test](#nerd "Title")', ['strip_placeholder_links' => true]);
        $this->assertHtmlGivesMarkdown('<a href="#nerd">Test</a>', '[Test](#nerd)', ['strip_placeholder_links' => true]);

        // Autolinking
        $this->assertHtmlGivesMarkdown('<a href="test">test</a>', '[test](test)');
        $this->assertHtmlGivesMarkdown('<a href="google.com">google.com</a>', '[google.com](google.com)');
        $this->assertHtmlGivesMarkdown('<a href="https://www.google.com">https://www.google.com</a>', '<https://www.google.com>');
        $this->assertHtmlGivesMarkdown('<a href="ftp://files.example.com">ftp://files.example.com</a>', '<ftp://files.example.com>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/?a=1&amp;b=2">https://example.com/?a=1&amp;b=2</a>', '<https://example.com/?a=1&b=2>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/?a=1&b=foo%2Fbar">https://example.com/?a=1&amp;b=foo/bar</a>', '<https://example.com/?a=1&b=foo%2Fbar>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/caf%C3%A9">https://example.com/café</a>', '<https://example.com/caf%C3%A9>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/a b">https://example.com/a b</a>', '<https://example.com/a%20b>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/a%2fb">https://example.com/a%2Fb</a>', '<https://example.com/a%2fb>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/?q=a+b">https://example.com/?q=a b</a>', '[https://example.com/?q=a b](https://example.com/?q=a+b)');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/&lt;b&gt;">https://example.com/&lt;b&gt;</a>', '<https://example.com/%3Cb%3E>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/a_b*c">https://example.com/a_b*c</a>', '<https://example.com/a_b%2Ac>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/a%5Fb">https://example.com/a_b</a>', '<https://example.com/a%5Fb>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/a%5C_b">https://example.com/a_b</a>', '[https://example.com/a\\_b](https://example.com/a%5C_b)');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/%26lt;b%26gt;">https://example.com/&lt;b&gt;</a>', '[https://example.com/&lt;b&gt;](https://example.com/%26lt;b%26gt;)');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/%2Afoo%2A">https://example.com/<em>foo</em></a>', '[https://example.com/*foo*](https://example.com/%2Afoo%2A)');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/a%20%20%0Ab">https://example.com/a<br>b</a>', '[https://example.com/a  ' . "\n" . 'b](https://example.com/a%20%20%0Ab)');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/a%0Ab">https://example.com/a<br>b</a>', '[https://example.com/a' . "\n" . 'b](https://example.com/a%0Ab)', ['hard_break' => true]);
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/%60">https://example.com/`</a>', '<https://example.com/%60>');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/%26%2396;">https://example.com/`</a>', '[https://example.com/&#96;](https://example.com/%26%2396;)');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/&amp;%2396;">https://example.com/&amp;#96;</a>', '<https://example.com/&%2396;>');
        $this->assertHtmlGivesMarkdown('<a href="mailto:test@example.com">test@example.com</a>', '<test@example.com>');
        $this->assertHtmlGivesMarkdown('<a href="mailto:test+foo@example.bar-baz.com">test+foo@example.bar-baz.com</a>', '<test+foo@example.bar-baz.com>');

        // Autolinking can be toggled off
        $this->assertHtmlGivesMarkdown('<a href="https://www.google.com">https://www.google.com</a>', '[https://www.google.com](https://www.google.com)', ['use_autolinks' => false]);
        $this->assertHtmlGivesMarkdown('<a href="https://www.google.com">Google</a>', '[Google](https://www.google.com)', ['use_autolinks' => false]);
        $this->assertHtmlGivesMarkdown('<a href="google.com">google.com</a>', '[google.com](google.com)', ['use_autolinks' => false]);
    }

    public function testHorizontalRule(): void
    {
        $this->assertHtmlGivesMarkdown('<hr>', '---');
        $this->assertHtmlGivesMarkdown('<hr/>', '---');
        $this->assertHtmlGivesMarkdown('<hr />', '---');
        $this->assertHtmlGivesMarkdown('<hr  />', '---');
        $this->assertHtmlGivesMarkdown('<p>Test</p><hr>', "Test\n\n---");
    }

    public function testLists(): void
    {
        $this->assertHtmlGivesMarkdown('<ul><li>Item A</li><li>Item B</li><li>Item C</li></ul>', "- Item A\n- Item B\n- Item C");
        $this->assertHtmlGivesMarkdown('<ul><li>   Item A</li><li>   Item B</li></ul>', "- Item A\n- Item B");
        $this->assertHtmlGivesMarkdown('<ul><li>  <h3> Item A</h3><p>Description</p></li><li>   Item B</li></ul>', "- ###  Item A\n    \n    Description\n- Item B");
        $this->assertHtmlGivesMarkdown('<ul><li>First</li><li>Second</li></ul>', "* First\n* Second", ['list_item_style' => '*']);
        $this->assertHtmlGivesMarkdown('<ol><li>Item A</li><li>Item B</li></ol>', "1. Item A\n2. Item B");
        $this->assertHtmlGivesMarkdown("<ol>\n    <li>Item A</li>\n    <li>Item B</li>\n</ol>", "1. Item A\n2. Item B");
        $this->assertHtmlGivesMarkdown('<ol><li>   Item A</li><li>   Item B</li></ol>', "1. Item A\n2. Item B");
        $this->assertHtmlGivesMarkdown('<ol><li>  <h3> Item A</h3><p>Description</p></li><li>   Item B</li></ol>', "1. ###  Item A\n    \n    Description\n2. Item B");
        $this->assertHtmlGivesMarkdown('<ol start="120"><li>Item A</li><li>Item B</li></ol>', "120. Item A\n121. Item B");
        $this->assertHtmlGivesMarkdown('<ol start="0"><li>Item A</li><li>Item B</li></ol>', "0. Item A\n1. Item B");
        $this->assertHtmlGivesMarkdown('<ul><li>first item of first list</li><li>second item of first list</li></ul><ul><li>first item of second list</li></ul>', "- first item of first list\n- second item of first list\n\n* first item of second list", ['list_item_style_alternate' => '*']);
    }

    public function testNestedLists(): void
    {
        $this->assertHtmlGivesMarkdown('<ul><li>Item A</li><li>Item B<ul><li>Nested A</li><li>Nested B</li></ul></li><li>Item C</li></ul>', "- Item A\n- Item B\n    - Nested A\n    - Nested B\n- Item C");
        $this->assertHtmlGivesMarkdown('<ul><li>   Item A<ol><li>Nested A</li></ol></li><li>   Item B</li></ul>', "- Item A\n    1. Nested A\n- Item B");
        $this->assertHtmlGivesMarkdown('<ol><li>Item A<ul><li>Nested A</li></ul></li><li>Item B</li></ol>', "1. Item A\n    - Nested A\n2. Item B");
    }

    public function testComplexNestedLists(): void
    {
        $this->assertHtmlGivesMarkdown("<ul>\n<li>Item A</li>\n<li>Item B\n<ul>\n<li>Nested A</li>\n<li>Nested B\n<ul>\n<li>Subnested A</li>\n<li>Subnested B</li>\n</ul>\n</li>\n</ul>\n</li>\n<li>Item C</li>\n</ul>", "- Item A\n- Item B \n    - Nested A\n    - Nested B \n        - Subnested A\n        - Subnested B\n- Item C");
        $this->assertHtmlGivesMarkdown("<ul>\n<li>Item A</li>\n<li><h2>Item B</h2>\n<p>Paragraph Item B</p>\n<ul>\n<li>Nested A</li>\n<li>Nested B\n<ul>\n<li>Subnested A</li>\n<li>Subnested B</li>\n</ul>\n</li>\n</ul>\n</li>\n<li>Item C</li>\n</ul>", "* Item A\n* ## Item B\n    \n    Paragraph Item B\n    \n    \n    * Nested A\n    * Nested B \n        * Subnested A\n        * Subnested B\n* Item C", ['list_item_style' => '*', 'header_style' => 'atx']);
        $this->assertHtmlGivesMarkdown("<ul>\n<li>Item A</li>\n<li><h2>Item B</h2>\n<p>Paragraph Item B</p>\n<ul>\n<li>Nested A</li>\n<li><h2>Nested B</h2>\n<p>Paragraph Nested B</p>\n<ul>\n<li>Subnested A</li>\n<li>Subnested B\n<ul>\n<li>Subsubnested A</li>\n<li><h2>Subsubnested B</h2>\n<p>Paragraph Subsubnested B</p>\n<ul>\n<li>Subsubsubnested A</li>\n<li>Subsubsubnested B</li>\n</ul>\n</li>\n</ul>\n</li>\n</ul>\n</li>\n</ul>\n</li>\n<li>Item C</li>\n</ul>", "* Item A\n* ## Item B\n    \n    Paragraph Item B\n    \n    \n    * Nested A\n    * ## Nested B\n        \n        Paragraph Nested B\n        \n        \n        * Subnested A\n        * Subnested B \n            * Subsubnested A\n            * ## Subsubnested B\n                \n                Paragraph Subsubnested B\n                \n                \n                * Subsubsubnested A\n                * Subsubsubnested B\n* Item C", ['list_item_style' => '*', 'header_style' => 'atx']);
        $this->assertHtmlGivesMarkdown("<ul>\n<li>Item A</li>\n<li>Item B\n<ul>\n<li>Nested A</li>\n<li>Nested B\n<ul>\n<li>Subnested A</li>\n<li>Subnested B\n<ul>\n<li>Subsubnested A</li>\n<li>Subsubnested B                                            \n<ul>\n<li>Subsubsubnested A</li>\n<li>Subsubsubnested B</li>\n</ul>\n</li>\n</ul>\n</li>\n</ul>\n</li>\n</ul>\n</li>\n<li>Item C</li>\n</ul>", "* Item A\n* Item B \n    * Nested A\n    * Nested B \n        * Subnested A\n        * Subnested B \n            * Subsubnested A\n            * Subsubnested B \n                * Subsubsubnested A\n                * Subsubsubnested B\n* Item C", ['list_item_style' => '*', 'header_style' => 'atx']);
    }

    public function testListItemsAreNumberedByWhatWasOutputBeforeThem(): void
    {
        $this->assertHtmlGivesMarkdown('<ol><!-- c --><li>A</li><span></span><li>B</li>text<li>C</li></ol>', "1. A\n<span></span>3. B\ntext5. C");
        $this->assertHtmlGivesMarkdown('<ol><li>A</li><em>x</em><li>B</li><em>y</em><li>C</li></ol>', "1. A\n*x*3. B\n*y*5. C");
        $this->assertHtmlGivesMarkdown('<ol><li>A</li><em>x</em><li>B</li><em>y</em><li>C</li></ol>', "1. A\n2. B\n3. C", ['remove_nodes' => 'em']);
        $this->assertHtmlGivesMarkdown("<ol>\n<li>A</li>\n\n<li>B<ol><li>N1</li> <li>N2</li></ol></li>\n<li>C</li></ol>", "1. A\n2. B\n    1. N1\n    2. N2\n3. C");
        $this->assertHtmlGivesMarkdown('<ul><li>A<ul><li>N1</li><li>N2</li></ul></li><li>B</li></ul><ul><li>C</li><li>D</li></ul>', "- A\n    * N1\n    * N2\n- B\n\n* C\n* D", ['list_item_style_alternate' => '*']);
    }

    /**
     * @dataProvider provideManySiblings
     */
    #[DataProvider('provideManySiblings')]
    public function testManySiblingsDoNotTakeDisproportionatelyLong(int $count, string $before, string $repeated, string $after, string $expectedEnd): void
    {
        $html = $before . \str_repeat($repeated, $count) . $after;

        $start    = \microtime(true);
        $markdown = (new HtmlConverter())->convert($html);
        $elapsed  = \microtime(true) - $start;

        $this->assertStringEndsWith($expectedEnd, $markdown);

        // Each of these took several times longer than this when the time taken grew with the square of the count
        $this->assertLessThan(10, $elapsed);
    }

    /**
     * @return iterable<string, array{int, string, string, string, string}>
     */
    public static function provideManySiblings(): iterable
    {
        yield 'ordered list items' => [10000, '<ol>', '<li>x</li>', '</ol>', "\n10000. x"];
        yield 'nested list items' => [10000, '<ul><li><ul>', '<li>x</li>', '</ul></li></ul>', "\n    - x"];
        yield 'preformatted blocks' => [10000, '', '<pre><code>x</code></pre>', '', "\n\n```\nx\n```"];
        yield 'preserved tags' => [30000, '<p>', '<span>x</span> ', '</p>', ' <span>x</span>'];
        yield 'code spans' => [30000, '<p>', '<code>x</code> ', '</p>', ' `x`'];
        yield 'non-breaking spaces' => [30000, '<p>a', '<em>&nbsp;</em>', 'b</p>', "\u{00A0}b"];
        yield 'a run of non-breaking spaces' => [100000, '<p><em>a', '&nbsp;', 'b</em> c&nbsp;</p>', "\u{00A0}b* c"];
        yield 'long emphasis' => [1000000, '<p><em>&nbsp;', 'a', '&nbsp;</em> b</p>', "a*\u{00A0} b"];
    }

    public function testListLikeThingsWhichArentLists(): void
    {
        $this->assertHtmlGivesMarkdown('<p>120.<p>', '120\.');
        $this->assertHtmlGivesMarkdown('<p>120. <p>', '120\.');
        $this->assertHtmlGivesMarkdown('<p>120.00<p>', '120.00');
        $this->assertHtmlGivesMarkdown('<p>120.00 USD<p>', '120.00 USD');
    }

    public function testTables(): void
    {
        $opt  = [];
        $conv = [new TableConverter()];
        $this->assertHtmlGivesMarkdown('<table><tr><th>A</th></tr></table>', "| A |\n|---|", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><td>A</td></tr></table>', "| A |\n|---|", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th>A</th><th>B</th></tr><tr><td>a</td><td>b</td></tr><tr><td>c</td><td>d</td></tr></table>', "| A | B |\n|---|---|\n| a | b |\n| c | d |", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th>A</th><th>B</th></tr><tr><td>a</td><td><code>foo</code></td></tr></table>', "| A | B |\n|---|---|\n| a | `foo` |", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th>A</th><th>B</th></tr><tr><td>a</td><td><em>foo</em>bar</td></tr></table>', "| A | B |\n|---|---|\n| a | *foo*bar |", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th>A</th><th>B</th></tr><tr><td>a</td><td><p>foo</p>bar</td></tr></table>', "| A | B |\n|---|---|\n| a | foo  bar |", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><td><a href="http://example.com">link</a></td></tr></table>', "| [link](http://example.com) |\n|---|", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th>A</th></tr><tr><td>a | b</td></tr></table>', "| A |\n|---|\n| a \\| b |", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th>A</th></tr><tr><td>a | b</td></tr></table>', "| A |\n|---|\n| a ][ b |", ['table_pipe_escape' => ']['], $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th>A</th></tr><tr><td><a href="https://example.com/a|b">https://example.com/a|b</a></td></tr></table>', "| A |\n|---|\n| [https://example.com/a&#124;b](https://example.com/a&#124;b) |", ['table_pipe_escape' => '&#124;'], $conv);
        $this->assertHtmlGivesMarkdown('<table><caption>Cap</caption><tr><th>A</th></tr></table>', "Cap\n\n| A |\n|---|", ['table_caption_side' => 'top'], $conv);
        $this->assertHtmlGivesMarkdown('<table><caption>Cap</caption><tr><th>A</th></tr></table>', "| A |\n|---|\n\nCap", ['table_caption_side' => 'bottom'], $conv);
        $this->assertHtmlGivesMarkdown('<table><caption>Cap</caption><tr><th>A</th></tr></table>', "| A |\n|---|", ['table_caption_side' => null], $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th align="left">A</th></tr></table>', "| A |\n|:--|", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th align="right">A</th></tr></table>', "| A |\n|--:|", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th align="center">A</th></tr></table>', "| A |\n|:-:|", $opt, $conv);
        $this->assertHtmlGivesMarkdown('<table><tr><th align="wrong">A</th></tr></table>', "| A |\n|---|", $opt, $conv);
        $html = <<<'EOT'
<table>
    <colgroup>
        <col>
        <col span="2" class="batman">
        <col span="2" class="flash">
    </colgroup>
    <thead>
        <tr>
            <td> </td>
            <th scope="col">Batman</th>
            <th scope="col">Robin</th>
            <th scope="col">The Flash</th>
            <th scope="col">Kid Flash</th>
        </tr>
    </thead>
    <tr>
        <th scope="row">Skill</th>
        <td>Smarts</td>
        <td>Dex, acrobat</td>
        <td>Super speed</td>
        <td>Super speed</td>
    </tr>
</table>
EOT;
        $this->assertHtmlGivesMarkdown($html, "|  | Batman | Robin | The Flash | Kid Flash |\n|---|---|---|---|---|\n| Skill | Smarts | Dex, acrobat | Super speed | Super speed |", $opt, $conv);
    }

    public function testCodeSamples(): void
    {
        $this->assertHtmlGivesMarkdown('<code>&lt;p&gt;Some sample HTML&lt;/p&gt;</code>', '`<p>Some sample HTML</p>`');
        $this->assertHtmlGivesMarkdown("<code>\n&lt;p&gt;Some sample HTML&lt;/p&gt;\n&lt;p&gt;And another line&lt;/p&gt;\n</code>", '`<p>Some sample HTML</p><p>And another line</p>`');
        $this->assertHtmlGivesMarkdown('<code>`</code>', '`` ` ``');
        $this->assertHtmlGivesMarkdown('<code>test</code>', '`test`');
        $this->assertHtmlGivesMarkdown('<code>test `` test</code>', '`test `` test`');
        $this->assertHtmlGivesMarkdown('<code>test` `test</code>', '``test` `test``');
        $this->assertHtmlGivesMarkdown("<p><code>\n&lt;p&gt;Some sample HTML&lt;/p&gt;\n&lt;p&gt;And another line&lt;/p&gt;\n</code></p><p>Paragraph after code.</p>", "`<p>Some sample HTML</p><p>And another line</p>`\n\nParagraph after code.");
        $this->assertHtmlGivesMarkdown("<p><code>\n#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n</code></p>", '`#sidebar h1 {    font-size: 1.5em;    font-weight: bold;}`');
        $this->assertHtmlGivesMarkdown("<p><code>#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n</code></p>", '`#sidebar h1 {    font-size: 1.5em;    font-weight: bold;}`');
        $this->assertHtmlGivesMarkdown('<pre><code>&lt;p&gt;Some sample HTML&lt;/p&gt;</code></pre>', "```\n<p>Some sample HTML</p>\n```");
        $this->assertHtmlGivesMarkdown('<pre><code class="language-php">&lt;?php //Some php code ?&gt;</code></pre>', "```php\n<?php //Some php code ?>\n```");
        $this->assertHtmlGivesMarkdown("<pre><code class=\"language-php\">&lt;?php //Some multiline php code\n\$myVar = 2; ?&gt;</code></pre>", "```php\n<?php //Some multiline php code\n\$myVar = 2; ?>\n```");
        $this->assertHtmlGivesMarkdown("<pre><code>&lt;p&gt;Multiline HTML&lt;/p&gt;\n&lt;p&gt;Here's the second line&lt;/p&gt;</code></pre>", "```\n<p>Multiline HTML</p>\n<p>Here's the second line</p>\n```");
        $this->assertHtmlGivesMarkdown("<pre><code>&lt;p&gt;Multiline HTML&lt;/p&gt;\n&lt;p&gt;Here's the second line&lt;/p&gt;</code></pre>\n<p>line</p>", "```\n<p>Multiline HTML</p>\n<p>Here's the second line</p>\n```\n\nline");
    }

    public function testPreformat(): void
    {
        $this->assertHtmlGivesMarkdown("<pre>test\ntest\r\ntest</pre>", "```\ntest\ntest\ntest\n```");
        $this->assertHtmlGivesMarkdown("<pre>test\ntest\r\ntest\n</pre>", "```\ntest\ntest\ntest\n```");
        $this->assertHtmlGivesMarkdown("<pre>test\n\ttab\r\n</pre>", "```\ntest\n\ttab\n```");
        $this->assertHtmlGivesMarkdown('<pre>  one line with spaces  </pre>', "```\n  one line with spaces  \n```");
        $this->assertHtmlGivesMarkdown('<pre></pre>', "```\n```");
        $this->assertHtmlGivesMarkdown('<pre></pre><pre></pre>', "```\n```\n\n```\n```");
        $this->assertHtmlGivesMarkdown("<pre>\n</pre>", "```\n```");
        $this->assertHtmlGivesMarkdown("<pre>foo\n</pre>", "```\nfoo\n```");
        $this->assertHtmlGivesMarkdown("<pre>\nfoo</pre>", "```\nfoo\n```");
        $this->assertHtmlGivesMarkdown("<pre>\nfoo\n</pre>", "```\nfoo\n```");
        $this->assertHtmlGivesMarkdown("<pre>\r\nfoo</pre>", "```\nfoo\n```");
        $this->assertHtmlGivesMarkdown("<pre>\n\nfoo</pre>", "```\n\nfoo\n```");
        $this->assertHtmlGivesMarkdown("<pre>\n\n</pre>", "```\n\n```");
        $this->assertHtmlGivesMarkdown("<pre>\n\n\n</pre>", "```\n\n\n```");
        $this->assertHtmlGivesMarkdown("<pre>\n</pre><pre>\n</pre>", "```\n```\n\n```\n```");
        $this->assertHtmlGivesMarkdown("<pre>one\ntwo\r\nthree</pre>\n<p>line</p>", "```\none\ntwo\nthree\n```\n\nline");
        $this->assertHtmlGivesMarkdown("<pre class='some-class'>test with attributes</pre>", "```\ntest with attributes\n```");
    }

    public function testCodeContainingTextWhichLooksLikeItsOwnTags(): void
    {
        $this->assertHtmlGivesMarkdown('<pre>a &lt;/pre&gt; b</pre>', "```\na </pre> b\n```");
        $this->assertHtmlGivesMarkdown('<pre>x &lt;pre foo y</pre>', "```\nx <pre foo y\n```");
        $this->assertHtmlGivesMarkdown('<pre><code>a &lt;/code&gt; b</code></pre>', "```\na </code> b\n```");
        $this->assertHtmlGivesMarkdown('<pre><code>a &lt;/pre&gt; b</code></pre>', "```\na </pre> b\n```");
        $this->assertHtmlGivesMarkdown('<p><code>a &lt;code x&gt; b</code></p>', '`a <code x> b`');
        $this->assertHtmlGivesMarkdown('<div><code>a &lt;/code&gt; b</code></div>', '<div><code>a &#60;&#47;code&#62; b</code></div>');
        $this->assertHtmlGivesMarkdown('<pre class="a>b">x</pre>', "```\nx\n```");
        $this->assertHtmlGivesMarkdown('<p><code class="a>b">x</code></p>', '`x`');
    }

    /**
     * @see https://github.com/thephpleague/html-to-markdown/issues/254
     */
    public function testCodeContainingTextWhichLooksLikeDocumentTags(): void
    {
        $this->assertHtmlGivesMarkdown('<pre><code>&lt;/body&gt;</code></pre>', "```\n</body>\n```");
        $this->assertHtmlGivesMarkdown('<pre><code>&lt;/html&gt;</code></pre>', "```\n</html>\n```");
        $this->assertHtmlGivesMarkdown('<pre><code>&lt;/head&gt;&lt;/body&gt;&lt;/html&gt;</code></pre>', "```\n</head></body></html>\n```");
        $this->assertHtmlGivesMarkdown('<pre><code>&lt;html&gt;&lt;body&gt;</code></pre>', "```\n<html><body>\n```");
        $this->assertHtmlGivesMarkdown('<p>a <code>&lt;/body&gt;&lt;/html&gt;</code></p>', 'a `</body></html>`');
        $this->assertHtmlGivesMarkdown('<pre><code>&lt;!DOCTYPE html&gt;</code></pre>', "```\n<!DOCTYPE html>\n```");
        $this->assertHtmlGivesMarkdown('<!DOCTYPE html><pre><code>&lt;!DOCTYPE html&gt;&lt;/html&gt;</code></pre>', "```\n<!DOCTYPE html></html>\n```");
        $this->assertHtmlGivesMarkdown('<p>Use <code>&lt;!DOCTYPE html</code> first.</p><p>a &gt; b</p>', "Use `<!DOCTYPE html` first.\n\na &gt; b");
        $this->assertHtmlGivesMarkdown('<p>x</p><!-- </body> -->', "x\n\n<!-- </body> -->", ['preserve_comments' => true]);
    }

    public function testNestedCode(): void
    {
        $this->assertHtmlGivesMarkdown('<pre><pre>a</pre></pre>', "```\na\n```");
        $this->assertHtmlGivesMarkdown('<pre><pre></pre></pre>', "```\n```");
        $this->assertHtmlGivesMarkdown('<pre>a<pre class="x">b</pre>c</pre>', "```\nabc\n```");
        $this->assertHtmlGivesMarkdown('<pre><code><pre>a</pre></code></pre>', "```\na\n```");
        $this->assertHtmlGivesMarkdown('<pre><code>a<code>b</code></code></pre>', "```\na`b`\n```");
        $this->assertHtmlGivesMarkdown('<code><code>a</code></code>', '`` `a` ``');
        $this->assertHtmlGivesMarkdown('<p><code>a<li><code>b</code></li></code></p>', '`a<li>b</li>`');
        $this->assertHtmlGivesMarkdown('<div><code>a<code>b</code></code></div>', '<div><code>ab</code></div>');
        $this->assertHtmlGivesMarkdown('<div><code>a<code>&lt;b&gt;</code></code></div>', '<div><code>a&#60;b&#62;</code></div>');
    }

    public function testCodeSpanContainingBackticks(): void
    {
        $this->assertHtmlGivesMarkdown('<code>a` &lt;script&gt;alert(1)&lt;/script&gt; `b</code>', '``a` <script>alert(1)</script> `b``');
        $this->assertHtmlGivesMarkdown('<code>a` &lt;script&gt;alert(1)&lt;/script&gt; ``b</code>', '```a` <script>alert(1)</script> ``b```');
        $this->assertHtmlGivesMarkdown('<code>`a</code>', '`` `a ``');
        $this->assertHtmlGivesMarkdown('<code>a`</code>', '`` a` ``');
        $this->assertHtmlGivesMarkdown('<code>``</code>', '` `` `');
        $this->assertHtmlGivesMarkdown('<code> a </code>', '`  a  `');
        $this->assertHtmlGivesMarkdown('<code>  </code>', '`  `');
        $this->assertHtmlGivesMarkdown('<code>a<code>b</code></code>', '`` a`b` ``');
        $this->assertHtmlGivesMarkdown("<p>x <code>a` `\n```\n**b**</code> y</p>", 'x ``a` ````**b**`` y');
    }

    public function testCodeBlockContainingFences(): void
    {
        $script = '&lt;script&gt;alert(1)&lt;/script&gt;';

        $this->assertHtmlGivesMarkdown("<pre>a\n```\n" . $script . '</pre>', "````\na\n```\n<script>alert(1)</script>\n````");
        $this->assertHtmlGivesMarkdown("<pre>a\n   `````\n" . $script . '</pre>', "``````\na\n   `````\n<script>alert(1)</script>\n``````");
        $this->assertHtmlGivesMarkdown("<pre><code>a\n```\n" . $script . '</code></pre>', "````\na\n```\n<script>alert(1)</script>\n````");
        $this->assertHtmlGivesMarkdown("<pre><code class=\"language-php\">a\n```\n" . $script . '</code></pre>', "````php\na\n```\n<script>alert(1)</script>\n````");
    }

    public function testPreformatContainingBackticksIsAlwaysFenced(): void
    {
        $script = '&lt;script&gt;alert(1)&lt;/script&gt;';

        $this->assertHtmlGivesMarkdown('<pre>`a` ' . $script . ' `b`</pre>', "```\n`a` <script>alert(1)</script> `b`\n```");
        $this->assertHtmlGivesMarkdown("<pre>`a`\n\n" . $script . "\n\n`b`</pre>", "```\n`a`\n\n<script>alert(1)</script>\n\n`b`\n```");
        $this->assertHtmlGivesMarkdown("<pre>```\na\n```\n" . $script . "\n```\nb\n```</pre>", "````\n```\na\n```\n<script>alert(1)</script>\n```\nb\n```\n````");
        $this->assertHtmlGivesMarkdown('<pre><code>a</code>' . $script . '<code>b</code></pre>', "```\na<script>alert(1)</script>b\n```");
        $this->assertHtmlGivesMarkdown("<pre><code>a = 1</code>\n<code>b = 2</code></pre>", "```\na = 1\nb = 2\n```");
        $this->assertHtmlGivesMarkdown('<pre><span>1</span><code class="language-php">a</code></pre>', "```\n<span>1</span>a\n```");
        $this->assertHtmlGivesMarkdown('<pre>     <code>' . $script . '</code></pre>', "```\n<script>alert(1)</script>\n```");
    }

    public function testPreformatAlwaysStartsOnItsOwnLine(): void
    {
        $script = '&lt;script&gt;alert(1)&lt;/script&gt;';
        $fenced = "```\n<script>alert(1)</script>\n```";

        $this->assertHtmlGivesMarkdown('<span>x</span><pre>' . $script . '</pre>', "<span>x</span>\n\n" . $fenced);
        $this->assertHtmlGivesMarkdown('<span>x</span><pre>' . $script . '</pre>', "x\n\n" . $fenced, ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<span>x</span><pre><code>' . $script . '</code></pre>', "x\n\n" . $fenced, ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<ul><li>x<pre>' . $script . '</pre></li></ul>', "- x\n    \n    ```\n    <script>alert(1)</script>\n    ```");
        $this->assertHtmlGivesMarkdown('<div><pre>' . $script . '</pre></div>', "<div>\n\n" . $fenced . "\n\n</div>");
        $this->assertHtmlGivesMarkdown('<p>x</p><pre>' . $script . '</pre>', "x\n\n" . $fenced);
        $this->assertHtmlGivesMarkdown('<html><head><title>T</title></head><body><pre>' . $script . '</pre></body></html>', "T\n\n" . $fenced, ['strip_tags' => true]);
    }

    public function testBlocksAfterInlineContentStartOnTheirOwnLine(): void
    {
        $this->assertHtmlGivesMarkdown("<img src=\"a.png\" />\n<h3>A new header</h3>", "![](a.png)\n\n### A new header");
        $this->assertHtmlGivesMarkdown('<img src="a.png" /><p>Paragraph</p>', "![](a.png)\n\nParagraph");
        $this->assertHtmlGivesMarkdown('<a href="b.html"><img src="a.png" /></a><h3>A new header</h3>', "[![](a.png)](b.html)\n\n### A new header");
        $this->assertHtmlGivesMarkdown('<blockquote><img src="a.png" /><p>Paragraph</p></blockquote>', "> ![](a.png)\n> \n> Paragraph");
        $this->assertHtmlGivesMarkdown('<img src="a.png"><hr>', "![](a.png)\n\n---");
        $this->assertHtmlGivesMarkdown('<b>bold</b><p>P</p>', "**bold**\n\nP");
        $this->assertHtmlGivesMarkdown('<span>x</span><h1>H</h1>', "<span>x</span>\n\nH\n=");
        $this->assertHtmlGivesMarkdown('<img src="a.png"><!-- c --><p>P</p>', "![](a.png)\n\nP");
        $this->assertHtmlGivesMarkdown('<img src="a.png"><p></p><p>P</p>', "![](a.png)\n\nP");
        $this->assertHtmlGivesMarkdown('<ul><li>a<p>b</p></li><li>c</li></ul>', "- a\n    \n    b\n- c");
        $this->assertHtmlGivesMarkdown('<ol><li>item<h3>H</h3></li></ol>', "1. item\n    \n    ### H");
        $this->assertHtmlGivesMarkdown('<ol><li>a<hr></li></ol>', "1. a\n    \n    ---");
        $this->assertHtmlGivesMarkdown('<ul><li>a<ul><li>b</li></ul></li></ul>', "- a\n    - b");

        // Only one blank line where one was already put before the block
        $this->assertHtmlGivesMarkdown('<img src="a.png"><pre>code</pre>', "![](a.png)\n\n```\ncode\n```");
        $this->assertHtmlGivesMarkdown('<img src="a.png"><blockquote>q</blockquote>', "![](a.png)\n\n> q");
        $this->assertHtmlGivesMarkdown('a<br><h1>H</h1>', "a\n\nH\n=");

        // Markdown isn't parsed on the same line as raw HTML, and link text can't contain blocks
        $this->assertHtmlGivesMarkdown('<div>a<p>b</p></div>', "<div>ab\n\n</div>");
        $this->assertHtmlGivesMarkdown('<a href="u">a<p>b</p></a>', '[ab](u)');
    }

    public function testBackticksOutsideOfCodeCannotDelimitIt(): void
    {
        $this->assertHtmlGivesMarkdown('<p>` <code>&lt;script&gt;alert(1)&lt;/script&gt;</code></p>', '&#96; `<script>alert(1)</script>`');
        $this->assertHtmlGivesMarkdown('<div>\` and <code>a</code></div>', '\&#96; and `a`', ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<code>a</code><code>b</code>', '`a` `b`');
        $this->assertHtmlGivesMarkdown('<code>a</code><span><code>b</code></span>', '`a` `b`', ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<p>x<code></code> y <code>a</code></p>', 'x y `a`');
        $this->assertHtmlGivesMarkdown('<p><img src="x`" alt="`" title="`"> and <code>a</code></p>', '![&#96;](x%60 "&#96;") and `a`');
        $this->assertHtmlGivesMarkdown('<p><a href="x`" title="`">`</a> and <code>a</code></p>', '[&#96;](x%60 "&#96;") and `a`');
    }

    public function testCodeInsideOfRawHtmlStaysAsHtml(): void
    {
        $script  = '&lt;script&gt;alert(1)&lt;/script&gt;';
        $encoded = '<code>&#60;script&#62;alert&#40;1&#41;&#60;&#47;script&#62;</code>';

        $this->assertHtmlGivesMarkdown('<div><code>' . $script . '</code></div>', '<div>' . $encoded . '</div>');
        $this->assertHtmlGivesMarkdown('<div><p>Use <code>foo()</code> here</p></div>', "<div>Use <code>foo&#40;&#41;</code> here\n\n</div>");
        $this->assertHtmlGivesMarkdown('<table><tr><td><code>' . $script . '</code></td></tr></table>', '<table><tr><td>' . $encoded . '</td></tr></table>');
        $this->assertHtmlGivesMarkdown('<address>x</address><code>' . $script . '</code>', '<address>x</address>' . $encoded);
        $this->assertHtmlGivesMarkdown('<a id="x"><br><code>' . $script . '</code></a>', "<a id=\"x\">  \n" . $encoded . '</a>', ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<!-- ` --><p><code>' . $script . '</code></p>', '<!-- ` -->' . $encoded, ['preserve_comments' => true]);

        $this->assertHtmlGivesMarkdown('<body class="home"><p>Use <code>' . $script . '</code></p></body>', '<body class="home">Use ' . $encoded);
        $this->assertHtmlGivesMarkdown('<html lang="en"><body><p>Use <code>' . $script . '</code></p></body></html>', '<html lang="en"><body>Use ' . $encoded);
        $this->assertHtmlGivesMarkdown('<p>a</p><p><source src="x"><code>' . $script . '</code></p>', "a\n\n<source src=\"x\">" . $encoded . '</source>');

        // These blocks aren't ended by a blank line
        $this->assertHtmlGivesMarkdown("<p>a</p><style>b{}\n\nc{}</style><code>" . $script . '</code>', "a\n\n<style>b{}\n\nc{}</style>" . $encoded);
        $this->assertHtmlGivesMarkdown("<!-- a\n\nb --><p><code>" . $script . '</code></p>', '<!-- a b -->' . $encoded, ['preserve_comments' => true]);

        // Markdown is parsed again after a blank line
        $this->assertHtmlGivesMarkdown('<div><p>a</p><p>Use <code>foo()</code></p></div>', "<div>a\n\nUse `foo()`\n\n</div>");
        $this->assertHtmlGivesMarkdown('<div><p>a</p><div>Use <code>foo()</code></div></div>', "<div>a\n\n<div>Use <code>foo&#40;&#41;</code></div></div>");

        // Markdown is still parsed here
        $this->assertHtmlGivesMarkdown('<div><code>' . $script . '</code></div>', '`<script>alert(1)</script>`', ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<p><span>x</span> y <code>' . $script . '</code></p>', '<span>x</span> y `<script>alert(1)</script>`');
        $this->assertHtmlGivesMarkdown('<div><pre>' . $script . '</pre></div>', "<div>\n\n```\n<script>alert(1)</script>\n```\n\n</div>");
    }

    public function testAttributesOfRawHtmlStayEncoded(): void
    {
        $this->assertHtmlGivesMarkdown('<span title="&quot; onmouseover=&quot;alert(1)">x</span>', '<span title="&quot; onmouseover=&quot;alert(1)">x</span>');
        $this->assertHtmlGivesMarkdown('<a title="&quot; onmouseover=&quot;alert(1)">x</a>', '<a title="&quot; onmouseover=&quot;alert(1)">x</a>');
        $this->assertHtmlGivesMarkdown('<div title="&quot;`"><em>x</em> &amp; &lt;y&gt;</div>', '<div title="&quot;&#96;">*x* &amp; &lt;y&gt;</div>');
    }

    public function testLinkAndImageAttributesStayInsideOfTheirSyntax(): void
    {
        $this->assertHtmlGivesMarkdown('<a href="u" title="t&quot;) &lt;b&gt; [l](v) \">x</a>', '[x](u "t&quot;) &lt;b&gt; &#91;l&#93;(v) &#92;")');
        $this->assertHtmlGivesMarkdown('<a href="u" title="AT&amp;T &amp;lt; **b** _c_">x</a>', '[x](u "AT&amp;T &amp;lt; &#42;&#42;b&#42;&#42; &#95;c&#95;")');
        $this->assertHtmlGivesMarkdown('<a href="u) &lt;b&gt;">x</a>', '[x](u%29%20%3Cb%3E)');
        $this->assertHtmlGivesMarkdown('<a href="u)&quot;[l](v)\**b**">x</a>', '[x](u%29%22%5Bl%5D%28v%29%5C%2A%2Ab%2A%2A)');
        $this->assertHtmlGivesMarkdown('<a href="u v" title="t">x</a>', '[x](u%20v "t")');
        $this->assertHtmlGivesMarkdown('<a href="https://example.com/a_(b)?c=d&amp;e=f#g">x</a>', '[x](https://example.com/a_(b)?c=d&e=f#g)');
        $this->assertHtmlGivesMarkdown('<a href="((((a))))">x</a>', '[x](%28%28%28%28a%29%29%29%29)');
        $this->assertHtmlGivesMarkdown('<a href="http://[::1]/">http://[::1]/</a>', '<http://%5B::1%5D/>');
        $this->assertHtmlGivesMarkdown('<a href="mailto:!--@example.com">!--@example.com</a>', '[!--@example.com](mailto:!--@example.com)');
        $this->assertHtmlGivesMarkdown('<img src="u v)" alt="a](v) &lt;b&gt;" title="&quot;">', '![a&#93;(v) &lt;b&gt;](u%20v%29 "&quot;")');
        $this->assertHtmlGivesMarkdown('<img src="a_(b).png" alt="[a]">', '![&#91;a&#93;](a_(b).png)');
        $this->assertHtmlGivesMarkdown('<img alt="a" title="b c">', '![a](<> "b c")');
    }

    public function testExclamationMarkBeforeALinkDoesNotMakeItAnImage(): void
    {
        $this->assertHtmlGivesMarkdown('<p>Hello!<a href="u">x</a></p>', 'Hello\\![x](u)');
        $this->assertHtmlGivesMarkdown('<p>a\\!<a href="u">x</a></p>', 'a\\\\\\![x](u)');
        $this->assertHtmlGivesMarkdown('<p><em>Hello!</em><a href="u">x</a></p>', '*Hello\\!*[x](u)');
        $this->assertHtmlGivesMarkdown('<p>Hello! <a href="u">x</a> !</p>', 'Hello! [x](u) !');
    }

    public function testCodeDelimitersCannotBeEscaped(): void
    {
        $this->assertHtmlGivesMarkdown('<ul><li>a<br><code>- b</code></li></ul>', "- a  \n    `- b`");
        $this->assertHtmlGivesMarkdown('<ul><li>a<br>- b</li></ul>', "- a  \n    \\- b");
        $this->assertHtmlGivesMarkdown('<ul><li>a<br><span>- b</span></li></ul>', "- a  \n    \\- b", ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<ul><li>a<br><span><code>- b</code></span></li></ul>', "- a  \n    `- b`", ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<p>a\<br><code>b</code></p>', "a\\\\\n`b`", ['hard_break' => true]);
        $this->assertHtmlGivesMarkdown('<div>\<code>a</code> and <code>b</code></div>', '\ `a` and `b`', ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<p>\<code>a</code></p>', '\\\\`a`');
    }

    public function testConverterCanBeReusedAfterAFailedConversion(): void
    {
        $converter = new HtmlConverter();
        $converter->getEnvironment()->addConverter(new class implements ConverterInterface {
            public function convert(ElementInterface $element): string
            {
                throw new \RuntimeException('Failed');
            }

            /**
             * @return string[]
             */
            public function getSupportedTags(): array
            {
                return ['blink'];
            }
        });

        try {
            $converter->convert('<div><blink>x</blink></div>');
            $this->fail();
        } catch (\RuntimeException $exception) {
            $this->assertSame('Failed', $exception->getMessage());
        }

        $this->assertSame('Use `foo()`', $converter->convert('<p>Use <code>foo()</code></p>'));
    }

    public function testBlocksContainingCodeStartOnTheirOwnLine(): void
    {
        $script = '&lt;script&gt;alert(1)&lt;/script&gt;';
        $fenced = "```\n> <script>alert(1)</script>\n> ```";

        $this->assertHtmlGivesMarkdown('<span>x</span><blockquote><pre>' . $script . '</pre></blockquote>', "<span>x</span>\n\n> " . $fenced);
        $this->assertHtmlGivesMarkdown('<a href="u">x</a><blockquote><pre>' . $script . '</pre></blockquote>', "[x](u)\n\n> " . $fenced, ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<address>x</address><ul><li><code>' . $script . '</code></li></ul>', "<address>x</address>\n\n- `<script>alert(1)</script>`");
        $this->assertHtmlGivesMarkdown('<div><ul><li><h2></h2><h2></h2><code>' . $script . '</code></li></ul></div>', "<div>\n\n- `<script>alert(1)</script>`\n\n</div>");
        $this->assertHtmlGivesMarkdown('<ul><li><address>x</address><ul><li><code>a</code></li></ul></li></ul>', "- <address>x</address>\n    \n    \n    - `a`");

        $this->assertHtmlGivesMarkdown('<ul><li>a<br><pre>b</pre></li></ul>', "- a  \n    \n    ```\n    b\n    ```");
        $this->assertHtmlGivesMarkdown(
            '<table><caption><div>x</div></caption><tr><th>h</th></tr><tr><td><code>' . $script . '</code></td></tr></table>',
            "<div>x</div>\n\n| h |\n|---|\n| <code>&#60;script&#62;alert&#40;1&#41;&#60;&#47;script&#62;</code> |",
            [],
            [new TableConverter()]
        );

        // Nothing changes where they already did
        $this->assertHtmlGivesMarkdown('<ol><li>a</li><ol><li>b</li></ol></ol>', "1. a\n1. b");
        $this->assertHtmlGivesMarkdown('<p>x</p><ul><li>a</li></ul><blockquote>q</blockquote><pre>p</pre>', "x\n\n- a\n\n> q\n\n```\np\n```");
        $this->assertHtmlGivesMarkdown('<ul><li>a<ul><li>b <code>c</code></li></ul></li></ul>', "- a\n    - b `c`");
    }

    public function testPreformatWhichCannotBeFencedStaysAsHtml(): void
    {
        $script  = '&lt;script&gt;alert(1)&lt;/script&gt;';
        $encoded = '<pre>&#60;script&#62;alert&#40;1&#41;&#60;&#47;script&#62;</pre>';

        $this->assertHtmlGivesMarkdown('<em><pre>' . $script . '</pre></em>', '*' . $encoded . '*');
        $this->assertHtmlGivesMarkdown('<a href="u"><pre><code class="language-php">' . $script . '</code></pre></a>', '[' . $encoded . '](u)');
        $this->assertHtmlGivesMarkdown("<h2><pre>a\nb</pre></h2>", "<pre>a&#10;b</pre>\n------------------");
        $this->assertHtmlGivesMarkdown("<b><pre>\n\nfoo</pre></b>", '**<pre>&#10;&#10;foo</pre>**');
        $this->assertHtmlGivesMarkdown('<ul><li><ul><li><pre>' . $script . '</pre></li></ul></li></ul>', '- - ' . $encoded);
        $this->assertHtmlGivesMarkdown('<ul><li><ul><li>a</li><li><pre>' . $script . '</pre></li></ul></li></ul>', "- - a\n    - " . $encoded);
        $this->assertHtmlGivesMarkdown('<ul><li><div><ul><li><pre>' . $script . '</pre></li></ul></div></li></ul>', '- - ' . $encoded, ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown(
            '<ul><li><ul><li><details>a</details></li><li><code>' . $script . '</code></li></ul></li></ul>',
            "- - <details>a</details>\n    - <code>&#60;script&#62;alert&#40;1&#41;&#60;&#47;script&#62;</code>"
        );
        $this->assertHtmlGivesMarkdown('<menu><li><pre>' . $script . '</pre></li></menu>', "<menu>\n\n1. " . $encoded . "\n\n</menu>");
        $this->assertHtmlGivesMarkdown('<ul><li>a<ul><li><pre>a</pre></li></ul></li></ul>', "- a\n    - ```\n        a\n        ```");

        $this->assertHtmlGivesMarkdown(
            "<table><tr><th>h</th></tr><tr><td><pre>a\nb</pre> and <code>c|d</code></td></tr></table>",
            "| h |\n|---|\n| <pre>a&#10;b</pre> and <code>c&#124;d</code> |",
            [],
            [new TableConverter()]
        );
    }

    public function testCodeInOrdinaryContentIsNotKeptAsHtml(): void
    {
        $this->assertHtmlGivesMarkdown('<p>Wrap it in <code>&lt;?php</code> and <code>?&gt;</code>, then call <code>echo</code>.</p>', 'Wrap it in `<?php` and `?>`, then call `echo`.');
        $this->assertHtmlGivesMarkdown('<p>a<br><code>foo()</code></p>', "a  \n`foo()`");
        $this->assertHtmlGivesMarkdown('<p><span class="label">Note:</span> run <code>cmd</code></p>', '<span class="label">Note:</span> run `cmd`');
        $this->assertHtmlGivesMarkdown('<div><p>a <code>b</code></p></div>', 'a `b`', ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<table><tr><td><pre>a = 1</pre></td></tr></table>', "```\na = 1\n```", ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<html><head><title>T</title></head><body>Use <code>x()</code></body></html>', 'Use `x()`', ['remove_nodes' => 'head']);
        $this->assertStringContainsString('`c`', (new HtmlConverter())->convert('<p>a <code>c</code> <?php echo 1; ?> b</p>'));
        $this->assertHtmlGivesMarkdown(
            '<p>x</p><table><tr><th>h</th></tr><tr><td><em>s</em> and <code>c</code></td></tr></table>',
            "x\n\n| h |\n|---|\n| *s* and `c` |",
            [],
            [new TableConverter()]
        );
    }

    public function testCustomCodeConverterIsOverriddenInsideOfRawHtml(): void
    {
        $converter = new class implements ConverterInterface {
            public function convert(ElementInterface $element): string
            {
                return '`' . $element->getValue() . '`';
            }

            /**
             * @return string[]
             */
            public function getSupportedTags(): array
            {
                return ['code'];
            }
        };

        $this->assertHtmlGivesMarkdown('<p><code>&lt;b&gt;</code></p>', '`<b>`', [], [$converter]);
        $this->assertHtmlGivesMarkdown('<div><code>&lt;b&gt;</code></div>', '<div><code>&#60;b&#62;</code></div>', [], [$converter]);
        $this->assertHtmlGivesMarkdown('<div>a<br><code>&lt;b&gt;</code></div>', "<div>a  \n<code>&#60;b&#62;</code></div>", [], [$converter]);
        $this->assertHtmlGivesMarkdown('<div><span>a</span> and <code>&lt;b&gt;</code></div>', '<div><span>a</span> and <code>&#60;b&#62;</code></div>', [], [$converter]);
    }

    public function testCodeSpanCannotBeSplitIntoTableCells(): void
    {
        $this->assertHtmlGivesMarkdown('<p><code>a|**b**</code><br>-|-</p>', "`a|**b**`  \n-\\|-");
        $this->assertHtmlGivesMarkdown('<div><code>a|**b**</code><br>-|-</div>', "`a|**b**`  \n-&#124;-", ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<p>a | b \\| c</p>', 'a \\| b \\\\\\| c');
        $this->assertHtmlGivesMarkdown(
            '<table><tr><th>a | b \\| c</th></tr><tr><td><code>d|e</code></td></tr></table>',
            "| a \\| b \\\\\\| c |\n|---|\n| `d\\|e` |",
            [],
            [new TableConverter()]
        );
    }

    public function testCodeBlockStaysInsideOfItsListItem(): void
    {
        $this->assertHtmlGivesMarkdown("<ul><li><pre><code>a\r**b**</code></pre></li></ul>", "- ```\n    a\n    **b**\n    ```");
        $this->assertHtmlGivesMarkdown('<ol start="1000000000"><li><pre>a</pre></li></ol>', "1. ```\n    a\n    ```");
        $this->assertHtmlGivesMarkdown('<ol start="999999999"><li>a</li></ol>', '999999999. a');
        $this->assertHtmlGivesMarkdown('<ol start="-5"><li>a</li></ol>', '1. a');
        $this->assertHtmlGivesMarkdown('<ol start="100"><li>a<pre>b</pre></li></ol>', "100. a\n     \n     ```\n     b\n     ```");
        $this->assertHtmlGivesMarkdown('<ol start="99"><li>a<pre>b</pre></li></ol>', "99. a\n    \n    ```\n    b\n    ```");

        // A list directly inside of another has to start on its own line
        $this->assertHtmlGivesMarkdown('<ul><li>y</li>t<ol><li><pre>a</pre></li></ol></ul>', "- y\nt\n\n1. ```\n    a\n    ```");
    }

    public function testLineBreaksInAttributesDoNotStartNewLines(): void
    {
        $this->assertHtmlGivesMarkdown('<p>a <img src="i&#10;.png" alt="b&#10;c" title="d&#13;&#10;e"></p>', 'a ![b c](i%0A.png "d e")');
        $this->assertHtmlGivesMarkdown('<p><a href="u" title="d&#10;e">t</a></p>', '[t](u "d e")');
    }

    public function testCodeInATableContainingHtmlStaysAsHtml(): void
    {
        $converters = [new TableConverter()];

        $this->assertHtmlGivesMarkdown(
            '<table><tr><th>h</th></tr><tr><td><span>s</span> and <code>c</code></td></tr></table>',
            "| h |\n|---|\n| <span>s</span> and <code>c</code> |",
            [],
            $converters
        );
        $this->assertHtmlGivesMarkdown(
            '<table><tr><th>h</th></tr><tr><td><span>s</span> and <code>c</code></td></tr></table>',
            "| h |\n|---|\n| s and `c` |",
            ['strip_tags' => true],
            $converters
        );
        $this->assertHtmlGivesMarkdown(
            '<table><!-- a --><tr><th>h</th></tr><tr><td><code>c</code></td></tr></table>',
            "| h |\n|---|\n| <code>c</code> |",
            [],
            $converters
        );
        $this->assertHtmlGivesMarkdown('<blockquote><td><code>c</code></td></blockquote>', '> | <code>c</code>', [], $converters);

        // With any part of a table out of place, nothing in the document is left as Markdown
        $this->assertHtmlGivesMarkdown(
            '<p><code>a</code></p><pre>b</pre><blockquote><td>c</td></blockquote>',
            "<code>a</code>\n\n<pre>b</pre>\n\n> | c",
            [],
            [new TableConverter()]
        );
        $this->assertHtmlGivesMarkdown('<p><code>a</code></p><blockquote><td>c</td></blockquote>', "`a`\n\n> <td>c</td>");
        $this->assertHtmlGivesMarkdown(
            '<p><code>a</code></p><table><!-- x --><tr><td>c</td></tr></table>',
            "<code>a</code>\n\n<!-- x -->| c |\n|---|",
            ['preserve_comments' => true],
            [new TableConverter()]
        );
        $this->assertHtmlGivesMarkdown(
            '<blockquote>a<tr><td>b</td></tr><code>c|d</code></blockquote>',
            "> a\n> \n> | b |\n> |---|\n> \n> <code>c&#124;d</code>",
            [],
            [new TableConverter()]
        );
    }

    public function testCodeLanguageCannotEndTheInfoString(): void
    {
        $this->assertHtmlGivesMarkdown("<pre><code class=\"language-x\n```\n**b**\">a</code></pre>", "```x\na\n```");
        $this->assertHtmlGivesMarkdown("<pre><code class=\"foo\tlanguage-php\">a</code></pre>", "```php\na\n```");
        $this->assertHtmlGivesMarkdown('<pre><code class="language-a`b">a</code></pre>', "```ab\na\n```");
    }

    public function testBlockquotes(): void
    {
        $this->assertHtmlGivesMarkdown('<blockquote>Something I said?</blockquote>', '> Something I said?');
        $this->assertHtmlGivesMarkdown('<blockquote><blockquote>Something I said?</blockquote></blockquote>', '> > Something I said?');
        $this->assertHtmlGivesMarkdown('<blockquote><p>Something I said?</p><p>Why, yes it was!</p></blockquote>', "> Something I said?\n> \n> Why, yes it was!");
    }

    public function testMalformedHtml(): void
    {
        $this->assertHtmlGivesMarkdown('<code><p>Some sample HTML</p></code>', '`<p>Some sample HTML</p>`'); // Invalid HTML, but should still work
        $this->assertHtmlGivesMarkdown('<strong><em>Strong italic</strong> Regular text', '***Strong italic*** Regular text'); // Missing closing </em>
    }

    public function testLibxmlInternalErrorsIsRestoredAfterConvert(): void
    {
        $previousState = \libxml_use_internal_errors(false);

        try {
            $converter = new HtmlConverter();
            $converter->convert('<p>Hello</p>');

            $this->assertFalse(\libxml_use_internal_errors());
        } finally {
            \libxml_use_internal_errors($previousState);
        }
    }

    public function testLibxmlInternalErrorsIsKeptEnabledAfterConvert(): void
    {
        $previousState = \libxml_use_internal_errors(true);

        try {
            $converter = new HtmlConverter();
            $converter->convert('<p>Unclosed <b>tags</p>');

            $this->assertTrue(\libxml_use_internal_errors());
            $this->assertSame([], \libxml_get_errors());
        } finally {
            \libxml_use_internal_errors($previousState);
        }
    }

    public function testLibxmlInternalErrorsIsUnchangedWhenSuppressErrorsDisabled(): void
    {
        $previousState = \libxml_use_internal_errors(true);

        try {
            $converter = new HtmlConverter(['suppress_errors' => false]);
            $converter->convert('<p>Hello</p>');

            $this->assertTrue(\libxml_use_internal_errors());
        } finally {
            \libxml_use_internal_errors($previousState);
        }
    }

    public function testHtml5TagsArePreserved(): void
    {
        $this->assertHtmlGivesMarkdown('<article>Some stuff</article>', '<article>Some stuff</article>');
    }

    public function testStripUnmarkdownable(): void
    {
        $this->assertHtmlGivesMarkdown('<span>Span</span>', 'Span', ['strip_tags' => true]);
    }

    public function testStripComments(): void
    {
        $this->assertHtmlGivesMarkdown('<p>Test</p><!-- Test comment -->', 'Test');
        $this->assertHtmlGivesMarkdown('<!-- Test comment --><p>Test</p>', 'Test');
        $this->assertHtmlGivesMarkdown('<p>Test</p><!-- Test comment -->', 'Test', ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<!-- Test comment --><p>Test</p>', 'Test', ['strip_tags' => true]);
    }

    public function testPreserveComments(): void
    {
        $this->assertHtmlGivesMarkdown('<p>Test</p><!-- more -->', "Test\n\n<!-- more -->", ['preserve_comments' => ['more']]);
        $this->assertHtmlGivesMarkdown('<p>Test</p><!-- Test comment --><!-- more -->', "Test\n\n<!-- more -->", ['preserve_comments' => ['more']]);
        $this->assertHtmlGivesMarkdown('<!-- Test comment --><p>Test</p><!-- Test comment -->', "<!-- Test comment -->Test\n\n<!-- Test comment -->", ['preserve_comments' => true]);
    }

    public function testCommentsWhichBrowsersWouldEndEarlyAreNotPreserved(): void
    {
        $this->assertHtmlGivesMarkdown('<p>a<!-- b <!-- c --></p>', 'a', ['preserve_comments' => true]);
        $this->assertHtmlGivesMarkdown('<p>a<!-- b <!---></p>', 'a', ['preserve_comments' => true]);
        $this->assertHtmlGivesMarkdown('<p>a<!-- b- --></p>', 'a<!-- b- -->', ['preserve_comments' => true]);
        $this->assertHtmlGivesMarkdown('<p>a<!-- b --->c</p>', 'ac', ['preserve_comments' => true]);
        $this->assertHtmlGivesMarkdown('<p>a<!-- -- b --></p>', 'a', ['preserve_comments' => true]);
        $this->assertHtmlGivesMarkdown('<div>a\\<!-- b --></div>', 'a\\ <!-- b -->', ['preserve_comments' => true, 'strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<p><code>a</code><!-- c -->b</p>', '`a`<!-- c -->b', ['preserve_comments' => true]);

        // Before, the paragraph would have ended in the middle of it, leaving the rest as HTML of its own
        $this->assertHtmlGivesMarkdown('<p>a<!-- <img src="x"> --></p>', 'a<!-- <img src="x"> -->', ['preserve_comments' => true, 'strip_tags' => true]);
        $this->assertHtmlGivesMarkdown("<p>a<!--\n\n<img src=\"x\">\n\n--></p>", 'a<!-- <img src="x"> -->', ['preserve_comments' => true]);
        $this->assertHtmlGivesMarkdown("<h3><!--\r<img src=\"x\">\r--></h3>", '### <!-- <img src="x"> -->', ['preserve_comments' => true]);
        $this->assertHtmlGivesMarkdown("<ul><li>a<!-- b\n  c --></li></ul>", '- a<!-- b c -->', ['preserve_comments' => true]);
        $this->assertHtmlGivesMarkdown("<p>a<!-- b \t c --></p>", "a<!-- b \t c -->", ['preserve_comments' => true]);

        // Which of these older versions of libxml end the comment at as well differs
        foreach (['<!-->', '<!--->', '<!-- --!>'] as $start) {
            foreach ([['preserve_comments' => true], ['preserve_comments' => true, 'strip_tags' => true]] as $options) {
                $markdown = (new HtmlConverter($options))->convert('<p>a' . $start . '<img src="x"> --></p>');
                $this->assertStringNotContainsString($start, $markdown);
                $this->assertStringNotContainsString('--!>', $markdown);
            }
        }
    }

    public function testPreserveCommentOrder(): void
    {
        $this->assertHtmlGivesMarkdown('<!-- 1 --><!-- 2 --><p>Test</p><!-- 3 -->', "<!-- 1 --><!-- 2 -->Test\n\n<!-- 3 -->", ['preserve_comments' => true]);
    }

    public function testPreserveWhitespace(): void
    {
        $this->assertHtmlGivesMarkdown('<a href="google.com">google.com</a> <code>test</code>', '[google.com](google.com) `test`');
    }

    public function testDeleteBlankP(): void
    {
        $this->assertHtmlGivesMarkdown('<p></p>', '');
        $this->assertHtmlGivesMarkdown('<p></p>', '', ['strip_tags' => true]);
    }

    public function testDivs(): void
    {
        $this->assertHtmlGivesMarkdown('<div>Hello</div><div>World</div>', '<div>Hello</div><div>World</div>');
        $this->assertHtmlGivesMarkdown('<div>Hello</div><div>World</div>', "Hello\n\nWorld", ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown("<div>Hello</div>\n<div>World</div>", "Hello\n\nWorld", ['strip_tags' => true]);
        $this->assertHtmlGivesMarkdown('<p>Paragraph</p><div>Hello</div><div>World</div>', "Paragraph\n\nHello\n\nWorld", ['strip_tags' => true]);
    }

    public function testRemoveNodes(): void
    {
        $this->assertHtmlGivesMarkdown('<div>Hello</div><div>World</div>', '', ['remove_nodes' => 'div']);
        $this->assertHtmlGivesMarkdown('<p>Hello</p><span>World</span>', '', ['remove_nodes' => 'p span']);
    }

    public function testHtmlEntities(): void
    {
        $this->assertHtmlGivesMarkdown('<p>&amp;euro;</p>', '&amp;euro;');
        $this->assertHtmlGivesMarkdown('<code>&lt;p&gt;Some sample HTML&lt;/p&gt;</code>', '`<p>Some sample HTML</p>`');
    }

    public function testNonBreakingSpaces(): void
    {
        $this->assertHtmlGivesMarkdown('<p>This is a test and&nbsp;test</p>', "This is a test and\u{00A0}test");
        $this->assertHtmlGivesMarkdown("<p>12,50\u{00A0}\u{00A0}€ \n total</p>", "12,50\u{00A0}\u{00A0}€ total");
        $this->assertHtmlGivesMarkdown('<p>12&#x202F;€ and 1&#x2007;000</p>', "12\u{202F}€ and 1\u{2007}000");
        $this->assertHtmlGivesMarkdown('<p><a href="u">a&nbsp;b</a> <span>c</span>&nbsp;<span>d</span></p>', "[a\u{00A0}b](u) <span>c</span>\u{00A0}<span>d</span>");
        $this->assertHtmlGivesMarkdown('<ul><li>a&nbsp;b</li></ul>', "- a\u{00A0}b");
        $this->assertHtmlGivesMarkdown('<p>foo&nbsp;<br>bar</p>', "foo\u{00A0}  \nbar");
        $this->assertHtmlGivesMarkdown('<p>&nbsp;- not a list</p>', "\u{00A0}- not a list");
        $this->assertHtmlGivesMarkdown('<blockquote><p>&nbsp;- not a list</p></blockquote>', "> \u{00A0}- not a list");
        $this->assertHtmlGivesMarkdown('<ul><li><p>&nbsp;- not a list</p></li><li>&nbsp;- nor this</li></ul>', "- \u{00A0}- not a list\n- \u{00A0}- nor this");
        $this->assertHtmlGivesMarkdown('<table><caption>&nbsp;- not a list</caption><tr><td>a</td></tr></table>', "\u{00A0}- not a list\n\n| a |\n|---|", [], [new TableConverter()]);
        $this->assertHtmlGivesMarkdown('<p>120.&nbsp;</p><p>-&nbsp;</p><p>a<br>+&nbsp;</p>', "120\\.\n\n\\-\n\na  \n\\+");

        // Emphasis can't be delimited next to them, so they're kept outside of it
        $this->assertHtmlGivesMarkdown('<p><strong>Total:&nbsp;</strong>12,50&nbsp;€</p>', "**Total:**\u{00A0}12,50\u{00A0}€");
        $this->assertHtmlGivesMarkdown('<p>a<b>&nbsp;x</b>b</p>', "a\u{00A0}**x**b");
        $this->assertHtmlGivesMarkdown('<p>a<b>b&nbsp;</b><b>c</b>d</p>', "a**b\u{00A0}c**d");
        $this->assertHtmlGivesMarkdown('<p>a<em>&nbsp;</em>b</p>', "a\u{00A0}b");
        $this->assertHtmlGivesMarkdown('<p>a<em>&nbsp; b</em>c<em>d &nbsp;</em>e</p>', "a\u{00A0} *b*c*d* \u{00A0}e");

        // On their own, or at the end of a block, they're whitespace like any other
        $this->assertHtmlGivesMarkdown('<p>a</p><p>&nbsp;</p><p>b</p>', "a\n\nb");
        $this->assertHtmlGivesMarkdown("<p>a</p>&nbsp;<p>b</p>\n&nbsp;\n<p>c</p>", "a\n\nb\n\nc");
        $this->assertHtmlGivesMarkdown('<p>a</p>&nbsp;<pre>b</pre> <table><tr><td>c</td></tr></table>', "a\n\n```\nb\n```\n\n<table><tr><td>c</td></tr></table>");
        $this->assertHtmlGivesMarkdown('<blockquote><p>&nbsp;</p><p>q&nbsp;</p></blockquote>', '> q');
        $this->assertHtmlGivesMarkdown('<p>line&nbsp;</p><p><b>bold&nbsp;</b></p>', "line\n\n**bold**");
        $this->assertHtmlGivesMarkdown('<ul><li>&nbsp;</li><li>a&nbsp;</li></ul>', "- \n- a");
        $this->assertHtmlGivesMarkdown('<table><tr><th>&nbsp;a&nbsp;</th></tr><tr><td>&nbsp;</td></tr></table>', "| a |\n|---|\n|  |", [], [new TableConverter()]);
    }

    public function testSetOption(): void
    {
        $markdown = new HtmlConverter();
        $markdown->getConfig()->setOption('strip_tags', true);
        $result = $markdown->convert('<span>Strip</span>');

        $this->assertEquals('Strip', $result);
    }

    public function testInvoke(): void
    {
        $markdown = new HtmlConverter();
        $markdown->getConfig()->setOption('strip_tags', true);
        $result = $markdown('<span>Strip</span>');

        $this->assertEquals('Strip', $result);
    }

    public function testSanitization(): void
    {
        $html     = '<pre><code>&lt;script type = "text/javascript"&gt; function startTimer() { var tim = window.setTimeout("hideMessage()", 5000) } &lt;/head&gt; &lt;body&gt;</code></pre>';
        $markdown = '```' . "\n" . '<script type = "text/javascript"> function startTimer() { var tim = window.setTimeout("hideMessage()", 5000) } </head> <body>' . "\n```";
        $this->assertHtmlGivesMarkdown($html, $markdown);
        $this->assertHtmlGivesMarkdown('<p>&gt; &gt; Look at me! &lt; &lt;</p>', '&gt; &gt; Look at me! &lt; &lt;');
        $this->assertHtmlGivesMarkdown('<p>&gt; &gt; <b>Look</b> at me! &lt; &lt;<br />&gt; Just look at me!</p>', "&gt; &gt; **Look** at me! &lt; &lt;  \n&gt; Just look at me!");
        $this->assertHtmlGivesMarkdown('<p>Foo<br>--<br>Bar<br>Foo--</p>', "Foo  \n\\--  \nBar  \nFoo--");
        $this->assertHtmlGivesMarkdown('<p>Foo<br>=<br>Bar<br>===<br>a = b<br>= =<br>=<em>x</em></p>', "Foo  \n\\=  \nBar  \n\\===  \na = b  \n= =  \n=*x*");
        $this->assertHtmlGivesMarkdown('<p>Foo<br>   ===&nbsp;</p>', "Foo  \n \\===");
        $this->assertHtmlGivesMarkdown('<p>Foo<br>=<br>Bar</p>', "Foo\n\\=\nBar", ['hard_break' => true]);
        $this->assertHtmlGivesMarkdown('<ul><li>Foo<br>=</li><li>Bar<br>---</li></ul>', "- Foo  \n    \\=\n- Bar  \n    \\---");
        $this->assertHtmlGivesMarkdown('<blockquote>Foo<br>=<br>Bar<br>-</blockquote>', "> Foo  \n> \\=  \n> Bar  \n> \\-");
        $this->assertHtmlGivesMarkdown('<h1>Foo<br>=</h1>', "Foo  \n\\=\n========");
        $this->assertHtmlGivesMarkdown('<ul><li>Foo<br>=<pre>x</pre></li></ul>', "- Foo  \n    \\=\n    \n    ```\n    x\n    ```");
        $this->assertHtmlGivesMarkdown("<p>Foo<br>---\t</p>", "Foo  \n\\---");
        $this->assertHtmlGivesMarkdown('<div>Thanks<br>--<br>Colin</div>', "<div>Thanks  \n--  \nColin</div>");
        $this->assertHtmlGivesMarkdown('<address>Thanks<br>=<br>Colin</address>', "<address>Thanks  \n=  \nColin</address>");
        $this->assertHtmlGivesMarkdown('<p><u>Foo<br>Bar</u><br>=</p>', "<u>Foo  \nBar</u>  \n\\=");
        $this->assertHtmlGivesMarkdown('<ul><li>Foo<br>- </li></ul>', "- Foo  \n    \\-");
        $this->assertHtmlGivesMarkdown('<ul><li>Foo<br>- Bar</li></ul>', "- Foo  \n    \\- Bar");
        $this->assertHtmlGivesMarkdown('Foo<br />* Bar', "Foo  \n\\* Bar");
        $this->assertHtmlGivesMarkdown("<p>123456789) Foo and 1234567890) Bar!</p>\n<p>1. Platz in 'Das große Backen'</p>", "123456789\\) Foo and 1234567890) Bar!\n\n1\\. Platz in 'Das große Backen'");
        $this->assertHtmlGivesMarkdown("<p>\n+ Siri works well for TV and movies<br>\n- No 4K support\n</p>", "\+ Siri works well for TV and movies  \n\- No 4K support");
        $this->assertHtmlGivesMarkdown('<p>You forgot the &lt;!--more--&gt; tag!</p>', 'You forgot the &lt;!--more--&gt; tag!');
    }

    public function testInstatiationWithEnvironment(): void
    {
        $markdown = new HtmlConverter(new Environment([]));

        $htmlH3 = '<h3>Test</h3>';
        $result = $markdown->convert($htmlH3);
        $this->assertEquals($htmlH3, $result);

        $htmlH4 = '<h4>Test</h4>';
        $result = $markdown->convert($htmlH4);
        $this->assertEquals($htmlH4, $result);
    }
}
