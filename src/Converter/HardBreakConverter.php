<?php

namespace HTMLToMarkdown\Converter;

use HTMLToMarkdown\ElementInterface;

class HardBreakConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        return '  ' . PHP_EOL;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('br');
    }
}
