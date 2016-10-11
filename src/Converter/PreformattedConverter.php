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

        // If the execution reaches this point it means it's just a pre tag, with no code tag nested

        // Checking if the string has multiple lines
        $lines = preg_split('/\r\n|\r|\n/', $code_content);
        if (count($lines) > 1) {
            // Multiple lines detected, adding three backticks and newlines
            $markdown .= '```' . "\n" . $code_content . "\n" . '```';
        } else {
            // One line of code, wrapping it on one backtick.
            $markdown .= '`' . $code_content . '`';
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
