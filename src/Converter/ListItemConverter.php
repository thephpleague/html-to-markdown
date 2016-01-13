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

        // Add spaces to start for nested list items
        $level = $element->getListItemLevel($element);
        $prefix = str_repeat('  ', $level);
        // If list item is the first in a nested list, add a newline before it
        if ($level > 0 && $element->getSiblingPosition() === 1) {
            $prefix = "\n" . $prefix;
        }

        if ($list_type === 'ul') {
            $markdown = $prefix . '- ' . trim($value) . "\n";
        } else {
            $number = $element->getSiblingPosition();
            $markdown = $prefix . $number . '. ' . trim($value) . "\n";
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
