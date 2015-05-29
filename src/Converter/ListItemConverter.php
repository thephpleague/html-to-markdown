<?php

namespace HTMLToMarkdown\Converter;

use HTMLToMarkdown\ElementInterface;

class ListItemConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        // If parent is an ol, use numbers, otherwise, use dashes
        $list_type = $element->getParent()->getTagName();
        $value = $element->getValue();

        if ($list_type == 'ul') {
            $markdown = '- ' . trim($value) . PHP_EOL;
        } else {
            $number = $element->getSiblingPosition();
            $markdown = $number . '. ' . trim($value) . PHP_EOL;
        }

        return $markdown;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('li');
    }
}
