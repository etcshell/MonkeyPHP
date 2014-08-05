<?php
namespace Markdown;

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

class MarkdownExtra extends Markdown {

    ### Configuration Variables ###

    # Prefix for footnote ids.
    public $fnIdPrefix = "";

    # Optional title attribute for footnote links and backlinks.
    public $fnLinkTitle = "";
    public $fnBacklinkTitle = "";

    # Optional class attribute for footnote links and backlinks.
    public $fnLinkClass = "footnote-ref";
    public $fnBacklinkClass = "footnote-backref";

    # Class name for table cell alignment (%% replaced left/center/right)
    # For instance: 'go-%%' becomes 'go-left' or 'go-right' or 'go-center'
    # If empty, the align attribute is used instead of a class name.
    public $tableAlignClassTmpl = '';

    # Optional class prefix for fenced code block.
    public $codeClassPrefix = "";
    # Class attribute for code blocks goes on the `code` tag;
    # setting this to true will put attributes on the `pre` tag instead.
    public $codeAttrOnPre = false;

    # Predefined abbreviations.
    public $predefAbbr = array();


    ### Parser Implementation ###

    public function __construct() {
        #
        # Constructor function. Initialize the parser object.
        #
        # Add extra escapable characters before parent constructor
        # initialize the table.
        $this->escapeChars .= ':|';

        # Insert extra document, block, and span transformations.
        # Parent constructor will do the sorting.
        $this->documentGamut += array(
            "doFencedCodeBlocks" => 5,
            "stripFootnotes" => 15,
            "stripAbbreviations" => 25,
            "appendFootnotes" => 50,
        );
        $this->blockGamut += array(
            "doFencedCodeBlocks" => 5,
            "doTables" => 15,
            "doDefLists" => 45,
        );
        $this->spanGamut += array(
            "doFootnotes" => 5,
            "doAbbreviations" => 70,
        );

        parent::__construct();
    }


    # Extra variables used during extra transformations.
    protected $footnotes = array();
    protected $footnotesOrdered = array();
    protected $footnotesRefCount = array();
    protected $footnotesNumbers = array();
    protected $abbrDesciptions = array();
    protected $abbrWordRe = '';

    # Give the current footnote number.
    protected $footnoteCounter = 1;


    protected function setup() {
        #
        # Setting up Extra-specific variables.
        #
        parent::setup();

        $this->footnotes = array();
        $this->footnotesOrdered = array();
        $this->footnotesRefCount = array();
        $this->footnotesNumbers = array();
        $this->abbrDesciptions = array();
        $this->abbrWordRe = '';
        $this->footnoteCounter = 1;

        foreach ($this->predefAbbr as $abbrWord => $abbrDesc) {
            if ($this->abbrWordRe) {
                $this->abbrWordRe .= '|';
            }
            $this->abbrWordRe .= preg_quote($abbrWord);
            $this->abbrDesciptions[$abbrWord] = trim($abbrDesc);
        }
    }

    protected function teardown() {
        #
        # Clearing Extra-specific variables.
        #
        $this->footnotes = array();
        $this->footnotesOrdered = array();
        $this->footnotesRefCount = array();
        $this->footnotesNumbers = array();
        $this->abbrDesciptions = array();
        $this->abbrWordRe = '';

        parent::teardown();
    }


    ### Extra Attribute Parser ###

    # Expression to use to catch attributes (includes the braces)
    protected $idClassAttrCatchRe = '\{((?:[ ]*[#.][-_:a-zA-Z0-9]+){1,})[ ]*\}';
    # Expression to use when parsing in a context when no capture is desired
    protected $idClassAttrNocatchRe = '\{(?:[ ]*[#.][-_:a-zA-Z0-9]+){1,}[ ]*\}';

    protected function doExtraAttributes($tagName, $attr) {
        #
        # Parse attributes caught by the $this->idClassAttrCatchRe expression
        # and return the HTML-formatted list of attributes.
        #
        # Currently supported attributes are .class and #id.
        #
        if (empty($attr)) {
            return "";
        }

        # Split on components
        preg_match_all('/[#.][-_:a-zA-Z0-9]+/', $attr, $matches);
        $elements = $matches[0];

        # handle classes and ids (only first id taken into account)
        $classes = array();
        $id = false;
        foreach ($elements as $element) {
            if ($element{0} == '.') {
                $classes[] = substr($element, 1);
            }
            else if ($element{0} == '#') {
                if ($id === false) {
                    $id = substr($element, 1);
                }
            }
        }

        # compose attributes as string
        $attrStr = "";
        if (!empty($id)) {
            $attrStr .= ' id="' . $id . '"';
        }
        if (!empty($classes)) {
            $attrStr .= ' class="' . implode(" ", $classes) . '"';
        }
        return $attrStr;
    }


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
					(?:[ ]* ' . $this->idClassAttrCatchRe . ' )?  # $5 = extra id & class attr
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
        $this->refAttr[$linkId] = $this->doExtraAttributes("", $dummy =& $matches[5]);
        return ''; # String that will replace the block
    }


    ### HTML Block Parser ###

    # Tags that are always treated as block tags:
    protected $blockTagsRe = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|address|form|fieldset|iframe|hr|legend|article|section|nav|aside|hgroup|header|footer|figcaption|figure';

    # Tags treated as block tags only if the opening tag is alone on its line:
    protected $contextBlockTagsRe = 'script|noscript|style|ins|del|iframe|object|source|track|param|math|svg|canvas|audio|video';

    # Tags where markdown="1" default to span mode:
    protected $containSpanTagsRe = 'p|h[1-6]|li|dd|dt|td|th|legend|address';

    # Tags which must not have their contents modified, no matter where
    # they appear:
    protected $cleanTagsRe = 'script|style|math|svg';

    # Tags that do not need to be closed.
    protected $autoCloseTagsRe = 'hr|img|param|source|track';


    protected function hashHTMLBlocks($text) {
        #
        # Hashify HTML Blocks and "clean tags".
        #
        # We only want to do this for block-level HTML tags, such as headers,
        # lists, and tables. That's because we still want to wrap <p>s around
        # "paragraphs" that are wrapped in non-block-level tags, such as anchors,
        # phrase emphasis, and spans. The list of tags we're looking for is
        # hard-coded.
        #
        # This works by calling _HashHTMLBlocks_InMarkdown, which then calls
        # _HashHTMLBlocks_InHTML when it encounter block tags. When the markdown="1"
        # attribute is found within a tag, _HashHTMLBlocks_InHTML calls back
        #  _HashHTMLBlocks_InMarkdown to handle the Markdown syntax within the tag.
        # These two functions are calling each other. It's recursive!
        #
        if ($this->noMarkup) {
            return $text;
        }

        #
        # Call the HTML-in-Markdown hasher.
        #
        list($text,) = $this->hashHTMLBlocksInMarkdown($text);

        return $text;
    }

    protected function hashHTMLBlocksInMarkdown(
        $text, $indent = 0, $enclosingTagRe = '', $span = false
    ) {
        #
        # Parse markdown text, calling _HashHTMLBlocks_InHTML for block tags.
        #
        # *   $indent is the number of space to be ignored when checking for code
        #     blocks. This is important because if we don't take the indent into
        #     account, something like this (which looks right) won't work as expected:
        #
        #     <div>
        #         <div markdown="1">
        #         Hello World.  <-- Is this a Markdown code block or text?
        #         </div>  <-- Is this a Markdown code block or a real tag?
        #     <div>
        #
        #     If you don't like this, just don't indent the tag on which
        #     you apply the markdown="1" attribute.
        #
        # *   If $enclosingTagRe is not empty, stops at the first unmatched closing
        #     tag with that name. Nested tags supported.
        #
        # *   If $span is true, text inside must treated as span. So any double
        #     newline will be replaced by a single newline so that it does not create
        #     paragraphs.
        #
        # Returns an array of that form: ( processed text , remaining text )
        #
        if ($text === '') {
            return array('', '');
        }

        # Regex to check for the presense of newlines around a block tag.
        $newlineBeforeRe = '/(?:^\n?|\n\n)*$/';
        $newlineAfterRe = '{
                ^						# Start of text following the tag.
                (?>[ ]*<!--.*?-->)?		# Optional comment.
                [ ]*\n					# Must be followed by newline.
            }xs';

        # Regex to match any tag.
        $blockTagRe = '{
                (					# $2: Capture whole tag.
                    </?					# Any opening or closing tag.
                        (?>				# Tag name.
                            ' . $this->blockTagsRe . '			|
							' . $this->contextBlockTagsRe . '	|
							' . $this->cleanTagsRe . '        	|
							(?!\s)' . $enclosingTagRe . '
						)
						(?:
							(?=[\s"\'/a-zA-Z0-9])	# Allowed characters after tag name.
							(?>
								".*?"		|	# Double quotes (can contain `>`)
								\'.*?\'   	|	# Single quotes (can contain `>`)
								.+?				# Anything but quotes and `>`.
							)*?
						)?
					>					# End of tag.
				|
					<!--    .*?     -->	# HTML Comment
				|
					<\?.*?\?> | <%.*?%>	# Processing instruction
				|
					<!\[CDATA\[.*?\]\]>	# CData Block
				' . (!$span ? ' # If not in span.
				|
					# Indented code block
					(?: ^[ ]*\n | ^ | \n[ ]*\n )
					[ ]{' . ($indent + 4) . '}[^\n]* \n
					(?>
						(?: [ ]{' . ($indent + 4) . '}[^\n]* | [ ]* ) \n
					)*
				|
					# Fenced code block marker
					(?<= ^ | \n )
					[ ]{0,' . ($indent + 3) . '}(?:~{3,}|`{3,})
									[ ]*
					(?:
					\.?[-_:a-zA-Z0-9]+ # standalone class name
					|
						' . $this->idClassAttrNocatchRe . ' # extra attributes
					)?
					[ ]*
					(?= \n )
				' : '') . ' # End (if not is span).
				|
					# Code span marker
					# Note, this regex needs to go after backtick fenced
					# code blocks but it should also be kept outside of the
					# "if not in span" condition adding backticks to the parser
					`+
				)
			}xs';


        $depth = 0; # Current depth inside the tag tree.
        $parsed = ""; # Parsed text that will be returned.

        #
        # Loop through every tag until we find the closing tag of the parent
        # or loop until reaching the end of text if no parent tag specified.
        #
        do {
            #
            # Split the text using the first $tagMatch pattern found.
            # Text before  pattern will be first in the array, text after
            # pattern will be at the end, and between will be any catches made
            # by the pattern.
            #
            $parts = preg_split(
                $blockTagRe,
                $text,
                2,
                PREG_SPLIT_DELIM_CAPTURE
            );

            # If in Markdown span mode, add a empty-string span-level hash
            # after each newline to prevent triggering any block element.
            if ($span) {
                $void = $this->hashPart("", ':');
                $newline = "$void\n";
                $parts[0] = $void . str_replace("\n", $newline, $parts[0]) . $void;
            }

            $parsed .= $parts[0]; # Text before current tag.

            # If end of $text has been reached. Stop loop.
            if (count($parts) < 3) {
                $text = "";
                break;
            }

            $tag = $parts[1]; # Tag to handle.
            $text = $parts[2]; # Remaining text after current tag.
            $tagRe = preg_quote($tag); # For use in a regular expression.

            #
            # Check for: Fenced code block marker.
            # Note: need to recheck the whole tag to disambiguate backtick
            # fences from code spans
            #
            if (preg_match(
                '{^\n?([ ]{0,' .
                ($indent + 3) .
                '})(~{3,}|`{3,})[ ]*(?:\.?[-_:a-zA-Z0-9]+|' .
                $this->idClassAttrNocatchRe .
                ')?[ ]*\n?$}',
                $tag,
                $capture
            )
            ) {
                # Fenced code block marker: find matching end marker.
                $fenceIndent = strlen($capture[1]); # use captured indent in re
                $fenceRe = $capture[2]; # use captured fence in re
                if (preg_match(
                    '{^(?>.*\n)*?[ ]{' . ($fenceIndent) . '}' . $fenceRe . '[ ]*(?:\n|$)}',
                    $text,
                    $matches
                )
                ) {
                    # End marker found: pass text unchanged until marker.
                    $parsed .= $tag . $matches[0];
                    $text = substr($text, strlen($matches[0]));
                }
                else {
                    # No end marker: just skip it.
                    $parsed .= $tag;
                }
            }
            #
            # Check for: Indented code block.
            #
            else if ($tag{0} == "\n" || $tag{0} == " ") {
                # Indented code block: pass it unchanged, will be handled
                # later.
                $parsed .= $tag;
            }
            #
            # Check for: Code span marker
            # Note: need to check this after backtick fenced code blocks
            #
            else if ($tag{0} == "`") {
                # Find corresponding end marker.
                $tagRe = preg_quote($tag);
                if (preg_match(
                    '{^(?>.+?|\n(?!\n))*?(?<!`)' . $tagRe . '(?!`)}',
                    $text,
                    $matches
                )
                ) {
                    # End marker found: pass text unchanged until marker.
                    $parsed .= $tag . $matches[0];
                    $text = substr($text, strlen($matches[0]));
                }
                else {
                    # Unmatched marker: just skip it.
                    $parsed .= $tag;
                }
            }
            #
            # Check for: Opening Block level tag or
            #            Opening Context Block tag (like ins and del)
            #               used as a block tag (tag is alone on it's line).
            #
            else if (preg_match('{^<(?:' . $this->blockTagsRe . ')\b}', $tag) ||
                (preg_match('{^<(?:' . $this->contextBlockTagsRe . ')\b}', $tag) &&
                    preg_match($newlineBeforeRe, $parsed) &&
                    preg_match($newlineAfterRe, $text))
            ) {
                # Need to parse tag and following text using the HTML parser.
                list($blockText, $text) = $this->hashHTMLBlocks_inHTML($tag . $text, "hashBlock", true);

                # Make sure it stays outside of any paragraph by adding newlines.
                $parsed .= "\n\n$blockText\n\n";
            }
            #
            # Check for: Clean tag (like script, math)
            #            HTML Comments, processing instructions.
            #
            else if (preg_match('{^<(?:' . $this->cleanTagsRe . ')\b}', $tag) || $tag{1} == '!' || $tag{1} == '?') {
                # Need to parse tag and following text using the HTML parser.
                # (don't check for markdown attribute)
                list($blockText, $text) = $this->hashHTMLBlocks_inHTML($tag . $text, "hashClean", false);

                $parsed .= $blockText;
            }
            #
            # Check for: Tag with same name as enclosing tag.
            #
            else if ($enclosingTagRe !== '' && # Same name as enclosing tag.
                preg_match('{^</?(?:' . $enclosingTagRe . ')\b}', $tag)
            ) {
                #
                # Increase/decrease nested tag count.
                #
                if ($tag{1} == '/') {
                    $depth--;
                }
                else if ($tag{strlen($tag) - 2} != '/') {
                    $depth++;
                }

                if ($depth < 0) {
                    #
                    # Going out of parent element. Clean up and break so we
                    # return to the calling function.
                    #
                    $text = $tag . $text;
                    break;
                }

                $parsed .= $tag;
            }
            else {
                $parsed .= $tag;
            }
        } while ($depth >= 0);

        return array($parsed, $text);
    }

    protected function hashHTMLBlocksInHTML($text, $hashMethod, $mdAttr) {
        #
        # Parse HTML, calling _HashHTMLBlocks_InMarkdown for block tags.
        #
        # *   Calls $hashMethod to convert any blocks.
        # *   Stops when the first opening tag closes.
        # *   $mdAttr indicate if the use of the `markdown="1"` attribute is allowed.
        #     (it is not inside clean tags)
        #
        # Returns an array of that form: ( processed text , remaining text )
        #
        if ($text === '') {
            return array('', '');
        }

        # Regex to match `markdown` attribute inside of a tag.
        $markdownAttrRe = '
			{
				\s*			# Eat whitespace before the `markdown` attribute
				markdown
				\s*=\s*
				(?>
					(["\'])		# $1: quote delimiter
					(.*?)		# $2: attribute value
					\1			# matching delimiter
				|
					([^\s>]*)	# $3: unquoted attribute value
				)
				()				# $4: make $3 always defined (avoid warnings)
			}xs';

        # Regex to match any tag.
        $tagRe = '{
				(					# $2: Capture whole tag.
					</?					# Any opening or closing tag.
						[\w:$]+			# Tag name.
						(?:
							(?=[\s"\'/a-zA-Z0-9])	# Allowed characters after tag name.
							(?>
								".*?"		|	# Double quotes (can contain `>`)
								\'.*?\'   	|	# Single quotes (can contain `>`)
								.+?				# Anything but quotes and `>`.
							)*?
						)?
					>					# End of tag.
				|
					<!--    .*?     -->	# HTML Comment
				|
					<\?.*?\?> | <%.*?%>	# Processing instruction
				|
					<!\[CDATA\[.*?\]\]>	# CData Block
				)
			}xs';

        $originalText = $text; # Save original text in case of faliure.

        $depth = 0; # Current depth inside the tag tree.
        $blockText = ""; # Temporary text holder for current text.
        $parsed = ""; # Parsed text that will be returned.

        #
        # Get the name of the starting tag.
        # (This pattern makes $baseTagNameRe safe without quoting.)
        #
        if (preg_match('/^<([\w:$]*)\b/', $text, $matches)) {
            $baseTagNameRe = $matches[1];
        }

        #
        # Loop through every tag until we find the corresponding closing tag.
        #
        do {
            #
            # Split the text using the first $tagMatch pattern found.
            # Text before  pattern will be first in the array, text after
            # pattern will be at the end, and between will be any catches made
            # by the pattern.
            #
            $parts = preg_split($tagRe, $text, 2, PREG_SPLIT_DELIM_CAPTURE);

            if (count($parts) < 3) {
                #
                # End of $text reached with unbalenced tag(s).
                # In that case, we return original text unchanged and pass the
                # first character as filtered to prevent an infinite loop in the
                # parent function.
                #
                return array($originalText{0}, substr($originalText, 1));
            }

            $blockText .= $parts[0]; # Text before current tag.
            $tag = $parts[1]; # Tag to handle.
            $text = $parts[2]; # Remaining text after current tag.

            #
            # Check for: Auto-close tag (like <hr/>)
            #			 Comments and Processing Instructions.
            #
            if (preg_match('{^</?(?:' . $this->autoCloseTagsRe . ')\b}', $tag) || $tag{1} == '!' || $tag{1} == '?') {
                # Just add the tag to the block as if it was text.
                $blockText .= $tag;
            }
            else {
                #
                # Increase/decrease nested tag count. Only do so if
                # the tag's name match base tag's.
                #
                if (preg_match('{^</?' . $baseTagNameRe . '\b}', $tag)) {
                    if ($tag{1} == '/') {
                        $depth--;
                    }
                    else if ($tag{strlen($tag) - 2} != '/') {
                        $depth++;
                    }
                }

                #
                # Check for `markdown="1"` attribute and handle it.
                #
                if ($mdAttr &&
                    preg_match($markdownAttrRe, $tag, $attr_m) &&
                    preg_match('/^1|block|span$/', $attr_m[2] . $attr_m[3])
                ) {
                    # Remove `markdown` attribute from opening tag.
                    $tag = preg_replace($markdownAttrRe, '', $tag);

                    # Check if text inside this tag must be parsed in span mode.
                    $this->mode = $attr_m[2] . $attr_m[3];
                    $spanMode =
                        $this->mode == 'span' ||
                        $this->mode != 'block' &&
                        preg_match('{^<(?:' . $this->containSpanTagsRe . ')\b}', $tag);

                    # Calculate indent before tag.
                    if (preg_match('/(?:^|\n)( *?)(?! ).*?$/', $blockText, $matches)) {
                        $strlen = $this->utf8Strlen;
                        $indent = $strlen($matches[1], 'UTF-8');
                    }
                    else {
                        $indent = 0;
                    }

                    # End preceding block with this tag.
                    $blockText .= $tag;
                    $parsed .= $this->$hashMethod($blockText);

                    # Get enclosing tag name for the ParseMarkdown function.
                    # (This pattern makes $tagNameRe safe without quoting.)
                    preg_match('/^<([\w:$]*)\b/', $tag, $matches);
                    $tagNameRe = $matches[1];

                    # Parse the content using the HTML-in-Markdown parser.
                    list ($blockText, $text) = $this->hashHTMLBlocks_inMarkdown(
                        $text,
                        $indent,
                        $tagNameRe,
                        $spanMode
                    );

                    # Outdent markdown text.
                    if ($indent > 0) {
                        $blockText = preg_replace(
                            "/^[ ]{1,$indent}/m",
                            "",
                            $blockText
                        );
                    }

                    # Append tag content to parsed text.
                    if (!$spanMode) {
                        $parsed .= "\n\n$blockText\n\n";
                    }
                    else {
                        $parsed .= "$blockText";
                    }

                    # Start over with a new block.
                    $blockText = "";
                }
                else {
                    $blockText .= $tag;
                }
            }

        } while ($depth > 0);

        #
        # Hash last block text that wasn't processed inside the loop.
        #
        $parsed .= $this->$hashMethod($blockText);

        return array($parsed, $text);
    }


    protected function hashClean($text) {
        #
        # Called whenever a tag must be hashed when a function inserts a "clean" tag
        # in $text, it passes through this function and is automaticaly escaped,
        # blocking invalid nested overlap.
        #
        return $this->hashPart($text, 'C');
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
			  (?:[ ]? ' . $this->idClassAttrCatchRe . ' )?	 # $8 = id/class attributes
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
            if (isset($this->refAttr[$linkId])) {
                $result .= $this->refAttr[$linkId];
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
        $attr = $this->doExtraAttributes("a", $dummy =& $matches[8]);

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
        $result .= $attr;

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
			  (?:[ ]? ' . $this->idClassAttrCatchRe . ' )?	 # $8 = id/class attributes
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
            if (isset($this->refAttr[$linkId])) {
                $result .= $this->refAttr[$linkId];
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
        $attr = $this->doExtraAttributes("img", $dummy =& $matches[8]);

        $altText = $this->encodeAttribute($altText);
        $url = $this->encodeAttribute($url);
        $result = "<img src=\"$url\" alt=\"$altText\"";
        if (isset($title)) {
            $title = $this->encodeAttribute($title);
            $result .= " title=\"$title\""; # $title already quoted
        }
        $result .= $attr;
        $result .= $this->emptyElementSuffix;

        return $this->hashPart($result);
    }


    protected function doHeaders($text) {
        #
        # Redefined to add id and class attribute support.
        #
        # Setext-style headers:
        #	  Header 1  {#header1}
        #	  ========
        #
        #	  Header 2  {#header2 .class1 .class2}
        #	  --------
        #
        $text = preg_replace_callback(
            '{
                (^.+?)								# $1: Header text
                (?:[ ]+ ' . $this->idClassAttrCatchRe . ' )?	 # $3 = id/class attributes
				[ ]*\n(=+|-+)[ ]*\n+				# $3: Header footer
			}mx',
            array($this, 'doHeadersCallbackSetext'),
            $text
        );

        # atx-style headers:
        #	# Header 1        {#header1}
        #	## Header 2       {#header2}
        #	## Header 2 with closing hashes ##  {#header3.class1.class2}
        #	...
        #	###### Header 6   {.class2}
        #
        $text = preg_replace_callback(
            '{
                            ^(\#{1,6})	# $1 = string of #\'s
                            [ ]*
                            (.+?)		# $2 = Header text
                            [ ]*
                            \#*			# optional closing #\'s (not counted)
                            (?:[ ]+ ' . $this->idClassAttrCatchRe . ' )?	 # $3 = id/class attributes
				[ ]*
				\n+
			}xm',
            array($this, 'doHeadersCallbackAtx'),
            $text
        );

        return $text;
    }

    protected function doHeadersCallbackSetext($matches) {
        if ($matches[3] == '-' && preg_match('{^- }', $matches[1])) {
            return $matches[0];
        }
        $level = $matches[3]{0} == '=' ? 1 : 2;
        $attr = $this->doExtraAttributes("h$level", $dummy =& $matches[2]);
        $block = "<h$level$attr>" . $this->runSpanGamut($matches[1]) . "</h$level>";
        return "\n" . $this->hashBlock($block) . "\n\n";
    }

    protected function doHeadersCallbackAtx($matches) {
        $level = strlen($matches[1]);
        $attr = $this->doExtraAttributes("h$level", $dummy =& $matches[3]);
        $block = "<h$level$attr>" . $this->runSpanGamut($matches[2]) . "</h$level>";
        return "\n" . $this->hashBlock($block) . "\n\n";
    }


    protected function doTables($text) {
        #
        # Form HTML tables.
        #
        $lessThanTab = $this->tabWidth - 1;
        #
        # Find tables with leading pipe.
        #
        #	| Header 1 | Header 2
        #	| -------- | --------
        #	| Cell 1   | Cell 2
        #	| Cell 3   | Cell 4
        #
        $text = preg_replace_callback(
            '
                        {
                            ^							# Start of a line
                            [ ]{0,' . $lessThanTab . '}	# Allowed whitespace.
				[|]							# Optional leading pipe (present)
				(.+) \n						# $1: Header row (at least one pipe)

				[ ]{0,' . $lessThanTab . '}	# Allowed whitespace.
				[|] ([ ]*[-:]+[-| :]*) \n	# $2: Header underline

				(							# $3: Cells
					(?>
						[ ]*				# Allowed whitespace.
						[|] .* \n			# Row content.
					)*
				)
				(?=\n|\Z)					# Stop at final double newline.
			}xm',
            array($this, 'doTableLeadingPipeCallback'),
            $text
        );

        #
        # Find tables without leading pipe.
        #
        #	Header 1 | Header 2
        #	-------- | --------
        #	Cell 1   | Cell 2
        #	Cell 3   | Cell 4
        #
        $text = preg_replace_callback(
            '
                        {
                            ^							# Start of a line
                            [ ]{0,' . $lessThanTab . '}	# Allowed whitespace.
				(\S.*[|].*) \n				# $1: Header row (at least one pipe)

				[ ]{0,' . $lessThanTab . '}	# Allowed whitespace.
				([-:]+[ ]*[|][-| :]*) \n	# $2: Header underline

				(							# $3: Cells
					(?>
						.* [|] .* \n		# Row content
					)*
				)
				(?=\n|\Z)					# Stop at final double newline.
			}xm',
            array($this, 'doTableCallback'),
            $text
        );

        return $text;
    }

    protected function doTableLeadingPipeCallback($matches) {
        $head = $matches[1];
        $underline = $matches[2];
        $content = $matches[3];

        # Remove leading pipe for each row.
        $content = preg_replace('/^ *[|]/m', '', $content);

        return $this->doTableCallback(array($matches[0], $head, $underline, $content));
    }

    protected function doTableMakeAlignAttr($alignname) {
        if (empty($this->tableAlignClassTmpl)) {
            return " align=\"$alignname\"";
        }

        $classname = str_replace('%%', $alignname, $this->tableAlignClassTmpl);
        return " class=\"$classname\"";
    }

    protected function doTableCallback($matches) {
        $head = $matches[1];
        $underline = $matches[2];
        $content = $matches[3];

        # Remove any tailing pipes for each line.
        $head = preg_replace('/[|] *$/m', '', $head);
        $underline = preg_replace('/[|] *$/m', '', $underline);
        $content = preg_replace('/[|] *$/m', '', $content);

        # Reading alignement from header underline.
        $separators = preg_split('/ *[|] */', $underline);
        foreach ($separators as $n => $s) {
            if (preg_match('/^ *-+: *$/', $s)) {
                $attr[$n] = $this->doTable_makeAlignAttr('right');
            }
            else if (preg_match('/^ *:-+: *$/', $s)) {
                $attr[$n] = $this->doTable_makeAlignAttr('center');
            }
            else if (preg_match('/^ *:-+ *$/', $s)) {
                $attr[$n] = $this->doTable_makeAlignAttr('left');
            }
            else {
                $attr[$n] = '';
            }
        }

        # Parsing span elements, including code spans, character escapes,
        # and inline HTML tags, so that pipes inside those gets ignored.
        $head = $this->parseSpan($head);
        $headers = preg_split('/ *[|] */', $head);
        $colCount = count($headers);
        $attr = array_pad($attr, $colCount, '');

        # Write column headers.
        $text = "<table>\n";
        $text .= "<thead>\n";
        $text .= "<tr>\n";
        foreach ($headers as $n => $header)
            $text .= "  <th$attr[$n]>" . $this->runSpanGamut(trim($header)) . "</th>\n";
        $text .= "</tr>\n";
        $text .= "</thead>\n";

        # Split content by row.
        $rows = explode("\n", trim($content, "\n"));

        $text .= "<tbody>\n";
        foreach ($rows as $row) {
            # Parsing span elements, including code spans, character escapes,
            # and inline HTML tags, so that pipes inside those gets ignored.
            $row = $this->parseSpan($row);

            # Split row by cell.
            $rowCells = preg_split('/ *[|] */', $row, $colCount);
            $rowCells = array_pad($rowCells, $colCount, '');

            $text .= "<tr>\n";
            foreach ($rowCells as $n => $cell)
                $text .= "  <td$attr[$n]>" . $this->runSpanGamut(trim($cell)) . "</td>\n";
            $text .= "</tr>\n";
        }
        $text .= "</tbody>\n";
        $text .= "</table>";

        return $this->hashBlock($text) . "\n";
    }


    protected function doDefLists($text) {
        #
        # Form HTML definition lists.
        #
        $lessThanTab = $this->tabWidth - 1;

        # Re-usable pattern to match any entire dl list:
        $wholeListRe = '(?>
			(								# $1 = whole list
			  (								# $2
				[ ]{0,' . $lessThanTab . '}
				((?>.*\S.*\n)+)				# $3 = defined term
				\n?
				[ ]{0,' . $lessThanTab . '}:[ ]+ # colon starting definition
			  )
			  (?s:.+?)
			  (								# $4
				  \z
				|
				  \n{2,}
				  (?=\S)
				  (?!						# Negative lookahead for another term
					[ ]{0,' . $lessThanTab . '}
					(?: \S.*\n )+?			# defined term
					\n?
					[ ]{0,' . $lessThanTab . '}:[ ]+ # colon starting definition
				  )
				  (?!						# Negative lookahead for another definition
					[ ]{0,' . $lessThanTab . '}:[ ]+ # colon starting definition
				  )
			  )
			)
		)'; // mx

        $text = preg_replace_callback(
            '{
                            (?>\A\n?|(?<=\n\n))
                            ' . $wholeListRe . '
			}mx',
            array($this, 'doDefListsCallback'),
            $text
        );

        return $text;
    }

    protected function doDefListsCallback($matches) {
        # Re-usable patterns to match list item bullets and number markers:
        $list = $matches[1];

        # Turn double returns into triple returns, so that we can make a
        # paragraph for the last item in a list, if necessary:
        $result = trim($this->processDefListItems($list));
        $result = "<dl>\n" . $result . "\n</dl>";
        return $this->hashBlock($result) . "\n\n";
    }


    protected function processDefListItems($listStr) {
        #
        #	Process the contents of a single definition list, splitting it
        #	into individual term and definition list items.
        #
        $lessThanTab = $this->tabWidth - 1;

        # trim trailing blank lines:
        $listStr = preg_replace("/\n{2,}\\z/", "\n", $listStr);

        # Process definition terms.
        $listStr = preg_replace_callback(
            '{
                        (?>\A\n?|\n\n+)					# leading line
                        (								# definition terms = $1
                            [ ]{0,' . $lessThanTab . '}	# leading whitespace
				(?!\:[ ]|[ ])				# negative lookahead for a definition
											#   mark (colon) or more whitespace.
				(?> \S.* \n)+?				# actual term (not whitespace).
			)
			(?=\n?[ ]{0,3}:[ ])				# lookahead for following line feed
											#   with a definition mark.
			}xm',
            array($this, 'processDefListItemsCallbackDt'),
            $listStr
        );

        # Process actual definitions.
        $listStr = preg_replace_callback(
            '{
                        \n(\n+)?						# leading line = $1
                        (								# marker space = $2
                            [ ]{0,' . $lessThanTab . '}	# whitespace before colon
				\:[ ]+						# definition mark (colon)
			)
			((?s:.+?))						# definition text = $3
			(?= \n+ 						# stop at next definition mark,
				(?:							# next term or end of text
					[ ]{0,' . $lessThanTab . '} \:[ ]	|
					<dt> | \z
				)
			)
			}xm',
            array($this, 'processDefListItemsCallbackDd'),
            $listStr
        );

        return $listStr;
    }

    protected function processDefListItemsCallbackDt($matches) {
        $terms = explode("\n", trim($matches[1]));
        $text = '';
        foreach ($terms as $term) {
            $term = $this->runSpanGamut(trim($term));
            $text .= "\n<dt>" . $term . "</dt>";
        }
        return $text . "\n";
    }

    protected function processDefListItemsCallbackDd($matches) {
        $leadingLine = $matches[1];
        $markerSpace = $matches[2];
        $def = $matches[3];

        if ($leadingLine || preg_match('/\n{2,}/', $def)) {
            # Replace marker with the appropriate whitespace indentation
            $def = str_repeat(' ', strlen($markerSpace)) . $def;
            $def = $this->runBlockGamut($this->outdent($def . "\n\n"));
            $def = "\n" . $def . "\n";
        }
        else {
            $def = rtrim($def);
            $def = $this->runSpanGamut($this->outdent($def));
        }

        return "\n<dd>" . $def . "</dd>\n";
    }


    protected function doFencedCodeBlocks($text) {
        #
        # Adding the fenced code block syntax to regular Markdown:
        #
        # ~~~
        # Code block
        # ~~~
        #
        $lessThanTab = $this->tabWidth;

        $text = preg_replace_callback(
            '{
                            (?:\n|\A)
                            # 1: Opening marker
                            (
                                (?:~{3,}|`{3,}) # 3 or more tildes/backticks.
                            )
                            [ ]*
                            (?:
                                \.?([-_:a-zA-Z0-9]+) # 2: standalone class name
                            |
                                ' . $this->idClassAttrCatchRe . ' # 3: Extra attributes
				)?
				[ ]* \n # Whitespace and newline following marker.

				# 4: Content
				(
					(?>
						(?!\1 [ ]* \n)	# Not a closing marker.
						.*\n+
					)+
				)

				# Closing marker.
				\1 [ ]* (?= \n )
			}xm',
            array($this, 'doFencedCodeBlocksCallback'),
            $text
        );

        return $text;
    }

    protected function doFencedCodeBlocksCallback($matches) {
        $classname =& $matches[2];
        $attrs =& $matches[3];
        $codeblock = $matches[4];
        $codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);
        $codeblock = preg_replace_callback(
            '/^\n+/',
            array($this, 'doFencedCodeBlocksNewlines'),
            $codeblock
        );

        if ($classname != "") {
            if ($classname{0} == '.') {
                $classname = substr($classname, 1);
            }
            $attrStr = ' class="' . $this->codeClassPrefix . $classname . '"';
        }
        else {
            $attrStr = $this->doExtraAttributes($this->codeAttrOnPre ? "pre" : "code", $attrs);
        }
        $preAttrStr = $this->codeAttrOnPre ? $attrStr : '';
        $codeAttrStr = $this->codeAttrOnPre ? '' : $attrStr;
        $codeblock = "<pre$preAttrStr><code$codeAttrStr>$codeblock</code></pre>";

        return "\n\n" . $this->hashBlock($codeblock) . "\n\n";
    }

    protected function doFencedCodeBlocksNewlines($matches) {
        return str_repeat(
            "<br$this->emptyElementSuffix",
            strlen($matches[0])
        );
    }


    #
    # Redefining emphasis markers so that emphasis by underscore does not
    # work in the middle of a word.
    #
    protected $emRelist = array(
        '' => '(?:(?<!\*)\*(?!\*)|(?<![a-zA-Z0-9_])_(?!_))(?![\.,:;]?\s)',
        '*' => '(?<![\s*])\*(?!\*)',
        '_' => '(?<![\s_])_(?![a-zA-Z0-9_])',
    );
    protected $strongRelist = array(
        '' => '(?:(?<!\*)\*\*(?!\*)|(?<![a-zA-Z0-9_])__(?!_))(?![\.,:;]?\s)',
        '**' => '(?<![\s*])\*\*(?!\*)',
        '__' => '(?<![\s_])__(?![a-zA-Z0-9_])',
    );
    protected $emStrongRelist = array(
        '' => '(?:(?<!\*)\*\*\*(?!\*)|(?<![a-zA-Z0-9_])___(?!_))(?![\.,:;]?\s)',
        '***' => '(?<![\s*])\*\*\*(?!\*)',
        '___' => '(?<![\s_])___(?![a-zA-Z0-9_])',
    );


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
            $value = trim($this->runSpanGamut($value));

            # Check if this should be enclosed in a paragraph.
            # Clean tag hashes & block tag hashes are left alone.
            $is_p = !preg_match('/^B\x1A[0-9]+B|^C\x1A[0-9]+C$/', $value);

            if ($is_p) {
                $value = "<p>$value</p>";
            }
            $grafs[$key] = $value;
        }

        # Join grafs in one text, then unhash HTML tags.
        $text = implode("\n\n", $grafs);

        # Finish by removing any tag hashes still present in $text.
        $text = $this->unhash($text);

        return $text;
    }


    ### Footnotes

    protected function stripFootnotes($text) {
        #
        # Strips link definitions from text, stores the URLs and titles in
        # hash references.
        #
        $lessThanTab = $this->tabWidth - 1;

        # Link defs are in the form: [^id]: url "optional title"
        $text = preg_replace_callback(
            '{
                        ^[ ]{0,' . $lessThanTab . '}\[\^(.+?)\][ ]?:	# note_id = $1
			  [ ]*
			  \n?					# maybe *one* newline
			(						# text = $2 (no blank lines allowed)
				(?:
					.+				# actual text
				|
					\n				# newlines but
					(?!\[.+?\][ ]?:\s)# negative lookahead for footnote or link definition marker.
					(?!\n+[ ]{0,3}\S)# ensure line is not blank and followed
									# by non-indented content
				)*
			)
			}xm',
            array($this, 'stripFootnotesCallback'),
            $text
        );
        return $text;
    }

    protected function stripFootnotesCallback($matches) {
        $noteId = $this->fnIdPrefix . $matches[1];
        $this->footnotes[$noteId] = $this->outdent($matches[2]);
        return ''; # String that will replace the block
    }


    protected function doFootnotes($text) {
        #
        # Replace footnote references in $text [^id] with a special text-token
        # which will be replaced by the actual footnote marker in appendFootnotes.
        #
        if (!$this->inAnchor) {
            $text = preg_replace('{\[\^(.+?)\]}', "F\x1Afn:\\1\x1A:", $text);
        }
        return $text;
    }


    protected function appendFootnotes($text) {
        #
        # Append footnote list to text.
        #
        $text = preg_replace_callback(
            '{F\x1Afn:(.*?)\x1A:}',
            array($this, 'appendFootnotesCallback'),
            $text
        );

        if (!empty($this->footnotesOrdered)) {
            $text .= "\n\n";
            $text .= "<div class=\"footnotes\">\n";
            $text .= "<hr" . $this->emptyElementSuffix . "\n";
            $text .= "<ol>\n\n";

            $attr = "";
            if ($this->fnBacklinkClass != "") {
                $class = $this->fnBacklinkClass;
                $class = $this->encodeAttribute($class);
                $attr .= " class=\"$class\"";
            }
            if ($this->fnBacklinkTitle != "") {
                $title = $this->fnBacklinkTitle;
                $title = $this->encodeAttribute($title);
                $attr .= " title=\"$title\"";
            }
            $num = 0;

            while (!empty($this->footnotesOrdered)) {
                $footnote = reset($this->footnotesOrdered);
                $noteId = key($this->footnotesOrdered);
                unset($this->footnotesOrdered[$noteId]);
                $refCount = $this->footnotesRefCount[$noteId];
                unset($this->footnotesRefCount[$noteId]);
                unset($this->footnotes[$noteId]);

                $footnote .= "\n"; # Need to append newline before parsing.
                $footnote = $this->runBlockGamut("$footnote\n");
                $footnote = preg_replace_callback(
                    '{F\x1Afn:(.*?)\x1A:}',
                    array($this, 'appendFootnotesCallback'),
                    $footnote
                );

                $attr = str_replace("%%", ++$num, $attr);
                $noteId = $this->encodeAttribute($noteId);

                # Prepare backlink, multiple backlinks if multiple references
                $backlink = "<a href=\"#fnref:$noteId\"$attr>&#8617;</a>";
                for ($refNum = 2; $refNum <= $refCount; ++$refNum) {
                    $backlink .= " <a href=\"#fnref$refNum:$noteId\"$attr>&#8617;</a>";
                }
                # Add backlink to last paragraph; create new paragraph if needed.
                if (preg_match('{</p>$}', $footnote)) {
                    $footnote = substr($footnote, 0, -4) . "&#160;$backlink</p>";
                }
                else {
                    $footnote .= "\n\n<p>$backlink</p>";
                }

                $text .= "<li id=\"fn:$noteId\">\n";
                $text .= $footnote . "\n";
                $text .= "</li>\n\n";
            }

            $text .= "</ol>\n";
            $text .= "</div>";
        }
        return $text;
    }

    protected function appendFootnotesCallback($matches) {
        $nodeId = $this->fnIdPrefix . $matches[1];

        # Create footnote marker only if it has a corresponding footnote *and*
        # the footnote hasn't been used by another marker.
        if (isset($this->footnotes[$nodeId])) {
            $num =& $this->footnotesNumbers[$nodeId];
            if (!isset($num)) {
                # Transfer footnote content to the ordered list and give it its
                # number
                $this->footnotesOrdered[$nodeId] = $this->footnotes[$nodeId];
                $this->footnotesRefCount[$nodeId] = 1;
                $num = $this->footnoteCounter++;
                $refCountMark = '';
            }
            else {
                $refCountMark = $this->footnotesRefCount[$nodeId] += 1;
            }

            $attr = "";
            if ($this->fnLinkClass != "") {
                $class = $this->fnLinkClass;
                $class = $this->encodeAttribute($class);
                $attr .= " class=\"$class\"";
            }
            if ($this->fnLinkTitle != "") {
                $title = $this->fnLinkTitle;
                $title = $this->encodeAttribute($title);
                $attr .= " title=\"$title\"";
            }

            $attr = str_replace("%%", $num, $attr);
            $nodeId = $this->encodeAttribute($nodeId);

            return "<sup id=\"fnref$refCountMark:$nodeId\">" . "<a href=\"#fn:$nodeId\"$attr>$num</a>" . "</sup>";
        }

        return "[^" . $matches[1] . "]";
    }


    ### Abbreviations ###

    protected function stripAbbreviations($text) {
        #
        # Strips abbreviations from text, stores titles in hash references.
        #
        $lessThanTab = $this->tabWidth - 1;

        # Link defs are in the form: [id]*: url "optional title"
        $text = preg_replace_callback(
            '{
                        ^[ ]{0,' . $lessThanTab . '}\*\[(.+?)\][ ]?:	# abbr_id = $1
			(.*)					# text = $2 (no blank lines allowed)
			}xm',
            array($this, 'stripAbbreviationsCallback'),
            $text
        );
        return $text;
    }

    protected function stripAbbreviationsCallback($matches) {
        $abbrWord = $matches[1];
        $abbrDesc = $matches[2];
        if ($this->abbrWordRe) {
            $this->abbrWordRe .= '|';
        }
        $this->abbrWordRe .= preg_quote($abbrWord);
        $this->abbrDesciptions[$abbrWord] = trim($abbrDesc);
        return ''; # String that will replace the block
    }


    protected function doAbbreviations($text) {
        #
        # Find defined abbreviations in text and wrap them in <abbr> elements.
        #
        if ($this->abbrWordRe) {
            // cannot use the /x modifier because abbr_word_re may
            // contain significant spaces:
            $text = preg_replace_callback(
                '{' . '(?<![\w\x1A])' . '(?:' . $this->abbrWordRe . ')' . '(?![\w\x1A])' . '}',
                array($this, 'doAbbreviationsCallback'),
                $text
            );
        }
        return $text;
    }

    protected function doAbbreviationsCallback($matches) {
        $abbr = $matches[0];
        if (isset($this->abbrDesciptions[$abbr])) {
            $desc = $this->abbrDesciptions[$abbr];
            if (empty($desc)) {
                return $this->hashPart("<abbr>$abbr</abbr>");
            }
            else {
                $desc = $this->encodeAttribute($desc);
                return $this->hashPart("<abbr title=\"$desc\">$abbr</abbr>");
            }
        }
        else {
            return $matches[0];
        }
    }

}