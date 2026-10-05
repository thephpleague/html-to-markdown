<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown;

/**
 * Keeps attribute values inside of the part of a Markdown link or image which they are output as.
 *
 * Each also has to stay inert as regular text, which is what it becomes if the link around it isn't recognized.
 *
 * @internal
 */
final class LinkSyntax
{
    /**
     * Parsers give up on a destination with parentheses nested any deeper than their limit
     */
    private const MAX_PARENTHESES_DEPTH = 3;

    /**
     * For a link title or the alternative text of an image
     */
    public static function escapeText(string $text): string
    {
        $text = \str_replace(
            ['&', '\\', '"', '<', '>', '[', ']', '*', '_'],
            ['&amp;', '&#92;', '&quot;', '&lt;', '&gt;', '&#91;', '&#93;', '&#42;', '&#95;'],
            $text
        );

        return Backticks::escapeText($text);
    }

    public static function escapeDestination(string $url): string
    {
        $characters = self::hasBalancedParentheses($url) ? '' : '()';

        $url = \preg_replace_callback('/[\x00-\x20\x7F"<>\\\\`\[\]*' . $characters . ']/', static function (array $matches): string {
            return '%' . \strtoupper(\bin2hex($matches[0]));
        }, $url);
        \assert($url !== null);

        return $url;
    }

    private static function hasBalancedParentheses(string $url): bool
    {
        $depth = 0;
        foreach (\str_split(\preg_replace('/[^()]/', '', $url) ?? $url) as $character) {
            $depth += $character === '(' ? 1 : ($character === ')' ? -1 : 0);
            if ($depth < 0 || $depth > self::MAX_PARENTHESES_DEPTH) {
                return false;
            }
        }

        return $depth === 0;
    }
}
