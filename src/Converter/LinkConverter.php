<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class LinkConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $href = $element->getAttribute('href');
        $title = $element->getAttribute('title');
        $text = $element->getValue();

        if ($title !== '') {
            $markdown = '[' . $text . '](' . $href . ' "' . $title . '")';
        } elseif ($href === $text) {
            $markdown = '<' . $href . '>';
        } else {
            $markdown = '[' . $text . '](' . $href . ')';
        }

        if (!$href) {
            $markdown = html_entity_decode($element->getChildrenAsString());
        }

        return $markdown;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('a');
    }
}
