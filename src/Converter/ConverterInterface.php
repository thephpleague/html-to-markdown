<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

interface ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element);

    /**
     * @return string[]
     */
    public function getSupportedTags();

    /**
     * @param \League\HTMLToMarkdown\ElementInterface $element
     *
     * @return void
     */
    public function openElement(ElementInterface $element);

    /**
     * @param \League\HTMLToMarkdown\ElementInterface $element
     *
     * @return void
     */
    public function closeElement(ElementInterface $element);
}
