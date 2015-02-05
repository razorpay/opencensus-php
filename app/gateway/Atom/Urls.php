<?php

namespace Gateway\Atom;

class Urls
{
    const MOCK_DOMAIN  = 'http://laskjdf';
    const TEST_DOMAIN  = 'http://203.114.240.183';
    const LIVE_DOMAIN  = 'https://payment.atomtech.in';

    const PAYMENT_URL  = '/paynetz/epi/fts';
    const VERIFY_URL   = '/paynetz/vfts';

    public static function getDomain($mode)
    {
        if ($mode === 'test')
        {
            return self::TEST_DOMAIN;
        }
        else if ($mode === 'live')
        {
            return self::LIVE_DOMAIN;
        }

        throw new \InvalidArgumentException('Not a valid mode: ' . $mode);
    }
}