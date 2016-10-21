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
                    // The space after the language avoids gluing the actual code with the language tag
                    $language = str_replace('language-', '', $class) . ' ';
                    break;
                }
            }
        }

        $markdown = '';
        $code = html_entity_decode($element->getChildrenAsString());

        // In order to remove the code tags we need to search for them and, in the case of the opening tag
        // use a regular expression to find the tag and the other attributes it might have
        $code = preg_replace('/<code\b[^>]*>/', '', $code);
        $code = str_replace('</code>', '', $code);

        // Checking if the code has multiple lines
        $lines = preg_split('/\r\n|\r|\n/', $code);
        if (count($lines) > 1) {
            // Multiple lines detected, adding three backticks and newlines
            $markdown .= '```' . $language . "\n" . $code . "\n" . '```';
        } else {
            // One line of code, wrapping it on one backtick.
            $markdown .= '`' . $language . $code . '`';
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
