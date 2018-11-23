<?php

namespace Lib\Formatters;

use DOMNode;
use DOMDocument;
use RZP\Exception;

class Xml
{
    /**
     * @var string
     */
    private static $encoding = 'UTF-8';

    /**
     * @var DomDocument|null
     */
    private static $xml = null;

    /**
     * Convert an Array to XML.
     *
     * @param string $rootNode  - name of the root node to be converted
     * @param array  $content   - array to be converted
     *
     * @return DomDocument
     */
    public static function create($rootNode, $content = [])
    {
        $xml = self::getXmlRoot();

        $xml->appendChild(self::convert($rootNode, $content));

        self::$xml = null;

        return $xml->saveXml();
    }

    /**
     * Initialize the root XML node [optional].
     *
     * @param string $version
     * @param string $encoding
     * @param bool   $standalone
     * @param bool   $formatOutput
     */
    public static function init(
        string $version = '1.0',
        string $encoding = 'utf-8',
        bool $standalone = false,
        bool $formatOutput = true)
    {
        self::$xml = new DomDocument($version, $encoding);

        self::$xml->xmlStandalone = $standalone;

        self::$xml->formatOutput = $formatOutput;

        self::$encoding = $encoding;
    }

    /**
     * Get string representation of boolean value.
     *
     * @param mixed $v
     *
     * @return string
     */
    private static function boolToString($value)
    {
        if (is_bool($value) === true)
        {
            $value = ($value === true) ? 'true' : 'false';
        }

        return $value;
    }

    /**
     * Convert an Array to XML.
     *
     * @param string $rootNode - name of the root node to be converted
     * @param array  $content       - array to be converted
     *
     * @return DOMNode
     *
     * @throws Exception
     */
    private static function convert($rootNode, $content = [])
    {
        $xml = self::getXmlRoot();
        $node = $xml->createElement($rootNode);

        if (is_array($content) === true)
        {
            if ((isset($content['@attributes']) === true) and
                (is_array($content['@attributes']) === true))
            {
                foreach ($content['@attributes'] as $key => $value)
                {
                    if (self::isValidTagName($key) === false)
                    {
                        throw new Exception\LogicException(
                            'Illegal character in tag name ' . $key . ' in node ' . $rootNode);
                    }

                    $node->setAttribute($key, self::boolToString($value));
                }

                unset($content['@attributes']);
            }
            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if (isset($content['@value']) === true)
            {
                $textChild = $xml->createTextNode(self::boolToString($content['@value']));

                $node->appendChild($textChild);

                unset($content['@value']);

                return $node;
            }
            elseif (isset($content['@cdata']) === true)
            {
                $cdataChild = $xml->createCDATASection(self::boolToString($content['@cdata']));

                $node->appendChild($cdataChild);

                unset($content['@cdata']);

                return $node;
            }
        }

        //create subnodes using recursion
        if (is_array($content) === true)
        {
            // recurse to get the node for that key
            foreach ($content as $key => $value)
            {
                if (self::isValidTagName($key) === false)
                {
                    throw new Exception\LogicException(
                        'Illegal character in tag name ' . $key . ' in node ' . $rootNode);
                }

                if ((is_array($value) === true) and
                    (is_numeric(key($value)) === true))
                {
                    // MORE THAN ONE NODE OF ITS KIND;
                    // if the new array is numeric index, means it is array of nodes of the same kind
                    // it should follow the parent key name
                    foreach ($value as $k => $v)
                    {
                        $node->appendChild(self::convert($key, $v));
                    }
                }
                else
                {
                    $node->appendChild(self::convert($key, $value));
                }

                unset($content[$key]);
            }
        }
        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if (is_array($content) === false)
        {
            $textChild = $xml->createTextNode(self::boolToString($content));

            $node->appendChild($textChild);
        }

        return $node;
    }

    /**
     * Get the root XML node, if there isn't one, create it.
     *
     * @return DomDocument|null
     */
    private static function getXmlRoot()
    {
        if (empty(self::$xml) === true)
        {
            self::init();
        }

        return self::$xml;
    }

    /**
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn.
     *
     * @param string $tag
     *
     * @return bool
     */
    private static function isValidTagName($tag)
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';

        return ((preg_match($pattern, $tag, $matches) === 1) and ($matches[0] == $tag));
    }
}
