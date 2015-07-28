<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class TextConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $value = $element->getValue();

        $markdown = preg_replace('~\s+~', ' ', $value);

        //escape the following characters: '*', '_' and '\'
        $markdown = preg_replace('~([*_\\\\])~', '\\\\$1', $markdown);

        $markdown = preg_replace('~^#~', '\\\\#', $markdown);

        if ($markdown === ' ') {
            $next = $element->getNext();
            if (!$next || $next->isBlock()) {
                $markdown = '';
            }
        }

        return $markdown;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('#text');
    }
}
