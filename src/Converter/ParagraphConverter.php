<?php

namespace HTMLToMarkdown\Converter;

use HTMLToMarkdown\ElementInterface;

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

        return (trim($value)) ? rtrim($value) . PHP_EOL . PHP_EOL : '';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('p');
    }
}
