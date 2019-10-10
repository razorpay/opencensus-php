<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Plan;
use RZP\Trace\TraceCode;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Feature\Constants as Feature;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function getModeAndMerchant(string $subscriptionId)
    {
        $modeAndMerchantId = $this->app['module']->subscription->fetchMerchantIdAndMode($subscriptionId);

        $mode = $modeAndMerchantId['mode'];

        $merchantId = $modeAndMerchantId['merchant_id'];

        $merchant = $this->repo->merchant->connection($mode)->find($merchantId);

        return [$mode, $merchant];
    }


    public function fetch(string $entityName, string $id, array $input): array
    {
        $entityResponse = $this->app['module']->subscription->fetchAdminEntity($entityName, $id, $input);

        return $entityResponse;
    }

    public function fetchMultiple(string $entityName, array $input): array
    {
        $entityResponse = $this->app['module']->subscription->fetchMultipleAdminEntity($entityName, $input);

        return $entityResponse;
    }

}
