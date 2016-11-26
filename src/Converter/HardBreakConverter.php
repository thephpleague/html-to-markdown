<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class HardBreakConverter extends BaseConverter
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        return "  \n";
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('br');
    }
}
