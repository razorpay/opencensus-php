<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Constants\Mode;

class Source
{
    const STATUSCAKE  = 'STATUSCAKE';
    const BILLDESK    = 'BILLDESK';
    const BANK        = 'BANK';
    const VAJRA       = 'VAJRA';
    const OTHER       = 'OTHER';

    const DUMMY       = 'dummy';

    protected static $sources = [
        Source::STATUSCAKE,
        Source::BILLDESK,
        Source::BANK,
        Source::VAJRA,
        Source::OTHER
    ];

    public static function isValid($source)
    {
        $app = \App::getFacadeRoot();

        if (($app['rzp.mode'] === Mode::TEST) and
            ($source === self::DUMMY))
        {
            return true;
        }

        return in_array(strtoupper($source), self::$sources, true);
    }

}
