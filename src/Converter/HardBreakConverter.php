<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class HardBreakConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration */
    protected $config;

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    public function convert(ElementInterface $element): string
    {
        $return = $this->config->getOption('hard_break') ? "\n" : "  \n";

        $next = $element->getNext();
        if ($next && ! $this->startsWithCode($next)) {
            $nextValue = $next->getValue();
            if ($nextValue) {
                if (\in_array(\substr($nextValue, 0, 2), ['- ', '* ', '+ '], true)) {
                    $parent = $element->getParent();
                    if ($parent && $parent->getTagName() === 'li') {
                        $return .= '\\';
                    }
                }
            }
        }

        return $return;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['br'];
    }

    /**
     * A backslash before a code span would escape its delimiter
     */
    private function startsWithCode(ElementInterface $element): bool
    {
        while (true) {
            if ($element->getTagName() === 'code') {
                return true;
            }

            // Text nodes have no child list at all on older versions of PHP
            $children = $element->hasChildren() ? $element->getChildren() : [];
            if ($children === []) {
                return false;
            }

            $element = $children[0];
        }
    }
}
