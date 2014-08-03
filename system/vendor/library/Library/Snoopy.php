<?php
namespace Library;

    /*************************************************
     *
     * Snoopy - the PHP net client
     * Author: Monte Ohrt <monte@ispi.net>
     * Copyright (c): 1999-2008 New Digital Group, all rights reserved
     * Version: 1.2.4
     * This library is free software; you can redistribute it and/or
     * modify it under the terms of the GNU Lesser General Public
     * License as published by the Free Software Foundation; either
     * version 2.1 of the License, or (at your option) any later version.
     *
     * This library is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
     * Lesser General Public License for more details.
     *
     * You should have received a copy of the GNU Lesser General Public
     * License along with this library; if not, write to the Free Software
     * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
     *
     * You may contact the author of Snoopy by e-mail at:
     * monte@ohrt.com
     *
     * The latest version of Snoopy can be obtained from:
     * http://snoopy.sourceforge.net/
     *************************************************/
/**
 * Snoopy
 * PHP采集库
 * @package Library
 */
class Snoopy {

    /**** Public variables ****/

    /* user definable vars */

    var $host = "www.php.net"; // host name we are connecting to
    var $port = 80; // port we are connecting to
    var $proxyHost = ""; // proxy host to use
    var $proxyPort = ""; // proxy port to use
    var $proxyUser = ""; // proxy user to use
    var $proxyPass = ""; // proxy password to use

    var $agent = "Snoopy v1.2.4"; // agent we masquerade as
    var $referer = ""; // referer info to pass
    var $cookies = array(); // array of cookies to pass
    // $cookies["username"]="joe";
    var $rawHeaders = array(); // array of raw headers to send
    // $rawheaders["Content-type"]="text/html";

    var $maxRedirs = 5; // http redirection depth maximum. 0 = disallow
    var $lastRedirectAddr = ""; // contains address of last redirected address
    var $offSiteOk = true; // allows redirection off-site
    var $maxFrames = 0; // frame content depth maximum. 0 = disallow
    var $expandLinks = true; // expand links to fully qualified URLs.
    // this only applies to fetchlinks()
    // submitlinks(), and submittext()
    var $passCookies = true; // pass set cookies back through redirects
    // NOTE: this currently does not respect
    // dates, domains or paths.

    var $user = ""; // user for http authentication
    var $pass = ""; // password for http authentication

    // http accept types
    var $accept = "image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*";

    var $results = ""; // where the content is put

    var $error = ""; // error messages sent here
    var $responseCode = ""; // response code returned from server
    var $headers = array(); // headers returned from server sent here
    var $maxLength = 500000; // max return data length (body)
    var $readTimeout = 0; // timeout on read operations, in seconds
    // supported only since PHP 4 Beta 4
    // set to 0 to disallow timeouts
    var $timedOut = false; // if a read operation timed out
    var $status = 0; // http request status

    var $tempDir = "/tmp"; // temporary directory that the webserver
    // has permission to write to.
    // under Windows, this should be C:\temp

    var $curlPath = "/usr/local/bin/curl";
    // Snoopy will use cURL for fetching
    // SSL content if a full system path to
    // the cURL binary is supplied here.
    // set to false if you do not have
    // cURL installed. See http://curl.haxx.se
    // for details on installing cURL.
    // Snoopy does *not* use the cURL
    // library functions built into php,
    // as these functions are not stable
    // as of this Snoopy release.

    /**** Private variables ****/

    var $maxLineLen = 4096; // max line length (headers)

    var $httpMethod = "GET"; // default http request method
    var $httpVersion = "HTTP/1.0"; // default http request version
    var $submitMethod = "POST"; // default submit method
    var $submitType = "application/x-www-form-urlencoded"; // default submit type
    var $mimeBoundary = ""; // MIME boundary for multipart/form-data submit type
    var $redirectAddr = false; // will be set if page fetched is a redirect
    var $redirectDepth = 0; // increments on an http redirect
    var $frameUrls = array(); // frame src urls
    var $frameDepth = 0; // increments on frame depth

    var $isProxy = false; // set if using a proxy server
    var $fpTimeout = 30; // timeout for socket connection

    /*======================================================================*\
        Function:   fetch
        Purpose:    fetch the contents of a web page
                    (and possibly other protocols in the
                    future like ftp, nntp, gopher, etc.)
        Input:      $URI    the location of the page to fetch
        Output:     $this->results  the output text from the fetch
    \*======================================================================*/

    function fetch($URI) {

        //preg_match("|^([^:]+)://([^:/]+)(:[\d]+)*(.*)|",$URI,$URI_PARTS);
        $URI_PARTS = parse_url($URI);
        if (!empty($URI_PARTS["user"])) {
            $this->user = $URI_PARTS["user"];
        }
        if (!empty($URI_PARTS["pass"])) {
            $this->pass = $URI_PARTS["pass"];
        }
        if (empty($URI_PARTS["query"])) {
            $URI_PARTS["query"] = '';
        }
        if (empty($URI_PARTS["path"])) {
            $URI_PARTS["path"] = '';
        }

        switch (strtolower($URI_PARTS["scheme"])) {
            case "http":
                $this->host = $URI_PARTS["host"];
                if (!empty($URI_PARTS["port"])) {
                    $this->port = $URI_PARTS["port"];
                }
                if ($this->connect($fp)) {
                    if ($this->isProxy) {
                        // using proxy, send entire URI
                        $this->httprequest($URI, $fp, $URI, $this->httpMethod);
                    }
                    else {
                        $path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
                        // no proxy, send only the path
                        $this->httprequest($path, $fp, $URI, $this->httpMethod);
                    }

                    $this->disconnect($fp);

                    if ($this->redirectAddr) {
                        /* url was redirected, check if we've hit the max depth */
                        if ($this->maxRedirs > $this->redirectDepth) {
                            // only follow redirect if it's on this site, or offsiteok is true
                            if (preg_match("|^http://" . preg_quote($this->host) . "|i", $this->redirectAddr) ||
                                $this->offSiteOk
                            ) {
                                /* follow the redirect */
                                $this->redirectDepth++;
                                $this->lastRedirectAddr = $this->redirectAddr;
                                $this->fetch($this->redirectAddr);
                            }
                        }
                    }

                    if ($this->frameDepth < $this->maxFrames && count($this->frameUrls) > 0) {
                        $frameUrls = $this->frameUrls;
                        $this->frameUrls = array();

                        while (list(, $frameUrl) = each($frameUrls)) {
                            if ($this->frameDepth < $this->maxFrames) {
                                $this->fetch($frameUrl);
                                $this->frameDepth++;
                            }
                            else {
                                break;
                            }
                        }
                    }
                }
                else {
                    return false;
                }
                return true;
                break;
            case "https":
                if (!$this->curlPath) {
                    return false;
                }
                if (function_exists("is_executable")) {
                    if (!is_executable($this->curlPath)) {
                        return false;
                    }
                }
                $this->host = $URI_PARTS["host"];
                if (!empty($URI_PARTS["port"])) {
                    $this->port = $URI_PARTS["port"];
                }
                if ($this->isProxy) {
                    // using proxy, send entire URI
                    $this->httpsrequest($URI, $URI, $this->httpMethod);
                }
                else {
                    $path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
                    // no proxy, send only the path
                    $this->httpsrequest($path, $URI, $this->httpMethod);
                }

                if ($this->redirectAddr) {
                    /* url was redirected, check if we've hit the max depth */
                    if ($this->maxRedirs > $this->redirectDepth) {
                        // only follow redirect if it's on this site, or offsiteok is true
                        if (preg_match("|^http://" . preg_quote($this->host) . "|i", $this->redirectAddr) ||
                            $this->offSiteOk
                        ) {
                            /* follow the redirect */
                            $this->redirectDepth++;
                            $this->lastRedirectAddr = $this->redirectAddr;
                            $this->fetch($this->redirectAddr);
                        }
                    }
                }

                if ($this->frameDepth < $this->maxFrames && count($this->frameUrls) > 0) {
                    $frameUrls = $this->frameUrls;
                    $this->frameUrls = array();

                    while (list(, $frameUrl) = each($frameUrls)) {
                        if ($this->frameDepth < $this->maxFrames) {
                            $this->fetch($frameUrl);
                            $this->frameDepth++;
                        }
                        else {
                            break;
                        }
                    }
                }
                return true;
                break;
            default:
                // not a valid protocol
                $this->error = 'Invalid protocol "' . $URI_PARTS["scheme"] . "\"\n";
                return false;
                break;
        }
        return true;
    }

    /*======================================================================*\
        Function:   submit
        Purpose:    submit an http form
        Input:      $URI    the location to post the data
                    $formvars   the formvars to use.
                        format: $formvars["var"] = "val";
                    $formfiles  an array of files to submit
                        format: $formfiles["var"] = "/dir/filename.ext";
        Output:     $this->results  the text output from the post
    \*======================================================================*/

    function submit($URI, $formvars = "", $formfiles = "") {
        unset($postdata);

        $postdata = $this->preparePostBody($formvars, $formfiles);

        $URI_PARTS = parse_url($URI);
        if (!empty($URI_PARTS["user"])) {
            $this->user = $URI_PARTS["user"];
        }
        if (!empty($URI_PARTS["pass"])) {
            $this->pass = $URI_PARTS["pass"];
        }
        if (empty($URI_PARTS["query"])) {
            $URI_PARTS["query"] = '';
        }
        if (empty($URI_PARTS["path"])) {
            $URI_PARTS["path"] = '';
        }

        switch (strtolower($URI_PARTS["scheme"])) {
            case "http":
                $this->host = $URI_PARTS["host"];
                if (!empty($URI_PARTS["port"])) {
                    $this->port = $URI_PARTS["port"];
                }
                if ($this->connect($fp)) {
                    if ($this->isProxy) {
                        // using proxy, send entire URI
                        $this->httprequest($URI, $fp, $URI, $this->submitMethod, $this->submitType, $postdata);
                    }
                    else {
                        $path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
                        // no proxy, send only the path
                        $this->httprequest($path, $fp, $URI, $this->submitMethod, $this->submitType, $postdata);
                    }

                    $this->disconnect($fp);

                    if ($this->redirectAddr) {
                        /* url was redirected, check if we've hit the max depth */
                        if ($this->maxRedirs > $this->redirectDepth) {
                            if (!preg_match("|^" . $URI_PARTS["scheme"] . "://|", $this->redirectAddr)) {
                                $this->redirectAddr = $this->expandlinks(
                                    $this->redirectAddr,
                                    $URI_PARTS["scheme"] . "://" . $URI_PARTS["host"]
                                );
                            }

                            // only follow redirect if it's on this site, or offsiteok is true
                            if (preg_match("|^http://" . preg_quote($this->host) . "|i", $this->redirectAddr) ||
                                $this->offSiteOk
                            ) {
                                /* follow the redirect */
                                $this->redirectDepth++;
                                $this->lastRedirectAddr = $this->redirectAddr;
                                if (strpos($this->redirectAddr, "?") > 0) {
                                    $this->fetch(
                                        $this->redirectAddr
                                    );
                                } // the redirect has changed the request method from post to get
                                else {
                                    $this->submit($this->redirectAddr, $formvars, $formfiles);
                                }
                            }
                        }
                    }

                    if ($this->frameDepth < $this->maxFrames && count($this->frameUrls) > 0) {
                        $frameurls = $this->frameUrls;
                        $this->frameUrls = array();

                        while (list(, $frameurl) = each($frameurls)) {
                            if ($this->frameDepth < $this->maxFrames) {
                                $this->fetch($frameurl);
                                $this->frameDepth++;
                            }
                            else {
                                break;
                            }
                        }
                    }

                }
                else {
                    return false;
                }
                return true;
                break;
            case "https":
                if (!$this->curlPath) {
                    return false;
                }
                if (function_exists("is_executable")) {
                    if (!is_executable($this->curlPath)) {
                        return false;
                    }
                }
                $this->host = $URI_PARTS["host"];
                if (!empty($URI_PARTS["port"])) {
                    $this->port = $URI_PARTS["port"];
                }
                if ($this->isProxy) {
                    // using proxy, send entire URI
                    $this->httpsrequest($URI, $URI, $this->submitMethod, $this->submitType, $postdata);
                }
                else {
                    $path = $URI_PARTS["path"] . ($URI_PARTS["query"] ? "?" . $URI_PARTS["query"] : "");
                    // no proxy, send only the path
                    $this->httpsrequest($path, $URI, $this->submitMethod, $this->submitType, $postdata);
                }

                if ($this->redirectAddr) {
                    /* url was redirected, check if we've hit the max depth */
                    if ($this->maxRedirs > $this->redirectDepth) {
                        if (!preg_match("|^" . $URI_PARTS["scheme"] . "://|", $this->redirectAddr)) {
                            $this->redirectAddr = $this->expandlinks(
                                $this->redirectAddr,
                                $URI_PARTS["scheme"] . "://" . $URI_PARTS["host"]
                            );
                        }

                        // only follow redirect if it's on this site, or offsiteok is true
                        if (preg_match("|^http://" . preg_quote($this->host) . "|i", $this->redirectAddr) ||
                            $this->offSiteOk
                        ) {
                            /* follow the redirect */
                            $this->redirectDepth++;
                            $this->lastRedirectAddr = $this->redirectAddr;
                            if (strpos($this->redirectAddr, "?") > 0) {
                                $this->fetch(
                                    $this->redirectAddr
                                );
                            } // the redirect has changed the request method from post to get
                            else {
                                $this->submit($this->redirectAddr, $formvars, $formfiles);
                            }
                        }
                    }
                }

                if ($this->frameDepth < $this->maxFrames && count($this->frameUrls) > 0) {
                    $frameurls = $this->frameUrls;
                    $this->frameUrls = array();

                    while (list(, $frameurl) = each($frameurls)) {
                        if ($this->frameDepth < $this->maxFrames) {
                            $this->fetch($frameurl);
                            $this->frameDepth++;
                        }
                        else {
                            break;
                        }
                    }
                }
                return true;
                break;

            default:
                // not a valid protocol
                $this->error = 'Invalid protocol "' . $URI_PARTS["scheme"] . "\"\n";
                return false;
                break;
        }
        return true;
    }

    /*======================================================================*\
        Function:   fetchlinks
        Purpose:    fetch the links from a web page
        Input:      $URI    where you are fetching from
        Output:     $this->results  an array of the URLs
    \*======================================================================*/

    function fetchlinks($URI) {
        if ($this->fetch($URI)) {
            if ($this->lastRedirectAddr) {
                $URI = $this->lastRedirectAddr;
            }
            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++)
                    $this->results[$x] = $this->striplinks($this->results[$x]);
            }
            else {
                $this->results = $this->striplinks($this->results);
            }

            if ($this->expandLinks) {
                $this->results = $this->expandlinks($this->results, $URI);
            }
            return true;
        }
        else {
            return false;
        }
    }

    /*======================================================================*\
        Function:   fetchform
        Purpose:    fetch the form elements from a web page
        Input:      $URI    where you are fetching from
        Output:     $this->results  the resulting html form
    \*======================================================================*/

    function fetchform($URI) {

        if ($this->fetch($URI)) {

            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++)
                    $this->results[$x] = $this->stripform($this->results[$x]);
            }
            else {
                $this->results = $this->stripform($this->results);
            }

            return true;
        }
        else {
            return false;
        }
    }


    /*======================================================================*\
        Function:   fetchtext
        Purpose:    fetch the text from a web page, stripping the links
        Input:      $URI    where you are fetching from
        Output:     $this->results  the text from the web page
    \*======================================================================*/

    function fetchtext($URI) {
        if ($this->fetch($URI)) {
            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++)
                    $this->results[$x] = $this->striptext($this->results[$x]);
            }
            else {
                $this->results = $this->striptext($this->results);
            }
            return true;
        }
        else {
            return false;
        }
    }

    /*======================================================================*\
        Function:   submitlinks
        Purpose:    grab links from a form submission
        Input:      $URI    where you are submitting from
        Output:     $this->results  an array of the links from the post
    \*======================================================================*/

    function submitlinks($URI, $formvars = "", $formfiles = "") {
        if ($this->submit($URI, $formvars, $formfiles)) {
            if ($this->lastRedirectAddr) {
                $URI = $this->lastRedirectAddr;
            }
            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++) {
                    $this->results[$x] = $this->striplinks($this->results[$x]);
                    if ($this->expandLinks) {
                        $this->results[$x] = $this->expandlinks($this->results[$x], $URI);
                    }
                }
            }
            else {
                $this->results = $this->striplinks($this->results);
                if ($this->expandLinks) {
                    $this->results = $this->expandlinks($this->results, $URI);
                }
            }
            return true;
        }
        else {
            return false;
        }
    }

    /*======================================================================*\
        Function:   submittext
        Purpose:    grab text from a form submission
        Input:      $URI    where you are submitting from
        Output:     $this->results  the text from the web page
    \*======================================================================*/

    function submittext($URI, $formvars = "", $formfiles = "") {
        if ($this->submit($URI, $formvars, $formfiles)) {
            if ($this->lastRedirectAddr) {
                $URI = $this->lastRedirectAddr;
            }
            if (is_array($this->results)) {
                for ($x = 0; $x < count($this->results); $x++) {
                    $this->results[$x] = $this->striptext($this->results[$x]);
                    if ($this->expandLinks) {
                        $this->results[$x] = $this->expandlinks($this->results[$x], $URI);
                    }
                }
            }
            else {
                $this->results = $this->striptext($this->results);
                if ($this->expandLinks) {
                    $this->results = $this->expandlinks($this->results, $URI);
                }
            }
            return true;
        }
        else {
            return false;
        }
    }


    /*======================================================================*\
        Function:   set_submit_multipart
        Purpose:    Set the form submission content type to
                    multipart/form-data
    \*======================================================================*/
    function setSubmitMultipart() {
        $this->submitType = "multipart/form-data";
    }


    /*======================================================================*\
        Function:   set_submit_normal
        Purpose:    Set the form submission content type to
                    application/x-www-form-urlencoded
    \*======================================================================*/
    function setSubmitNormal() {
        $this->submitType = "application/x-www-form-urlencoded";
    }




    /*======================================================================*\
        Private functions
    \*======================================================================*/


    /*======================================================================*\
        Function:   _striplinks
        Purpose:    strip the hyperlinks from an html document
        Input:      $document   document to strip.
        Output:     $match      an array of the links
    \*======================================================================*/

    function striplinks($document) {
        preg_match_all(
            "'<\s*a\s.*?href\s*=\s*          # find <a href=
                                    ([\"\'])?                   # find single or double quote
                                    (?(1) (.*?)\\1 | ([^\s\>]+))        # if quote found, match up to next matching
                                                                # quote, otherwise match up to next space
                                    'isx",
            $document,
            $links
        );


        // catenate the non-empty matches from the conditional subpattern

        while (list($key, $val) = each($links[2])) {
            if (!empty($val)) {
                $match[] = $val;
            }
        }

        while (list($key, $val) = each($links[3])) {
            if (!empty($val)) {
                $match[] = $val;
            }
        }

        // return the links
        return $match;
    }

    /*======================================================================*\
        Function:   _stripform
        Purpose:    strip the form elements from an html document
        Input:      $document   document to strip.
        Output:     $match      an array of the links
    \*======================================================================*/

    function stripform($document) {
        preg_match_all(
            "'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi",
            $document,
            $elements
        );

        // catenate the matches
        $match = implode("\r\n", $elements[0]);

        // return the links
        return $match;
    }


    /*======================================================================*\
        Function:   _striptext
        Purpose:    strip the text from an html document
        Input:      $document   document to strip.
        Output:     $text       the resulting text
    \*======================================================================*/

    function striptext($document) {

        // I didn't use preg eval (//e) since that is only available in PHP 4.0.
        // so, list your entities one by one here. I included some of the
        // more common ones.

        $search = array(
            "'<script[^>]*?>.*?</script>'si", // strip out javascript
            "'<[\/\!]*?[^<>]*?>'si", // strip out html tags
            "'([\r\n])[\s]+'", // strip out white space
            "'&(quot|#34|#034|#x22);'i", // replace html entities
            "'&(amp|#38|#038|#x26);'i", // added hexadecimal values
            "'&(lt|#60|#060|#x3c);'i",
            "'&(gt|#62|#062|#x3e);'i",
            "'&(nbsp|#160|#xa0);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&(reg|#174);'i",
            "'&(deg|#176);'i",
            "'&(#39|#039|#x27);'",
            "'&(euro|#8364);'i", // europe
            "'&a(uml|UML);'", // german
            "'&o(uml|UML);'",
            "'&u(uml|UML);'",
            "'&A(uml|UML);'",
            "'&O(uml|UML);'",
            "'&U(uml|UML);'",
            "'&szlig;'i",
        );
        $replace = array(
            "",
            "",
            "\\1",
            "\"",
            "&",
            "<",
            ">",
            " ",
            chr(161),
            chr(162),
            chr(163),
            chr(169),
            chr(174),
            chr(176),
            chr(39),
            chr(128),
            "ä",
            "ö",
            "ü",
            "Ä",
            "Ö",
            "Ü",
            "ß",
        );

        $text = preg_replace($search, $replace, $document);

        return $text;
    }

    /*======================================================================*\
        Function:   _expandlinks
        Purpose:    expand each link into a fully qualified URL
        Input:      $links          the links to qualify
                    $URI            the full URI to get the base from
        Output:     $expandedLinks  the expanded links
    \*======================================================================*/

    function expandLinks($links, $URI) {

        preg_match("/^[^\?]+/", $URI, $match);

        $match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|", "", $match[0]);
        $match = preg_replace("|/$|", "", $match);
        $matchPart = parse_url($match);
        $matchRoot = $matchPart["scheme"] . "://" . $matchPart["host"];

        $search = array(
            "|^http://" . preg_quote($this->host) . "|i",
            "|^(\/)|i",
            "|^(?!http://)(?!mailto:)|i",
            "|/\./|",
            "|/[^\/]+/\.\./|"
        );

        $replace = array(
            "",
            $matchRoot . "/",
            $match . "/",
            "/",
            "/"
        );

        $expandedLinks = preg_replace($search, $replace, $links);

        return $expandedLinks;
    }

    /*======================================================================*\
        Function:   _httprequest
        Purpose:    go get the http data from the server
        Input:      $url        the url to fetch
                    $fp         the current open file pointer
                    $URI        the full URI
                    $body       body contents to send if any (POST)
        Output:
    \*======================================================================*/

    function httprequest($url, $fp, $URI, $httpMethod, $contentType = "", $body = "") {
        $cookieHeaders = '';
        if ($this->passCookies && $this->redirectAddr) {
            $this->setcookies();
        }

        $URI_PARTS = parse_url($URI);
        if (empty($url)) {
            $url = "/";
        }
        $headers = $httpMethod . " " . $url . " " . $this->httpVersion . "\r\n";
        if (!empty($this->agent)) {
            $headers .= "User-Agent: " . $this->agent . "\r\n";
        }
        if (!empty($this->host) && !isset($this->rawHeaders['Host'])) {
            $headers .= "Host: " . $this->host;
            if (!empty($this->port)) {
                $headers .= ":" . $this->port;
            }
            $headers .= "\r\n";
        }
        if (!empty($this->accept)) {
            $headers .= "Accept: " . $this->accept . "\r\n";
        }
        if (!empty($this->referer)) {
            $headers .= "Referer: " . $this->referer . "\r\n";
        }
        if (!empty($this->cookies)) {
            if (!is_array($this->cookies)) {
                $this->cookies = (array)$this->cookies;
            }

            reset($this->cookies);
            if (count($this->cookies) > 0) {
                $cookieHeaders .= 'Cookie: ';
                foreach ($this->cookies as $cookieKey => $cookieVal) {
                    $cookieHeaders .= $cookieKey . "=" . urlencode($cookieVal) . "; ";
                }
                $headers .= substr($cookieHeaders, 0, -2) . "\r\n";
            }
        }
        if (!empty($this->rawHeaders)) {
            if (!is_array($this->rawHeaders)) {
                $this->rawHeaders = (array)$this->rawHeaders;
            }
            while (list($headerKey, $headerVal) = each($this->rawHeaders))
                $headers .= $headerKey . ": " . $headerVal . "\r\n";
        }
        if (!empty($contentType)) {
            $headers .= "Content-type: $contentType";
            if ($contentType == "multipart/form-data") {
                $headers .= "; boundary=" . $this->mimeBoundary;
            }
            $headers .= "\r\n";
        }
        if (!empty($body)) {
            $headers .= "Content-length: " . strlen($body) . "\r\n";
        }
        if (!empty($this->user) || !empty($this->pass)) {
            $headers .= "Authorization: Basic " . base64_encode($this->user . ":" . $this->pass) . "\r\n";
        }

        //add proxy auth headers
        if (!empty($this->proxyUser)) {
            $headers .=
                'Proxy-Authorization: ' .
                'Basic ' .
                base64_encode($this->proxyUser . ':' . $this->proxyPass) .
                "\r\n";
        }


        $headers .= "\r\n";

        // set the read timeout if needed
        if ($this->readTimeout > 0) {
            socket_set_timeout($fp, $this->readTimeout);
        }
        $this->timedOut = false;

        fwrite($fp, $headers . $body, strlen($headers . $body));

        $this->redirectAddr = false;
        unset($this->headers);

        while ($currentHeader = fgets($fp, $this->maxLineLen)) {
            if ($this->readTimeout > 0 && $this->checkTimeout($fp)) {
                $this->status = -100;
                return false;
            }

            if ($currentHeader == "\r\n") {
                break;
            }

            // if a header begins with Location: or URI:, set the redirect
            if (preg_match("/^(Location:|URI:)/i", $currentHeader)) {
                // get URL portion of the redirect
                preg_match("/^(Location:|URI:)[ ]+(.*)/i", chop($currentHeader), $matches);
                // look for :// in the Location header to see if hostname is included
                if (!preg_match("|\:\/\/|", $matches[2])) {
                    // no host in the path, so prepend
                    $this->redirectAddr = $URI_PARTS["scheme"] . "://" . $this->host . ":" . $this->port;
                    // eliminate double slash
                    if (!preg_match("|^/|", $matches[2])) {
                        $this->redirectAddr .= "/" . $matches[2];
                    }
                    else {
                        $this->redirectAddr .= $matches[2];
                    }
                }
                else {
                    $this->redirectAddr = $matches[2];
                }
            }

            if (preg_match("|^HTTP/|", $currentHeader)) {
                if (preg_match("|^HTTP/[^\s]*\s(.*?)\s|", $currentHeader, $status)) {
                    $this->status = $status[1];
                }
                $this->responseCode = $currentHeader;
            }

            $this->headers[] = $currentHeader;
        }

        $results = '';
        do {
            $data = fread($fp, $this->maxLength);
            if (strlen($data) == 0) {
                break;
            }
            $results .= $data;
        } while (true);

        if ($this->readTimeout > 0 && $this->checkTimeout($fp)) {
            $this->status = -100;
            return false;
        }

        // check if there is a a redirect meta tag

        if (preg_match(
            "'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",
            $results,
            $match
        )
        ) {
            $this->redirectAddr = $this->expandlinks($match[1], $URI);
        }

        // have we hit our frame depth and is there frame src to fetch?
        if (($this->frameDepth < $this->maxFrames) &&
            preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i", $results, $match)
        ) {
            $this->results[] = $results;
            for ($x = 0; $x < count($match[1]); $x++)
                $this->frameUrls[] = $this->expandlinks($match[1][$x], $URI_PARTS["scheme"] . "://" . $this->host);
        }
        // have we already fetched framed content?
        elseif (is_array($this->results)) {
            $this->results[] = $results;
        }
        // no framed content
        else {
            $this->results = $results;
        }

        return true;
    }

    /*======================================================================*\
        Function:   _httpsrequest
        Purpose:    go get the https data from the server using curl
        Input:      $url        the url to fetch
                    $URI        the full URI
                    $body       body contents to send if any (POST)
        Output:
    \*======================================================================*/

    function httpsrequest($url, $URI, $httpMethod, $contentType = "", $body = "") {
        if ($this->passCookies && $this->redirectAddr) {
            $this->setcookies();
        }

        $headers = array();

        $URI_PARTS = parse_url($URI);
        if (empty($url)) {
            $url = "/";
        }
        // GET ... header not needed for curl
        //$headers[] = $httpMethod." ".$url." ".$this->httpversion;
        if (!empty($this->agent)) {
            $headers[] = "User-Agent: " . $this->agent;
        }
        if (!empty($this->host)) {
            if (!empty($this->port)) {
                $headers[] = "Host: " . $this->host . ":" . $this->port;
            }
            else {
                $headers[] = "Host: " . $this->host;
            }
        }
        if (!empty($this->accept)) {
            $headers[] = "Accept: " . $this->accept;
        }
        if (!empty($this->referer)) {
            $headers[] = "Referer: " . $this->referer;
        }
        if (!empty($this->cookies)) {
            if (!is_array($this->cookies)) {
                $this->cookies = (array)$this->cookies;
            }

            reset($this->cookies);
            if (count($this->cookies) > 0) {
                $cookieStr = 'Cookie: ';
                foreach ($this->cookies as $cookieKey => $cookieVal) {
                    $cookieStr .= $cookieKey . "=" . urlencode($cookieVal) . "; ";
                }
                $headers[] = substr($cookieStr, 0, -2);
            }
        }
        if (!empty($this->rawHeaders)) {
            if (!is_array($this->rawHeaders)) {
                $this->rawHeaders = (array)$this->rawHeaders;
            }
            while (list($headerKey, $headerVal) = each($this->rawHeaders))
                $headers[] = $headerKey . ": " . $headerVal;
        }
        if (!empty($contentType)) {
            if ($contentType == "multipart/form-data") {
                $headers[] = "Content-type: $contentType; boundary=" . $this->mimeBoundary;
            }
            else {
                $headers[] = "Content-type: $contentType";
            }
        }
        if (!empty($body)) {
            $headers[] = "Content-length: " . strlen($body);
        }
        if (!empty($this->user) || !empty($this->pass)) {
            $headers[] = "Authorization: BASIC " . base64_encode($this->user . ":" . $this->pass);
        }

        for ($currHeader = 0; $currHeader < count($headers); $currHeader++) {
            $saferHeader = strtr($headers[$currHeader], "\"", " ");
            $cmdlineParams .= " -H \"" . $saferHeader . "\"";
        }

        if (!empty($body)) {
            $cmdlineParams .= " -d \"$body\"";
        }

        if ($this->readTimeout > 0) {
            $cmdlineParams .= " -m " . $this->readTimeout;
        }

        $headerfile = tempnam($tempDir, "sno");

        exec(
            $this->curlPath . " -k -D \"$headerfile\"" . $cmdlineParams . " \"" . escapeshellcmd($URI) . "\"",
            $results,
            $return
        );

        if ($return) {
            $this->error = "Error: cURL could not retrieve the document, error $return.";
            return false;
        }


        $results = implode("\r\n", $results);

        $resultHeaders = file("$headerfile");

        $this->redirectAddr = false;
        unset($this->headers);

        for ($currentHeader = 0; $currentHeader < count($resultHeaders); $currentHeader++) {

            // if a header begins with Location: or URI:, set the redirect
            if (preg_match("/^(Location: |URI: )/i", $resultHeaders[$currentHeader])) {
                // get URL portion of the redirect
                preg_match("/^(Location: |URI:)\s+(.*)/", chop($resultHeaders[$currentHeader]), $matches);
                // look for :// in the Location header to see if hostname is included
                if (!preg_match("|\:\/\/|", $matches[2])) {
                    // no host in the path, so prepend
                    $this->redirectAddr = $URI_PARTS["scheme"] . "://" . $this->host . ":" . $this->port;
                    // eliminate double slash
                    if (!preg_match("|^/|", $matches[2])) {
                        $this->redirectAddr .= "/" . $matches[2];
                    }
                    else {
                        $this->redirectAddr .= $matches[2];
                    }
                }
                else {
                    $this->redirectAddr = $matches[2];
                }
            }

            if (preg_match("|^HTTP/|", $resultHeaders[$currentHeader])) {
                $this->responseCode = $resultHeaders[$currentHeader];
            }

            $this->headers[] = $resultHeaders[$currentHeader];
        }

        // check if there is a a redirect meta tag

        if (preg_match(
            "'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",
            $results,
            $match
        )
        ) {
            $this->redirectAddr = $this->expandlinks($match[1], $URI);
        }

        // have we hit our frame depth and is there frame src to fetch?
        if (($this->frameDepth < $this->maxFrames) &&
            preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i", $results, $match)
        ) {
            $this->results[] = $results;
            for ($x = 0; $x < count($match[1]); $x++)
                $this->frameUrls[] = $this->expandlinks($match[1][$x], $URI_PARTS["scheme"] . "://" . $this->host);
        }
        // have we already fetched framed content?
        elseif (is_array($this->results)) {
            $this->results[] = $results;
        }
        // no framed content
        else {
            $this->results = $results;
        }

        unlink("$headerfile");

        return true;
    }

    /*======================================================================*\
        Function:   setcookies()
        Purpose:    set cookies for a redirection
    \*======================================================================*/

    function setcookies() {
        for ($x = 0; $x < count($this->headers); $x++) {
            if (preg_match('/^set-cookie:[\s]+([^=]+)=([^;]+)/i', $this->headers[$x], $match)) {
                $this->cookies[$match[1]] = urldecode($match[2]);
            }
        }
    }


    /*======================================================================*\
        Function:   _check_timeout
        Purpose:    checks whether timeout has occurred
        Input:      $fp file pointer
    \*======================================================================*/

    function checkTimeout($fp) {
        if ($this->readTimeout > 0) {
            $fpStatus = socket_get_status($fp);
            if ($fpStatus["timed_out"]) {
                $this->timedOut = true;
                return true;
            }
        }
        return false;
    }

    /*======================================================================*\
        Function:   _connect
        Purpose:    make a socket connection
        Input:      $fp file pointer
    \*======================================================================*/

    function connect(&$fp) {
        if (!empty($this->proxyHost) && !empty($this->proxyPort)) {
            $this->isProxy = true;

            $host = $this->proxyHost;
            $port = $this->proxyPort;
        }
        else {
            $host = $this->host;
            $port = $this->port;
        }

        $this->status = 0;

        if ($fp = fsockopen(
            $host,
            $port,
            $errNo,
            $errStr,
            $this->fpTimeout
        )
        ) {
            // socket connection succeeded

            return true;
        }
        else {
            // socket connection failed
            $this->status = $errNo;
            switch ($errNo) {
                case -3:
                    $this->error = "socket creation failed (-3)";
                case -4:
                    $this->error = "dns lookup failure (-4)";
                case -5:
                    $this->error = "connection refused or timed out (-5)";
                default:
                    $this->error = "connection failed (" . $errNo . ")";
            }
            return false;
        }
    }

    /*======================================================================*\
        Function:   _disconnect
        Purpose:    disconnect a socket connection
        Input:      $fp file pointer
    \*======================================================================*/

    function disconnect($fp) {
        return (fclose($fp));
    }


    /*======================================================================*\
        Function:   _prepare_post_body
        Purpose:    Prepare post body according to encoding type
        Input:      $formvars  - form variables
                    $formfiles - form upload files
        Output:     post body
    \*======================================================================*/

    function preparePostBody($formvars, $formfiles) {
        settype($formvars, "array");
        settype($formfiles, "array");
        $postdata = '';

        if (count($formvars) == 0 && count($formfiles) == 0) {
            return;
        }

        switch ($this->submitType) {
            case "application/x-www-form-urlencoded":
                reset($formvars);
                while (list($key, $val) = each($formvars)) {
                    if (is_array($val) || is_object($val)) {
                        while (list($curKey, $curVal) = each($val)) {
                            $postdata .= urlencode($key) . "[]=" . urlencode($curVal) . "&";
                        }
                    }
                    else {
                        $postdata .= urlencode($key) . "=" . urlencode($val) . "&";
                    }
                }
                break;

            case "multipart/form-data":
                $this->mimeBoundary = "Snoopy" . md5(uniqid(microtime()));

                reset($formvars);
                while (list($key, $val) = each($formvars)) {
                    if (is_array($val) || is_object($val)) {
                        while (list($curKey, $curVal) = each($val)) {
                            $postdata .= "--" . $this->mimeBoundary . "\r\n";
                            $postdata .= "Content-Disposition: form-data; name=\"$key\[\]\"\r\n\r\n";
                            $postdata .= "$curVal\r\n";
                        }
                    }
                    else {
                        $postdata .= "--" . $this->mimeBoundary . "\r\n";
                        $postdata .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
                        $postdata .= "$val\r\n";
                    }
                }

                reset($formfiles);
                while (list($fieldName, $fileNames) = each($formfiles)) {
                    settype($fileNames, "array");
                    while (list(, $fileName) = each($fileNames)) {
                        if (!is_readable($fileName)) {
                            continue;
                        }

                        $fp = fopen($fileName, "r");
                        $fileContent = fread($fp, filesize($fileName));
                        fclose($fp);
                        $baseName = basename($fileName);

                        $postdata .= "--" . $this->mimeBoundary . "\r\n";
                        $postdata .= "Content-Disposition: form-data; name=\"$fieldName\"; filename=\"$baseName\"\r\n\r\n";
                        $postdata .= "$fileContent\r\n";
                    }
                }
                $postdata .= "--" . $this->mimeBoundary . "--\r\n";
                break;
        }

        return $postdata;
    }
}