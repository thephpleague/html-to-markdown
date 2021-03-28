<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class TableConverter implements ConverterInterface, PreConverterInterface, ConfigurationAwareInterface
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function setConfig(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @var array
     */
    private static $alignments = array(
        'left' => ':--',
        'right' => '--:',
        'center' => ':-:',
    );

    /**
     * @var array
     */
    private $columnAlignments = array();

    /**
     * @var str
     */
    private $caption = null;

    /**
     * @param ElementInterface $element
     *
     * @return void
     */
    public function preConvert(ElementInterface $element)
    {
        $tag = $element->getTagName();
        // Only table cells and caption are allowed to contain content.
        // Remove all text between other table elements.
        if ($tag !== 'th' and $tag !== 'td' and $tag !== 'caption') {
            foreach ($element->getChildren() as $child) {
                if ($child->isText()) {
                    $child->setFinalMarkdown('');
                }
            }
        }
    }

    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $value = $element->getValue();

        switch ($element->getTagName()) {
            case 'table':
                $this->columnAlignments = array();
                if ($this->caption) {
                    $side = $this->config->getOption('table_caption_side');
                    if ($side === 'top') {
                        $value = $this->caption . "\n" . $value;
                    } else if ($side === 'bottom') {
                        $value .= $this->caption;
                    }
                    $this->caption = null;
                }
                return $value . "\n";
            case 'caption':
                $this->caption = trim($value);
                return '';
            case 'tr':
                $value .= "|\n";
                if ($this->columnAlignments !== null) {
                    $value .= "|" . implode("|", $this->columnAlignments) . "|\n";
                    $this->columnAlignments = null;
                }
                return $value;
            case 'th':
            case 'td':
                if ($this->columnAlignments !== null) {
                    $align = $element->getAttribute('align');
                    $this->columnAlignments[] = isset(self::$alignments[$align]) ? self::$alignments[$align] : '---';
                }
                $value = str_replace("\n", ' ', $value);
                $value = str_replace('|', $this->config->getOption('table_pipe_escape'), $value);
                return '| ' . trim($value) . ' ';
            case 'thead':
            case 'tbody':
            case 'tfoot':
            case 'colgroup':
            case 'col':
                return $value;
        }
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('table', 'tr', 'th', 'td', 'thead', 'tbody', 'tfoot', 'colgroup', 'col', 'caption');
    }
}
