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
}

interface PreConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return void
     */
    public function preConvert(ElementInterface $element);
}
