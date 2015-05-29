<?php

namespace HTMLToMarkdown\Converter;

use HTMLToMarkdown\ElementInterface;

class DivConverter implements ConverterInterface
{
    protected $stripTags;

    public function __construct($stripTags = false)
    {
        $this->stripTags = $stripTags;
    }

    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        return ($this->stripTags) ? $element->getValue() . PHP_EOL . PHP_EOL : html_entity_decode($element->getChildrenAsString());
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('div');
    }
}
