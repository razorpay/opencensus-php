<?php

namespace RZP\Models\Gateway\Terminal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
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

        $pgMerchant =$this->repo->merchant->findByPublicId($input['terminal']['pg_merchant_id']);

        $gatewayData = [
            'merchant'          => $pgMerchant,
            'merchant_details'  => $merchantDetail,
            'gateway_input'     => $input[self::GATEWAY_INPUT],
        ];

        try
        {
            $terminalData = $this->app['gateway']->call($gateway,
                                                        Terminal::MERCHANT_ONBOARD,
                                                        $gatewayData,
                                                        $this->mode);

            $terminal = (new \RZP\Models\Terminal\Core)->create($terminalData, $pgMerchant);

            return $terminal->toArrayPublic();
        }
        catch (Exception\GatewayErrorException $e)
        {
            //TODO: Handle error if needed.
            throw $e;
        }
    }
}
