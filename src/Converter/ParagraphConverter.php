<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class ParagraphConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $value = $element->getValue();

        $markdown = '';

        /*
         * &gt; ocurrences must be escaped, otherwise instead of rendering p tags as paragraph blocks,
         * the > will make them appear as a blockquote.
         * To achieve this, the content of the paragraph must be exploded and then each line must be check
         * if the first character (sans blank space) is a >
         */

        $lines = explode("\n", $value);
        foreach ($lines as $line) {
            if (strpos(ltrim($line), '>') === 0) {
                // Found a > char, escaping it
                $markdown .= '\\' . ltrim($line);
            } else {
                $markdown .= $line;
            }
            $markdown .= "\n";
        }

        return trim($markdown) !== '' ? rtrim($markdown) . "\n\n" : '';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('p');
    }
}
