<?php

namespace League\HTMLToMarkdown\Test;

use League\HTMLToMarkdown\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
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
