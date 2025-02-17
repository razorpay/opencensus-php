<?php

namespace RZP\Models\LinkedNumber;

use App;
use RZP\Constants\Entity as E;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payout\Metric;
use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\FundAccount;
use RZP\Http\Request\Requests;
use RZP\Exception;
use RZP\Models\Internal\PayoutService;
use RZP\Models\FundAccount\Validation as FAV;
use RZP\Services\PayoutService\VpaMapperFetch;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $favCore;
    protected $mappedVpaFetchClient;
    protected $vpaCore;
    public function __construct()
    {
        parent::__construct();

        $this->favCore = new FAV\Core();
        $this->mappedVpaFetchClient = $this->app[VpaMapperFetch::MAPPED_VPA_FETCH];
    }

    public function FetchMappedVpaFromLinkedNumber(string $linkedNumber, string $accountHolderName)
    {
        $mappedVpa = $this->mappedVpaFetchClient->fetchMappedVpaViaMicroservice($linkedNumber);

        if (empty($mappedVpa) || !isset($mappedVpa[FundAccount\Entity::VPA])) {
            $this->trace->count(Metric::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND_COUNT);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VPA_NOT_FOUND,
                null,
                null,
                PublicErrorDescription::BAD_REQUEST_VPA_NOT_FOUND
            );
        }

        $this->trace->info(TraceCode::MAPPED_VPA_FROM_LINKED_NUMBER,
            [
                'MappedVpa' => $mappedVpa,
                'LinkedNumber' => $linkedNumber
            ]);

        $customerName = $mappedVpa[FundAccount\Entity::CUSTOMER_NAME];

        $this->doMatchScoring($customerName, $accountHolderName);

        return [
            FundAccount\Entity::VPA => $mappedVpa[FundAccount\Entity::VPA],
            FundAccount\Entity::CUSTOMER_NAME => $mappedVpa[FundAccount\Entity::CUSTOMER_NAME]
        ];
    }

    public function doMatchScoring(string $customerName, string $accountHolderName)
    {
        $threshold = $this->app['config']->get('app.payouts_to_phone_number_name_match_threshold');

        $matchScore = $this->favCore->getNameScoreForValidation($customerName, $accountHolderName);

        $this->trace->info(TraceCode::NAME_MATCHING_SCORE_FOR_LINKED_NUMBER,
            [
                'matchScore' => $matchScore
            ]);

        if ($matchScore < $threshold) {
            $this->trace->count(Metric::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_THRESHOLD_FAILURE_COUNT);
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CONTACT_NAME_MISMATCH_WITH_MAPPED_VPA,
                null,
                [],
                PublicErrorDescription::BAD_REQUEST_CONTACT_NAME_MISMATCH_WITH_MAPPED_VPA
            );
        }
    }
}
