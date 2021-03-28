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
     * @param ElementInterface|null $element
     *
     * @return string
     */
    protected function getNormTag($element)
    {
        if ($element !== null && !$element->isText()) {
            $tag = $element->getTagName();
            if ($tag === 'i' || $tag === 'em') {
                return 'em';
            } else if ($tag === 'b' || $tag === 'strong') {
                return 'strong';
            }
        }
        return '';
    }

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
        $tag = $this->getNormTag($element);
        $value = $element->getValue();

        if (!trim($value)) {
            return $value;
        }

        if ($tag === 'em') {
            $style = $this->config->getOption('italic_style');
        } else {
            $style = $this->config->getOption('bold_style');
        }

        $prefix = ltrim($value) !== $value ? ' ' : '';
        $suffix = rtrim($value) !== $value ? ' ' : '';

        /* If this node is immediately preceded or followed by one of the same type don't emit
         * the start or end $style, respectively. This prevents <em>foo</em><em>bar</em> from
         * being converted to *foo**bar* which is incorrect. We want *foobar* instead.
         */
        $pre_style = $this->getNormTag($element->getPreviousSibling()) === $tag ? '' : $style;
        $post_style = $this->getNormTag($element->getNextSibling()) === $tag ? '' : $style;

        return $prefix . $pre_style . trim($value) . $post_style . $suffix;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('em', 'i', 'strong', 'b');
    }
}
