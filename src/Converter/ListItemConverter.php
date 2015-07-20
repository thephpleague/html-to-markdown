<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

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

        if ($list_type === 'ul') {
            $markdown = '- ' . trim($value) . "\n";
        } else {
            $number = $element->getSiblingPosition();
            $markdown = $number . '. ' . trim($value) . "\n";
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
