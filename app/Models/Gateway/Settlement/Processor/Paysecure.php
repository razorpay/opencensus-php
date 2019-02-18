<?php
namespace RZP\Models\Gateway\Settlement\Processor;

use RZP\Models\Payment\Action;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Base\CardCacheTrait;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Settlement;

class Paysecure extends Base
{
    use CardCacheTrait;

    protected $secureCacheDriver;

    const CACHE_KEY = 'paysecure_%s_card_details';
    const CACHE_TTL = 180;

    public function getPayments(array $input): PublicCollection
    {
        return $this->repo->payment->fetchPaysecureAuthorizedAndCapturedPaymentsBetweenTimestampsToSettle(
            $input[Settlement\Service::FROM],
            $input[Settlement\Service::TO]
        );
    }

    protected function preProcessGatewayInput(&$input)
    {
        $this->secureCacheDriver = $this->app['config']->get('cache.secure_default');

        $this->setCardNumberAndCvv($input);
    }

    public function sendGatewayRequest(array $gatewayInput)
    {
        try
        {
            sd($gatewayInput);
            $this->app['gateway']->call(
                Gateway::HITACHI,
                Action::AUTHORIZE,
                $gatewayInput,
                $this->mode
            );
        }
        catch (\Exception $e)
        {
            $this->trace->critical(
                TraceCode::GATEWAY_SETTLEMENT_FAILURE,
                [
                    'payment_id' => $gatewayInput['payment']['id'],
                ]
            );
        }
    }
}
