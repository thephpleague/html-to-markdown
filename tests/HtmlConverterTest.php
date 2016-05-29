<?php

namespace League\HTMLToMarkdown\Test;

use League\HTMLToMarkdown\HtmlConverter;

class HtmlConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function htmlDataProvider()
    {
        $removeNodesConfiguration = [
            'remove_nodes' => 'style',
        ];

        $whiteTagsData = [
            'This is a WhiteTag <a href="http://www.google.com" target="_blank">Link</a> `This is code html <a href="http://www.google.com" target="_blank">Link2</a>`',
            'This is a WhiteTag <a href="http://www.google.com" target="_blank">Link</a> `This is code html <a href="http://www.google.com" target="_blank">Link2</a>`',
            ['white_tags' => ['a']],
        ];

        return [
            'empty_input' => ['     ', ''],
            'plain_text' => ['<p>\\*test\\*</p>', '\\\\\\*test\\\\\\*'], // \\\*test\\\*
            'line_breaks' => ['<p>test<br />another line</p>', "test  \nanother line"],
            'headers' => ['<h6>Test</h6>', '###### Test'],
            'spans' => ['<em>This is </em><strong>a </strong>test', '_This is_ **a** test'],
            'nesting' => ['<span><span>Test</span></span>', '<span><span>Test</span></span>'],
            'script' => ["<script>alert('test');</script>", "<script>alert('test');</script>"],
            'image' => ['<img src="/path/img.jpg" alt="alt text" title="Title" />', '![alt text](/path/img.jpg "Title")'],
            'anchor' => ['<a href="#nerd">Test</a>', '[Test](#nerd)'],
            'horizontal_rule' => ['<hr>', '- - - - - -'],
            'lists' => ['<ol><li>   Item A</li><li>   Item B</li></ol>', "1. Item A\n2. Item B"],
            'nested_lists' => ['<ol><li>Item A<ul><li>Nested A</li></ul></li><li>Item B</li></ol>', "1. Item A\n  - Nested A\n2. Item B"],
            'code_samples' => ["<p><code>#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n</code></p>", "    #sidebar h1 {\n        font-size: 1.5em;\n        font-weight: bold;\n    }"],
            'pre_format' => ['<pre>  one line with spaces  </pre>', '    ' . '  one line with spaces  '],
            'blockquotes' => ['<blockquote><p>Something I said?</p><p>Why, yes it was!</p></blockquote>', "> Something I said?\n> \n> Why, yes it was!"],
            'malformed_html' => ['<strong><em>Strong italic</strong> Regular text', '**_Strong italic_** Regular text'],
            'html5_tags' => ['<article>Some stuff</article>', '<article>Some stuff</article>'],
            'unmarkdownable' => ['<span>Span</span>', 'Span', ['strip_tags' => true]],
            'strip_comments' => ['<p>Test</p><!-- Test comment -->', 'Test'],
            'delete_blank_p' => ['<p></p>', ''],
            'divs' => ['<p>Paragraph</p><div>Hello</div><div>World</div>', "Paragraph\n\nHello\n\nWorld", ['strip_tags' => true]],
            'remove_nodes' => ['<style>foo</style>', '', $removeNodesConfiguration],
            'htmlentities' => ['<code>&lt;p&gt;Some sample HTML&lt;/p&gt;</code>', '`<p>Some sample HTML</p>`'],
            'with_white_tags' => $whiteTagsData,
        ];
    }

    /**
     * @param string $html
     * @param string $expected
     * @param array  $options
     *
     * @dataProvider htmlDataProvider
     */
    public function testHtmlConverter($html = '', $expected = '', $options = [])
    {
        $htmlConverter = new HtmlConverter($options);
        $actual = $htmlConverter->convert($html);

        $this->assertEquals($expected, $actual);
    }

    public function testInvokeHtmlConverter()
    {
        $htmlConverter = new HtmlConverter();
        $htmlConverter->getConfig()->setOption('strip_tags', true);

        $actual = $htmlConverter('<span>Strip</span>');

        $this->assertEquals('Strip', $actual);
    }

    public function testGetEnvironment()
    {
        $htmlConverter = new HtmlConverter();
        $actual = $htmlConverter->getEnvironment();

        $this->assertInstanceOf('League\HTMLToMarkdown\Environment', $actual);
    }
}
