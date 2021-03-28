<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Test;

use League\HTMLToMarkdown\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testCreation(): void
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
