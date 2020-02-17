<?php


namespace RZP\Models\Terminal;

use App;
use RZP\Services\RazorXClient;

class RazorxTreatment
{
    const shouldMigrateTerminal = 'shouldMigrateTerminal';

    public static function shouldMigrateTerminalOrFail() : bool
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? \RZP\Constants\Mode::LIVE;

        $variant = $app['razorx']->getTreatment('', self::shouldMigrateTerminal, $mode);

        if ($variant === RazorXClient::DEFAULT_CASE)
        {
            return false;
        }

        return true;
    }
}
