<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class ParagraphConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $value = $element->getValue();

        return (trim($value)) ? rtrim($value) . "\n\n" : '';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('p');
    }
}
