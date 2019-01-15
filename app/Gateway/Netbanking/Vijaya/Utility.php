<?php

namespace RZP\Gateway\Netbanking\Vijaya;

use Symfony\Component\DomCrawler\Crawler;

class Utility extends \RZP\Gateway\Utility
{
    public static function parseHtmlAndGetTagContents($html, $tag)
    {
        $crawler = new Crawler($html);

        return $crawler->filter('h4')->each(function ($node, $i)
        {
              return $node->text();
        });
    }
}
