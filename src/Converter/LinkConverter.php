<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\LinkSyntax;
use League\HTMLToMarkdown\RawHtml;

class LinkConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration */
    protected $config;

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    public function convert(ElementInterface $element): string
    {
        $href  = LinkSyntax::escapeDestination($element->getAttribute('href'));
        $title = LinkSyntax::escapeText($element->getAttribute('title'));
        $text  = \trim($element->getValue(), "\t\n\r\0\x0B");

        if ($title !== '') {
            $markdown = '[' . $text . '](' . $href . ' "' . $title . '")';
        } elseif (($label = $this->getPlainText($text)) !== null && $this->isSameUrl($href, $label) && $this->isValidAutolink($href)) {
            $markdown = '<' . $href . '>';
        } elseif ($href === 'mailto:' . $text && $this->isValidEmail($text) && \strpbrk($text[0], '!?') === false) {
            $markdown = '<' . $text . '>';
        } else {
            $markdown = '[' . $text . '](' . $href . ')';
        }

        if (! $href) {
            if ($this->shouldStrip()) {
                $markdown = $text;
            } else {
                $markdown = RawHtml::fromElement($element);
            }
        }

        return $markdown;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['a'];
    }

    /**
     * Undoes the escaping of text, or returns null where the Markdown has more than text in it
     */
    private function getPlainText(string $markdown): ?string
    {
        // Text has all of these escaped, and its line breaks collapsed, so any others came from elements inside of the link
        if (\preg_match('~^(?:\\\\[*_\[\]|\\\\#]|[^*_\[\]|\\\\`<>\n])*+\z~u', $markdown) !== 1) {
            return null;
        }

        $text = \preg_replace('~\\\\(.)~u', '$1', $markdown);
        \assert(\is_string($text));

        // Backticks were escaped last, so are undone first
        return \htmlspecialchars_decode(\str_replace('&#96;', '`', $text), \ENT_NOQUOTES);
    }

    private function isSameUrl(string $href, string $label): bool
    {
        // The label may show characters which are percent-encoded in the href, such as a `/` for `%2F`
        return \rawurldecode($href) === \rawurldecode($label);
    }

    private function isValidAutolink(string $href): bool
    {
        $useAutolinks = $this->config->getOption('use_autolinks');

        // A table could escape a `|` as an entity, which isn't decoded in an autolink
        return $useAutolinks && \strpos($href, '|') === false && (\preg_match('/^[A-Za-z][A-Za-z0-9.+-]{1,31}:[^<>\x00-\x20]*/i', $href) === 1);
    }

    private function isValidEmail(string $email): bool
    {
        // Email validation is messy business, but this should cover most cases
        return \filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function shouldStrip(): bool
    {
        return \boolval($this->config->getOption('strip_placeholder_links') ?? false);
    }
}
