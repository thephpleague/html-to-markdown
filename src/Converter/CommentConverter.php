<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class CommentConverter extends BaseConverter
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        return '';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('#comment');
    }
}
