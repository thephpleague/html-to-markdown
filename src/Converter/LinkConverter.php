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
        $text = trim($element->getValue(), "\t\n\r\0\x0B");

        if ($title !== '') {
            $markdown = '[' . $text . '](' . $href . ' "' . $title . '")';
        } elseif ($href === $text && $this->isValidAutolink($href)) {
            $markdown = '<' . $href . '>';
        } elseif ($href === 'mailto:' . $text && $this->isValidEmail($text)) {
            $markdown = '<' . $text . '>';
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

    /**
     * @param string $email
     *
     * @return bool
     */
    private function isValidEmail($email)
    {
        // Email validation is messy business, but this should cover most cases
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
