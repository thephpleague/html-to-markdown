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

        $lines = preg_split('/\r\n|\r|\n/', $value);
        foreach ($lines as $line) {
            /*
             * Some special characters need to be escaped based on the position that they appear
             * The following function will deal with those special cases.
             */
            $markdown .= $this->escapeSpecialCharacters($line);
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

    /**
     * @param string $line
     *
     * @return string
     */
    private function escapeSpecialCharacters($line)
    {
        $line = $this->escapeHeaderlikeCharacters($line);
        $line = $this->escapeBlockquotelikeCharacters($line);

        return $line;
    }

    /**
     * @param string $line
     *
     * @return string
     */
    private function escapeBlockquotelikeCharacters($line)
    {
        if (strpos(ltrim($line), '>') === 0) {
            // Found a > char, escaping it
            return '\\' . ltrim($line);
        } else {
            return $line;
        }
    }

    /**
     * @param string $line
     *
     * @return string
     */
    private function escapeHeaderlikeCharacters($line)
    {
        if (strpos(ltrim($line), '--') === 0) {
            // Found a -- structure, escaping it
            return '\\' . ltrim($line);
        } else {
            return $line;
        }
    }
}
