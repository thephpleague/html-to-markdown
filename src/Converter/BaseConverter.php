<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

abstract class BaseConverter implements ConverterInterface
{
    /**
     * @param \League\HTMLToMarkdown\ElementInterface $element
     *
     * @return void
     */
    public function openElement(ElementInterface $element)
    {
        // Do nothing
    }

    /**
     * @param \League\HTMLToMarkdown\ElementInterface $element
     *
     * @return void
     */
    public function closeElement(ElementInterface $element)
    {
        // Do nothing
    }
}
