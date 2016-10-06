<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class CodeConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $language = null;

        // Checking for language class on the code block
        $classes = $element->getAttribute('class');

        if ($classes) {
            // Since tags can have more than one class, we need to find the one that starts with 'language-'
            $classes = explode(' ', $classes);
            foreach ($classes as $class) {
                if (strpos($class, 'language-') !== false) {
                    // Found one, save it as the selected language and stop looping over the classes.
                    $language = str_replace('language-', '', $class);
                    break;
                }
            }
        }

        // Store the content of the code block in an array, one entry for each line

        $markdown = '';
        $element->getNext($element->node);
        $code_content = html_entity_decode($element->getChildrenAsString());
        $code_content = str_replace(array('<code>', '</code>'), '', $code_content);
        $code_content = str_replace(array('<pre>', '</pre>'), '', $code_content);

        $lines = preg_split('/\r\n|\r|\n/', $code_content);
        $total = count($lines);

        // If there's more than one line of code, prepend each line with four spaces and no backticks.
        if ($total > 1 || $element->getTagName() === 'pre') {
            // Remove the first and last line if they're empty
            $first_line = trim($lines[0]);
            $last_line = trim($lines[$total - 1]);
            $first_line = trim($first_line, '&#xD;'); //trim XML style carriage returns too
            $last_line = trim($last_line, '&#xD;');

            if (empty($first_line)) {
                array_shift($lines);
            }

            if (empty($last_line)) {
                array_pop($lines);
            }

            $count = 1;
            foreach ($lines as $line) {
                $line = str_replace('&#xD;', '', $line);
                $markdown .= '    ' . $line;
                // Add newlines, except final line of the code
                if ($count !== $total) {
                    $markdown .= "\n";
                }
                $count++;
            }
            $markdown .= "\n";
        } else {
            // There's only one line of code. It's a code span, not a block. Just wrap it with backticks.
            $markdown .= '`' . $lines[0] . '`';
        }

        if ($element->getTagName() === 'code') {
            $markdown = "\n" . $markdown . "\n";
        }

        return $markdown;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('code');
    }
}
