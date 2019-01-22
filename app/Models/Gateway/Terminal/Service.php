<?php

namespace RZP\Models\Gateway\Terminal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Core;
use RZP\Gateway\Base\Terminal;

class Service extends Base\Service
{
    const MERCHANT_ONBOARD  = 'merchant_onboard';
    const GATEWAY_INPUT     = 'gateway_input';
    const TERMINAL          = 'terminal';
    const PG_MERCHANT_ID    = 'pg_merchant_id';

    public function onboardMerchant(string $merchantId, array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_ONBOARD_REQUEST,
            [
                'merchant_id' => $merchantId,
                'input'       => $input
            ]);

        (new Validator)->validateInput(self::MERCHANT_ONBOARD, $input);

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $merchantDetail = $merchant->merchantDetail->toArray();

        $gateway = $input['gateway'];
        
        $gatewayData = [
            'merchant'          => $merchant,
            'merchant_details'  => $merchantDetail,
            'gateway_input'     => $input[self::GATEWAY_INPUT],
        ];

        try
        {
            $terminalData = $this->app['gateway']->call($gateway,
                                                        Terminal::MERCHANT_ONBOARD,
                                                        $gatewayData,
                                                        $this->mode);

            $terminal = (new Core)->create($terminalData, $merchant);

            return $terminal->toArrayPublic();
        }
        catch (Exception\GatewayErrorException $e)
        {
            //TODO: Handle error if needed.
            throw $e;
        }
    }
}
