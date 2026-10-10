<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Test;

use League\HTMLToMarkdown\Element;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
    public function testSiblingPositionIgnoresWhitespaceInAnyOrderOfAsking(): void
    {
        $document = new \DOMDocument();
        $document->loadXML('<ol> <li>a</li> x <li>b</li><!-- c --><li>c</li> </ol>');

        $list = $document->documentElement;
        \assert($list !== null);
        $children = (new Element($list))->getChildren();
        $this->assertCount(7, $children);

        $expected = [0, 1, 2, 3, 4, 5, 5];
        foreach ([6, 3, 6, 0, 1, 5, 2, 4, 0, 6] as $index) {
            $this->assertSame($expected[$index], $children[$index]->getSiblingPosition());
        }

        // An earlier sibling which becomes empty no longer counts
        $children[1]->setFinalMarkdown('');
        $this->assertFalse($children[1]->hasParent());
        $this->assertSame(1, $children[2]->getSiblingPosition());
        $this->assertSame(2, $children[3]->getSiblingPosition());
        $this->assertSame(4, $children[6]->getSiblingPosition());

        $children[3]->setFinalMarkdown('');
        $this->assertSame(2, $children[4]->getSiblingPosition());
        $this->assertSame(3, $children[6]->getSiblingPosition());
    }

    public function testSiblingPositionWithoutAParent(): void
    {
        $this->assertSame(0, (new Element(new \DOMDocument()))->getSiblingPosition());
    }

    public function testChildrenAsStringIsCanonicalHtml(): void
    {
        $document = new \DOMDocument();
        $document->loadHTML('<html><body><p title="a&quot;b" id=x class="y">a<br>&amp; <i>b</i><!-- c --></p> text</body></html>');

        $paragraph = $document->getElementsByTagName('p')->item(0);
        \assert($paragraph !== null);
        $text    = $paragraph->nextSibling;
        $comment = $paragraph->lastChild;
        \assert($text !== null);
        \assert($comment !== null);

        $this->assertSame('<p class="y" id="x" title="a&quot;b">a<br></br>&amp; <i>b</i></p>', (new Element($paragraph))->getChildrenAsString());
        $this->assertSame(' text', (new Element($text))->getChildrenAsString());
        $this->assertSame('', (new Element($comment))->getChildrenAsString());
    }
}
