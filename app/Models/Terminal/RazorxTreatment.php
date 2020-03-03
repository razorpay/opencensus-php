<?php


namespace RZP\Models\Terminal;

use App;
use RZP\Services\RazorXClient;
use RZP\Trace\TraceCode;

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

        self::logRazorxResponse($feature, $variant);

        if ($variant === self::migrateVariant)
        {
            return true;
        }

        return false;
    }

    protected static function logRazorxResponse(string $feature, string $variant)
    {
        $app = App::getFacadeRoot();

        $data = [
            'feature'   => $feature,
            'variant'   => $variant,
        ];

        $app['trace']->info(TraceCode::TERMINALS_SERVICE_RAZORX_RESPONSE, $data);
    }
}
