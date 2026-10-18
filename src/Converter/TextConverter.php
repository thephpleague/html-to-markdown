<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Backticks;
use League\HTMLToMarkdown\Coerce;
use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\PrecedingMarkdown;

class TextConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /**
     * Spaces which keep the text either side of them on the same line
     */
    private const NO_BREAK_SPACES = '\x{00A0}\x{2007}\x{202F}';

    /**
     * Besides blocks, these are always output on lines of their own
     */
    private const LINE_BREAKING_TAGS = ['br', 'caption', 'pre', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr'];

    /** @var Configuration|null */
    private $config;

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    public function convert(ElementInterface $element): string
    {
        $markdown = $element->getValue();

        // Remove leftover \n at the beginning of the line
        $markdown = \ltrim($markdown, "\n");

        // Replace sequences of invisible characters with spaces
        $markdown = \preg_replace('~[^\S' . self::NO_BREAK_SPACES . ']+~u', ' ', $markdown);
        \assert(\is_string($markdown));

        // Where a line starts or ends, such as in a <p>&nbsp;</p> used for spacing, they're just whitespace
        if (\preg_match('~^\s*[' . self::NO_BREAK_SPACES . ']~u', $markdown) === 1 && $this->startsLine($element)) {
            $markdown = \preg_replace('~^\s+~u', ' ', $markdown);
            \assert(\is_string($markdown));
        }

        if (\preg_match('~[' . self::NO_BREAK_SPACES . ']\s*$~u', $markdown) === 1 && $this->endsLine($element)) {
            $markdown = \preg_replace('~\s+$~u', ' ', $markdown);
            \assert(\is_string($markdown));
        }

        // Escape the following characters: '*', '_', '[', ']', '|' and '\'
        $isEscaped = ($parent = $element->getParent()) && $parent->getTagName() !== 'div';
        if ($isEscaped) {
            $markdown = \preg_replace('~([*_\\[\\]|\\\\])~u', '\\\\$1', $markdown);
            \assert(\is_string($markdown));
        }

        $markdown = \preg_replace('~^#~u', '\\\\#', $markdown);
        \assert(\is_string($markdown));

        if ($markdown === ' ') {
            $next = $element->getNext();
            if (! $next || $next->isBlock()) {
                $markdown = '';
            }
        }

        $markdown = \htmlspecialchars($markdown, ENT_NOQUOTES, 'UTF-8');

        // A pipe could otherwise make a table out of its line, cutting through any code span on the line above
        if (! $isEscaped) {
            $markdown = \str_replace('|', '&#124;', $markdown);
        }

        return Backticks::escapeText($markdown);
    }

    private function startsLine(ElementInterface $element): bool
    {
        for ($node = $element; $node !== null; $node = $node->getParent()) {
            if ($this->breaksLine($node)) {
                return true;
            }

            $startsLine = PrecedingMarkdown::isAtStartOfLine($node);
            if ($startsLine !== null) {
                return $startsLine;
            }
        }

        return true;
    }

    private function endsLine(ElementInterface $element): bool
    {
        for ($node = $element; $node !== null; $node = $node->getParent()) {
            // Whatever follows the block it's in is on another line
            if ($this->breaksLine($node)) {
                return true;
            }

            for ($next = $node->getNextSibling(); $next !== null; $next = $next->getNextSibling()) {
                $endsLine = $this->endsLineBefore($next);
                if ($endsLine !== null) {
                    return $endsLine;
                }
            }
        }

        return true;
    }

    /**
     * Whether the line ends before what the given node is converted to, judging by the node alone
     *
     * @return bool|null Null when the node won't be converted to anything, so it depends on what comes after it
     */
    private function endsLineBefore(ElementInterface $node): ?bool
    {
        $tag = $node->getTagName();
        if ($tag === '#text') {
            return \trim($node->getValue()) === '' ? null : false;
        }

        if ($tag === '#comment') {
            return $this->getOption('preserve_comments') ? false : null;
        }

        if (\in_array($tag, \explode(' ', Coerce::toString($this->getOption('remove_nodes') ?? '')), true)) {
            return null;
        }

        if ($this->breaksLine($node)) {
            return true;
        }

        foreach ($node->hasChildren() ? $node->getChildren() : [] as $child) {
            $endsLine = $this->endsLineBefore($child);
            if ($endsLine !== null) {
                return $endsLine;
            }
        }

        // With nothing in them, links and images are still output, while emphasis and code aren't, nor any stripped tag
        if ($tag === 'a' || $tag === 'img') {
            return false;
        }

        return \in_array($tag, ['b', 'code', 'em', 'i', 'strong'], true) || $this->getOption('strip_tags') ? null : false;
    }

    private function breaksLine(ElementInterface $node): bool
    {
        return $node->isBlock() || \in_array($node->getTagName(), self::LINE_BREAKING_TAGS, true);
    }

    /**
     * @return mixed
     */
    private function getOption(string $name)
    {
        return $this->config === null ? null : $this->config->getOption($name);
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['#text'];
    }
}
