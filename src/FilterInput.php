<?php

declare(strict_types=1);
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf;

use DOMDocument;
use DOMNode;

/**
 * FilterInput is a class for filtering input from any data source
 *
 * Forked from the php input filter library by Daniel Morris
 *
 * Original Contributors: Gianpaolo Racca, Ghislain Picard,
 *                        Marco Wandschneider, Chris Tobin and Andrew Eddie.
 *
 * @category  Xmf\FilterInput
 * @package   Xmf
 * @author    Daniel Morris <dan@rootcube.com>
 * @author    Louis Landry <louis.landry@joomla.org>
 * @author    Grégory Mage (Aka Mage)
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2005 Daniel Morris
 * @copyright 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @copyright 2011-2023 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class FilterInput
{
    /** @var string[] */
    protected $tagsArray = []; // default is empty array

    /** @var string[] */
    protected $attrArray = []; // default is empty array

    /** @var int */
    protected $tagsMethod = 0; // default is 0

    /** @var int */
    protected $attrMethod = 0; // default is 0

    /** @var int */
    protected $xssAuto = 1; // default is 1

    /** @var string[] */
    protected $tagBlacklist = [
        'applet',
        'body',
        'bgsound',
        'base',
        'basefont',
        'embed',
        'frame',
        'frameset',
        'head',
        'html',
        'id',
        'iframe',
        'ilayer',
        'layer',
        'link',
        'meta',
        'name',
        'object',
        'script',
        'style',
        'title',
        'xml',
    ];

    /** @var string[] */
    // also, it will strip ALL event handlers
    protected $attrBlacklist = ['action', 'background', 'codebase', 'dynsrc', 'lowsrc'];

    /**
     * Constructor
     *
     * @param array $tagsArray  - list of user-defined tags
     * @param array $attrArray  - list of user-defined attributes
     * @param int   $tagsMethod - 0 = allow just user-defined, 1 = allow all but user-defined
     * @param int   $attrMethod - 0 = allow just user-defined, 1 = allow all but user-defined
     * @param int   $xssAuto    - 0 = only auto clean essentials, 1 = allow clean blacklisted tags/attr
     */
    protected function __construct(
        array $tagsArray = [],
        array $attrArray = [],
        int $tagsMethod = 0,
        int $attrMethod = 0,
        int $xssAuto = 1
    ) {
        // make sure user defined arrays are in lowercase
        $this->tagsArray = array_map('strtolower', $tagsArray);
        $this->attrArray = array_map('strtolower', $attrArray);

        // assign to member vars
        $this->tagsMethod = $tagsMethod;
        $this->attrMethod = $attrMethod;
        $this->xssAuto    = $xssAuto;
    }

    /**
     * Returns an input filter object, only creating it if it does not already exist.
     *
     * This method must be invoked as:
     *   $filter = FilterInput::getInstance();
     *
     * @param array $tagsArray  list of user-defined tags
     * @param array $attrArray  list of user-defined attributes
     * @param int   $tagsMethod WhiteList method = 0, BlackList method = 1
     * @param int   $attrMethod WhiteList method = 0, BlackList method = 1
     * @param int   $xssAuto    Only auto clean essentials = 0,
     *                          Allow clean blacklisted tags/attr = 1
     *
     * @return FilterInput object.
     */
    public static function getInstance(
        array $tagsArray = [],
        array $attrArray = [],
        int $tagsMethod = 0,
        int $attrMethod = 0,
        int $xssAuto = 1
    ): self {
        static $instances = [];

        $className = get_called_class(); // so an extender gets an instance of itself

        $sig = md5(serialize([$className, $tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto]));

        if (empty($instances[$sig])) {
            $instances[$sig] = new static($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto);
        }

        return $instances[$sig];
    }

    /**
     * Method to be called by another php script. Processes for XSS and
     * any specified bad code.
     *
     * @param mixed $source - input string/array-of-string to be 'cleaned'
     *
     * @return string|array $source - 'cleaned' version of input parameter
     */
    public function process($source)
    {
        if (is_array($source)) {
            // clean all elements in this array
            foreach ($source as $key => $value) {
                // filter element for XSS and other 'bad' code etc.
                if (is_string($value)) {
                    $source[$key] = $this->remove($this->decode($value));
                }
            }
            return $source;
        }
        if (is_string($source)) {
            // clean this string
            return $this->remove($this->decode($source));
        }

        // return parameter as given
        return $source;
    }

    /**
     * Static method to be called by another php script.
     * Clean the supplied input using the default filter
     *
     * @param mixed  $source Input string/array-of-string to be 'cleaned'
     * @param string $type   Return/cleaning type for the variable, one of
     *                       (INTEGER, FLOAT, BOOLEAN, WORD, ALPHANUM, CMD, BASE64,
     *                        STRING, ARRAY, PATH, USERNAME, WEBURL, EMAIL, IP)
     *
     * @return mixed 'Cleaned' version of input parameter
     * @static
     */
    public static function clean($source, string $type = 'string')
    {
        static $filter = null;

        // need an instance for methods, since this is supposed to be static
        // we must instantiate the class - this will take defaults
        if (!is_object($filter)) {
            $filter = static::getInstance();
        }

        return $filter->cleanVar($source, $type);
    }

    /**
     * Method to be called by another php script. Processes for XSS and
     * specified bad code according to rules supplied when this instance
     * was instantiated.
     *
     * @param mixed  $source Input string/array-of-string to be 'cleaned'
     * @param string $type   Return/cleaning type for the variable, one of
     *                       (INTEGER, FLOAT, BOOLEAN, WORD, ALPHANUM, CMD, BASE64,
     *                        STRING, ARRAY, PATH, USERNAME, WEBURL, EMAIL, IP)
     *
     * @return mixed 'Cleaned' version of input parameter
     */
    public function cleanVar($source, string $type = 'string')
    {
        // Handle the type constraint
        switch (strtoupper($type)) {
            case 'INT':
            case 'INTEGER':
                // Only use the first integer value
                preg_match('/-?\d+/', (string) $source, $matches);
                $result = isset($matches[0]) ? (int) $matches[0] : 0;
                break;

            case 'FLOAT':
            case 'DOUBLE':
                // Only use the first floating point value
                preg_match('/-?\d+(\.\d+)?/', (string) $source, $matches);
                $result = isset($matches[0]) ? (float) $matches[0] : 0;
                break;

            case 'BOOL':
            case 'BOOLEAN':
                $result = (bool) $source;
                break;

            case 'WORD':
                $result = (string) preg_replace('/[^A-Z_]/i', '', $source);
                break;

            case 'ALPHANUM':
            case 'ALNUM':
                $result = (string) preg_replace('/[^A-Z0-9]/i', '', $source);
                break;

            case 'CMD':
                $result = (string) preg_replace('/[^A-Z0-9_\.-]/i', '', $source);
                $result = strtolower($result);
                break;

            case 'BASE64':
                $result = (string) preg_replace('/[^A-Z0-9\/+=]/i', '', $source);
                break;

            case 'STRING':
                $result = (string) $this->process($source);
                break;

            case 'ARRAY':
                $result = (array) $this->process($source);
                break;

            case 'PATH':
                $source = trim((string) $source);
                $pattern = '/^([-_\.\/A-Z0-9=&%?~]+)(.*)$/i';
                preg_match($pattern, $source, $matches);
                $result = isset($matches[1]) ? (string) $matches[1] : '';
                break;

            case 'USERNAME':
                $result = (string) preg_replace('/[\x00-\x1F\x7F<>"\'%&]/', '', $source);
                break;

            case 'WEBURL':
                $result = (string) $this->process($source);
                // allow only relative, http or https
                $urlparts = parse_url($result);
                if (!empty($urlparts['scheme'])
                    && !('http' === $urlparts['scheme'] || 'https' === $urlparts['scheme'])
                ) {
                    $result = '';
                }
                // do not allow quotes, tag brackets or controls
                if (!preg_match('#^[^"<>\x00-\x1F]+$#', $result)) {
                    $result = '';
                }
                break;

            case 'EMAIL':
                $result = (string) $source;
                if (!filter_var((string) $source, FILTER_VALIDATE_EMAIL)) {
                    $result = '';
                }
                break;

            case 'IP':
                $result = (string) $source;
                // this may be too restrictive.
                // Should the FILTER_FLAG_NO_PRIV_RANGE flag be excluded?
                if (!filter_var((string) $source, FILTER_VALIDATE_IP)) {
                    $result = '';
                }
                break;

            default:
                $result = $this->process($source);
                break;
        }

        return $result;
    }

    /**
     * Internal method to iteratively remove all unwanted tags and attributes
     *
     * @param string $source - input string to be 'cleaned'
     *
     * @return string $source - 'cleaned' version of input parameter
     */
    protected function remove(string $source): string
    {
        if (empty(trim($source))) {
            return $source;
        }

        $dom = new DOMDocument();
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $source, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $this->filterNode($dom->documentElement);

        // Get the cleaned HTML, removing the wrapper div
        $body = $dom->getElementsByTagName('body')->item(0);
        $cleanedHtml = '';
        if ($body) {
            foreach ($body->childNodes as $child) {
                $cleanedHtml .= $dom->saveHTML($child);
            }
        }

        return $cleanedHtml;
    }

    /**
     * Recursively filter a DOM node, removing unwanted tags and attributes.
     *
     * @param DOMNode $node The node to filter
     */
    protected function filterNode(DOMNode $node): void
    {
        // Filter children first
        if ($node->hasChildNodes()) {
            // Iterate backwards to avoid issues with node removal
            for ($i = $node->childNodes->length - 1; $i >= 0; --$i) {
                $child = $node->childNodes->item($i);
                if ($child instanceof \DOMElement) {
                    $this->filterNode($child);
                }
            }
        }

        // Filter the node itself
        if ($node instanceof \DOMElement) {
            $tagName = strtolower($node->tagName);

            // 1. Check tag against blacklist
            if ($this->xssAuto && in_array($tagName, $this->tagBlacklist, true)) {
                $node->parentNode->removeChild($node);
                return;
            }

            // 2. Check tag against whitelist/blacklist
            $tagAllowed = in_array($tagName, $this->tagsArray, true);
            if ($this->tagsMethod === 0 && !$tagAllowed) { // Whitelist
                $node->parentNode->removeChild($node);
                return;
            }
            if ($this->tagsMethod === 1 && $tagAllowed) { // Blacklist
                $node->parentNode->removeChild($node);
                return;
            }

            // 3. Filter attributes
            if ($node->hasAttributes()) {
                $attributes = iterator_to_array($node->attributes);
                foreach ($attributes as $attr) {
                    $attrName = strtolower($attr->name);

                    // 3a. Remove all event handlers
                    if ($this->xssAuto && 0 === strpos($attrName, 'on')) {
                        $node->removeAttributeNode($attr);
                        continue;
                    }

                    // 3b. Check attribute against blacklist
                    if ($this->xssAuto && in_array($attrName, $this->attrBlacklist, true)) {
                        $node->removeAttributeNode($attr);
                        continue;
                    }

                    // 3c. Check attribute against whitelist/blacklist
                    $attrAllowed = in_array($attrName, $this->attrArray, true);
                    if ($this->attrMethod === 0 && !$attrAllowed) { // Whitelist
                        $node->removeAttributeNode($attr);
                        continue;
                    }
                    if ($this->attrMethod === 1 && $attrAllowed) { // Blacklist
                        $node->removeAttributeNode($attr);
                        continue;
                    }

                    // 3d. Check for dangerous attribute values
                    $attrValue = strtolower($attr->value);
                    if (
                        strpos($attrValue, 'expression') !== false ||
                        strpos($attrValue, 'javascript:') !== false ||
                        strpos($attrValue, 'vbscript:') !== false ||
                        strpos($attrValue, 'mocha:') !== false ||
                        strpos($attrValue, 'livescript:') !== false
                    ) {
                        $node->removeAttributeNode($attr);
                        continue;
                    }
                }
            }
        }
    }


    /**
     * Try to convert to plaintext
     *
     * @param string $source string to decode
     *
     * @return string $source decoded
     */
    protected function decode(string $source): string
    {
        // url decode
        $charset = defined('_CHARSET') ? constant('_CHARSET') : 'utf-8';
        $source = html_entity_decode($source, ENT_QUOTES, $charset);
        // convert decimal
        $source = preg_replace_callback(
            '/&#(\d+);/m',
            static function ($matches) {
                return chr((int)$matches[1]);
            },
            $source
        );
        // convert hex notation
        $source = preg_replace_callback(
            '/&#x([a-f0-9]+);/mi',
            static function ($matches) {
                return chr(hexdec($matches[1]));
            },
            $source
        );

        return $source;
    }
}
