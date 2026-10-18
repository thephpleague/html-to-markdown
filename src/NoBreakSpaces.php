<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown;

/**
 * Spaces which keep the text either side of them on the same line, such as `&nbsp;`
 *
 * @internal
 */
final class NoBreakSpaces
{
    public const CHARACTERS = '\x{00A0}\x{2007}\x{202F}';

    /**
     * Like trim(), but also removes these, along with any other Unicode whitespace
     */
    public static function trim(string $markdown): string
    {
        // The lookbehind keeps the trailing whitespace from being tried at every position within a long run of it
        $markdown = \preg_replace('~^\s++|(?<!\s)\s++\z~u', '', $markdown);
        \assert(\is_string($markdown));

        return $markdown;
    }

    /**
     * Like rtrim(), but also removes these, along with any other Unicode whitespace
     */
    public static function rtrim(string $markdown): string
    {
        $markdown = \preg_replace('~(?<!\s)\s++\z~u', '', $markdown);
        \assert(\is_string($markdown));

        return $markdown;
    }
}
