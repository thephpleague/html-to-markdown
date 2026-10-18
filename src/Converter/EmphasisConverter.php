<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Coerce;
use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class EmphasisConverter implements ConverterInterface, ConfigurationAwareInterface
{
    private const NO_BREAK_SPACES = '\x{00A0}\x{2007}\x{202F}';

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

        // Delimiters next to any Unicode whitespace, including non-breaking spaces, wouldn't be recognized
        if (\preg_match('~^(\s*)(.*?)(\s*)\z~su', $value, $matches) !== 1) {
            return $value;
        }

        [, $leading, $content, $trailing] = $matches;
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
        $whitespace = \preg_replace('~[^\S' . self::NO_BREAK_SPACES . ']+~u', ' ', $whitespace);
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
