<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown;

/**
 * @internal
 */
final class Backticks
{
    public static function longestRun(string $content): int
    {
        return \max(self::runLengths($content) ?: [0]);
    }

    /**
     * Returns a fence which cannot be closed by anything inside of the given content
     */
    public static function fenceFor(string $content): string
    {
        return \str_repeat('`', \max(3, self::longestRun($content) + 1));
    }

    /**
     * Wraps the content in a code span which cannot be closed by anything inside of the given content
     */
    public static function wrapSpan(string $content): string
    {
        // An empty span would leave behind an unmatched delimiter which could pair up with a later one
        if ($content === '') {
            return '';
        }

        $runLengths = self::runLengths($content);

        // A span is only closed by a run of exactly the same length as the one which opened it
        $length = 1;
        while (\in_array($length, $runLengths, true)) {
            $length++;
        }

        $delimiter = \str_repeat('`', $length);

        // Keep backticks at the edges of the content from merging into the delimiters,
        // and spaces at both edges from being taken as this same padding
        if ($content[0] === '`' || \substr($content, -1) === '`' || \preg_match('/^ .*[^ ].* $/s', $content) === 1) {
            $content = ' ' . $content . ' ';
        }

        return $delimiter . $content . $delimiter;
    }

    /**
     * Keeps literal backticks from pairing up with the delimiter of a code span.
     *
     * An entity is used because backslashes aren't always escaped, so one could undo a backslash escape.
     */
    public static function escapeText(string $text): string
    {
        // A line break in an attribute value would also start a new line in the Markdown
        return \str_replace(['`', "\r\n", "\r", "\n"], ['&#96;', ' ', ' ', ' '], $text);
    }

    /**
     * @return int[]
     */
    private static function runLengths(string $content): array
    {
        \preg_match_all('/`+/', $content, $matches);

        return \array_map('strlen', $matches[0]);
    }
}
