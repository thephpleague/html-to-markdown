<?php

namespace League\HTMLToMarkdown;

class Configuration
{
    protected $config;

    /**
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->config = $config;

        $this->checkForDeprecatedOptions($config);
    }

    /**
     * @param array $config
     */
    public function merge(array $config = array())
    {
        $this->checkForDeprecatedOptions($config);
        $this->config = array_replace_recursive($this->config, $config);
    }

    /**
     * @param array $config
     */
    public function replace(array $config = array())
    {
        $this->checkForDeprecatedOptions($config);
        $this->config = $config;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setOption($key, $value)
    {
        $this->checkForDeprecatedOptions(array($key => $value));
        $this->config[$key] = $value;
    }

    /**
     * @param string|null $key
     * @param mixed|null  $default
     *
     * @return mixed|null
     */
    public function getOption($key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        if (!isset($this->config[$key])) {
            return $default;
        }

        return $this->config[$key];
    }

    private function checkForDeprecatedOptions(array $config)
    {
        foreach ($config as $key => $value) {
            if ($key === 'bold_style' && $value !== '**') {
                @trigger_error('Customizing the bold_style option is deprecated and may be removed in the next major version', E_USER_DEPRECATED);
            } elseif ($key === 'italic_style' && $value !== '*') {
                @trigger_error('Customizing the italic_style option is deprecated and may be removed in the next major version', E_USER_DEPRECATED);
            }
        }
    }
}
