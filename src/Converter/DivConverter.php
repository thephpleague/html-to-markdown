<?php

namespace HTMLToMarkdown\Converter;

use HTMLToMarkdown\Configuration;
use HTMLToMarkdown\ConfigurationAwareInterface;
use HTMLToMarkdown\ElementInterface;

class DivConverter implements ConverterInterface, ConfigurationAwareInterface
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
        if ($this->config->getOption('strip_tags', false)) {
            return $element->getValue() . PHP_EOL . PHP_EOL;
        }

        return html_entity_decode($element->getChildrenAsString());
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('div');
    }
}
