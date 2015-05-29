<?php

namespace HTMLToMarkdown\Converter;

use HTMLToMarkdown\ElementInterface;

class EmphasisConverter implements ConverterInterface
{
    protected $italicStyle;
    protected $boldStyle;

    public function __construct($italicStyle = '_', $boldStyle = '**')
    {
        $this->italicStyle = $italicStyle;
        $this->boldStyle = $boldStyle;
    }

    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $tag = $element->getTagName();
        $value = $element->getValue();

        if ($tag == 'i' || $tag == 'em') {
            return $this->italicStyle . $value . $this->italicStyle;
        } else {
            return $this->boldStyle . $value . $this->boldStyle;
        }
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('em', 'i', 'strong', 'b');
    }
}
