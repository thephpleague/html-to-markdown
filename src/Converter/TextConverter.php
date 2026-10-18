<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Backticks;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\PrecedingMarkdown;
use League\HTMLToMarkdown\RawHtml;

class TextConverter implements ConverterInterface
{
    /**
     * Spaces which keep the text either side of them on the same line
     */
    private const NO_BREAK_SPACES = '\x{00A0}\x{2007}\x{202F}';

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
        $node = $element;
        while (($end = PrecedingMarkdown::getEndOfSiblings($node)) === '') {
            $node = $node->getParent();
            if ($node === null || RawHtml::isBlockTag($node->getTagName())) {
                return true;
            }
        }

        return \substr($end, -1) === "\n";
    }

    private function endsLine(ElementInterface $element): bool
    {
        $next = $element->getNext();
        while ($next !== null && $next->isText() && \trim($next->getValue()) === '') {
            $next = $next->getNext();
        }

        return $next === null || $next->getTagName() === 'br' || RawHtml::isBlockTag($next->getTagName());
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['#text'];
    }
}
