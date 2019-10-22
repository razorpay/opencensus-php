<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Constants\Mode;

class Source
{
    const STATUSCAKE  = 'STATUSCAKE';
    const BILLDESK    = 'BILLDESK';
    const BANK        = 'BANK';
    const VAJRA       = 'VAJRA';
    const INTERNAL    = 'INTERNAL';
    const OTHER       = 'OTHER';
    const DOPPLER     = 'DOPPLER';

    const DUMMY       = 'dummy';

    protected static $sources = [
        Source::STATUSCAKE,
        Source::BILLDESK,
        Source::BANK,
        Source::VAJRA,
        Source::INTERNAL,
        Source::OTHER,
        Source::DOPPLER,
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
