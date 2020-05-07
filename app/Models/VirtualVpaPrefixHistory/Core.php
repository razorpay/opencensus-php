<?php


namespace RZP\Models\VirtualVpaPrefixHistory;

use Carbon\Carbon;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function deactivatePreviousPrefix(string $merchantId, string $virtualVpaPrefixId) : int
    {
        $deactivatedAt = Carbon::now()->getTimestamp();

        return $this->repo
                    ->virtual_vpa_prefix_history
                    ->deactivatePrefix($merchantId, $virtualVpaPrefixId, $deactivatedAt);
    }
}
