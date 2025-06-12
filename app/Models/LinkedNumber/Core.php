<?php

namespace RZP\Models\LinkedNumber;

use App;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payout\DualWrite\Payout as DualWritePayout;
use RZP\Models\Payout\Metric;
use RZP\Models\Payout;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\FundAccount;
use RZP\Exception;
use RZP\Models\FundAccount\Validation as FAV;
use RZP\Services\PayoutService\VpaMapperFetch;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $favCore;
    protected $mappedVpaFetchClient;
    protected $vpaCore;
    protected $payoutService;
    protected $payoutCore;

    const PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND = 'PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND';
    const PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD = 'PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD';

    public function __construct()
    {
        parent::__construct();

        $this->favCore = new FAV\Core();
        $this->mappedVpaFetchClient = $this->app[VpaMapperFetch::MAPPED_VPA_FETCH];
        $this->payoutService = new DualWritePayout();
        $this->payoutCore = new Payout\Core();
    }

    public function FetchMappedVpaFromLinkedNumber(string $linkedNumber, string $accountHolderName, string $merchantId)
    {
        $mappedVpa = $this->mappedVpaFetchClient->fetchMappedVpaViaMicroservice($linkedNumber);

        if (empty($mappedVpa) || empty($mappedVpa[FundAccount\Entity::VPA]) || empty($mappedVpa[FundAccount\Entity::CUSTOMER_NAME])) {
            $this->trace->count(Metric::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND_COUNT);

            // Track the failure event
            try {
                $sanitizedData = $this->payoutCore->sanitizeDataForTracking([
                    FundAccount\Entity::MOBILE => $linkedNumber
                ]);
                $this->payoutCore->trackPhoneNumberPayoutFailureEvents(
                    self::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND,
                    [
                        Base\PublicEntity::MERCHANT_ID      => $merchantId,
                        FundAccount\Entity::MOBILE          => $sanitizedData[FundAccount\Entity::MOBILE],
                        FundAccount\Entity::CUSTOMER_NAME   => $accountHolderName,
                        'failure_reason'                    => self::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND
                    ]
                );
            } catch (\Throwable $e) {
                $this->trace->error(TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED, [
                    'error_message'     => $e->getMessage(),
                    'merchant_id'       => $merchantId,
                    'context'           => self::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND
                ]);
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                FundAccount\Entity::MOBILE,
                null,
                PublicErrorDescription::BAD_REQUEST_LINKED_ACCOUNT_NOT_FOUND
            );
        }

        $this->trace->info(TraceCode::MAPPED_VPA_FROM_LINKED_NUMBER,
            [
                'MappedVpa' => $mappedVpa,
                'LinkedNumber' => $linkedNumber
            ]);

        $customerName = $mappedVpa[FundAccount\Entity::CUSTOMER_NAME];
        $vpaID = $mappedVpa[FundAccount\Entity::VPA];

        $this->doMatchScoring($customerName, $accountHolderName, $merchantId, $linkedNumber, $vpaID);

        return [
            FundAccount\Entity::VPA => $mappedVpa[FundAccount\Entity::VPA],
            FundAccount\Entity::CUSTOMER_NAME => $mappedVpa[FundAccount\Entity::CUSTOMER_NAME]
        ];
    }

    public function doMatchScoring(string $customerName, string $accountHolderName, string $merchantId, string $linkedNumber, string $vpaID): void
    {
        $threshold = $this->payoutService->getMerchantSettingsForThresholdFromPayoutService($merchantId);

        $matchScore = $this->favCore->getNameScoreForValidation($customerName, $accountHolderName);

        $this->trace->info(TraceCode::NAME_MATCHING_SCORE_FOR_LINKED_NUMBER,
            [
                'matchScore' => $matchScore
            ]);

        if ($matchScore < $threshold) {
            $this->trace->count(Metric::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_THRESHOLD_FAILURE_COUNT);

            // Track the failure event
            try {
                $sanitizedData = $this->payoutCore->sanitizeDataForTracking([
                    FundAccount\Entity::MOBILE  => $linkedNumber,
                    FundAccount\Entity::VPA     => $vpaID
                ]);

                $this->payoutCore->trackPhoneNumberPayoutFailureEvents(
                    self::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD,
                    [
                        Base\PublicEntity::MERCHANT_ID      => $merchantId,
                        FundAccount\Entity::MOBILE          => $sanitizedData[FundAccount\Entity::MOBILE],
                        FundAccount\Entity::CUSTOMER_NAME   => $accountHolderName,
                        FundAccount\Entity::VPA             => $sanitizedData[FundAccount\Entity::VPA],
                        'bank_customer_name'                => $customerName,
                        'match_score'                       => $matchScore,
                        'threshold'                         => $threshold,
                        'failure_reason'                    => self::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD
                    ]
                );
            } catch (\Throwable $e) {
                $this->trace->error(TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED, [
                    'error_message'         => $e->getMessage(),
                    'merchant_id'           => $merchantId,
                    'context'               => self::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD
                ]);
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                FundAccount\Entity::ACCOUNT_HOLDER_NAME,
                [],
                PublicErrorDescription::BAD_REQUEST_CONTACT_NAME_MISMATCH_WITH_MAPPED_VPA
            );
        }
    }
}
