<?php
include 'vendor/autoload.php';

use League\HTMLToMarkdown\HtmlConverter;

$converter = new HtmlConverter();

$html = '<pre><code class="language-ruby testing">def foo(x)
  return 3
end
</code></pre>';
$markdown = $converter->convert($html);

null;