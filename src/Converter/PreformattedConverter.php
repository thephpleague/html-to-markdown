<?php

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\ElementInterface;

class PreformattedConverter implements ConverterInterface
{
    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        $markdown = '';

        $code_content = html_entity_decode($element->getChildrenAsString());
        $code_content = str_replace(array('<pre>', '</pre>'), '', $code_content);

        /*
         * Checking for the code tag.
         * Usually pre tags are used along with code tags. This conditional will check for converted code tags,
         * which use backticks, and if those backticks are at the beginning and at the end of the string it means
         * there's no more information to convert.
         */

        $firstBacktick = strpos(trim($code_content), '`');
        $lastBacktick = strrpos(trim($code_content), '`');
        if ($firstBacktick === 0 && $lastBacktick === strlen(trim($code_content)) - 1) {
            return $code_content;
        }

        /*
         * If the execution reaches this point it means either the pre tag has more information besides the one inside
         * the code tag or there's no code tag.
         */

        // Store the content of the code block in an array, one entry for each line
        $lines = preg_split('/\r\n|\r|\n/', $code_content);

        // Checking if the string has multiple lines
        if (count($lines) > 1) {
            // Multiple lines detected, adding three backticks and newlines
            $markdown .= '```' . "\n" . $code_content . "\n" . '```';
        } else {
            // One line of code, wrapping it on one backtick.
            $markdown .= '`' . ' ' . $code_content . '`';
        }

        return $markdown;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('pre');
    }
}
