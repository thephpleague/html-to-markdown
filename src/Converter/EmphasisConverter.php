<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Coerce;
use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\Element;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\NoBreakSpaces;
use League\HTMLToMarkdown\PreConverterInterface;

class EmphasisConverter implements ConverterInterface, ConfigurationAwareInterface, PreConverterInterface
{
    /** @var Configuration */
    protected $config;

    protected function getNormTag(?ElementInterface $element): string
    {
        if ($element !== null && ! $element->isText()) {
            $tag = $element->getTagName();
            if ($tag === 'i' || $tag === 'em') {
                return 'em';
            }

            if ($tag === 'b' || $tag === 'strong') {
                return 'strong';
            }
        }

        return '';
    }

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    /**
     * Puts the contents of a run of the same kind into one of them, so that <em>foo</em><em>bar</em> becomes *foobar*
     * rather than *foo**bar*, whatever is in each of them
     */
    public function preConvert(ElementInterface $element): void
    {
        // The first of the run has already taken in the rest
        $tag = $this->getNormTag($element);
        if (! $element instanceof Element || $this->getNormTag($element->getPreviousSibling()) === $tag) {
            return;
        }

        $target = null;
        for ($sibling = $element; $sibling instanceof Element && $this->getNormTag($sibling) === $tag; $sibling = $next) {
            $next = $sibling->getNextSibling();

            // Whatever is in a removed one stays out of the output, while the rest of the run carries on around it.
            // It's moved along with the rest, so that what's output before anything inside of it is as close as ever.
            if ($this->isRemoved($sibling)) {
                if ($target !== null) {
                    $target->appendElement($sibling);
                }

                continue;
            }

            if ($target === null) {
                $target = $sibling;
            } else {
                $target->absorb($sibling);
            }
        }
    }

    public function convert(ElementInterface $element): string
    {
        $tag   = $this->getNormTag($element);
        $value = $element->getValue();

        // Delimiters next to any Unicode whitespace, including non-breaking spaces, wouldn't be recognized.
        // Neither match can backtrack through the content, which could otherwise run out of the limit for it.
        \preg_match('~^\s*+~u', $value, $matches);
        $start = \strlen($matches[0] ?? '');

        // The lookbehind keeps the trailing whitespace from being tried at every position within a long run of it
        if ($start === \strlen($value) || \preg_match('~(?<!\s)\s*+\z~u', $value, $matches, \PREG_OFFSET_CAPTURE, $start) !== 1) {
            return $value;
        }

        $end      = (int) $matches[0][1];
        $leading  = \substr($value, 0, $start);
        $content  = \substr($value, $start, $end - $start);
        $trailing = \substr($value, $end);
        if (! $content) {
            return $value;
        }

        if ($tag === 'em') {
            $style = Coerce::toString($this->config->getOption('italic_style'));
        } else {
            $style = Coerce::toString($this->config->getOption('bold_style'));
        }

        $prefix = $this->getOuterWhitespace($leading);
        $suffix = $this->getOuterWhitespace($trailing);

        return $prefix . $style . $content . $style . $suffix;
    }

    private function isRemoved(ElementInterface $element): bool
    {
        $tagsToRemove = \explode(' ', Coerce::toString($this->config->getOption('remove_nodes') ?? ''));

        return \in_array($element->getTagName(), $tagsToRemove, true);
    }

    private function getOuterWhitespace(string $whitespace): string
    {
        $whitespace = \preg_replace('~[^\S' . NoBreakSpaces::CHARACTERS . ']+~u', ' ', $whitespace);
        \assert(\is_string($whitespace));

        return $whitespace;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['em', 'i', 'strong', 'b'];
    }
}
