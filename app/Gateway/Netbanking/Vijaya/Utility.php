<?php

namespace RZP\Gateway\Netbanking\Vijaya;

use DOMDocument;

class Utility extends \RZP\Gateway\Utility
{
    public static function parseHtmlAndGetTagContents($html, $tag)
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        $dom->loadHTML($html);

        $tags = [];

        foreach ($dom->getElementsByTagName($tag) as $node)
        {
            $tags[] = trim($node->textContent);
        };

        return $tags;
    }
}
