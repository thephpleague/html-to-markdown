#!/usr/bin/env php
<?php

declare(strict_types=1);

use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

require_once __DIR__ . '/../../vendor/autoload.php';

// Each size should be at least 4x the one before it, as smaller steps leave too little room
// between the linear time allowed and the quadratic time this is meant to catch
$cases = [
    'Backslashes before a code span' => [
        // The comments keep each backslash in a text node of its own
        'sizes' => [10000, 40000, 160000],
        'input' => static function (int $n): string {
            return '<p>' . str_repeat('\\<!---->', $n) . '<code>x</code></p>';
        },
    ],
    'Large sibling before a list item' => [
        // The sibling has to be much larger than the number of elements after it
        'sizes' => [5000, 20000, 80000],
        'input' => static function (int $n): string {
            return '<ul>' . str_repeat('x', 20 * $n) . '<li>' . str_repeat('<p>a</p>', $n) . '</li></ul>';
        },
    ],
    'Misplaced table cells' => [
        'extension' => 'table',
        'sizes' => [5000, 20000, 80000],
        'input' => static function (int $n): string {
            return str_repeat('<td>a</td><th>b</th>', $n);
        },
    ],
    'Comments before the root element' => [
        'sizes' => [5000, 20000, 80000],
        'input' => static function (int $n): string {
            return str_repeat('<!--x-->', $n) . '<p>a</p>';
        },
        'expected' => static function (int $n): string {
            return 'a';
        },
    ],
    'Processing instructions' => [
        'sizes' => [5000, 20000, 80000],
        'input' => static function (int $n): string {
            return '<p>' . str_repeat('<?pi x?>', $n) . '</p>';
        },
    ],
    'Runs of emphasis broken up by removed ones' => [
        'options' => ['remove_nodes' => 'b'],
        'sizes' => [5000, 20000, 80000],
        'input' => static function (int $n): string {
            return '<p>' . str_repeat('<strong>a</strong><b>b</b><strong></strong>', $n) . '</p>';
        },
    ],
    'Code in emphasis removed from a run' => [
        'options' => ['remove_nodes' => 'b'],
        'sizes' => [5000, 20000, 80000],
        'input' => static function (int $n): string {
            return '<p>' . str_repeat('<strong>a</strong><b><code>x</code></b>', $n) . '</p>';
        },
    ],
    'Whitespace in a preserved comment' => [
        'options' => ['preserve_comments' => true],
        'sizes' => [100000, 400000, 1600000],
        'input' => static function (int $n): string {
            return '<p>a<!--' . str_repeat(' ', $n) . 'x--></p>';
        },
    ],
    'Backslashes before a pipe in a table cell, without the PCRE JIT' => [
        // Without the pipe, PCRE gives up on the cell straight away
        'extension' => 'table',
        'ini' => ['pcre.jit' => '0'],
        'sizes' => [10000, 40000, 160000],
        'input' => static function (int $n): string {
            return '<table><tr><td>' . str_repeat('\\', $n) . 'x|</td></tr></table>';
        },
    ],
];

$isCi = isset($_ENV['CI']) || isset($_SERVER['CI']);

print 'Running ' . count($cases) . " pathological test cases\n\n";

$failures = [];
foreach ($cases as $name => $case) {
    print "\033[1m" . $name . "\033[0m\n";

    $lastRunTime   = null;
    $lastInputSize = null;
    foreach ($case['sizes'] as $size) {
        printf('  - %s: ', number_format($size));

        $input     = $case['input']($size);
        $inputSize = strlen($input);

        if ($lastRunTime === null || $lastInputSize === null) {
            $timeout = 5;
        } else {
            // These should take linear time, with some margin for error (more in CI), but at least 5 seconds and at most 60
            $timeout = ceil($lastRunTime * $inputSize / $lastInputSize * ($isCi ? 2 : 1.5));
            $timeout = max(5, min(60, $timeout));
        }

        $command = [PHP_BINARY, '-d', 'memory_limit=1G'];
        foreach ($case['ini'] ?? [] as $key => $value) {
            $command[] = '-d';
            $command[] = $key . '=' . $value;
        }

        $command[] = 'convert.php';
        $command[] = (string) json_encode($case['options'] ?? []);
        if (isset($case['extension'])) {
            $command[] = $case['extension'];
        }

        $process = new Process($command, __DIR__, null, $input, $timeout);

        $startTime = microtime(true);
        try {
            $result        = $process->run();
            $executionTime = microtime(true) - $startTime;

            if ($lastRunTime !== null) {
                printf("%.3f seconds (%.1fx slower)\n", $executionTime, $executionTime / $lastRunTime);
            } else {
                printf("%.3f seconds\n", $executionTime);
            }

            $lastRunTime = $executionTime;

            if ($result !== 0) {
                printf("    \033[31m%s\033[0m\n", 'Process failed: ' . $process->getErrorOutput());
                $failures[$name] = 'Process failed';
            } elseif (isset($case['expected']) && trim($case['expected']($size)) !== trim($process->getOutput())) {
                printf("    \033[31m%s\033[0m\n", 'Unexpected output');
                $failures[$name] = 'Unexpected output';
            }
        } catch (ProcessTimedOutException $e) {
            printf("\033[31m%s\033[0m\n", 'Max execution time (' . $e->getExceededTimeout() . 's) exceeded');
            $failures[$name] = 'Max execution time exceeded';
        } catch (ProcessSignaledException $e) {
            printf("\033[31m%s\033[0m\n", 'Process signaled: ' . $e->getSignal());
            $failures[$name] = 'Process signaled';
        } finally {
            $lastInputSize = $inputSize;
        }
    }

    print "\n";
}

print count($failures) . ' failure(s) out of ' . count($cases) . " test(s)\n";

exit(count($failures) > 0 ? 1 : 0);
