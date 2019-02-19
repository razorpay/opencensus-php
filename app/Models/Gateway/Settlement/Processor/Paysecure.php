<?php
namespace RZP\Models\Gateway\Settlement\Processor;

use RZP\Models\Payment;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Base\CardCacheTrait;
use RZP\Models\Base\PublicCollection;

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

    protected function sendGatewayRequest(array $gatewayInput)
    {
        $this->app['gateway']->call(
            Gateway::HITACHI,
            Action::AUTHORIZE,
            $gatewayInput,
            $this->mode
        );
    }

    protected function updateGatewayPaymentEntity(Payment\Entity $payment)
    {
        $gatewayPayment = $this->repo->paysecure->findByPaymentIdAndActionOrFail($payment['id'], Action::AUTHORIZE);

        $gatewayPayment->setSettled(true);

        $this->repo->saveOrFail($gatewayPayment);
    }
}
