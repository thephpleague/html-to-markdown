<?php

declare(strict_types=1);

namespace League\HTMLToMarkdown\Test;

use PHPUnit\Framework\TestCase;
use mikehaertl\shellcommand\Command;

class BinTest extends TestCase
{
    /**
     * Tests the behavior of not providing any HTML input
     */
    public function testNoArgsOrStdin(): void
    {
        $cmd = new Command($this->getPathToCommonmark());
        $cmd->execute();

        $this->assertEquals(1, $cmd->getExitCode());
        $this->assertEmpty($cmd->getOutput());
        $this->assertStringContainsString('Usage:', $cmd->getError());
    }

    /**
     * Tests the -h flag
     */
    public function testHelpShortFlag(): void
    {
        $cmd = new Command($this->getPathToCommonmark());
        $cmd->addArg('-h');
        $cmd->execute();

        $this->assertEquals(0, $cmd->getExitCode());
        $this->assertStringContainsString('Usage:', $cmd->getOutput());
    }

    /**
     * Tests the --help option
     */
    public function testHelpOption(): void
    {
        $cmd = new Command($this->getPathToCommonmark());
        $cmd->addArg('--help');
        $cmd->execute();

        $this->assertEquals(0, $cmd->getExitCode());
        $this->assertStringContainsString('Usage:', $cmd->getOutput());
    }

    /**
     * Tests the behavior of using unknown options
     */
    public function testUnknownOption(): void
    {
        $cmd = new Command($this->getPathToCommonmark());
        $cmd->addArg('--foo');
        $cmd->execute();

        $this->assertEquals(1, $cmd->getExitCode());
        $this->assertStringContainsString('Unknown option', $cmd->getError());
    }

    /**
     * Tests converting a file by filename
     */
    public function testFileArgument(): void
    {
        $cmd = new Command($this->getPathToCommonmark());
        $cmd->addArg($this->getPathToData('header.html'));
        $cmd->execute();

        $this->assertEquals(0, $cmd->getExitCode());
        $expectedContents = \trim(\file_get_contents($this->getPathToData('header.md')));
        $this->assertEquals($expectedContents, \trim($cmd->getOutput()));
    }

    /**
     * Tests converting HTML from STDIN
     */
    public function testStdin(): void
    {
        $cmd = new Command(\sprintf('cat %s | %s ', $this->getPathToData('header.html'), $this->getPathToCommonmark()));
        $cmd->execute();

        $this->assertEquals(0, $cmd->getExitCode());
        $expectedContents = \trim(\file_get_contents($this->getPathToData('header.md')));
        $this->assertEquals($expectedContents, \trim($cmd->getOutput()));
    }

    /**
     * Returns the full path the html-to-markdown "binary"
     */
    protected function getPathToCommonmark(): string
    {
        return \realpath(__DIR__ . '/../bin/html-to-markdown');
    }

    /**
     * Returns the full path to the test data file
     */
    protected function getPathToData(string $file): string
    {
        return \realpath(__DIR__ . '/data/' . $file);
    }
}
