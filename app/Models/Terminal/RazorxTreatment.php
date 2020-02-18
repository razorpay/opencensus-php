<?php


namespace RZP\Models\Terminal;

use App;
use RZP\Services\RazorXClient;

class RazorxTreatment
{
    const shouldMigrateTerminalFeature = 'MigrateTerminal';
    const migrateVariant = 'migrate';

    public static function shouldMigrateTerminalOrFail() : bool
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? \RZP\Constants\Mode::LIVE;

        $variant = $app['razorx']->getTreatment($app['request']->getId(), self::shouldMigrateTerminalFeature, $mode);

        if ($variant === self::migrateVariant)
        {
            return true;
        }

        return false;
    }
}
