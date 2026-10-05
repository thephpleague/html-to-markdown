<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Backticks;
use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\PrecedingMarkdown;
use League\HTMLToMarkdown\RawHtml;

class CodeConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration|null */
    protected $config;

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    public function convert(ElementInterface $element): string
    {
        $language = '';

        // Checking for language class on the code block
        $classes = $element->getAttribute('class');

        if ($classes) {
            // Since tags can have more than one class, we need to find the one that starts with 'language-'
            $classes = \preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($classes as $class) {
                if (\strpos($class, 'language-') !== false) {
                    // Found one, save it as the selected language and stop looping over the classes.
                    $language = \str_replace(['language-', '`'], '', $class);
                    break;
                }
            }
        }

        $code = RawHtml::getCode($element);

        if ($this->isInsidePre($element)) {
            // Alongside anything else, or where a fence won't work, it's left to the parent to wrap all of it
            if (! $this->isOnlyChild($element) || RawHtml::cannotBeFenced($element, $this->config)) {
                return $code;
            }

            // Code block detected, newlines will be added in parent
            $fence = Backticks::fenceFor($code);

            return $fence . $language . "\n" . $code . "\n" . $fence;
        }

        // One line of code, removing new lines
        $code = \preg_replace('/\r\n|\r|\n/', '', $code);
        \assert($code !== null);
        if ($code === '') {
            return '';
        }

        // Keep it apart from a backtick it would merge with, or a backslash which would escape it
        return (PrecedingMarkdown::endsInDelimiterHazard($element) ? ' ' : '') . Backticks::wrapSpan($code);
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['code'];
    }

    private function isInsidePre(ElementInterface $element): bool
    {
        $parent = $element->getParent();

        return $parent !== null && $parent->getTagName() === 'pre';
    }

    private function isOnlyChild(ElementInterface $element): bool
    {
        for ($sibling = $element->getNextSibling(); $sibling !== null; $sibling = $sibling->getNextSibling()) {
            if (! $sibling->isWhitespace()) {
                return false;
            }
        }

        // The rest only needs checking for the last one, which matters when there are many of them
        $parent = $element->getParent();
        \assert($parent !== null);

        $count = 0;
        foreach ($parent->getChildren() as $child) {
            if (! $child->isWhitespace() && ++$count > 1) {
                return false;
            }
        }

        return true;
    }
}
