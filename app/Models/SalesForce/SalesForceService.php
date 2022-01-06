<?php

namespace RZP\Models\SalesForce;

use RZP\Models\Merchant\Entity;
use RZP\Services\SalesForceClient;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Merchant\Core as MerchantCore;

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
                $eventPayload = array_merge($eventPayload, $salesForceEventRequestDTO->getEventProperties());
                $this->addPartnerAndSourceDetailsToPayloadIfApplicable($eventPayload, $salesForceEventRequestType->getValue(), $merchant);
                return $eventPayload;

            case 'RX_WEBSITE_SF_EVENTS':
                return $salesForceEventRequestDTO->getEventProperties();

            default:
                throw new InvalidArgumentException("Invalid Event Type");
        }
    }

    private function addPartnerAndSourceDetailsToPayloadIfApplicable(array & $payload, string $sfEventRequestType, Entity $merchant)
    {
        $merchantCore = (new MerchantCore());

        $isExpEnabled = $merchantCore->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::SEND_PARTNER_AND_SOURCE_DETAILS_TO_SALESFORCE);

        if ($sfEventRequestType === 'CURRENT_ACCOUNT_INTEREST' and $isExpEnabled === true)
        {
            $partners = $merchantCore->fetchAffiliatedPartners($merchant->getId());

            $partner = $partners->first();

            if (empty($partner) === false)
            {
                // data to create opportunity for partnership leads on SF
                $data = ['partner_id' => $partner->getId(), 'source_detail' => 'banking'];

                $payload = array_merge($payload, $data);
            }
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
