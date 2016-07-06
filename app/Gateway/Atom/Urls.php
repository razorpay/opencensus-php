<?php

namespace Gateway\Atom;

use Constants\Mode;
use EE\Exception;

class Urls
{
    const MOCK_DOMAIN  = 'http://laskjdf';
    const TEST_DOMAIN  = 'http://203.114.240.183';
    const LIVE_DOMAIN  = 'https://payment.atomtech.in';

    const PAYMENT_URL  = '/paynetz/epi/fts';
    const VERIFY_URL   = '/paynetz/vfts';

    public static function getDomain($mode)
    {
        if ($mode === Mode::TEST)
        {
            return self::TEST_DOMAIN;
        }
        else if ($mode === Mode::LIVE)
        {
            return self::LIVE_DOMAIN;
        }

        throw new Exception\InvalidArgumentException('Not a valid mode: ' . $mode);
    }
}