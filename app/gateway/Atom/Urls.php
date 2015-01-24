<?php

namespace Gateway\Atom;

class Urls
{
    const ATOM_MOCK_URL = 'http://laskjdf';
    const ATOM_TEST_URL = 'http://203.114.240.183/paynetz/epi/fts';
    const ATOM_LIVE_URL = 'https://payment.atomtech.in';

    public static function getUrl($mode)
    {
        if ($mode === 'test')
            return self::ATOM_TEST_URL;
        else if ($mode === 'live')
            return self::ATOM_LIVE_URL;

        throw new \InvalidArgumentException('Not a valid mode: ' . $mode);
    }
}