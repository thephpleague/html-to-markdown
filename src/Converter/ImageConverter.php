<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class ImageConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $src = $element->getAttribute('src');
        $alt = $element->getAttribute('alt');
        $title = $element->getAttribute('title');

        if ($title !== '') {
            // No newlines added. <img> should be in a block-level element.
            return '![' . $alt . '](' . $src . ' "' . $title . '")';
        }

        return '![' . $alt . '](' . $src . ')';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('img');
    }
}
