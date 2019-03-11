<?php

namespace RZP\Gateway\Paysecure;

use SimpleXMLElement;

class XmlSerializer
{
    public static $rootNode =  '<paysecure/>';

    public static function getXmlStringFromArray(array $body)
    {
        $xml = new SimpleXMLElement(self::$rootNode);

        // $xml being passed by reference.
        self::arrayToXml($body, $xml);

        $dom = dom_import_simplexml($xml);

        $dom = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);

        return $dom;
    }

    public static function xmlToArray($xmlData)
    {
        $arr = [];

        // Iterate through all children of the xml element
        foreach ($xmlData->children() as $child)
        {
            // If no grand-children are present, convert it to string and store in array.
            // Else, run the function recursively until the child does not have any grand-children.
            if (count($child->children()) === 0)
            {
                $arr[$child->getName()] = strval($child);
            }
            else
            {
                $arr[$child->getName()] = self::xmlToArray($child);
            }
        }

        return $arr;
    }

    public static function arrayToXml($arrayContent, &$xmlData)
    {
        foreach ($arrayContent as $key => $value)
        {
            if (is_array($value))
            {
                $subNode = $xmlData->addChild($key);

                self::arrayToXml($value, $subNode);
            }
            else
            {
                $xmlData->addChild("$key", htmlspecialchars("$value"));
            }
        }

        return $xmlData;
    }
}
