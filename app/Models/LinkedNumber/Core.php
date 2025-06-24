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
    protected $payoutEvents;

    public function __construct()
    {
        parent::__construct();

        $this->favCore = new FAV\Core();
        $this->mappedVpaFetchClient = $this->app[VpaMapperFetch::MAPPED_VPA_FETCH];
        $this->payoutService = new DualWritePayout();
        $this->payoutEvents = new Payout\Events();
    }

    public function FetchMappedVpaFromLinkedNumber(string $linkedNumber, string $accountHolderName, string $merchantId)
    {
        $mappedVpa = $this->mappedVpaFetchClient->fetchMappedVpaViaMicroservice($linkedNumber);

        if (empty($mappedVpa) ||
            empty($mappedVpa[FundAccount\Entity::VPA]) ||
            empty($mappedVpa[FundAccount\Entity::CUSTOMER_NAME]) ||
            !str_contains($mappedVpa[FundAccount\Entity::VPA], '@'))
        {
            $this->trace->count(Metric::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND_COUNT);

            $this->payoutEvents->trackPayoutsToPhoneNumberVpaNotFoundEvent(
                $merchantId,
                $linkedNumber,
                $accountHolderName
            );

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

            $this->payoutEvents->trackPayoutsToPhoneNumberNameMatchingBelowThresholdEvent(
                $merchantId,
                $linkedNumber,
                $accountHolderName,
                $customerName,
                $vpaID,
                $matchScore,
                $threshold
            );

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                FundAccount\Entity::ACCOUNT_HOLDER_NAME,
                [],
                PublicErrorDescription::BAD_REQUEST_CONTACT_NAME_MISMATCH_WITH_MAPPED_VPA
            );
        }
    }
}
