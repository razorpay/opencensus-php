<?php

namespace RZP\lib\DataParser;

/**
 * Interface DataParserInterface
 *
 * defines methods , supported by different parsers implementation class
 *
 */
interface DataParserInterface
{
    // contain the function to parse data
    public function parseWebhookData();
}
