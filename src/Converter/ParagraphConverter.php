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
        $line = $this->escapeFirstCharacters($line);
        $line = $this->escapeOtherCharacters($line);
        $line = $this->escapeOtherCharactersRegex($line);

        return $line;
    }

    /**
     * @param string $line
     *
     * @return string
     */
    private function escapeFirstCharacters($line)
    {
        $escapable = array(
            '>',
            '- ',
            '+ ',
            '--',
            '~~~',
            '---',
            '- - -'
        );

        foreach ($escapable as $i) {
            if (strpos(ltrim($line), $i) === 0) {
                // Found a character that must be escaped, adding a backslash before
                return '\\' . ltrim($line);
            }
        }

        return $line;
    }

    /**
     * @param string $line
     *
     * @return string
     */
    private function escapeOtherCharacters($line)
    {
        $escapable = array(
            '<!--'
        );

        foreach ($escapable as $i) {
            if (strpos($line, $i) !== false) {
                // Found an escapable character, escaping it
                $line = substr_replace($line, '\\', strpos($line, $i), 0);
            }
        }

        return $line;
    }

    /**
     * @param string $line
     *
     * @return string
     */
    private function escapeOtherCharactersRegex($line)
    {
        $regExs = array(
            // Match numbers ending on ')' or '.' that are at the beginning of the line.
            '/^[0-9]+(?=\)|\.)/'
        );

        foreach ($regExs as $i) {
            if (preg_match($i, $line, $match)) {
                // Matched an escapable character, adding a backslash on the string before the offending character
                $line = substr_replace($line, '\\', strlen($match[0]), 0);
            }
        }

        return $line;
    }
}
