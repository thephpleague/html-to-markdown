HTML To Markdown for PHP
========================

A helper class that converts HTML to [Markdown](http://daringfireball.net/projects/markdown/) for your sanity and convenience.

**Version**: 2.0  
**Requires**: PHP 5.2+  
**Author**: [@nickcernis](http://twitter.com/nickcernis)   
**License**: [MIT](http://www.opensource.org/licenses/mit-license.php)  

### Why convert HTML to Markdown?
*"What alchemy is this?"* you mutter. *"I can see why you'd convert [Markdown to HTML](http://michelf.com/projects/php-markdown/),"* you continue, already labouring the point somewhat, *"but why go the other way?"*

Typically you would convert HTML to Markdown if:

1. You have an existing HTML document that needs to be edited by people with good taste.
2. You want to store new content in HTML format but write and edit it as Markdown. (Sometimes, converting the HTML from the database to Markdown before displaying it in a textarea makes more sense than storing it as Markdown and converting it to HTML when displaying it on the front end. Or, worse, storing it as Markdown *and* HTML and updating both versions every time the content changes.)
3. You know a guy who's been converting HTML to Markdown for years, and now he can speak Elvish. You'd quite like to be able to speak Elvish.
4. You just really like Markdown.

### How to use it
First, you must create the universe. But it gets easier after that.

Either include HTML_To_Markdown.php directly:

    require_once( dirname( __FILE__) . '/HTML_To_Markdown.php' );

Or, require the library in your composer.json:

    {
        "require": {
            "nickcernis/html-to-markdown": "dev-master"
        }
    }

Then `composer install` and add `require 'vendor/autoload.php';` to top of your script.

Next, create a new HTML_To_Markdown instance, passing in your valid HTML code:

    $html = "<h3>Quick, to the Batpoles!</h3>";
    $markdown = new HTML_To_Markdown($html);

The `$markdown` object now contains the Markdown version of your HTML. Use it like a string:

    echo $markdown;
    // ==> ### Quick, to the Batpoles!

Or access the Markdown output directly:

    $string = $markdown->output();

The included `demo` directory contains an HTML->Markdown conversion form to try out.

### Limitations

- Markdown Extra, MultiMarkdown and other variants aren't supported – just Markdown.

### Known issues

- Nested lists and lists containing multiple paragraphs aren't converted correctly.
- Lists inside blockquotes aren't converted correctly.

[Report your issue or request a feature here.](https://github.com/nickcernis/html2markdown/issues/new) Issues with patches are especially welcome.

### Style notes

- Setext (underlined) headers are the default for H1 and H2. If you prefer the ATX style for H1 and H2 (# Header 1 and ## Header 2), set `header_style` to 'atx' in the options array when you instantiate the object:

    `$markdown = new HTML_To_Markdown( $html, array('header_style'=>'atx') );`

     Headers of H3 priority and lower always use atx style.

- Links and images are referenced inline. Footnote references (where image src and anchor href attributes are listed in the footnotes) are not used. 
- Blockquotes aren't line wrapped – it makes the converted Markdown easier to edit.

### Architecture notes
HTML To Markdown is a single file with no dependencies except for PHP 5.2. It uses native DOM manipulation libraries (DOMDocument), not regex voodoo, to convert code.

### Contributors

Many thanks to all [contributors](https://github.com/nickcernis/html2markdown/graphs/contributors) so far. You'd be very welcome to contribute improvements and feature suggestions as well.

### How it works
HTML To Markdown creates a DOMDocument from the supplied HTML, walks through the tree, and converts each node to a text node containing the equivalent markdown, starting from the most deeply nested node and working inwards towards the root node.

### To-do
- Support for nested lists and lists inside blockquotes.
- Preserve tags as HTML if they contain attributes that can't be represented with Markdown (e.g. `style`).

### Trying to convert Markdown to HTML?

Use [PHP Markdown](http://michelf.com/projects/php-markdown/) from Michel Fortin. No guarantees about the Elvish, though.

