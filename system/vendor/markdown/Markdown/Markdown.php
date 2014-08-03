<?php
#
# Markdown  -  A text-to-HTML conversion tool for web writers
#
# PHP Markdown  
# Copyright (c) 2004-2014 Michel Fortin  
# <http://michelf.com/projects/php-markdown/>
#
# Original Markdown  
# Copyright (c) 2004-2006 John Gruber  
# <http://daringfireball.net/projects/markdown/>
#
namespace Markdown;

    #
    # Markdown Parser Class
#

class Markdown implements MarkdownInterface {

    ### Version ###

    const  MARKDOWNLIB_VERSION = "1.4.1";

    ### Simple Function Interface ###

    public static function defaultTransform($text) {
        #
        # Initialize the parser and return the result of its transform method.
        # This will work fine for derived classes too.
        #
        # Take parser class on which this function was called.
        $parserClass = \get_called_class();

        # try to take parser from the static parser list
        static $parserList;
        $parser =& $parserList[$parserClass];

        # create the parser it not already set
        if (!$parser) {
            $parser = new $parserClass;
        }

        # Transform text using parser.
        return $parser->transform($text);
    }

    ### Configuration Variables ###

    # Change to ">" for HTML output.
    public $emptyElementSuffix = " />";
    public $tabWidth = 4;

    # Change to `true` to disallow markup or entities.
    public $noMarkup = false;
    public $noEntities = false;

    # Predefined urls and titles for reference links and images.
    public $predefUrls = array();
    public $predefTitles = array();


    ### Parser Implementation ###

    # Regex to match balanced [brackets].
    # Needed to insert a maximum bracked depth while converting to PHP.
    protected $nestedBracketsDepth = 6;
    protected $nestedBracketsRe;

    protected $nestedUrlParenthesisDepth = 4;
    protected $nestedUrlParenthesisRe;

    # Table of hash values for escaped characters:
    protected $escapeChars = '\`*_{}[]()>#+-.!';
    protected $escapeCharsRe;


    public function __construct() {
        #
        # Constructor function. Initialize appropriate member variables.
        #
        $this->initDetab();
        $this->prepareItalicsAndBold();

        $this->nestedBracketsRe =
            str_repeat('(?>[^\[\]]+|\[', $this->nestedBracketsDepth) .
            str_repeat('\])*', $this->nestedBracketsDepth);

        $this->nestedUrlParenthesisRe =
            str_repeat('(?>[^()\s]+|\(', $this->nestedUrlParenthesisDepth) .
            str_repeat('(?>\)))*', $this->nestedUrlParenthesisDepth);

        $this->escapeCharsRe = '[' . preg_quote($this->escapeChars) . ']';

        # Sort document, block, and span gamut in ascendent priority order.
        asort($this->documentGamut);
        asort($this->blockGamut);
        asort($this->spanGamut);
    }


    # Internal hashes used during transformation.
    protected $urls = array();
    protected $titles = array();
    protected $htmlHashes = array();

    # Status flag to avoid invalid nesting.
    protected $inAnchor = false;


    protected function setup() {
        #
        # Called before the transformation process starts to setup parser
        # states.
        #
        # Clear global hashes.
        $this->urls = $this->predefUrls;
        $this->titles = $this->predefTitles;
        $this->htmlHashes = array();

        $this->inAnchor = false;
    }

    protected function teardown() {
        #
        # Called after the transformation process to clear any variable
        # which may be taking up memory unnecessarly.
        #
        $this->urls = array();
        $this->titles = array();
        $this->htmlHashes = array();
    }


    public function transform($text) {
        #
        # Main function. Performs some preprocessing on the input text
        # and pass it through the document gamut.
        #
        $this->setup();

        # Remove UTF-8 BOM and marker character in input, if present.
        $text = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $text);

        # Standardize line endings:
        #   DOS to Unix and Mac to Unix
        $text = preg_replace('{\r\n?}', "\n", $text);

        # Make sure $text ends with a couple of newlines:
        $text .= "\n\n";

        # Convert all tabs to spaces.
        $text = $this->detab($text);

        # Turn block-level HTML blocks into hash entries
        $text = $this->hashHTMLBlocks($text);

        # Strip any lines consisting only of spaces and tabs.
        # This makes subsequent regexen easier to write, because we can
        # match consecutive blank lines with /\n+/ instead of something
        # contorted like /[ ]*\n+/ .
        $text = preg_replace('/^[ ]+$/m', '', $text);

        # Run document gamut methods.
        foreach ($this->documentGamut as $method => $priority) {
            $text = $this->$method($text);
        }

        $this->teardown();

        return $text . "\n";
    }

    protected $documentGamut = array(
        # Strip link definitions, store in hashes.
        "stripLinkDefinitions" => 20,
        "runBasicBlockGamut" => 30,
    );


    protected function stripLinkDefinitions($text) {
        #
        # Strips link definitions from text, stores the URLs and titles in
        # hash references.
        #
        $lessThanTab = $this->tabWidth - 1;

        # Link defs are in the form: ^[id]: url "optional title"
        $text = preg_replace_callback(
            '{
                                        ^[ ]{0,' . $lessThanTab . '}\[(.+)\][ ]?:	# id = $1
							  [ ]*
							  \n?				# maybe *one* newline
							  [ ]*
							(?:
							  <(.+?)>			# url = $2
							|
							  (\S+?)			# url = $3
							)
							  [ ]*
							  \n?				# maybe one newline
							  [ ]*
							(?:
								(?<=\s)			# lookbehind for whitespace
								["(]
								(.*?)			# title = $4
								[")]
								[ ]*
							)?	# title is optional
							(?:\n+|\Z)
			}xm',
            array($this, 'stripLinkDefinitionsCallback'),
            $text
        );
        return $text;
    }

    protected function stripLinkDefinitionsCallback($matches) {
        $linkId = strtolower($matches[1]);
        $url = $matches[2] == '' ? $matches[3] : $matches[2];
        $this->urls[$linkId] = $url;
        $this->titles[$linkId] =& $matches[4];
        return ''; # String that will replace the block
    }


    protected function hashHTMLBlocks($text) {
        if ($this->noMarkup) {
            return $text;
        }

        $lessThanTab = $this->tabWidth - 1;

        # Hashify HTML blocks:
        # We only want to do this for block-level HTML tags, such as headers,
        # lists, and tables. That's because we still want to wrap <p>s around
        # "paragraphs" that are wrapped in non-block-level tags, such as anchors,
        # phrase emphasis, and spans. The list of tags we're looking for is
        # hard-coded:
        #
        # *  List "a" is made of tags which can be both inline or block-level.
        #    These will be treated block-level when the start tag is alone on
        #    its line, otherwise they're not matched here and will be taken as
        #    inline later.
        # *  List "b" is made of tags which are always block-level;
        #
        $blockTagsARe = 'ins|del';
        $blockTagsBRe =
            'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|address|' .
            'script|noscript|style|form|fieldset|iframe|math|svg|' .
            'article|section|nav|aside|hgroup|header|footer|' .
            'figure';

        # Regular expression for the content of a block tag.
        $nestedTagsLevel = 4;
        $attr = '
			(?>				# optional tag attributes
			  \s			# starts with whitespace
			  (?>
				[^>"/]+		# text outside quotes
			  |
				/+(?!>)		# slash not followed by ">"
			  |
				"[^"]*"		# text inside double quotes (tolerate ">")
			  |
				\'[^\']*\'	# text inside single quotes (tolerate ">")
			  )*
			)?	
			';
        $content = str_repeat(
                '
                                (?>
                                  [^<]+			# content without tag
                                |
                                  <\2			# nested opening tag
                                    ' . $attr . '	# attributes
					(?>
					  />
					|
					  >',
                $nestedTagsLevel
            ) . # end of opening tag
            '.*?' . # last level nested tag content
            str_repeat(
                '
                                      </\2\s*>	# closing nested tag
                                    )
                                  |
                                    <(?!/\2\s*>	# other tags with a different name
                                  )
                                )*',
                $nestedTagsLevel
            );
        $content2 = str_replace('\2', '\3', $content);

        # First, look for nested blocks, e.g.:
        # 	<div>
        # 		<div>
        # 		tags for inner block must be indented.
        # 		</div>
        # 	</div>
        #
        # The outermost tags must start at the left margin for this to match, and
        # the inner nested divs must be indented.
        # We need to do this before the next, more liberal match, because the next
        # match will start at the first `<div>` and stop at the first `</div>`.
        $text = preg_replace_callback(
            '{(?>
                        (?>
                            (?<=\n)			# Starting on its own line
                            |				# or
                            \A\n?			# the at beginning of the doc
                        )
                        (						# save in $1

                          # Match from `\n<tag>` to `</tag>\n`, handling nested tags
                          # in between.

                                    [ ]{0,' . $lessThanTab . '}
						<(' . $blockTagsBRe . ')# start tag = $2
						' . $attr . '>			# attributes followed by > and \n
						' . $content . '		# content, support nesting
						</\2>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document

			| # Special version for tags of group a.

						[ ]{0,' . $lessThanTab . '}
						<(' . $blockTagsARe . ')# start tag = $3
						' . $attr . '>[ ]*\n	# attributes followed by >
						' . $content2 . '		# content, support nesting
						</\3>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document
					
			| # Special case just for <hr />. It was easier to make a special 
			  # case than to make the other regex more complicated.
			
						[ ]{0,' . $lessThanTab . '}
						<(hr)				# start tag = $2
						' . $attr . '			# attributes
						/?>					# the matching end tag
						[ ]*
						(?=\n{2,}|\Z)		# followed by a blank line or end of document
			
			| # Special case for standalone HTML comments:
			
					[ ]{0,' . $lessThanTab . '}
					(?s:
						<!-- .*? -->
					)
					[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
			
			| # PHP and ASP-style processor instructions (<? and <%)
			
					[ ]{0,' . $lessThanTab . '}
					(?s:
						<([?%])			# $2
						.*?
						\2>
					)
					[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
					
			)
			)}Sxmi',
            array($this, 'hashHTMLBlocksCallback'),
            $text
        );

        return $text;
    }

    protected function hashHTMLBlocksCallback($matches) {
        $text = $matches[1];
        $key = $this->hashBlock($text);
        return "\n\n$key\n\n";
    }


    protected function hashPart($text, $boundary = 'X') {
        #
        # Called whenever a tag must be hashed when a function insert an atomic
        # element in the text stream. Passing $text to through this function gives
        # a unique text-token which will be reverted back when calling unhash.
        #
        # The $boundary argument specify what character should be used to surround
        # the token. By convension, "B" is used for block elements that needs not
        # to be wrapped into paragraph tags at the end, ":" is used for elements
        # that are word separators and "X" is used in the general case.
        #
        # Swap back any tag hash found in $text so we do not have to `unhash`
        # multiple times at the end.
        $text = $this->unhash($text);

        # Then hash the block.
        static $i = 0;
        $key = "$boundary\x1A" . ++$i . $boundary;
        $this->htmlHashes[$key] = $text;
        return $key; # String that will replace the tag.
    }


    protected function hashBlock($text) {
        #
        # Shortcut function for hashPart with block-level boundaries.
        #
        return $this->hashPart($text, 'B');
    }


    protected $blockGamut = array(
        #
        # These are all the transformations that form block-level
        # tags like paragraphs, headers, and list items.
        #
        "doHeaders" => 10,
        "doHorizontalRules" => 20,
        "doLists" => 40,
        "doCodeBlocks" => 50,
        "doBlockQuotes" => 60,
    );

    protected function runBlockGamut($text) {
        #
        # Run block gamut tranformations.
        #
        # We need to escape raw HTML in Markdown source before doing anything
        # else. This need to be done for each block, and not only at the
        # begining in the Markdown function since hashed blocks can be part of
        # list items and could have been indented. Indented blocks would have
        # been seen as a code block in a previous pass of hashHTMLBlocks.
        $text = $this->hashHTMLBlocks($text);

        return $this->runBasicBlockGamut($text);
    }

    protected function runBasicBlockGamut($text) {
        #
        # Run block gamut tranformations, without hashing HTML blocks. This is
        # useful when HTML blocks are known to be already hashed, like in the first
        # whole-document pass.
        #
        foreach ($this->blockGamut as $method => $priority) {
            $text = $this->$method($text);
        }

        # Finally form paragraph and restore hashed blocks.
        $text = $this->formParagraphs($text);

        return $text;
    }


    protected function doHorizontalRules($text) {
        # Do Horizontal Rules:
        return preg_replace(
            '{
                ^[ ]{0,3}	# Leading space
                ([-*_])		# $1: First marker
                (?>			# Repeated marker group
                    [ ]{0,2}	# Zero, one, or two spaces.
                    \1			# Marker character
                ){2,}		# Group repeated at least twice
                [ ]*		# Tailing spaces
                $			# End of line.
            }mx',
            "\n" . $this->hashBlock("<hr$this->emptyElementSuffix") . "\n",
            $text
        );
    }


    protected $spanGamut = array(
        #
        # These are all the transformations that occur *within* block-level
        # tags like paragraphs, headers, and list items.
        #
        # Process character escapes, code spans, and inline HTML
        # in one shot.
        "parseSpan" => -30,
        # Process anchor and image tags. Images must come first,
        # because ![foo][f] looks like an anchor.
        "doImages" => 10,
        "doAnchors" => 20,
        # Make links out of things like `<http://example.com/>`
        # Must come after doAnchors, because you can use < and >
        # delimiters in inline links like [this](<url>).
        "doAutoLinks" => 30,
        "encodeAmpsAndAngles" => 40,
        "doItalicsAndBold" => 50,
        "doHardBreaks" => 60,
    );

    protected function runSpanGamut($text) {
        #
        # Run span gamut tranformations.
        #
        foreach ($this->spanGamut as $method => $priority) {
            $text = $this->$method($text);
        }

        return $text;
    }


    protected function doHardBreaks($text) {
        # Do hard breaks:
        return preg_replace_callback(
            '/ {2,}\n/',
            array($this, 'doHardBreaksCallback'),
            $text
        );
    }

    protected function doHardBreaksCallback($matches) {
        return $this->hashPart("<br$this->emptyElementSuffix\n");
    }


    protected function doAnchors($text) {
        #
        # Turn Markdown link shortcuts into XHTML <a> tags.
        #
        if ($this->inAnchor) {
            return $text;
        }
        $this->inAnchor = true;

        #
        # First, handle reference-style links: [link text] [id]
        #
        $text = preg_replace_callback(
            '{
                        (					# wrap whole match in $1
                          \[
                            (' . $this->nestedBracketsRe . ')	# link text = $2
			  \]

			  [ ]?				# one optional space
			  (?:\n[ ]*)?		# one optional newline followed by spaces

			  \[
				(.*?)		# id = $3
			  \]
			)
			}xs',
            array($this, 'doAnchorsReferenceCallback'),
            $text
        );

        #
        # Next, inline-style links: [link text](url "optional title")
        #
        $text = preg_replace_callback(
            '{
                        (				# wrap whole match in $1
                          \[
                            (' . $this->nestedBracketsRe . ')	# link text = $2
			  \]
			  \(			# literal paren
				[ \n]*
				(?:
					<(.+?)>	# href = $3
				|
					(' . $this->nestedUrlParenthesisRe . ')	# href = $4
				)
				[ \n]*
				(			# $5
				  ([\'"])	# quote char = $6
				  (.*?)		# Title = $7
				  \6		# matching quote
				  [ \n]*	# ignore any spaces/tabs between closing quote and )
				)?			# title is optional
			  \)
			)
			}xs',
            array($this, 'doAnchorsInlineCallback'),
            $text
        );

        #
        # Last, handle reference-style shortcuts: [link text]
        # These must come last in case you've also got [link text][1]
        # or [link text](/foo)
        #
        $text = preg_replace_callback(
            '{
                        (					# wrap whole match in $1
                          \[
                            ([^\[\]]+)		# link text = $2; can\'t contain [ or ]
                          \]
                        )
                        }xs',
            array($this, 'doAnchorsReferenceCallback'),
            $text
        );

        $this->inAnchor = false;
        return $text;
    }

    protected function doAnchorsReferenceCallback($matches) {
        $wholeMatch = $matches[1];
        $linkText = $matches[2];
        $linkId =& $matches[3];

        if ($linkId == "") {
            # for shortcut links like [this][] or [this].
            $linkId = $linkText;
        }

        # lower-case and turn embedded newlines into spaces
        $linkId = strtolower($linkId);
        $linkId = preg_replace('{[ ]?\n}', ' ', $linkId);

        if (isset($this->urls[$linkId])) {
            $url = $this->urls[$linkId];
            $url = $this->encodeAttribute($url);

            $result = "<a href=\"$url\"";
            if (isset($this->titles[$linkId])) {
                $title = $this->titles[$linkId];
                $title = $this->encodeAttribute($title);
                $result .= " title=\"$title\"";
            }

            $linkText = $this->runSpanGamut($linkText);
            $result .= ">$linkText</a>";
            $result = $this->hashPart($result);
        }
        else {
            $result = $wholeMatch;
        }
        return $result;
    }

    protected function doAnchorsInlineCallback($matches) {
        $wholeMatch = $matches[1];
        $linkText = $this->runSpanGamut($matches[2]);
        $url = $matches[3] == '' ? $matches[4] : $matches[3];
        $title =& $matches[7];

        // if the URL was of the form <s p a c e s> it got caught by the HTML
        // tag parser and hashed. Need to reverse the process before using the URL.
        $unhashed = $this->unhash($url);
        if ($unhashed != $url) {
            $url = preg_replace('/^<(.*)>$/', '\1', $unhashed);
        }

        $url = $this->encodeAttribute($url);

        $result = "<a href=\"$url\"";
        if (isset($title)) {
            $title = $this->encodeAttribute($title);
            $result .= " title=\"$title\"";
        }

        $linkText = $this->runSpanGamut($linkText);
        $result .= ">$linkText</a>";

        return $this->hashPart($result);
    }


    protected function doImages($text) {
        #
        # Turn Markdown image shortcuts into <img> tags.
        #
        #
        # First, handle reference-style labeled images: ![alt text][id]
        #
        $text = preg_replace_callback(
            '{
                        (				# wrap whole match in $1
                          !\[
                            (' . $this->nestedBracketsRe . ')		# alt text = $2
			  \]

			  [ ]?				# one optional space
			  (?:\n[ ]*)?		# one optional newline followed by spaces

			  \[
				(.*?)		# id = $3
			  \]

			)
			}xs',
            array($this, 'doImagesReferenceCallback'),
            $text
        );

        #
        # Next, handle inline images:  ![alt text](url "optional title")
        # Don't forget: encode * and _
        #
        $text = preg_replace_callback(
            '{
                        (				# wrap whole match in $1
                          !\[
                            (' . $this->nestedBracketsRe . ')		# alt text = $2
			  \]
			  \s?			# One optional whitespace character
			  \(			# literal paren
				[ \n]*
				(?:
					<(\S*)>	# src url = $3
				|
					(' . $this->nestedUrlParenthesisRe . ')	# src url = $4
				)
				[ \n]*
				(			# $5
				  ([\'"])	# quote char = $6
				  (.*?)		# title = $7
				  \6		# matching quote
				  [ \n]*
				)?			# title is optional
			  \)
			)
			}xs',
            array($this, 'doImagesInlineCallback'),
            $text
        );

        return $text;
    }

    protected function doImagesReferenceCallback($matches) {
        $wholeMatch = $matches[1];
        $altText = $matches[2];
        $linkId = strtolower($matches[3]);

        if ($linkId == "") {
            $linkId = strtolower($altText); # for shortcut links like ![this][].
        }

        $altText = $this->encodeAttribute($altText);
        if (isset($this->urls[$linkId])) {
            $url = $this->encodeAttribute($this->urls[$linkId]);
            $result = "<img src=\"$url\" alt=\"$altText\"";
            if (isset($this->titles[$linkId])) {
                $title = $this->titles[$linkId];
                $title = $this->encodeAttribute($title);
                $result .= " title=\"$title\"";
            }
            $result .= $this->emptyElementSuffix;
            $result = $this->hashPart($result);
        }
        else {
            # If there's no such link ID, leave intact:
            $result = $wholeMatch;
        }

        return $result;
    }

    protected function doImagesInlineCallback($matches) {
        $wholeMatch = $matches[1];
        $altText = $matches[2];
        $url = $matches[3] == '' ? $matches[4] : $matches[3];
        $title =& $matches[7];

        $altText = $this->encodeAttribute($altText);
        $url = $this->encodeAttribute($url);
        $result = "<img src=\"$url\" alt=\"$altText\"";
        if (isset($title)) {
            $title = $this->encodeAttribute($title);
            $result .= " title=\"$title\""; # $title already quoted
        }
        $result .= $this->emptyElementSuffix;

        return $this->hashPart($result);
    }


    protected function doHeaders($text) {
        # Setext-style headers:
        #	  Header 1
        #	  ========
        #
        #	  Header 2
        #	  --------
        #
        $text = preg_replace_callback(
            '{ ^(.+?)[ ]*\n(=+|-+)[ ]*\n+ }mx',
            array($this, 'doHeadersCallbackSetext'),
            $text
        );

        # atx-style headers:
        #	# Header 1
        #	## Header 2
        #	## Header 2 with closing hashes ##
        #	...
        #	###### Header 6
        #
        $text = preg_replace_callback(
            '{
                            ^(\#{1,6})	# $1 = string of #\'s
                            [ ]*
                            (.+?)		# $2 = Header text
                            [ ]*
                            \#*			# optional closing #\'s (not counted)
                            \n+
                        }xm',
            array($this, 'doHeadersCallbackAtx'),
            $text
        );

        return $text;
    }

    protected function doHeadersCallbackSetext($matches) {
        # Terrible hack to check we haven't found an empty list item.
        if ($matches[2] == '-' && preg_match('{^-(?: |$)}', $matches[1])) {
            return $matches[0];
        }

        $level = $matches[2]{0} == '=' ? 1 : 2;
        $block = "<h$level>" . $this->runSpanGamut($matches[1]) . "</h$level>";
        return "\n" . $this->hashBlock($block) . "\n\n";
    }

    protected function doHeadersCallbackAtx($matches) {
        $level = strlen($matches[1]);
        $block = "<h$level>" . $this->runSpanGamut($matches[2]) . "</h$level>";
        return "\n" . $this->hashBlock($block) . "\n\n";
    }


    protected function doLists($text) {
        #
        # Form HTML ordered (numbered) and unordered (bulleted) lists.
        #
        $lessThanTab = $this->tabWidth - 1;

        # Re-usable patterns to match list item bullets and number markers:
        $markerUlRe = '[*+-]';
        $markerOlRe = '\d+[\.]';

        $markersRelist = array(
            $markerUlRe => $markerOlRe,
            $markerOlRe => $markerUlRe,
        );

        foreach ($markersRelist as $markerRe => $otherMarkerRe) {
            # Re-usable pattern to match any entirel ul or ol list:
            $wholeListRe = '
				(								# $1 = whole list
				  (								# $2
					([ ]{0,' . $lessThanTab . '})	# $3 = number of spaces
					(' . $markerRe . ')			# $4 = first list item marker
					[ ]+
				  )
				  (?s:.+?)
				  (								# $5
					  \z
					|
					  \n{2,}
					  (?=\S)
					  (?!						# Negative lookahead for another list item marker
						[ ]*
						' . $markerRe . '[ ]+
					  )
					|
					  (?=						# Lookahead for another kind of list
					    \n
						\3						# Must have the same indentation
						' . $otherMarkerRe . '[ ]+
					  )
				  )
				)
			'; // mx

            # We use a different prefix before nested lists than top-level lists.
            # See extended comment in _ProcessListItems().

            if ($this->listLevel) {
                $text = preg_replace_callback(
                    '{
                                            ^
                                            ' . $wholeListRe . '
					}mx',
                    array($this, 'doListsCallback'),
                    $text
                );
            }
            else {
                $text = preg_replace_callback(
                    '{
                                            (?:(?<=\n)\n|\A\n?) # Must eat the newline
                                            ' . $wholeListRe . '
					}mx',
                    array($this, 'doListsCallback'),
                    $text
                );
            }
        }

        return $text;
    }

    protected function doListsCallback($matches) {
        # Re-usable patterns to match list item bullets and number markers:
        $markerUlRe = '[*+-]';
        $markerOlRe = '\d+[\.]';
        $markerAnyRe = "(?:$markerUlRe|$markerOlRe)";

        $list = $matches[1];
        $listType = preg_match("/$markerUlRe/", $matches[4]) ? "ul" : "ol";

        $markerAnyRe = ($listType == "ul" ? $markerUlRe : $markerOlRe);

        $list .= "\n";
        $result = $this->processListItems($list, $markerAnyRe);

        $result = $this->hashBlock("<$listType>\n" . $result . "</$listType>");
        return "\n" . $result . "\n\n";
    }

    protected $listLevel = 0;

    protected function processListItems($listStr, $markerAnyRe) {
        #
        #	Process the contents of a single ordered or unordered list, splitting it
        #	into individual list items.
        #
        # The $this->listLevel global keeps track of when we're inside a list.
        # Each time we enter a list, we increment it; when we leave a list,
        # we decrement. If it's zero, we're not in a list anymore.
        #
        # We do this because when we're not inside a list, we want to treat
        # something like this:
        #
        #		I recommend upgrading to version
        #		8. Oops, now this line is treated
        #		as a sub-list.
        #
        # As a single paragraph, despite the fact that the second line starts
        # with a digit-period-space sequence.
        #
        # Whereas when we're inside a list (or sub-list), that line will be
        # treated as the start of a sub-list. What a kludge, huh? This is
        # an aspect of Markdown's syntax that's hard to parse perfectly
        # without resorting to mind-reading. Perhaps the solution is to
        # change the syntax rules such that sub-lists must start with a
        # starting cardinal number; e.g. "1." or "a.".

        $this->listLevel++;

        # trim trailing blank lines:
        $listStr = preg_replace("/\n{2,}\\z/", "\n", $listStr);

        $listStr = preg_replace_callback(
            '{
                        (\n)?							# leading line = $1
                        (^[ ]*)							# leading whitespace = $2
                        (' . $markerAnyRe . '				# list marker and space = $3
				(?:[ ]+|(?=\n))	# space only required if item is not empty
			)
			((?s:.*?))						# list item text   = $4
			(?:(\n+(?=\n))|\n)				# tailing blank line = $5
			(?= \n* (\z | \2 (' . $markerAnyRe . ') (?:[ ]+|(?=\n))))
			}xm',
            array($this, 'processListItemsCallback'),
            $listStr
        );

        $this->listLevel--;
        return $listStr;
    }

    protected function processListItemsCallback($matches) {
        $item = $matches[4];
        $leadingLine =& $matches[1];
        $leadingSpace =& $matches[2];
        $markerSpace = $matches[3];
        $tailingBlankLine =& $matches[5];

        if ($leadingLine || $tailingBlankLine || preg_match('/\n{2,}/', $item)) {
            # Replace marker with the appropriate whitespace indentation
            $item = $leadingSpace . str_repeat(' ', strlen($markerSpace)) . $item;
            $item = $this->runBlockGamut($this->outdent($item) . "\n");
        }
        else {
            # Recursion for sub-lists:
            $item = $this->doLists($this->outdent($item));
            $item = preg_replace('/\n+$/', '', $item);
            $item = $this->runSpanGamut($item);
        }

        return "<li>" . $item . "</li>\n";
    }


    protected function doCodeBlocks($text) {
        #
        #	Process Markdown `<pre><code>` blocks.
        #
        $text = preg_replace_callback(
            '{
                            (?:\n\n|\A\n?)
                            (	            # $1 = the code block -- one or more lines, starting with a space/tab
                              (?>
                                [ ]{' . $this->tabWidth . '}  # Lines must start with a tab or a tab-width of spaces
					.*\n+
				  )+
				)
				((?=^[ ]{0,' . $this->tabWidth . '}\S)|\Z)	# Lookahead for non-space at line-start, or end of doc
			}xm',
            array($this, 'doCodeBlocksCallback'),
            $text
        );

        return $text;
    }

    protected function doCodeBlocksCallback($matches) {
        $codeblock = $matches[1];

        $codeblock = $this->outdent($codeblock);
        $codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);

        # trim leading newlines and trailing newlines
        $codeblock = preg_replace('/\A\n+|\n+\z/', '', $codeblock);

        $codeblock = "<pre><code>$codeblock\n</code></pre>";
        return "\n\n" . $this->hashBlock($codeblock) . "\n\n";
    }


    protected function makeCodeSpan($code) {
        #
        # Create a code span markup for $code. Called from handleSpanToken.
        #
        $code = htmlspecialchars(trim($code), ENT_NOQUOTES);
        return $this->hashPart("<code>$code</code>");
    }


    protected $emRelist = array(
        '' => '(?:(?<!\*)\*(?!\*)|(?<!_)_(?!_))(?![\.,:;]?\s)',
        '*' => '(?<![\s*])\*(?!\*)',
        '_' => '(?<![\s_])_(?!_)',
    );
    protected $strongRelist = array(
        '' => '(?:(?<!\*)\*\*(?!\*)|(?<!_)__(?!_))(?![\.,:;]?\s)',
        '**' => '(?<![\s*])\*\*(?!\*)',
        '__' => '(?<![\s_])__(?!_)',
    );
    protected $emStrongRelist = array(
        '' => '(?:(?<!\*)\*\*\*(?!\*)|(?<!_)___(?!_))(?![\.,:;]?\s)',
        '***' => '(?<![\s*])\*\*\*(?!\*)',
        '___' => '(?<![\s_])___(?!_)',
    );
    protected $emStrongPreparedRelist;

    protected function prepareItalicsAndBold() {
        #
        # Prepare regular expressions for searching emphasis tokens in any
        # context.
        #
        foreach ($this->emRelist as $em => $emRe) {
            foreach ($this->strongRelist as $strong => $strongRe) {
                # Construct list of allowed token expressions.
                $tokenRelist = array();
                if (isset($this->emStrongRelist["$em$strong"])) {
                    $tokenRelist[] = $this->emStrongRelist["$em$strong"];
                }
                $tokenRelist[] = $emRe;
                $tokenRelist[] = $strongRe;

                # Construct master expression from list.
                $tokenRe = '{(' . implode('|', $tokenRelist) . ')}';
                $this->emStrongPreparedRelist["$em$strong"] = $tokenRe;
            }
        }
    }

    protected function doItalicsAndBold($text) {
        $tokenStack = array('');
        $textStack = array('');
        $em = '';
        $strong = '';
        $treeCharEm = false;

        while (1) {
            #
            # Get prepared regular expression for seraching emphasis tokens
            # in current context.
            #
            $tokenRe = $this->emStrongPreparedRelist["$em$strong"];

            #
            # Each loop iteration search for the next emphasis token.
            # Each token is then passed to handleSpanToken.
            #
            $parts = preg_split($tokenRe, $text, 2, PREG_SPLIT_DELIM_CAPTURE);
            $textStack[0] .= $parts[0];
            $token =& $parts[1];
            $text =& $parts[2];

            if (empty($token)) {
                # Reached end of text span: empty stack without emitting.
                # any more emphasis.
                while ($tokenStack[0]) {
                    $textStack[1] .= array_shift($tokenStack);
                    $textStack[0] .= array_shift($textStack);
                }
                break;
            }

            $tokenLen = strlen($token);
            if ($treeCharEm) {
                # Reached closing marker while inside a three-char emphasis.
                if ($tokenLen == 3) {
                    # Three-char closing marker, close em and strong.
                    array_shift($tokenStack);
                    $span = array_shift($textStack);
                    $span = $this->runSpanGamut($span);
                    $span = "<strong><em>$span</em></strong>";
                    $textStack[0] .= $this->hashPart($span);
                    $em = '';
                    $strong = '';
                }
                else {
                    # Other closing marker: close one em or strong and
                    # change current token state to match the other
                    $tokenStack[0] = str_repeat($token{0}, 3 - $tokenLen);
                    $tag = $tokenLen == 2 ? "strong" : "em";
                    $span = $textStack[0];
                    $span = $this->runSpanGamut($span);
                    $span = "<$tag>$span</$tag>";
                    $textStack[0] = $this->hashPart($span);
                    $$tag = ''; # $$tag stands for $em or $strong
                }
                $treeCharEm = false;
            }
            else if ($tokenLen == 3) {
                if ($em) {
                    # Reached closing marker for both em and strong.
                    # Closing strong marker:
                    for ($i = 0; $i < 2; ++$i) {
                        $shiftedToken = array_shift($tokenStack);
                        $tag = strlen($shiftedToken) == 2 ? "strong" : "em";
                        $span = array_shift($textStack);
                        $span = $this->runSpanGamut($span);
                        $span = "<$tag>$span</$tag>";
                        $textStack[0] .= $this->hashPart($span);
                        $$tag = ''; # $$tag stands for $em or $strong
                    }
                }
                else {
                    # Reached opening three-char emphasis marker. Push on token
                    # stack; will be handled by the special condition above.
                    $em = $token{0};
                    $strong = "$em$em";
                    array_unshift($tokenStack, $token);
                    array_unshift($textStack, '');
                    $treeCharEm = true;
                }
            }
            else if ($tokenLen == 2) {
                if ($strong) {
                    # Unwind any dangling emphasis marker:
                    if (strlen($tokenStack[0]) == 1) {
                        $textStack[1] .= array_shift($tokenStack);
                        $textStack[0] .= array_shift($textStack);
                    }
                    # Closing strong marker:
                    array_shift($tokenStack);
                    $span = array_shift($textStack);
                    $span = $this->runSpanGamut($span);
                    $span = "<strong>$span</strong>";
                    $textStack[0] .= $this->hashPart($span);
                    $strong = '';
                }
                else {
                    array_unshift($tokenStack, $token);
                    array_unshift($textStack, '');
                    $strong = $token;
                }
            }
            else {
                # Here $tokenLen == 1
                if ($em) {
                    if (strlen($tokenStack[0]) == 1) {
                        # Closing emphasis marker:
                        array_shift($tokenStack);
                        $span = array_shift($textStack);
                        $span = $this->runSpanGamut($span);
                        $span = "<em>$span</em>";
                        $textStack[0] .= $this->hashPart($span);
                        $em = '';
                    }
                    else {
                        $textStack[0] .= $token;
                    }
                }
                else {
                    array_unshift($tokenStack, $token);
                    array_unshift($textStack, '');
                    $em = $token;
                }
            }
        }
        return $textStack[0];
    }


    protected function doBlockQuotes($text) {
        $text = preg_replace_callback(
            '/
                          (								# Wrap whole match in $1
                            (?>
                              ^[ ]*>[ ]?			# ">" at the start of a line
                                .+\n					# rest of the first line
                              (.+\n)*					# subsequent consecutive lines
                              \n*						# blanks
                            )+
                          )
                        /xm',
            array($this, 'doBlockQuotesCallback'),
            $text
        );

        return $text;
    }

    protected function doBlockQuotesCallback($matches) {
        $bq = $matches[1];
        # trim one level of quoting - trim whitespace-only lines
        $bq = preg_replace('/^[ ]*>[ ]?|^[ ]+$/m', '', $bq);
        $bq = $this->runBlockGamut($bq); # recurse

        $bq = preg_replace('/^/m', "  ", $bq);
        # These leading spaces cause problem with <pre> content,
        # so we need to fix that:
        $bq = preg_replace_callback(
            '{(\s*<pre>.+?</pre>)}sx',
            array($this, 'doBlockQuotesCallback2'),
            $bq
        );

        return "\n" . $this->hashBlock("<blockquote>\n$bq\n</blockquote>") . "\n\n";
    }

    protected function doBlockQuotesCallback2($matches) {
        $pre = $matches[1];
        $pre = preg_replace('/^  /m', '', $pre);
        return $pre;
    }


    protected function formParagraphs($text) {
        #
        #	Params:
        #		$text - string to process with html <p> tags
        #
        # Strip leading and trailing lines:
        $text = preg_replace('/\A\n+|\n+\z/', '', $text);

        $grafs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);

        #
        # Wrap <p> tags and unhashify HTML blocks
        #
        foreach ($grafs as $key => $value) {
            if (!preg_match('/^B\x1A[0-9]+B$/', $value)) {
                # Is a paragraph.
                $value = $this->runSpanGamut($value);
                $value = preg_replace('/^([ ]*)/', "<p>", $value);
                $value .= "</p>";
                $grafs[$key] = $this->unhash($value);
            }
            else {
                # Is a block.
                # Modify elements of @grafs in-place...
                $graf = $value;
                $block = $this->htmlHashes[$graf];
                $graf = $block;
                //				if (preg_match('{
                //					\A
                //					(							# $1 = <div> tag
                //					  <div  \s+
                //					  [^>]*
                //					  \b
                //					  markdown\s*=\s*  ([\'"])	#	$2 = attr quote char
                //					  1
                //					  \2
                //					  [^>]*
                //					  >
                //					)
                //					(							# $3 = contents
                //					.*
                //					)
                //					(</div>)					# $4 = closing tag
                //					\z
                //					}xs', $block, $matches))
                //				{
                //					list(, $divOpen, , $divContent, $divClose) = $matches;
                //
                //					# We can't call Markdown(), because that resets the hash;
                //					# that initialization code should be pulled into its own sub, though.
                //					$divContent = $this->hashHTMLBlocks($divContent);
                //
                //					# Run document gamut methods on the content.
                //					foreach ($this->documentGamut as $method => $priority) {
                //						$divContent = $this->$method($divContent);
                //					}
                //
                //					$divOpen = preg_replace(
                //						'{\smarkdown\s*=\s*([\'"]).+?\1}', '', $divOpen);
                //
                //					$graf = $divOpen . "\n" . $divContent . "\n" . $divClose;
                //				}
                $grafs[$key] = $graf;
            }
        }

        return implode("\n\n", $grafs);
    }


    protected function encodeAttribute($text) {
        #
        # Encode text for a double-quoted HTML attribute. This function
        # is *not* suitable for attributes enclosed in single quotes.
        #
        $text = $this->encodeAmpsAndAngles($text);
        $text = str_replace('"', '&quot;', $text);
        return $text;
    }


    protected function encodeAmpsAndAngles($text) {
        #
        # Smart processing for ampersands and angle brackets that need to
        # be encoded. Valid character entities are left alone unless the
        # no-entities mode is set.
        #
        if ($this->noEntities) {
            $text = str_replace('&', '&amp;', $text);
        }
        else {
            # Ampersand-encoding based entirely on Nat Irons's Amputator
            # MT plugin: <http://bumppo.net/projects/amputator/>
            $text = preg_replace(
                '/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/',
                '&amp;',
                $text
            );
        }
        # Encode remaining <'s
        $text = str_replace('<', '&lt;', $text);

        return $text;
    }


    protected function doAutoLinks($text) {
        $text = preg_replace_callback(
            '{<((https?|ftp|dict):[^\'">\s]+)>}i',
            array($this, 'doAutoLinksUrlCallback'),
            $text
        );

        # Email addresses: <address@domain.foo>
        $text = preg_replace_callback(
            '{
                        <
                        (?:mailto:)?
                        (
                            (?:
                                [-!#$%&\'*+/=?^_`.{|}~\w\x80-\xFF]+
                            |
                                ".*?"
                            )
                            \@
                            (?:
                                [-a-z0-9\x80-\xFF]+(\.[-a-z0-9\x80-\xFF]+)*\.[a-z]+
                            |
                                \[[\d.a-fA-F:]+\]	# IPv4 & IPv6
                            )
                        )
                        >
                        }xi',
            array($this, 'doAutoLinksEmailCallback'),
            $text
        );
        $text = preg_replace_callback('{<(tel:([^\'">\s]+))>}i', array($this, 'doAutoLinksTelCallback'), $text);

        return $text;
    }

    protected function doAutoLinksTelCallback($matches) {
        $url = $this->encodeAttribute($matches[1]);
        $tel = $this->encodeAttribute($matches[2]);
        $link = "<a href=\"$url\">$tel</a>";
        return $this->hashPart($link);
    }

    protected function doAutoLinksUrlCallback($matches) {
        $url = $this->encodeAttribute($matches[1]);
        $link = "<a href=\"$url\">$url</a>";
        return $this->hashPart($link);
    }

    protected function doAutoLinksEmailCallback($matches) {
        $address = $matches[1];
        $link = $this->encodeEmailAddress($address);
        return $this->hashPart($link);
    }


    protected function encodeEmailAddress($addr) {
        #
        #	Input: an email address, e.g. "foo@example.com"
        #
        #	Output: the email address as a mailto link, with each character
        #		of the address encoded as either a decimal or hex entity, in
        #		the hopes of foiling most address harvesting spam bots. E.g.:
        #
        #	  <p><a href="&#109;&#x61;&#105;&#x6c;&#116;&#x6f;&#58;&#x66;o&#111;
        #        &#x40;&#101;&#x78;&#97;&#x6d;&#112;&#x6c;&#101;&#46;&#x63;&#111;
        #        &#x6d;">&#x66;o&#111;&#x40;&#101;&#x78;&#97;&#x6d;&#112;&#x6c;
        #        &#101;&#46;&#x63;&#111;&#x6d;</a></p>
        #
        #	Based by a filter by Matthew Wickline, posted to BBEdit-Talk.
        #   With some optimizations by Milian Wolff.
        #
        $addr = "mailto:" . $addr;
        $chars = preg_split('/(?<!^)(?!$)/', $addr);
        $seed = (int)abs(crc32($addr) / strlen($addr)); # Deterministic seed.

        foreach ($chars as $key => $char) {
            $ord = ord($char);
            # Ignore non-ascii chars.
            if ($ord < 128) {
                $r = ($seed * (1 + $key)) % 100; # Pseudo-random function.
                # roughly 10% raw, 45% hex, 45% dec
                # '@' *must* be encoded. I insist.
                # '"' has to be encoded inside the attribute
                if ($r > 90 && $char != '@' && $char != '"') /* do nothing */ {
                    ;
                }
                else if ($r < 45) {
                    $chars[$key] = '&#x' . dechex($ord) . ';';
                }
                else {
                    $chars[$key] = '&#' . $ord . ';';
                }
            }
        }

        $addr = implode('', $chars);
        $text = implode('', array_slice($chars, 7)); # text without `mailto:`
        $addr = "<a href=\"$addr\">$text</a>";

        return $addr;
    }


    protected function parseSpan($str) {
        #
        # Take the string $str and parse it into tokens, hashing embeded HTML,
        # escaped characters and handling code spans.
        #
        $output = '';

        $spanRe = '{
				(
					\\\\' . $this->escapeCharsRe . '
				|
					(?<![`\\\\])
					`+						# code span marker
			' . ($this->noMarkup ? '' : '
				|
					<!--    .*?     -->		# comment
				|
					<\?.*?\?> | <%.*?%>		# processing instruction
				|
					<[!$]?[-a-zA-Z0-9:_]+	# regular tags
					(?>
						\s
						(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*
					)?
					>
				|
					<[-a-zA-Z0-9:_]+\s*/> # xml-style empty tag
				|
					</[-a-zA-Z0-9:_]+\s*> # closing tag
			') . '
				)
				}xs';

        while (1) {
            #
            # Each loop iteration seach for either the next tag, the next
            # openning code span marker, or the next escaped character.
            # Each token is then passed to handleSpanToken.
            #
            $parts = preg_split($spanRe, $str, 2, PREG_SPLIT_DELIM_CAPTURE);

            # Create token from text preceding tag.
            if ($parts[0] != "") {
                $output .= $parts[0];
            }

            # Check if we reach the end.
            if (isset($parts[1])) {
                $output .= $this->handleSpanToken($parts[1], $parts[2]);
                $str = $parts[2];
            }
            else {
                break;
            }
        }

        return $output;
    }


    protected function handleSpanToken($token, &$str) {
        #
        # Handle $token provided by parseSpan by determining its nature and
        # returning the corresponding value that should replace it.
        #
        switch ($token{0}) {
            case "\\":
                return $this->hashPart("&#" . ord($token{1}) . ";");
            case "`":
                # Search for end marker in remaining text.
                if (preg_match(
                    '/^(.*?[^`])' . preg_quote($token) . '(?!`)(.*)$/sm',
                    $str,
                    $matches
                )
                ) {
                    $str = $matches[2];
                    $codespan = $this->makeCodeSpan($matches[1]);
                    return $this->hashPart($codespan);
                }
                return $token; // return as text since no ending marker found.
            default:
                return $this->hashPart($token);
        }
    }


    protected function outdent($text) {
        #
        # Remove one level of line-leading tabs or spaces
        #
        return preg_replace('/^(\t|[ ]{1,' . $this->tabWidth . '})/m', '', $text);
    }


    # String length function for detab. `_initDetab` will create a function to
    # hanlde UTF-8 if the default function does not exist.
    protected $utf8Strlen = 'mb_strlen';

    protected function detab($text) {
        #
        # Replace tabs with the appropriate amount of space.
        #
        # For each line we separate the line in blocks delemited by
        # tab characters. Then we reconstruct every line by adding the
        # appropriate number of space between each blocks.

        $text = preg_replace_callback(
            '/^.*\t.*$/m',
            array($this, 'detabCallback'),
            $text
        );

        return $text;
    }

    protected function detabCallback($matches) {
        $line = $matches[0];
        $strLen = $this->utf8Strlen; # strlen function for UTF-8.

        # Split in blocks.
        $blocks = explode("\t", $line);
        # Add each blocks to the line.
        $line = $blocks[0];
        unset($blocks[0]); # Do not add first block twice.
        foreach ($blocks as $block) {
            # Calculate amount of space, insert spaces, insert block.
            $amount = $this->tabWidth - $strLen($line, 'UTF-8') % $this->tabWidth;
            $line .= str_repeat(" ", $amount) . $block;
        }
        return $line;
    }

    protected function initDetab() {
        #
        # Check for the availability of the function in the `utf8_strlen` property
        # (initially `mb_strlen`). If the function is not available, create a
        # function that will loosely count the number of UTF-8 characters with a
        # regular expression.
        #
        if (function_exists($this->utf8Strlen)) {
            return;
        }
        $this->utf8Strlen = create_function(
            '$text',
            'return preg_match_all(
                       "/[\\\\x00-\\\\xBF]|[\\\\xC0-\\\\xFF][\\\\x80-\\\\xBF]*/",
                       $text, $m);'
        );
    }


    protected function unhash($text) {
        #
        # Swap back in all the tags hashed by _HashHTMLBlocks.
        #
        return preg_replace_callback(
            '/(.)\x1A[0-9]+\1/',
            array($this, 'unhashCallback'),
            $text
        );
    }

    protected function unhashCallback($matches) {
        return $this->htmlHashes[$matches[0]];
    }

}


#
# Temporary Markdown Extra Parser Implementation Class
#
# NOTE: DON'T USE THIS CLASS
# Currently the implementation of of Extra resides here in this temporary class.
# This makes it easier to propagate the changes between the three different
# packaging styles of PHP Markdown. When this issue is resolved, this
# MarkdownExtra_TmpImpl class here will disappear and \Michelf\MarkdownExtra
# will contain the code. So please use \Michelf\MarkdownExtra and ignore this
# one.
#

class MarkdownExtraTmpImpl extends Markdown {


}
