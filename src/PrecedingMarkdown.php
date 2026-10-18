<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown;

/**
 * Looks at what has already been output before an element which is about to be converted.
 *
 * Elements are converted in document order, so everything before one has already been replaced by its Markdown.
 *
 * @internal
 */
final class PrecedingMarkdown
{
    /**
     * Returns the last two characters output for the earlier siblings of the given element
     */
    public static function getEndOfSiblings(ElementInterface $element): string
    {
        $markdown = '';
        $siblings = 0;
        foreach (self::getSiblingValues($element) as $value) {
            $markdown = $value . $markdown;
            if (\strlen($markdown) >= 2) {
                break;
            }

            // Past a long run of empty ones, it's just taken not to end in a blank line
            if (++$siblings > 100) {
                return '  ';
            }
        }

        return (string) \substr($markdown, -2);
    }

    /**
     * Whether anything other than whitespace has been output for the nearest earlier siblings of the given element
     */
    public static function hasContent(ElementInterface $element): bool
    {
        $siblings = 0;
        foreach (self::getSiblingValues($element) as $value) {
            if (\trim($value) !== '') {
                return true;
            }

            if (++$siblings > 100) {
                break;
            }
        }

        return false;
    }

    /**
     * Whether a code span output next would have its delimiter merged with a backtick, or escaped by a backslash
     */
    public static function endsInDelimiterHazard(ElementInterface $element): bool
    {
        $markdown = '';
        for ($node = $element; $node !== null; $node = $node->getParent()) {
            foreach (self::getSiblingValues($node) as $value) {
                $markdown = $value . $markdown;

                // Only a whole run of backslashes says whether the last one is itself escaped
                if (\rtrim($markdown, '\\') !== '') {
                    break 2;
                }
            }
        }

        return \preg_match('/(?:`|(?<!\\\\)(?:\\\\\\\\)*+\\\\)\z/', $markdown) === 1;
    }

    /**
     * @return iterable<string> The Markdown of each earlier sibling, nearest first
     */
    private static function getSiblingValues(ElementInterface $element): iterable
    {
        if ($element instanceof Element) {
            return $element->getPrecedingSiblingValues();
        }

        $parent = $element->getParent();
        if ($parent === null) {
            return [];
        }

        // Without access to the node itself, it can only be assumed to be the first child not yet converted to text
        $values = [];
        foreach ($parent->getChildren() as $sibling) {
            if (! $sibling->isText()) {
                break;
            }

            $values[] = $sibling->getValue();
        }

        return \array_reverse($values);
    }
}
