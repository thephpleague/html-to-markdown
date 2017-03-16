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
        $text = trim($element->getValue());

        if ($title !== '') {
            $markdown = '[' . $text . '](' . $href . ' "' . $title . '")';
        } elseif ($href === $text && $this->isValidAutolink($href)) {
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

    /**
     * @param string $href
     *
     * @return bool
     */
    private function isValidAutolink($href)
    {
        return preg_match('/^[A-Za-z][A-Za-z0-9.+-]{1,31}:[^<>\x00-\x20]*/i', $href) === 1;
    }
}
