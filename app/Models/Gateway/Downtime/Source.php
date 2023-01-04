<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Constants\Mode;

class Source
{
    const STATUSCAKE        = 'STATUSCAKE';
    const BILLDESK          = 'BILLDESK';
    const BANK              = 'BANK';
    const VAJRA             = 'VAJRA';
    const DOPPLER           = 'DOPPLER';
    const INTERNAL          = 'INTERNAL';
    const DOWNTIME_V2       = 'DOWNTIME_V2';
    const DOWNTIME_SERVICE  = 'DOWNTIME_SERVICE';
    const PHONEPE           = 'PHONEPE';
    const OTHER             = 'OTHER';
    const PAYNET            = 'PAYNET';

    const DUMMY             = 'dummy';

    protected static $sources = [
        Source::STATUSCAKE,
        Source::BILLDESK,
        Source::BANK,
        Source::VAJRA,
        Source::DOPPLER,
        Source::INTERNAL,
        Source::DOWNTIME_V2,
        Source::DOWNTIME_SERVICE,
        Source::PHONEPE,
        Source::OTHER,
        Source::PAYNET,
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
