<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class HeaderConverter implements ConverterInterface, ConfigurationAwareInterface
{
    const STYLE_ATX = 'atx';
    const STYLE_SETEXT = 'setext';

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
        $level = (int) substr($element->getTagName(), 1, 1);
        $style = $this->config->getOption('header_style', self::STYLE_SETEXT);

        if (($level === 1 || $level === 2) && !$element->isDescendantOf('blockquote') && $style === self::STYLE_SETEXT) {
            return $this->createSetextHeader($level, $element->getValue());
        }

        return $this->createAtxHeader($level, $element->getValue());
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
    }

    /**
     * @param int    $level
     * @param string $content
     *
     * @return string
     */
    private function createSetextHeader($level, $content)
    {
        $length = function_exists('mb_strlen') ? mb_strlen($content, 'utf-8') : strlen($content);
        $underline = ($level === 1) ? '=' : '-';

        return $content . "\n" . str_repeat($underline, $length) . "\n\n";
    }

    /**
     * @param int    $level
     * @param string $content
     *
     * @return string
     */
    private function createAtxHeader($level, $content)
    {
        $prefix = str_repeat('#', $level) . ' ';

        return $prefix . $content . "\n\n";
    }
}
