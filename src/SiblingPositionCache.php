<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown;

/**
 * Remembers how far the children of one parent have been counted, so that each one doesn't recount those before it.
 *
 * @internal
 */
final class SiblingPositionCache
{
    /**
     * An already-converted child, which is therefore never going to change again
     *
     * @var \DOMNode|null
     */
    public $anchor;

    /**
     * The position of the anchor
     *
     * @var int
     */
    public $position = 0;
}
