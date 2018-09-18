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
        $markdown = $element->getValue();

        // Remove leftover \n at the beginning of the line
        $markdown = ltrim($markdown, "\n");

        // Replace sequences of invisible characters with spaces
        $markdown = preg_replace('~\s+~u', ' ', $markdown);

        // Escape the following characters: '*', '_', '[', ']' and '\'
        if ($element->getParent() && $element->getParent()->getTagName() !== 'div') {
            $markdown = preg_replace('~([*_\\[\\]\\\\])~u', '\\\\$1', $markdown);
        }

        $markdown = preg_replace('~^#~u', '\\\\#', $markdown);

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
