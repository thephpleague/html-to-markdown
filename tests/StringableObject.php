<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Test;

final class StringableObject
{
    public function __toString(): string
    {
        return 'some object';
    }
}
