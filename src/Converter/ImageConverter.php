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
            $markdown = '![' . $alt . '](' . $src . ' "' . $title . '")';
        } else {
            $markdown = '![' . $alt . '](' . $src . ')';
        }

        return $markdown;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('img');
    }
}
