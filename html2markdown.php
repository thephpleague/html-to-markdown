<?php
#
# html2markdown  -  An HTML-to-markdown conversion tool for PHP
# Copyright (c) 2011 Nick Cernis | @nickcernis | http://modnerd.com
# 
# Version 1.0
#
# Licensed under The MIT license 
# http://www.opensource.org/licenses/mit-license.php
#
# Requires PHP 5
#

## Settings ##

# Change to "ATX" to output H1 and H2 headers as #Header1 and ##Header2
# See http://daringfireball.net/projects/markdown/syntax#header
if (!defined('HTML2MD_HEADER_STYLE')) define('HTML2MD_HEADER_STYLE', 'SETEX');

# Change to false to show warnings when loading malformed HTML/unsupported tags
if (!defined('HTML2MD_SUPPRESS_ERRORS')) define('HTML2MD_SUPPRESS_ERRORS', true);

if (!function_exists('html2markdown')) {
	function html2markdown($html) {
		$parser = new HTML_Parser($html);
		return $parser->get_markdown();
	}
}

class HTML_Parser
{

	public $doc;
	//public $html;

	public function __construct($html)
	{	
		
		$html = preg_replace('~>\s+<~', '><', $html); # Strip white space between tags to ensure uniform output	
		
		$this->doc = new DOMDocument();

		if (HTML2MD_SUPPRESS_ERRORS)
			libxml_use_internal_errors(true); # Suppress conversion errors (from http://bit.ly/pCCRSX )
		
		$this->doc->loadHTML('<?xml encoding="UTF-8">'.$html);	# Hack to load utf-8 HTML (from http://bit.ly/pVDyCt )
		$this->doc->encoding = 'UTF-8';

		if (HTML2MD_SUPPRESS_ERRORS)
			libxml_clear_errors();

	}



	public function get_markdown()
	{
		
		# Use the body tag as our root element
		$body = $this->doc->getElementsByTagName("body")->item(0);
		
		# For each element inside the body, find child and grandchild
		# elements and convert those to markdown #text nodes, starting
		# with the innermost element ($grandchild_node) and working
		# towards the outermost element ($node).
				
		$nodes = $body->childNodes;
		$total = $nodes->length;
		
		for ($i = 0; $i < $total; $i++)
		{
			
			$node 			= $nodes->item($i);
			$child_nodes 	= $node->childNodes;
			$total_children = $child_nodes->length;
			
			
			for ($j = 0; $j < $total_children; $j++)
			{
				
				$child_node			= $child_nodes->item($j);
				$grandchild_nodes	= $child_node->childNodes;
				$grandchild_total	= $grandchild_nodes->length;
				
				if ($child_node->nodeName != "code"){ #don't convert tags inside code blocks
					
					for ($k = 0; $k < $grandchild_total; $k++)
					{
						$grandchild_node = $grandchild_nodes->item($k);
						$this->convert_to_markdown($grandchild_node);
					}
				
				}
				
				$this->convert_to_markdown($child_node);
	
			}
	
			$this->convert_to_markdown($node);
	
		}
	
	
		# The DOMDocument represented by $doc now consists of #text nodes, each containing a markdown
		# version of the original DOM node created by convert_to_markdown().
	
		# Return the <body> contents of $doc, first stripping html and body tags, the DOCTYPE 
		# and XML encoding lines, then converting entities such as &amp; back to &.
	
		$markdown = $this->doc->saveHTML();
		$markdown = str_replace(array('<html>','</html>','<body>','</body>'), array('','','',''), $markdown);
		$markdown = preg_replace("/<!DOCTYPE [^>]+>/", "", $markdown);
		$markdown = str_replace("<?xml encoding=\"UTF-8\">", "", $markdown);
		$markdown = html_entity_decode($markdown, ENT_QUOTES, 'UTF-8');
	
		return $markdown;
	
	}
	
	
	# Convert the supplied element into it's markdown equivalent,
	# then swap the original element in the DOM with the markdown
	# version as a #text node. This converts the HTML $doc into
	# markdown while retaining the nesting and order of all tags.
	
	private function convert_to_markdown($node)	
	{
	
		$tag 	= $node->nodeName;	#the type of element, e.g. h1
		$value 	= $node->nodeValue;	#the value of that element, e.g. The Title
	
		switch ($tag)
		{
			case "p":
			case "pre":			
				$markdown = $value."\r\n\r\n";
				break;
			case "h1":
				$markdown = $this->convert_header("h1", $value);
				break;
			case "h2":
				$markdown = $this->convert_header("h2", $value);
				break;
			case "h3":
				$markdown = "### ".$value."\r\n\r\n";
				break;
			case "h4":
				$markdown = "#### ".$value."\r\n\r\n";
				break;
			case "h5":
				$markdown = "##### ".$value."\r\n\r\n";
				break;
			case "h6":
				$markdown = "###### ".$value."\r\n\r\n";
				break;
			case "em":
			case "i":
				$markdown = "*".$value."*";
				break;
			case "strong":
			case "b":
				$markdown = "**".$value."**";
				break;
			case "hr":
				$markdown = "- - - - - -\r\n\r\n";
				break;
			case "br":
				$markdown = "  \r\n";
				break;
			case "blockquote":
				$markdown =  $this->convert_blockquote($node);
				break;			
			case "code":
				$markdown = $this->convert_code($node);
				break;
			case "ol":
			case "ul":
				$markdown = $value."\r\n";
				break;
			case "li":				
				$markdown = $this->convert_list($node);
				break;
			case "img":
				$markdown = $this->convert_image($node);
				break;
			case "a":
				$markdown = $this->convert_anchor($node);					
				break;
			default:
				# Preserve tags that don't have markdown equivalents, such as <span>
				# and #text nodes on their own, like WordPressÔ [short_tags].
				# C14N() is the XML canonicalization function (undocumented).
				# It returns the full content of the node, including surrounding tags.
				$markdown = $node->C14N();
				
		}
	
		#Create a DOM text node containing the markdown equivalent of the original node
		$markdown_node = $this->doc->createTextNode($markdown);
		
		#Swap the old $node e.g. <h3>Title</h3> with the new $markdown_node e.g. ###Title
		$node->parentNode->replaceChild($markdown_node, $node); 
	
	
	}
	
	
	
	# Converts h1 and h2 headers to markdown-style headers in setex style, 
	# matching the number of underscores with the length of the title.
	#
	#	e.g.	Header 1	Header Two
	#			========	----------
	#
	#	Returns atx headers instead if HTML2MD_HEADER_STYLE is "ATX"
	#
	#	e.g.	#Header 1	##Header Two
	#
	
	private function convert_header($level, $content)
	{
	
		if (HTML2MD_HEADER_STYLE == "SETEX")
		{
			$length = strlen($content);			
			$underline = ($level == "h1") ? "=" : "-";
			$markdown = $content."\r\n".str_repeat($underline, $length)."\r\n\r\n"; #Setex style
			
		} else {
		
			$prefix = ($level == "h1") ? "# " : "## ";
			$markdown = $prefix.$content."\r\n\r\n"; #atx style
			
		}
		
		return $markdown;
		
	}
	
	
	
	# Converts <img /> tags to markdown
	#
	# e.g.		<img src="/path/img.jpg" alt="blah" title="Title" /> 
	# 
	# becomes	![alt text](/path/img.jpg "Title")
	#      
		
	private function convert_image($node)
	{
		
		$src 	= $node->getAttribute('src');
		$alt 	= $node->getAttribute('alt');
		$title 	= $node->getAttribute('title');
		
		if ($title != ""){
			$markdown = '!['.$alt.']('.$src.' "'.$title.'")'; # No newlines added. <img> should be in a block-level element.
		} else {
			$markdown = '!['.$alt.']('.$src.')';			
		}
		
		return $markdown;
	
	}
	
	
	
	# Converts <a> tags to markdown
	#
	# e.g.		<a href="http://modnerd.com" title="Title">Modern Nerd</a> 
	# 
	# becomes	[Modern Nerd](http://modnerd.com "Title")
	#      
		
	private function convert_anchor($node)
	{
		
		$href 	= $node->getAttribute('href');
		$title 	= $node->getAttribute('title');
		$text 	= $node->nodeValue;
		
		if ($title != ""){
			$markdown = '['.$text.']('.$href.' "'.$title.'")';
		} else {
			$markdown = '['.$text.']('.$href.')';
		}
		
		return $markdown;
		
	}
	
	
	
	private function convert_list($node)
	{
		
		#If parent is an ol, use numbers, otherwise, use dashes		
		$list_type 	= $node->parentNode->nodeName;
		$value		= $node->nodeValue;

		if ($list_type == "ul"){
		
			$markdown = "- ".$value."\r\n";

		} else {
			
			$number = $this->get_list_position($node);		
			$markdown = $number.". ".$value."\r\n";						
			
		}
		
		return $markdown;
		
	}
	
	
	
	private function convert_code($node)
	{
	
		# Store the content of the code block in an array, one entry for each line
		
		$code_content = $node->C14N();
		$code_content = str_replace(array("<code>","</code>"), array("",""), $code_content);
		
		$lines = preg_split( '/\r\n|\r|\n/', $code_content );
		$total = count($lines);				
						
		# If there's more than one line of code, prepend each line with four spaces and no backticks.
		if ($total > 1){
									
			# Remove the first and last line if they're empty
			$first_line	= trim($lines[0]);
			$last_line	= trim($lines[$total-1]);
			$first_line = trim($first_line, "&#xD;"); //trim XML style carriage returns too
			$last_line	= trim($last_line, "&#xD;");
			
			if ( empty( $first_line ) )
				array_shift($lines);

			if ( empty( $last_line ) )
				array_pop($lines);
			
			$count = 1;
			foreach($lines as $line){
	
				if ($count == $total){
					$markdown .= "    ".$line."\r\n";
				} else {
					$markdown .= "    ".$line; #final line of the code block; don't add newlines.
				}
				
				$count++;				
			}


		} else { # There's only one line of code. It's a code span, not a block. Just wrap it with backticks.

				$markdown .= "`".$lines[0]."`";
		
		}
		
		return $markdown;
	
	}
	
	
	
	private function convert_blockquote($node)
	{
		
		# Contents should have already been converted to markdown by this point,
		# so we just need to add ">" symbols to each line.
		
		$quote_content = trim($node->nodeValue);
		
		$lines = preg_split( '/\r\n|\r|\n/', $quote_content );
		$lines = array_filter($lines); //strips empty lines		
		
		foreach($lines as $line){	
			$markdown .= "> ".$line."\r\n\r\n";
		}
		
		return $markdown;	
	}
	
	
	
	#
	# Helper methods
	#
		
	# Returns numbered position of an <li> inside an <ol>
	private function get_list_position($node)
	{
		
		# Get all of the li nodes inside the parent
		$list_nodes  = $node->parentNode->childNodes;
		$total_nodes = $list_nodes->length;
		
		$position = 1;
		
		# Loop through all li nodes and find the one we passed
		for ($a = 0; $a < $total_nodes; $a++)
		{
			$current_node = $list_nodes->item($a);
			
			if ($current_node->isSameNode($node))
				$position = $a + 1;
				
		}
		
		return $position;
	
	}


}