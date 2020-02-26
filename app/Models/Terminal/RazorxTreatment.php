<?php


namespace RZP\Models\Terminal;

use App;
use RZP\Services\RazorXClient;

class RazorxTreatment
{
    const shouldMigrateTerminalFeature     = 'MigrateTerminal';
    const shouldMigrateSubmerchantFeature  = 'MigrateSubmerchant';

    const migrateVariant = 'migrate';

    public static function shouldMigrateTerminal(string $newSyncStatus) : bool
    {
       if ($newSyncStatus !== SyncStatus::NOT_SYNCED)
       {
           return false;
       }

        return self::getRazorxTreatment(self::shouldMigrateTerminalFeature);
    }

    public static function shouldMigrateSubmerchant(): bool
    {
        return self::getRazorxTreatment(self::shouldMigrateSubmerchantFeature);
    }

    protected static function getRazorxTreatment(string $feature): bool
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? \RZP\Constants\Mode::LIVE;

        $variant = $app['razorx']->getTreatment($app['request']->getId(), $feature, $mode);

        if ($variant === self::migrateVariant)
        {
            return true;
        }

        return false;
    }
}
