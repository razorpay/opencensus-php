<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Base;
use RZP\Constants\Mode;

class Metric extends Base\Core
{
    /**
     * Metric Name
     */
    const BHARAT_QR_TERMINAL_NOT_FOUND = 'bharat_qr_terminal_not_found';

    /**
     * Dimensions
     */
    const LABEL_MODE    = 'mode';
    const LABEL_GATEWAY = 'gateway';

    public function pushBharatQrTerminalNotFoundMetrics(array $gatewayResponse, string $mode)
    {
        $dimensions = [
            self::LABEL_GATEWAY => $gatewayResponse[GatewayResponseParams::GATEWAY] ?? "",
            self::LABEL_MODE    => $mode
        ];

        $this->trace->count(self::BHARAT_QR_TERMINAL_NOT_FOUND, $dimensions);
    }

}
