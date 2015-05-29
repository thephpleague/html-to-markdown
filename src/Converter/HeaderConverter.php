<?php

namespace HTMLToMarkdown\Converter;

use HTMLToMarkdown\ElementInterface;

class HeaderConverter implements ConverterInterface
{
    const STYLE_ATX = 'atx';
    const STYLE_SETEXT = 'setext';

    protected $style;

    public function __construct($style = self::STYLE_ATX)
    {
        $this->style = $style;
    }

    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $level = (int)$element->getTagName()[1];
        if (($level === 1 || $level === 2) && !$element->isDescendantOf('blockquote') && $this->style === self::STYLE_SETEXT) {
            return $this->createSetextHeader($level, $element->getValue());
        } else {
            return $this->createAtxHeader($level, $element->getValue());
        }
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
    }

    /**
     * @param int   $level
     * @param string $content
     *
     * @return string
     */
    private function createSetextHeader($level, $content)
    {
        $length = (function_exists('mb_strlen')) ? mb_strlen($content, 'utf-8') : strlen($content);
        $underline = ($level === 1) ? '=' : '-';

        return $content . PHP_EOL . str_repeat($underline, $length) . PHP_EOL . PHP_EOL;
    }

    /**
     * @param int   $level
     * @param string $content
     *
     * @return string
     */
    private function createAtxHeader($level, $content)
    {
        $prefix = str_repeat('#', $level) . ' ';

        return $prefix . $content . PHP_EOL . PHP_EOL;
    }
}
