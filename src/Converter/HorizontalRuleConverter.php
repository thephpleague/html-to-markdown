<?php

namespace HTMLToMarkdown\Converter;

use HTMLToMarkdown\ElementInterface;

class HorizontalRuleConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        return '- - - - - -' . PHP_EOL . PHP_EOL;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('hr');
    }
}
