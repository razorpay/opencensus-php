<?php

namespace RZP\Models\SalesForce;

use RZP\Exception\InvalidArgumentException;
use RZP\Models\Merchant\Entity;
use RZP\Services\SalesForceClient;

class SalesForceService {

    /** @var $salesForceClient SalesForceClient */
    private $salesForceClient;


    public function __construct(SalesForceClient $salesForceClient) {
        $this->salesForceClient = $salesForceClient;
    }

    public function raiseEvent(Entity $merchant, SalesForceEventRequestDTO $salesForceEventRequestDTO) {
        $eventPayload = $this->buildSalesforceEventPayloadForEventType($salesForceEventRequestDTO->getEventType(), $salesForceEventRequestDTO, $merchant);
        $this->salesForceClient->sendEventToSalesForce($eventPayload);
    }

    private function buildSalesforceEventPayloadForEventType(SalesForceEventRequestType $salesForceEventRequestType,
                                                             SalesForceEventRequestDTO $salesForceEventRequestDTO,
                                                             Entity $merchant) {
        switch ($salesForceEventRequestType->getValue()) {
            case 'CURRENT_ACCOUNT_INTEREST':
                $DATE_FORMAT = 'Y-m-d';
                $eventPayload = [
                    'merchant_id'           => $merchant->getId(),
                    'name'                  => $merchant->getName(),
                    'email'                 => $merchant->getEmail(),
                    'activated'             => (int)$merchant->isActivated(),
                    'signup_date'           => date($DATE_FORMAT, $merchant->getCreatedAt()),
                    'business_name'         => $merchant->getMerchantDetail()->getBusinessName(),
                    'contact_name'          => $merchant->getMerchantDetail()->getContactName(),
                    'event_submission_date' => date($DATE_FORMAT)
                ];
                return array_merge($eventPayload, $salesForceEventRequestDTO->getEventProperties());
            default:
                throw new InvalidArgumentException("Invalid Event Type");
        }

    }
}
