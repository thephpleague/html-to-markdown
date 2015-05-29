<?php

namespace HTMLToMarkdown;

use HTMLToMarkdown\Converter\BlockquoteConverter;
use HTMLToMarkdown\Converter\CommentConverter;
use HTMLToMarkdown\Converter\ConverterInterface;
use HTMLToMarkdown\Converter\DefaultConverter;
use HTMLToMarkdown\Converter\DivConverter;
use HTMLToMarkdown\Converter\EmphasisConverter;
use HTMLToMarkdown\Converter\HardBreakConverter;
use HTMLToMarkdown\Converter\HeaderConverter;
use HTMLToMarkdown\Converter\HorizontalRuleConverter;
use HTMLToMarkdown\Converter\ImageConverter;
use HTMLToMarkdown\Converter\LinkConverter;
use HTMLToMarkdown\Converter\ListBlockConverter;
use HTMLToMarkdown\Converter\ListItemConverter;
use HTMLToMarkdown\Converter\ParagraphConverter;
use HTMLToMarkdown\Converter\PreformattedConverter;
use HTMLToMarkdown\Converter\TextConverter;

class Environment
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
    protected function addConverter(ConverterInterface $converter)
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
