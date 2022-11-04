<?php

namespace RZP\Models\SalesForce;

use App;
use ApiResponse;

use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Merchant\Entity;
use RZP\Services\SalesForceClient;
use RZP\Models\Merchant\Attribute\Group;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\XChannelDefinition;
use RZP\Models\Merchant\Attribute\Repository;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Attribute\Type as MerchantAttributeType;
use RZP\Models\Merchant\Attribute\Entity as MerchantAttributeEntity;
use RZP\Models\Merchant\Attribute\Repository as MerchantAttributeRepository;
use RZP\Models\BankingAccountService\Constants as BankingAccountServiceConstants;
use RZP\Trace\TraceCode;

class SalesForceService extends Base\Service {
    const VendorPayout = 'Vendor_Payout';
    /** @var $salesForceClient SalesForceClient */
    private $salesForceClient;


    public function __construct(SalesForceClient $salesForceClient) {
        parent::__construct();
        $this->salesForceClient = $salesForceClient;
    }

    public function raiseEvent(Entity $merchant, SalesForceEventRequestDTO $salesForceEventRequestDTO) {
        $eventType    = $salesForceEventRequestDTO->getEventType()->getValue();
        $eventPayload = $this->buildSalesforceEventPayloadForEventType($salesForceEventRequestDTO->getEventType(), $salesForceEventRequestDTO, $merchant);

        if ($eventType === Constants::RX_WEBSITE_SF_EVENTS)
        {
            $this->salesForceClient->sendLeadUpsertEventsToSalesforce($eventPayload);

            return;
        }

        if ($eventType === Constants::CURRENT_ACCOUNT_INTEREST)
        {
            if (empty($eventPayload['Business_Type']) === false)
            {
                $eventPayload['Business_Type'] = $this->salesForceClient->getCaBusinessTypeIndex($eventPayload['Business_Type']);
            }
        }

        $repo = new MerchantAttributeRepository();

        $merchantAttribute = $repo->getKeyValues($merchant->getId(), ProductType::BANKING, Group::X_MERCHANT_PREFERENCES, ['x_signup_platform'])->first();

        $eventPayload[BankingAccountServiceConstants::SOURCE_DETAIL] = $merchantAttribute[MerchantAttributeEntity::VALUE] ?? BankingAccountServiceConstants::X_DASHBOARD;

        $merchantAttributeOnboardingFlow = $repo->getKeyValues($merchant->getId(), ProductType::BANKING, Group::X_MERCHANT_CURRENT_ACCOUNTS, [MerchantAttributeType::CA_ONBOARDING_FLOW])->first();

        $caOnboardingFlow = $merchantAttributeOnboardingFlow[MerchantAttributeEntity::VALUE] ?? null;

        if ($caOnboardingFlow != null)
        {
            $eventPayload[MerchantAttributeType::CA_ONBOARDING_FLOW] = $caOnboardingFlow;
        }

        $this->addAndStoreChannelDetailsIfApplicable($merchant, $eventType, $eventPayload);

        $this->salesForceClient->sendEventToSalesForce($eventPayload);

        if ($eventType == Constants::CURRENT_ACCOUNT_INTEREST)
        {
            // added lumberjack integration to match data pulled from SF with product data
            app('diag')->trackOnboardingEvent(EventCode::X_CA_ONBOARDING_OPPORTUNITY_UPSERT, $merchant, null, $eventPayload);
        }
    }

    public function getMerchantDetailsOnOpportunity(string $merchantId, array $opportunities): array {
        $app = App::getFacadeRoot();
        $merchant = $app['basicauth']->getMerchant();
        if (empty($merchant) === true ||
            $merchant->getId() !== $merchantId)
        {
            return ["unauthorized" => true];
        }
        $responsePayload = $this->salesForceClient->getMerchantDetailsOnOpportunity($merchantId, $opportunities);
        return $this->parseResponseToMerchantDetail($responsePayload);
    }

    private function buildSalesforceEventPayloadForEventType(SalesForceEventRequestType $salesForceEventRequestType,
                                                             SalesForceEventRequestDTO $salesForceEventRequestDTO,
                                                             Entity $merchant) {
        switch ($salesForceEventRequestType->getValue()) {
            case Constants::CURRENT_ACCOUNT_CLARITY_CONTEXT:
                $eventPayload = [
                    'merchant_id'           => $merchant->getId(),
                    'product_name'          => 'Current_Account'
                ];
                return array_merge($eventPayload, $salesForceEventRequestDTO->getEventProperties());

            case Constants::LOS_NEW_APPLICATION:
            case Constants::CURRENT_ACCOUNT_INTEREST:
            case Constants::SHOPIFY_MIGRATION_REQUEST:
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

            case Constants::RX_WEBSITE_SF_EVENTS:
                return $salesForceEventRequestDTO->getEventProperties();

            case Constants::VENDOR_PAYMENT_EVENT:
                $eventPayload = [
                    'merchant_id'           => $merchant->getId(),
                    'product_name'          => self::VendorPayout
                ];
                return array_merge($eventPayload, $salesForceEventRequestDTO->getEventProperties());

            default:
                throw new InvalidArgumentException("Invalid Event Type");
        }
    }

    private function addPartnerAndSourceDetailsToPayloadIfApplicable(array & $payload, string $sfEventRequestType, Entity $merchant)
    {
        $merchantCore = (new MerchantCore());

        if ($sfEventRequestType === 'CURRENT_ACCOUNT_INTEREST')
        {
            $partners = $merchantCore->fetchAffiliatedPartners($merchant->getId());

            $partner = $partners->first();

            if (empty($partner) === false)
            {
                $partnerId = $partner->getId();

                // data to create opportunity for partnership leads on SF
                $data = ['partner_id' => $partnerId, 'source_detail' => 'banking'];

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

    public function getSalesforceDetailsForMerchantIDs(array $merchantIds) : array
    {
        return app('salesforce')->getSalesforceDetailsForMerchantIDs($merchantIds);
    }

    protected function addAndStoreChannelDetailsIfApplicable(Entity $merchant, ?string $eventType, array &$eventPayload)
    {
        $xChannelDefinitionService = new XChannelDefinition\Service;

        if (empty($eventType) === false && $eventType === 'CURRENT_ACCOUNT_INTEREST')
        {
            $campaignId = $eventPayload['Campaign_ID'] ?? '';

            $this->trace->info(TraceCode::X_CHANNEL_DEFINITION_SF_OPPORTUNITY_EVENT, compact('eventType', 'campaignId'));

            try
            {
                if (str_contains(strtolower($campaignId), 'nitro'))
                {
                    $xChannelDefinitionService->storeChannelAndSubchannel($merchant, XChannelDefinition\Channels::PG, XChannelDefinition\Channels::PG_NITRO);
                    $eventPayload['X_Channel']    = XChannelDefinition\Channels::PG;
                    $eventPayload['X_Subchannel'] = XChannelDefinition\Channels::PG_NITRO;
                }
                elseif ($campaignId === XChannelDefinition\Constants::SF_CAMPAIGN_ID_BANKING_WIDGET)
                {
                    $xChannelDefinitionService->storeChannelAndSubchannel($merchant, XChannelDefinition\Channels::PG, XChannelDefinition\Channels::PG_BANKING_WIDGET);
                    $eventPayload['X_Channel']    = XChannelDefinition\Channels::PG;
                    $eventPayload['X_Subchannel'] = XChannelDefinition\Channels::PG_BANKING_WIDGET;
                }

                if (isset($eventPayload['X_Channel']) && isset($eventPayload['X_Subchannel'])) {
                    $this->trace->info(TraceCode::X_CHANNEL_DEFINITION_UPDATING_SF_PAYLOAD, [
                        'channel'    => $eventPayload['X_Channel'],
                        'subchannel' => $eventPayload['X_Subchannel'],
                    ]);
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, null, TraceCode::X_CHANNEL_DEFINITION_FAILED_TO_SAVE_FROM_SF_EVENT);
            }
        }
    }
}
