<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown;

/**
 * @internal
 */
final class RawHtml
{
    /**
     * Keeps the element as HTML around the Markdown which its children were converted to
     */
    public static function fromElement(ElementInterface $element): string
    {
        $html = $element->getChildrenAsString();

        // The attributes must stay encoded, otherwise their values could break out of their quotes
        if (\preg_match('/^<([^\s>\/!?=]++)(?:\s++[^\s=>]++="[^"]*+")*+\s*+>/', $html, $matches) === 1) {
            return $matches[0] . $element->getValue() . '</' . $matches[1] . '>';
        }

        // Nodes which aren't elements have no tag to keep
        if (\preg_match('/^<[^!?]/', $html) !== 1) {
            return \html_entity_decode($html);
        }

        $tag = $element->getTagName();

        return '<' . $tag . '>' . $element->getValue() . '</' . $tag . '>';
    }
}
