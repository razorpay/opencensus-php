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

    public function getMerchantDetailsOnOpportunity(string $merchantId, array $opportunities): array {
        $responsePayload = $this->salesForceClient->getMerchantDetailsOnOpportunity($merchantId, $opportunities);
        return $this->parseResponseToMerchantDetail($responsePayload);
    }

    private function buildSalesforceEventPayloadForEventType(SalesForceEventRequestType $salesForceEventRequestType,
                                                             SalesForceEventRequestDTO $salesForceEventRequestDTO,
                                                             Entity $merchant) {
        switch ($salesForceEventRequestType->getValue()) {
            case 'LOS_NEW_APPLICATION':
            case 'CURRENT_ACCOUNT_INTEREST':
                $DATE_FORMAT = 'Y-m-d';
                $eventPayload = [
                    'merchant_id'           => $merchant->getId(),
                    'name'                  => $merchant->getName(),
                    'email'                 => $merchant->getEmail(),
                    'activated'             => (int)$merchant->isActivated(),
                    'signup_date'           => date($DATE_FORMAT, $merchant->getCreatedAt()),
                    'event_submission_date' => date($DATE_FORMAT)
                ];
                return array_merge($eventPayload, $salesForceEventRequestDTO->getEventProperties());
            case 'RX_WEBSITE_SF_EVENTS':
                return $salesForceEventRequestDTO->getEventProperties();
            default:
                throw new InvalidArgumentException("Invalid Event Type");
        }

    }

    private function parseResponseToMerchantDetail($response): array {
        $merchantOpportunityDetails = array();
        if ($response['totalSize'] >= 1) {
            foreach ($response['records'] as $record) {
                $merchantOpportunityDetail = new SalesforceMerchantOpportunityDetail();
                $merchantOpportunityDetail->setMerchantId($record['Account']['Merchant_ID__c']);
                $merchantOpportunityDetail->setOpportunityName($record['Type']);
                $merchantOpportunityDetail->setOpportunityStage($record['StageName']);
                $merchantOpportunityDetail->setOpportunityLossReason($record['Loss_Reason__c']);
                $merchantOpportunityDetail->setOpportunityOwnerName($record['Owner']['Name']);
                $merchantOpportunityDetail->setOpportunityOwnerRole($record['Owner_Role__c']);
                $merchantOpportunityDetail->setOpportunityLastModifiedTime($record['LastModifiedDate']);
                $merchantOpportunityDetails[] = $merchantOpportunityDetail;
            }
        }
        return $merchantOpportunityDetails;
    }
}
