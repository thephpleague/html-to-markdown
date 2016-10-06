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
        $code_content = html_entity_decode($element->getValue());

        $markdown .= '```' . $language . "\n" . $code_content . "\n" . '```';

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
