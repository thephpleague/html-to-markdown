<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class ListItemConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /**
     * @var Configuration
     */
    protected $config;

  /**
   * @var bool
   */
    protected $doAlternatePrefixes = FALSE;

  /**
   * @var array
   */
    protected $prefixes;

  /**
   * @var array
   */
    protected $standardPrefixes;

  /**
   * @var array
   */
    protected $alternatePrefixes;

    /**
     * @param Configuration $config
     */
    public function setConfig(Configuration $config)
    {
        $this->config = $config;
        $list_item_alternate = $this->config->getOption('list_item_alternate');
        if (isset($list_item_alternate)) {
            foreach (['standard', 'alternate'] as $item) {
                if (!isset($list_item_alternate[$item])) {
                   throw new \Exception("The '{$item}' property is missing in 'list_item_alternate' config of HtmlConverter.");
                }
                if (count($list_item_alternate[$item]) != 2) {
                   throw new \Exception("The '{$item}' property (in 'list_item_alternate' config of HtmlConverter) should have 2 entries.");
                }
            }

            $this->doAlternatePrefixes = TRUE;
            $this->prefixes = $list_item_alternate['alternate'];
            $this->standardPrefixes = $list_item_alternate['standard'];
            $this->alternatePrefixes = $list_item_alternate['alternate'];
        }
        else {
            $list_item_style = $this->config->getOption('list_item_style', '-');
            $this->prefixes = [$list_item_style, '.'];
        }
    }

    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        // If parent is an ol, use numbers, otherwise, use dashes
        $list_type = $element->getParent()->getTagName();

        // Add spaces to start for nested list items
        $level = $element->getListItemLevel($element);

        $prefixForParagraph = str_repeat('  ', $level + 1);
        $value = trim(implode("\n" . $prefixForParagraph, explode("\n", trim($element->getValue()))));

        // If list item is the first in a nested list, add a newline before it
        $prefix = '';
        if ($level > 0 && $element->getSiblingPosition() === 1) {
            $prefix = "\n";
        }
        elseif ($level == 0 && $element->getSiblingPosition() === 1 && $this->doAlternatePrefixes) {
          $this->prefixes = ($this->prefixes == $this->standardPrefixes) ? $this->alternatePrefixes : $this->standardPrefixes;
        }

        if ($list_type === 'ul') {
            $list_item_style = $this->prefixes[0];
            return $prefix . $list_item_style . ' ' . $value . "\n";
        }

        if ($list_type === 'ol' && $start = $element->getParent()->getAttribute('start')) {
            $number = $start + $element->getSiblingPosition() - 1;
        } else {
            $number = $element->getSiblingPosition();
        }

        return $prefix . $number . $this->prefixes[1] . ' ' . $value . "\n";
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('li');
    }
}
