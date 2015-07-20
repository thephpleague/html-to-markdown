<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class BlockquoteConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        // Contents should have already been converted to Markdown by this point,
        // so we just need to add '>' symbols to each line.

        $markdown = '';

        $quote_content = trim($element->getValue());

        $lines = preg_split('/\r\n|\r|\n/', $quote_content);

        $total_lines = count($lines);

        foreach ($lines as $i => $line) {
            $markdown .= '> ' . $line . "\n";
            if ($i + 1 === $total_lines) {
                $markdown .= "\n";
            }
        }

        return $markdown;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('blockquote');
    }
}
