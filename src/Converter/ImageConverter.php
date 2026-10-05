<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Backticks;
use League\HTMLToMarkdown\ElementInterface;

class ImageConverter implements ConverterInterface
{
    public function convert(ElementInterface $element): string
    {
        $src   = Backticks::escapeUrl($element->getAttribute('src'));
        $alt   = Backticks::escapeText($element->getAttribute('alt'));
        $title = Backticks::escapeText($element->getAttribute('title'));

        if ($title !== '') {
            // No newlines added. <img> should be in a block-level element.
            return '![' . $alt . '](' . $src . ' "' . $title . '")';
        }

        return '![' . $alt . '](' . $src . ')';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['img'];
    }
}
