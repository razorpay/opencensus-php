<?php

namespace RZP\Models\Merchant\Product\Events;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
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
     *
     * @param Entity $merchantProductEntity
     */
    public function notifyProductActivationStatus(Entity $merchantProductEntity)
    {
        $productName = $merchantProductEntity->getProduct();

        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
            case Name::PAYMENT_LINKS:
            case Name::ROUTE:
                $data = $this->getPaymentProductsEventData($merchantProductEntity);
                $this->dispatchProductStatusEvent($merchantProductEntity, $data);
                break;

        }
    }

    private function dispatchProductStatusEvent(Entity $merchantProduct, array $data)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN        => $merchantProduct,
            ApiEventSubscriber::WITH        => $data,
            ApiEventSubscriber::MERCHANT_ID => $merchantProduct->getMerchantId()
        ];

        $this->trace->info(TraceCode::MERCHANT_PRODUCT_STATUS_WEBHOOK_EVENT_PAYLOAD, $eventPayload);

        $event = 'api.product.' . $merchantProduct->getProduct() . '.' . $merchantProduct->getStatus();

        $this->app['events']->dispatch($event, $eventPayload);
    }

    private function getPaymentProductsEventData(Entity $merchantProduct): array
    {
        $data = [];

        $merchantProductActivationStatus = $merchantProduct->getStatus();

        $merchant = $merchantProduct->merchant;

        if ($merchantProductActivationStatus !== Status::NEEDS_CLARIFICATION)
        {
            return $data;
        }

        $requirementService = Requirements\Factory::getInstance($merchantProduct->getProduct());

        $ncRequirements = $requirementService->fetchRequirements($merchant, $merchantProduct);

        $data[Util\Constants::REQUIREMENTS] = $ncRequirements;

        return $data;
    }
}
