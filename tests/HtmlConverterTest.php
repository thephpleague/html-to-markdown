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
        $removeNodesConfiguration = array('remove_nodes' => 'style');

        $whiteTagsData = array(
            'This is a WhiteTag <a href="http://www.google.com" target="_blank">Link</a> `This is code html <a href="http://www.google.com" target="_blank">Link2</a>`',
            'This is a WhiteTag <a href="http://www.google.com" target="_blank">Link</a> `This is code html <a href="http://www.google.com" target="_blank">Link2</a>`',
            array('white_tags' => array('a')),
        );

        return array(
            'empty_input'     => array('     ', ''),
            'plain_text'      => array('<p>\\*test\\*</p>', '\\\\\\*test\\\\\\*'), // \\\*test\\\*
            'line_breaks'     => array('<p>test<br />another line</p>', "test  \nanother line"),
            'headers'         => array('<h6>Test</h6>', '###### Test'),
            'spans'           => array('<em>This is </em><strong>a </strong>test', '_This is_ **a** test'),
            'nesting'         => array('<span><span>Test</span></span>', '<span><span>Test</span></span>'),
            'script'          => array("<script>alert('test');</script>", "<script>alert('test');</script>"),
            'image'           => array('<img src="/path/img.jpg" alt="alt text" title="Title" />', '![alt text](/path/img.jpg "Title")'),
            'anchor'          => array('<a href="#nerd">Test</a>', '[Test](#nerd)'),
            'horizontal_rule' => array('<hr>', '- - - - - -'),
            'lists'           => array('<ol><li>   Item A</li><li>   Item B</li></ol>', "1. Item A\n2. Item B"),
            'nested_lists'    => array('<ol><li>Item A<ul><li>Nested A</li></ul></li><li>Item B</li></ol>', "1. Item A\n  - Nested A\n2. Item B"),
            'code_samples'    => array("<p><code>#sidebar h1 {\n    font-size: 1.5em;\n    font-weight: bold;\n}\n</code></p>", "    #sidebar h1 {\n        font-size: 1.5em;\n        font-weight: bold;\n    }"),
            'pre_format'      => array('<pre>  one line with spaces  </pre>', '    ' . '  one line with spaces  '),
            'blockquotes'     => array('<blockquote><p>Something I said?</p><p>Why, yes it was!</p></blockquote>', "> Something I said?\n> \n> Why, yes it was!"),
            'malformed_html'  => array('<strong><em>Strong italic</strong> Regular text', '**_Strong italic_** Regular text'),
            'html5_tags'      => array('<article>Some stuff</article>', '<article>Some stuff</article>'),
            'unmarkdownable'  => array('<span>Span</span>', 'Span', array('strip_tags' => true)),
            'strip_comments'  => array('<p>Test</p><!-- Test comment -->', 'Test'),
            'delete_blank_p'  => array('<p></p>', ''),
            'divs'            => array('<p>Paragraph</p><div>Hello</div><div>World</div>', "Paragraph\n\nHello\n\nWorld", array('strip_tags' => true)),
            'remove_nodes'    => array('<style>foo</style>', '', $removeNodesConfiguration),
            'htmlentities'    => array('<code>&lt;p&gt;Some sample HTML&lt;/p&gt;</code>', '`<p>Some sample HTML</p>`'),
            'with_white_tags' => $whiteTagsData,
        );
    }

    /**
     * @param string $html
     * @param string $expected
     * @param array  $options
     *
     * @dataProvider htmlDataProvider
     */
    public function testHtmlConverter($html = '', $expected = '', $options = array())
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
