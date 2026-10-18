<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Coerce;
use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\LinkSyntax;
use League\HTMLToMarkdown\PreConverterInterface;
use League\HTMLToMarkdown\RawHtml;

class LinkConverter implements ConverterInterface, ConfigurationAwareInterface, PreConverterInterface
{
    /** @var Configuration */
    protected $config;

    /**
     * Each link's text from before its children were converted, or null where it has more than text in it
     *
     * @var array<int, string|null>
     */
    private $labels = [];

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    public function preConvert(ElementInterface $element): void
    {
        // A removed link isn't converted, so its label would never be taken back off
        if (\in_array('a', \explode(' ', Coerce::toString($this->config->getOption('remove_nodes') ?? '')), true)) {
            return;
        }

        // Otherwise one which failed to be converted would leave its label behind
        if (! $element->isDescendantOf('a')) {
            $this->labels = [];
        }

        foreach ($element->getChildren() as $child) {
            if (! $child->isText()) {
                $this->labels[] = null;

                return;
            }
        }

        $this->labels[] = \trim($element->getValue(), "\t\n\r\0\x0B");
    }

    public function convert(ElementInterface $element): string
    {
        $label = \array_pop($this->labels);

        $href  = LinkSyntax::escapeDestination($element->getAttribute('href'));
        $title = LinkSyntax::escapeText($element->getAttribute('title'));
        $text  = \trim($element->getValue(), "\t\n\r\0\x0B");

        if ($title !== '') {
            $markdown = '[' . $text . '](' . $href . ' "' . $title . '")';
        } elseif ($label !== null && $this->isSameUrl($href, $label) && $this->isValidAutolink($href)) {
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

    private function isSameUrl(string $href, string $label): bool
    {
        // The label may show characters which are percent-encoded in the href, such as a `/` for `%2F`
        return \rawurldecode($href) === \rawurldecode($label);
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
