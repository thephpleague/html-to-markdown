<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\NoBreakSpaces;

class BlockquoteConverter implements ConverterInterface
{
    public function convert(ElementInterface $element): string
    {
        // Contents should have already been converted to Markdown by this point,
        // so we just need to add '>' symbols to each line.

        $markdown = '';

        // A leading no-break space is kept, as whatever follows it wasn't escaped as being at the start of the line
        $quoteContent = \ltrim(NoBreakSpaces::rtrim($element->getValue()));

        $lines = \preg_split('/\r\n|\r|\n/', $quoteContent);
        \assert(\is_array($lines));

        $totalLines = \count($lines);

        foreach ($lines as $i => $line) {
            $markdown .= '> ' . $line . "\n";
            if ($i + 1 === $totalLines) {
                $markdown .= "\n";
            }
        }

        return $markdown;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['blockquote'];
    }
}
