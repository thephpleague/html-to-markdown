<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class LinkConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function setConfig(Configuration $config) {
        $this->config = $config;
    }

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
            if (stristr($href, ' ')) {
                $href = '<'.$href.'>';
            }
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
        $useAutolinks = $this->config->getOption('use_autolinks');
        return $useAutolinks && (preg_match('/^[A-Za-z][A-Za-z0-9.+-]{1,31}:[^<>\x00-\x20]*/i', $href) === 1);
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
