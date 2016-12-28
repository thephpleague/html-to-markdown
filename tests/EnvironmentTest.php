<?php

namespace League\HTMLToMarkdown\Test;

use League\HTMLToMarkdown\Environment;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    public function test_creation()
    {
        $environment = Environment::createDefaultEnvironment();

        $this->assertInstanceOf(
            'League\HTMLToMarkdown\Converter\HeaderConverter',
            $environment->getConverterByTag('h3')
        );
        $this->assertInstanceOf(
            'League\HTMLToMarkdown\Converter\ImageConverter',
            $environment->getConverterByTag('img')
        );
    }
}
