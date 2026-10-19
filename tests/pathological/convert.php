#!/usr/bin/env php
<?php

declare(strict_types=1);

use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;

require_once __DIR__ . '/../../vendor/autoload.php';

ini_set('display_errors', 'stderr');

$html = stream_get_contents(STDIN);
if ($html === false || $html === '') {
    fwrite(STDERR, "No input provided\n");
    exit(1);
}

$options = json_decode($argv[1] ?? '[]', true);

$converter = new HtmlConverter(is_array($options) ? $options : []);
if (($argv[2] ?? null) === 'table') {
    $converter->getEnvironment()->addConverter(new TableConverter());
}

echo $converter->convert($html);
