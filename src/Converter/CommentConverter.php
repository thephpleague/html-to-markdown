<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\PrecedingMarkdown;

class CommentConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration */
    protected $config;

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    public function convert(ElementInterface $element): string
    {
        if (! $this->shouldPreserve($element)) {
            return '';
        }

        // Wherever it ends up, a line after the first could otherwise be outside of it, such as past the end of a heading
        $text = \preg_replace_callback('/\s++/', static function (array $matches): string {
            return \strpbrk($matches[0], "\r\n") === false ? $matches[0] : ' ';
        }, $element->getValue());
        \assert(\is_string($text));

        if (! self::canBeKept($text)) {
            return '';
        }

        // A backslash before it would make the whole of it text
        return (PrecedingMarkdown::endsInEscape($element) ? ' ' : '') . '<!--' . $text . '-->';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['#comment'];
    }

    /**
     * Whether the text would stay inside of the comment, which browsers or Markdown parsers would otherwise end early,
     * letting the rest of it through as HTML. Older versions of CommonMark don't allow "--" anywhere in it.
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#comments
     * @see https://spec.commonmark.org/0.30/#html-comment
     */
    private static function canBeKept(string $text): bool
    {
        return \preg_match('/^-?>|--|-\z/', $text) !== 1;
    }

    private function shouldPreserve(ElementInterface $element): bool
    {
        $preserve = $this->config->getOption('preserve_comments');
        if ($preserve === true) {
            return true;
        }

        if (\is_array($preserve)) {
            $value = \trim($element->getValue());

            return \in_array($value, $preserve, true);
        }

        return false;
    }
}
