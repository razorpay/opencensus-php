<?php

namespace RZP\Models\OfflinePayment;

use Config;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Core extends Base\Core
{
    protected $mutex;
    protected $transformer;


    const MUTEX_KEY = 'offline_processing_%s';


    public function __construct()
    {
        parent::__construct();

        $this->transformer = new Transformer();

        $this->mutex = $this->app['api.mutex'];
    }


    public function processAndSaveOffline(array $input)
    {
        $offlinePayment = null;
        $errorMessage = null;

        $input[Entity::CURRENCY] = "INR";

        $offlinePayment = (new Entity)->build($input);

        $processor = new Processor();

        //on basis of challan_number
        $mutexKey = sprintf(self::MUTEX_KEY, $input[Entity::CHALLAN_NUMBER]);

        $offlinePayment = $this->mutex->acquireAndRelease(
            $mutexKey,
            function () use ($processor, $offlinePayment)
            {
                return $processor->process($offlinePayment);
            },
            60,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS,
            10,
            200,
            400);

        return $offlinePayment;
    }

    public function getFeesForOffline(Entity $offlinePayment, Merchant\Entity $merchant)
    {
        return $this->getFees($offlinePayment->getAmount(), $merchant, Currency::INR);
    }

    /**
     * @param int             $amount
     * @param Merchant\Entity $merchant
     * @param string          $currency
     *
     * @return mixed
     */
    protected function getFees(int $amount, Merchant\Entity $merchant, string $currency)
    {
        $request = [
            Payment\Entity::AMOUNT   => $amount,
            Payment\Entity::CURRENCY => $currency,
            Payment\Entity::METHOD   => Payment\Method::OFFLINE,
        ];

        $paymentProcessor = new PaymentProcessor($merchant);

        $data = $paymentProcessor->processAndReturnFees($request);

        return $data['fees'];
    }

}
