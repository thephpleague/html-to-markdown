<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Backticks;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\NoBreakSpaces;
use League\HTMLToMarkdown\RawHtml;

class TextConverter implements ConverterInterface
{
    public function convert(ElementInterface $element): string
    {
        $markdown = $element->getValue();

        // Remove leftover \n at the beginning of the line
        $markdown = \ltrim($markdown, "\n");

        // Replace sequences of invisible characters with spaces, other than those which keep text on the same line
        $markdown = \preg_replace('~[^\S' . NoBreakSpaces::CHARACTERS . ']+~u', ' ', $markdown);
        \assert(\is_string($markdown));

        // Escape the following characters: '*', '_', '[', ']', '|' and '\'
        $isEscaped = ($parent = $element->getParent()) && $parent->getTagName() !== 'div';
        if ($isEscaped) {
            $markdown = \preg_replace('~([*_\\[\\]|\\\\])~u', '\\\\$1', $markdown);
            \assert(\is_string($markdown));
        }

        $markdown = \preg_replace('~^#~u', '\\\\#', $markdown);
        \assert(\is_string($markdown));

        // A `!` right before a link would turn the link into an image
        if ($isEscaped && \substr($markdown, -1) === '!' && ($next = $element->getNext()) !== null && $next->getTagName() === 'a') {
            $markdown = \substr($markdown, 0, -1) . '\\!';
        }

        if ($this->isOwnLineAfterBreak($element) && ! $this->isInsideRawHtmlBlock($parent) && ! HardBreakConverter::escapesListMarker($parent, $element->getValue())) {
            // It would otherwise underline the line before it as a setext heading
            $markdown = \preg_replace('~^( *+)(?=(?:=++|-++)\s*+\z)~u', '$1\\\\', $markdown);
            \assert(\is_string($markdown));
        }

        if (\preg_match('~^\s++\z~u', $markdown) === 1) {
            $next = $element->getNext();
            if (! $next || $next->isBlock() || \in_array($next->getTagName(), ['pre', 'table'], true)) {
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

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['#text'];
    }

    /**
     * Whether the text is directly inside of a block which isn't converted to Markdown, where a backslash would show
     */
    private function isInsideRawHtmlBlock(?ElementInterface $parent): bool
    {
        if ($parent === null) {
            return false;
        }

        $tag = $parent->getTagName();

        return RawHtml::isBlockTag($tag) && ! \in_array($tag, ['blockquote', 'body', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'html', 'li', 'p'], true);
    }

    private function isOwnLineAfterBreak(ElementInterface $element): bool
    {
        $previous = $element->getPreviousSibling();
        if ($previous === null || $previous->getTagName() !== 'br') {
            return false;
        }

        $next = $element->getNextSibling();

        return $next === null || $next->isBlock() || \in_array($next->getTagName(), ['br', 'pre', 'table'], true);
    }
}
