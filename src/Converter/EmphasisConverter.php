<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Coerce;
use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\NoBreakSpaces;

class EmphasisConverter implements ConverterInterface, ConfigurationAwareInterface
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

        /* If this node is immediately preceded or followed by one of the same type don't emit
         * the start or end $style, respectively. This prevents <em>foo</em><em>bar</em> from
         * being converted to *foo**bar* which is incorrect. We want *foobar* instead.
         */
        $preStyle  = $this->getNormTag($element->getPreviousSibling()) === $tag ? '' : $style;
        $postStyle = $this->getNormTag($element->getNextSibling()) === $tag ? '' : $style;

        return $prefix . $preStyle . $content . $postStyle . $suffix;
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
