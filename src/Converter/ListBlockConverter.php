<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class ListBlockConverter extends BaseConverter
{
    public static $level = -1;

    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        if (self::$level >= 0) {
            return "\n" . $element->getValue() . "\n";
        } else {
            return $element->getValue() . "\n";
        }
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('ol', 'ul');
    }

    public function openElement(ElementInterface $element)
    {
        self::$level++;
    }

    public function closeElement(ElementInterface $element)
    {
        self::$level--;
    }
}
