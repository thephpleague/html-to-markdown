<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;

class EmphasisConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration */
    protected $config;

    protected function getNormTag(?ElementInterface $element): string
    {
        if ($element !== null && ! $element->isText()) {
            $tag = $element->getTagName();
            switch($tag) {
                case 'i':
                case 'em':
                case 'cite':
                case 'dfn':
                case 'var':
                    return 'em';
                case 'b':
                case 'strong':
                    return 'strong';
                case 'strike':
                case 's':
                case 'del':
                    return 'del';
                case 'sub':
                    return 'sub';
                case 'sup':
                    return 'sup';
                case 'u':
                case 'ins':
                    return 'u';
                case 'kdb':
                    return 'kbd';
                case 'span':
                case 'small':
                case 'abbr':
                    return $tag;
            }
        }
        return '';
    }

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    public function convert(ElementInterface $element): string
    {
        $tag   = $this->getNormTag($element);
        $value = $element->getValue();

        if (! \trim($value)) {
            return $value;
        }
        switch ($tag) {
            case 'em':
                $style = $this->config->getOption('italic_style');
                break;
            case 'del':
                $style = $this->config->getOption('strikethrough_style');
                break;
            case 'sub':
                $style = $this->config->getOption('subscript_style');
                break;
            case 'sup':
                $style = $this->config->getOption('superscript_style');
                break;
            case 'strong':
                $style = $this->config->getOption('bold_style');
                break;
            case 'u':
                $style = $this->config->getOption('underline_style');
                break;
            case 'kdb':
                $style = $this->config->getOption('keyboard_style');
                break;
            default:
                $style = $this->config->getOption('undefined_style');
                break;
        }

        $prefix = \ltrim($value) !== $value ? ' ' : '';
        $suffix = \rtrim($value) !== $value ? ' ' : '';

        $preStyle  = $this->makeDelimiter($element, $tag, $style);
        $postStyle = $this->makeDelimiter($element, $tag, $style, false);

        return $prefix . $preStyle . \trim($value) . $postStyle . $suffix;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return [
            'em', 'i', 'cite', 'dfn', 'var',
            'strong', 'b',
            'del', 'strike', 's',
            'sub', 'sup',
            'u', 'ins',
            'kbd',
            'span', 'small', 'abbr'
        ];
    }
    
    protected function makeDelimiter($element, string $tag, $style, bool $prev = true): string
    {
        /* If this node is immediately preceded or followed by one of the same type don't emit
         * the start or end $style, respectively. This prevents <em>foo</em><em>bar</em> from
         * being converted to *foo**bar* which is incorrect. We want *foobar* instead.
         */
        if($prev) {
            $ignore = $this->getNormTag($element->getPreviousSibling()) === $tag;
        } else {
            $ignore = $this->getNormTag($element->getNextSibling()) === $tag;
        }
        if (!is_string($style ?? null) || $ignore) return '';
        return empty($style) ? "<" . ($prev ? "" : "/") ."{$tag}>" : $style;
    }
}
