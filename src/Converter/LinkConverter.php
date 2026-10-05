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
        } elseif ($href === $text && $this->isValidAutolink($href)) {
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

    private function isValidAutolink(string $href): bool
    {
        $useAutolinks = $this->config->getOption('use_autolinks');

        return $useAutolinks && (\preg_match('/^[A-Za-z][A-Za-z0-9.+-]{1,31}:[^<>\x00-\x20]*/i', $href) === 1);
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
