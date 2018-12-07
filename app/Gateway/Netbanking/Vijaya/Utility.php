<?php

namespace RZP\Gateway\Netbanking\Vijaya;

use DOMDocument;

class Utility extends \RZP\Gateway\Utility
{
    public static function parseHtmlAndGetTagContents($html, $tag)
    {
        $dom = new DOMDocument();

        $oldValue = libxml_use_internal_errors(true);

        $dom->loadHTML($html);

        libxml_clear_errors();

        libxml_use_internal_errors($oldValue);

        $tags = [];

        foreach ($dom->getElementsByTagName($tag) as $node)
        {
            $tags[] = trim($node->textContent);
        };

        return $tags;
    }
}
