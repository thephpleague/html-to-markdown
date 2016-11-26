<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class HorizontalRuleConverter extends BaseConverter
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        return "- - - - - -\n\n";
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('hr');
    }
}
