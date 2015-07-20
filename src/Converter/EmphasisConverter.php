<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class EmphasisConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function setConfig(Configuration $config)
    {
        $this->config = $config;
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

        if ($tag === 'i' || $tag === 'em') {
            $style = $this->config->getOption('italic_style');
        } else {
            $style = $this->config->getOption('bold_style');
        }

        return $style . $value . $style;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('em', 'i', 'strong', 'b');
    }
}
