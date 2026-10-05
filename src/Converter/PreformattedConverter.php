<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Converter;

use League\HTMLToMarkdown\Backticks;
use League\HTMLToMarkdown\Configuration;
use League\HTMLToMarkdown\ConfigurationAwareInterface;
use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\RawHtml;

class PreformattedConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration|null */
    protected $config;

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    public function convert(ElementInterface $element): string
    {
        if (RawHtml::cannotBeFenced($element, $this->config)) {
            return RawHtml::fromCode($this->getContent($element), 'pre');
        }

        return $this->convertToFencedCodeBlock($element) . "\n\n";
    }

    private function getContent(ElementInterface $element): string
    {
        $preContent = \html_entity_decode($element->getChildrenAsString());
        $preContent = \preg_replace('/<pre\b[^>]*>/', '', $preContent);
        \assert($preContent !== null);

        return \str_replace('</pre>', '', $preContent);
    }

    private function convertToFencedCodeBlock(ElementInterface $element): string
    {
        $preContent = $this->getContent($element);

        // A nested code tag has already been converted into a fenced code block, so there's nothing more to convert
        $trimmedContent = \trim($preContent);
        if ($this->isFencedCodeBlock($trimmedContent)) {
            return $trimmedContent;
        }

        // If the execution reaches this point it means it's just a pre tag, with no code tag nested

        // Empty lines are a special case
        if ($preContent === '') {
            return "```\n```";
        }

        // Normalizing new lines
        $preContent = \preg_replace('/\r\n|\r|\n/', "\n", $preContent);
        \assert(\is_string($preContent));

        // Ensure there's a newline at the end
        if (\strrpos($preContent, "\n") !== \strlen($preContent) - \strlen("\n")) {
            $preContent .= "\n";
        }

        $fence = Backticks::fenceFor($preContent);

        return $fence . "\n" . $preContent . $fence;
    }

    private function isFencedCodeBlock(string $markdown): bool
    {
        if (\preg_match('/^(`{3,})[^`\n]*\n(.*\n)?\1\z/s', $markdown, $matches) !== 1) {
            return false;
        }

        // Anything else containing a fence of its own would end the block early
        return Backticks::longestRun($matches[2] ?? '') < \strlen($matches[1]);
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['pre'];
    }
}
