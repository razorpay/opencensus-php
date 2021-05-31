<?php

namespace RZP\Models\Merchant\Product\Events;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Product\Name;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Merchant\Product\Util;
use RZP\Models\Merchant\Product\Status;
use RZP\Models\Merchant\Product\Entity;
use RZP\Models\Merchant\Product\Requirements;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * This function can be used to notify the respective consumers upon status change for merchant products
     * @param Detail\Entity $merchantDetails
     * @param Entity        $merchantProductEntity
     */
    public function notifyProductActivationStatus(Detail\Entity $merchantDetails, Entity $merchantProductEntity)
    {
        $productName = $merchantProductEntity->getProduct();

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
                $this->notifyProductActivationStatusChangeToPartner($merchantDetails, $merchantProductEntity);
                break;

        }
    }

    private function notifyProductActivationStatusChangeToPartner(Detail\Entity $merchantDetails, Entity $merchantProduct)
    {
        $merchantProductActivationStatus = $merchantProduct->getStatus();

        $merchant = $merchantDetails->merchant;

        $withPayload = [];

        if ($merchantProductActivationStatus === Status::NEEDS_CLARIFICATION)
        {
            $ncRequirements = (new Requirements\BaseProcessor())->fetchRequirements($merchant, $merchantProduct);

            $withPayload[Util\Constants::REQUIREMENTS] = $ncRequirements;
        }

        $eventPayload = [
            ApiEventSubscriber::MAIN        => $merchantProduct,
            ApiEventSubscriber::WITH        => $withPayload,
            ApiEventSubscriber::MERCHANT_ID => $merchant->getId()
        ];

        $this->trace->info(TraceCode::MERCHANT_PRODUCT_STATUS_WEBHOOK_EVENT_PAYLOAD, $eventPayload);

        $this->app['events']->dispatch('api.account.product_status', $eventPayload);
    }
}
