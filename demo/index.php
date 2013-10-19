<?php
require_once(dirname(__FILE__) . '/../HTML_To_Markdown.php');

$markdown = '';
$html = ($_POST) ? $_POST["html"] : null;

if (!is_null($html)) {
    if (get_magic_quotes_gpc())
        $html = stripslashes($html);

    $markdown = new HTML_To_Markdown($html);
//    $markdown = new HTML_To_Markdown($html, array('strip_tags' => true));

}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>HTML To Markdown Demo</title>
    <style>
        body {
            font-family: helvetica, arial, sans-serif;
        }
    </style>
</head>

<body>
<div style="width:50%;float:left;">
    <h3>HTML</h3>

    <form method="post" action="">

        <?php if (!is_null($html)): ?>
            <textarea rows="30" style="width:95%" name="html" id="html"><?php echo $html ?></textarea><br/>
        <?php else: ?>
            <textarea rows="30" style="width:95%" name="html" id="html">
<h1>A level one header</h1>

<p>Some paragraph text&#8482; containing &ldquo;UTF-8&rdquo; chars&hellip;</p>

<h2>A longer level two header</h2>

<h3>Here's a <em>level 3</em> title</h3>

<p>Some text containing<br/>a forced break.</p>

<p>Some text containing an
unforced break.</p>

<h2>Blockquotes and horizontal rules</h2>
<blockquote>Here's a blockquote</blockquote>

<hr/>

<blockquote>
    <p>This should have a single arrow.</p>
    <blockquote>
        <p>A blockquote inside a blockquote, with a double arrow, on a new line.</p>
    </blockquote>
</blockquote>

<hr/>

<blockquote>
    <p>A multi-paragraph blockquote.</p>

    <p>Here's the second paragraph. (Should be inside blockquote.)</p>

    <h4>A header inside a blockquote</h4>

    <p><img src="/path/img.jpg" alt="Image in a blockquote" title="Image in a blockquote"/></p>

    <ul>
        <li>List in a blockquote</li>
        <li>Second list item</li>
    </ul>

</blockquote>

<h2>Lists</h2>
<ul>
    <li>An unordered list</li>
    <li>Appears with hyphens</li>
</ul>

<ol>
    <li>An ordered list</li>
    <li>Appears with numbers.</li>
    <li>Automatically indexed.</li>
</ol>

<h2>Links and images</h2>

<p><img src="/path/img.jpg" alt="alt text" title="Title"/></p>

<p>An example of a <a href="http://url.com/" title="Title">link.</a></p>

<p>An image inside a link:<br/>
    <a href="http://url.com/" title="Title"><img src="/path/img.jpg" alt="alt text" title="Title"/></a>
</p>

<h2>Inline elements</h2>

<p><em>This text is in italics.</em></p>

<p><strong>This text is in bold.</strong></p>

<p>An <em>em</em> and a <strong>strong</strong> inside a paragraph.</p>

<p>A <em><span>span</span> inside</em> an em.</p>

<p>A <em><strong>strong</strong> inside</em> an em.</p>

<p>A <span><strong>strong</strong> inside</span> a span.</p>


<h2>Code blocks and spans</h2>

<p><code>
#sidebar h1 {
    font-size: 1.5em;
    font-weight: bold;
}
</code></p>

<p><code>A <strong>code</strong> span</code></p>

<h2>Bugs (tests from here on fail)</h2>

<h4>A list with multiple paragraphs</h4>
<ul>
    <li><p>A list item.</p>

        <p>With multiple paragraphs.</p></li>
    <li>List item two.</li>
</ul>

<h4>Mixed ordered and unordered nested lists</h4>

<ul>
    <li>List 1
        <ul>
            <li>List 2</li>
        </ul>
    </li>
    <li>List 1b
        <ol>
            <li>List 3a</li>
            <li>List 3b
                <ul>
                    <li>List 4</li>
                </ul>
            </li>
            <li>List 3c</li>
        </ol>
    </li>
    <li>List 1c</li>
</ul>
            </textarea>
        <?php endif; ?>
        <input type="submit" value="Convert HTML to Markdown >>" name="submit">
    </form>

</div>

<div style="width:50%;float:right;">
    <h3>Markdown</h3>
    <textarea rows="30" style="width:95%; font-family:monospace;" name="markdown" id="markdown"
              style="font-family:monospace"><?php
        echo htmlspecialchars($markdown); ?></textarea><br/>
</div>

<div style="clear:both;"></div>

<p>
    <small><a href="https://github.com/nickcernis/html-to-markdown">HTML To Markdown</a> is a helper class to convert HTML into Markdown with PHP.</small>
</p>

</body>
</html>