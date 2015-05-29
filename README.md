HTML To Markdown for PHP
========================

A helper class that converts HTML to [Markdown](http://daringfireball.net/projects/markdown/) for your sanity and convenience.

[![Build Status](https://travis-ci.org/nickcernis/html-to-markdown.png?branch=master)](https://travis-ci.org/nickcernis/html-to-markdown)

**Version**: 2.2.2  
**Requires**: PHP 5.3+  
**Author**: [@nickcernis](http://twitter.com/nickcernis)  
**License**: [MIT](http://www.opensource.org/licenses/mit-license.php)  

### Why convert HTML to Markdown?

*"What alchemy is this?"* you mutter. *"I can see why you'd convert [Markdown to HTML](http://michelf.com/projects/php-markdown/),"* you continue, already labouring the question somewhat, *"but why go the other way?"*

Typically you would convert HTML to Markdown if:

1. You have an existing HTML document that needs to be edited by people with good taste.
2. You want to store new content in HTML format but edit it as Markdown.
3. You want to convert HTML email to plain text email. 
4. You know a guy who's been converting HTML to Markdown for years, and now he can speak Elvish. You'd quite like to be able to speak Elvish.
5. You just really like Markdown.

### How to use it

Either include HTML_To_Markdown.php directly:

    require_once( dirname( __FILE__) . '/HTML_To_Markdown.php' );

Or, require the library in your composer.json:

    {
        "require": {
            "nickcernis/html-to-markdown": "dev-master"
        }
    }

Then `composer install` and add `require 'vendor/autoload.php';` to the top of your script.

Next, create a new HTML_To_Markdown instance, passing in your valid HTML code:

    $html = "<h3>Quick, to the Batpoles!</h3>";
    $markdown = new HTML_To_Markdown($html);

The `$markdown` object now contains the Markdown version of your HTML. Use it like a string:

    echo $markdown; // ==> ### Quick, to the Batpoles!

Or access the Markdown output directly:

    $string = $markdown->output();

The included `demo` directory contains an HTML->Markdown conversion form to try out.

### Conversion options

By default, HTML To Markdown preserves HTML tags without Markdown equivalents, like `<span>` and `<div>`.

To strip HTML tags that don't have a Markdown equivalent while preserving the content inside them, set `strip_tags` to true, like this:

    $html = '<span>Turnips!</span>';
    $markdown = new HTML_To_Markdown($html, array('strip_tags' => true)); // $markdown now contains "Turnips!"    

Or more explicitly, like this:

    $html = '<span>Turnips!</span>';
    $markdown = new HTML_To_Markdown();
    $markdown->set_option('strip_tags', true);
    $markdown->convert($html); // $markdown now contains "Turnips!"

Note that only the tags themselves are stripped, not the content they hold.

To strip tags and their content, pass a space-separated list of tags in `remove_nodes`, like this:

    $html = '<span>Turnips!</span><div>Monkeys!</div>';
    $markdown = new HTML_To_Markdown($html, array('remove_nodes' => 'span div')); // $markdown now contains ""

### Style options

Bold and italic tags are converted using the asterisk syntax by default. Change this to the underlined syntax using the `bold_style` and `italic_style` options.

    $html = '<em>Italic</em> and a <strong>bold</strong>';
    $markdown = new HTML_To_Markdown();
    $markdown->set_option('italic_style', '_');
    $markdown->set_option('bold_style', '__');
    $markdown->convert($html); // $markdown now contains "_Italic_ and a __bold__"

### Limitations

- Markdown Extra, MultiMarkdown and other variants aren't supported – just Markdown.

### Known issues

- Nested lists and lists containing multiple paragraphs aren't converted correctly.
- Lists inside blockquotes aren't converted correctly.
- Any reported [open issues here](https://github.com/nickcernis/html-to-markdown/issues?state=open).

[Report your issue or request a feature here.](https://github.com/nickcernis/html2markdown/issues/new) Issues with patches or failing tests are especially welcome.

### Style notes

- Setext (underlined) headers are the default for H1 and H2. If you prefer the ATX style for H1 and H2 (# Header 1 and ## Header 2), set `header_style` to 'atx' in the options array when you instantiate the object:

    `$markdown = new HTML_To_Markdown( $html, array('header_style'=>'atx') );`

     Headers of H3 priority and lower always use atx style.

- Links and images are referenced inline. Footnote references (where image src and anchor href attributes are listed in the footnotes) are not used. 
- Blockquotes aren't line wrapped – it makes the converted Markdown easier to edit.

### Dependencies

HTML To Markdown requires PHP's [xml](http://www.php.net/manual/en/xml.installation.php), [lib-xml](http://www.php.net/manual/en/libxml.installation.php), and [dom](http://www.php.net/manual/en/dom.installation.php) extensions, all of which are enabled by default on most distributions.

Errors such as "Fatal error: Class 'DOMDocument' not found" on distributions such as CentOS that disable PHP's xml extension can be resolved by installing php-xml.

### Architecture notes

HTML To Markdown is a single file that uses native DOM manipulation libraries (DOMDocument), not regex voodoo, to convert code.

### Contributors

Many thanks to all [contributors](https://github.com/nickcernis/html2markdown/graphs/contributors) so far. Further improvements and feature suggestions are very welcome.

### How it works

HTML To Markdown creates a DOMDocument from the supplied HTML, walks through the tree, and converts each node to a text node containing the equivalent markdown, starting from the most deeply nested node and working inwards towards the root node.

### To-do

- Support for nested lists and lists inside blockquotes.
- Offer an option to preserve tags as HTML if they contain attributes that can't be represented with Markdown (e.g. `style`).

### Trying to convert Markdown to HTML?

Use [PHP Markdown](http://michelf.com/projects/php-markdown/) from Michel Fortin. No guarantees about the Elvish, though.

