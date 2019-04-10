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

        // This is a validation flow, so as default
        // we use the more restricted option
        $mode = Mode::LIVE;

        // This blocks writing tests in live mode, but that's
        // acceptable till we have a better way to set mode in tests
        if ($app->runningUnitTests() === true)
        {
            $mode = Mode::TEST;
        }

        // In almost all flows except unit tests and direct auth requests,
        // rzp.mode should be used as source of truth for mode
        if (isset($app['rzp.mode']) === true)
        {
            $mode = $app['rzp.mode'];
        }

        // Used for downtime tests, where no specific provider is required
        if (($mode === Mode::TEST) and
            ($source === self::DUMMY))
        {
            return true;
        }

        return in_array(strtoupper($source), self::$sources, true);
    }

}
