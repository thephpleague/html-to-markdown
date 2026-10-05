<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Test;

use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\RawHtml;
use PHPUnit\Framework\TestCase;

final class RawHtmlTest extends TestCase
{
    public function testFromElementKeepsAttributesEncoded(): void
    {
        $element = $this->createElement('span', '<span title="&quot; onmouseover=&quot;alert(1)">x</span>', 'x');

        $this->assertSame('<span title="&quot; onmouseover=&quot;alert(1)">x</span>', RawHtml::fromElement($element));
    }

    public function testFromElementKeepsAttributesEncodedHoweverManyThereAre(): void
    {
        $attributes = \str_repeat(' a="&quot;>"', 200000);
        $element    = $this->createElement('span', '<span' . $attributes . '>x</span>', 'x');

        $this->assertSame('<span' . $attributes . '>x</span>', RawHtml::fromElement($element));
    }

    public function testFromElementGoesByTheSerializedTagName(): void
    {
        $element = $this->createElement('st1:place', '<place title="&quot;>">x</place>', 'x');

        $this->assertSame('<place title="&quot;>">x</place>', RawHtml::fromElement($element));
    }

    public function testFromElementDropsAttributesItCannotMakeOut(): void
    {
        $element = $this->createElement('span', '<span title=&quot;>x</span>', 'x');

        $this->assertSame('<span>x</span>', RawHtml::fromElement($element));
    }

    public function testFromElementDecodesNodesWhichAreNotElements(): void
    {
        $this->assertSame('a < b', RawHtml::fromElement($this->createElement('#cdata-section', 'a &lt; b', 'a < b')));
        $this->assertSame('<?php echo 1; ?>', RawHtml::fromElement($this->createElement('php', '<?php echo 1; ?>', 'echo 1; ')));
    }

    private function createElement(string $tag, string $html, string $value): ElementInterface
    {
        $element = $this->createStub(ElementInterface::class);
        $element->method('getTagName')->willReturn($tag);
        $element->method('getChildrenAsString')->willReturn($html);
        $element->method('getValue')->willReturn($value);

        return $element;
    }
}
