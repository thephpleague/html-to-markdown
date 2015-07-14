<?php

namespace League\HTMLToMarkdown;

interface ConfigurationAwareInterface
{
    /**
     * @param Configuration $config
     */
    public function setConfig(Configuration $config);
}
