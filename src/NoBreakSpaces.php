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
     * Removes them where a line starts or ends, along with any other whitespace there.
     *
     * They're commonly used there for layout, such as in a `<p>&nbsp;</p>` used for spacing, and would only
     * leave invisible characters behind. This has to wait for the element's children to be converted, as only
     * then is it known what's output either side of them.
     */
    public static function removeFromEndsOfLines(ElementInterface $element): void
    {
        $children = $element->getChildren();

        $values = [];
        foreach ($children as $child) {
            $values[] = $child->getValue();
        }

        foreach ($values as $i => $value) {
            if (\preg_match('~[' . self::CHARACTERS . ']~u', $value) !== 1) {
                continue;
            }

            $trimmed = $value;
            if (self::isEndOfLine($values, $i, -1)) {
                $trimmed = \preg_replace('~^\s*[' . self::CHARACTERS . ']\s*~u', '', $trimmed);
                \assert(\is_string($trimmed));
            }

            if (self::isEndOfLine($values, $i, 1)) {
                $trimmed = \preg_replace('~\s*[' . self::CHARACTERS . ']\s*\z~u', '', $trimmed);
                \assert(\is_string($trimmed));
            }

            if ($trimmed !== $value) {
                $children[$i]->setFinalMarkdown($trimmed);
            }
        }
    }

    /**
     * Whether nothing but whitespace is output between the given child and the start or end of its line
     *
     * @param string[] $values    The Markdown of each child
     * @param int      $direction -1 to look before the child, 1 to look after it
     */
    private static function isEndOfLine(array $values, int $index, int $direction): bool
    {
        for ($i = $index + $direction; isset($values[$i]); $i += $direction) {
            $markdown = \preg_replace('~[^\S\n]+~u', '', $values[$i]);
            \assert(\is_string($markdown));

            if ($markdown !== '') {
                return ($direction > 0 ? $markdown[0] : \substr($markdown, -1)) === "\n";
            }
        }

        return true;
    }
}
