<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Coerce;
use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\PreConverterInterface;

class TableConverter implements ConverterInterface, PreConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration */
    protected $config;

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    /** @var array<string, string> */
    private static $alignments = [
        'left' => ':--',
        'right' => '--:',
        'center' => ':-:',
    ];

    /** @var array<int, string>|null */
    private $columnAlignments = [];

    /** @var string|null */
    private $caption = null;

    public function preConvert(ElementInterface $element): void
    {
        $tag = $element->getTagName();
        // Only table cells and caption are allowed to contain content.
        // Remove all text between other table elements.
        if ($tag === 'th' || $tag === 'td' || $tag === 'caption') {
            return;
        }

        foreach ($element->getChildren() as $child) {
            if ($child->isText()) {
                $child->setFinalMarkdown('');
            }
        }
    }

    public function convert(ElementInterface $element): string
    {
        $value = $element->getValue();

        switch ($element->getTagName()) {
            case 'table':
                $this->columnAlignments = [];
                if ($this->caption) {
                    $side = $this->config->getOption('table_caption_side');
                    // Kept apart from the rows, since it can contain a block of HTML which would take them in
                    if ($side === 'top') {
                        $value = $this->caption . "\n\n" . $value;
                    } elseif ($side === 'bottom') {
                        $value .= "\n" . $this->caption . "\n";
                    }

                    $this->caption = null;
                }

                return $value . "\n";
            case 'caption':
                $this->caption = \trim($value);

                return '';
            case 'tr':
                $value .= "|\n";
                if ($this->columnAlignments !== null) {
                    $value .= '|' . \implode('|', $this->columnAlignments) . "|\n";

                    $this->columnAlignments = null;
                }

                return $value;
            case 'th':
            case 'td':
                if ($this->columnAlignments !== null) {
                    $align = $element->getAttribute('align');

                    $this->columnAlignments[] = self::$alignments[$align] ?? '---';
                }

                $value  = \str_replace("\n", ' ', $value);
                $escape = Coerce::toString($this->config->getOption('table_pipe_escape') ?? '\|');
                $value  = \preg_replace_callback('/(\\\\*)\|/', static function (array $matches) use ($escape): string {
                    // Those in regular text have already been escaped with a backslash
                    return (string) \substr($matches[1], 0, \strlen($matches[1]) - \strlen($matches[1]) % 2) . $escape;
                }, $value);
                \assert($value !== null);

                return '| ' . \trim($value) . ' ';
            case 'thead':
            case 'tbody':
            case 'tfoot':
            case 'colgroup':
            case 'col':
                return $value;
            default:
                return '';
        }
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['table', 'tr', 'th', 'td', 'thead', 'tbody', 'tfoot', 'colgroup', 'col', 'caption'];
    }
}
