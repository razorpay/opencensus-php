<?php

namespace RZP\Gateway\CardlessEmi;

use App;
use RZP\Gateway\Base;

class Metric extends Base\Metric
{
    public function getDimensions($action, $input, $gateway = 'none')
    {
        $provider =
            isset($input['terminal']['gateway_acquirer']) ? $input['terminal']['gateway_acquirer'] : $input['provider'];

        return [
            Metric::DIMENSION_GATEWAY              => $gateway,
            Metric::DIMENSION_PAYMENT_METHOD       => $gateway,
            Metric::DIMENSION_ACTION               => $action,
            Metric::DIMENSION_CARD_TYPE            => 'none',
            Metric::DIMENSION_CARD_NETWORK         => 'none',
            Metric::DIMENSION_CARD_COUNTRY         => 'none',
            Metric::DIMENSION_PAYMENT_RECURRING    => 'none',
            Metric::DIMENSION_INSTRUMENT_TYPE      => 'none',
            Metric::DIMENSION_TPV                  => 'none',
            Metric::DIMENSION_ISSUER               => $provider,
            Metric::DIMENSION_UPI_PSP              => 'none',
            Metric::DIMENSION_CARD_INTERNATIONAL   => 'none',
            Metric::DIMENSION_BHARAT_QR            => 'none',
            Metric::DIMENSION_AUTH_TYPE            => 'none',
            Metric::DIMENSION_TERMINAL_ID          => 'none',
            Metric::DIMENSION_MERCHANT_CATEGORY    => 'none'
        ];
    }
}
