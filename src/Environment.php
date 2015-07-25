<?php

namespace League\HTMLToMarkdown;

use League\HTMLToMarkdown\Converter\BlockquoteConverter;
use League\HTMLToMarkdown\Converter\CommentConverter;
use League\HTMLToMarkdown\Converter\ConverterInterface;
use League\HTMLToMarkdown\Converter\DefaultConverter;
use League\HTMLToMarkdown\Converter\DivConverter;
use League\HTMLToMarkdown\Converter\EmphasisConverter;
use League\HTMLToMarkdown\Converter\HardBreakConverter;
use League\HTMLToMarkdown\Converter\HeaderConverter;
use League\HTMLToMarkdown\Converter\HorizontalRuleConverter;
use League\HTMLToMarkdown\Converter\ImageConverter;
use League\HTMLToMarkdown\Converter\LinkConverter;
use League\HTMLToMarkdown\Converter\ListBlockConverter;
use League\HTMLToMarkdown\Converter\ListItemConverter;
use League\HTMLToMarkdown\Converter\ParagraphConverter;
use League\HTMLToMarkdown\Converter\PreformattedConverter;
use League\HTMLToMarkdown\Converter\TextConverter;

final class Environment
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var ConverterInterface[]
     */
    protected $converters = array();

    public function __construct(array $config = array())
    {
        $this->config = new Configuration($config);
        $this->addConverter(new DefaultConverter());
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param ConverterInterface $converter
     */
    public function addConverter(ConverterInterface $converter)
    {
        if ($converter instanceof ConfigurationAwareInterface) {
            $converter->setConfig($this->config);
        }

        foreach ($converter->getSupportedTags() as $tag) {
            $this->converters[$tag] = $converter;
        }
    }

    /**
     * @param string $tag
     *
     * @return ConverterInterface
     */
    public function getConverterByTag($tag)
    {
        if (isset($this->converters[$tag])) {
            return $this->converters[$tag];
        }

        return $this->converters[DefaultConverter::DEFAULT_CONVERTER];
    }

    /**
     * @param array $config
     *
     * @return Environment
     */
    public static function createDefaultEnvironment(array $config = array())
    {
        $environment = new static($config);

        $environment->addConverter(new BlockquoteConverter());
        $environment->addConverter(new CommentConverter());
        $environment->addConverter(new DivConverter());
        $environment->addConverter(new EmphasisConverter());
        $environment->addConverter(new HardBreakConverter());
        $environment->addConverter(new HeaderConverter());
        $environment->addConverter(new HorizontalRuleConverter());
        $environment->addConverter(new ImageConverter());
        $environment->addConverter(new LinkConverter());
        $environment->addConverter(new ListBlockConverter());
        $environment->addConverter(new ListItemConverter());
        $environment->addConverter(new ParagraphConverter());
        $environment->addConverter(new PreformattedConverter());
        $environment->addConverter(new TextConverter());

        return $environment;
    }
}
