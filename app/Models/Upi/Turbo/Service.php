<?php

namespace RZP\Models\Upi\Turbo;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function fetchErrorMappings()
    {
        $this->trace->info(TraceCode::UPI_TURBO_ERROR_MAPPINGS_FETCH_REQUEST);

        [$errorMappings, $fileHash] = $this->core->getTurboErrorMappings();

        $this->trace->info(TraceCode::UPI_TURBO_ERROR_MAPPINGS_FETCHED,
                           [
                               'error_mapping_hash' => $fileHash,
                           ]);

        return $errorMappings;
    }

    public function setErrorMappingsAdmin()
    {
        $this->trace->info(TraceCode::UPI_TURBO_ERROR_MAPPINGS_SET_REQUEST);

        [$errorMappings, $fileHash] = $this->core->generateTurboErrorMappings([Gateway::UPI_AXISOLIVE]);

        $this->trace->info(TraceCode::UPI_TURBO_ERROR_MAPPINGS_SET_COMPLETE,
                           [
                               'error_mapping_hash' => $fileHash,
                           ]);

        return $errorMappings;
    }

    public function recordCustomerConsent($input): array
    {
        $this->trace->info(TraceCode::UPI_TURBO_CUSTOMER_RECORD_CONSENT_REQUEST);

        $input[Constants::ACKNOWLEDGE] = boolval($input[Constants::ACKNOWLEDGE]);

        (new Validator)->validateInput('customer_record_consent', $input);

        $input[Constants::CUSTOMER_IDENTIFIER_VALUE] = hash('sha256', $input[Constants::CUSTOMER_IDENTIFIER_VALUE]);

        /*
         * We are logging the consent data temporarily. Once CDP is live with customer consents, we will be saving
         * the consent data in CDP apart from just logging it here.
         */
        $this->trace->info(TraceCode::UPI_TURBO_CUSTOMER_RECORD_CONSENT_PROCESSED,
                           [
                                'input' => $input
                           ]);

        return [
            "success" => true
        ];
    }

    public function fetchCustomerRewardEligibility($input): array
    {
        $traceCode = TraceCode::TURBO_UPI_CUSTOMER_REWARD_ELIGIBILITY_REQUEST_RECEIVED;

        $this->redactContactForlogger($traceCode, $input);

        return (new RewardProcessor\Base)->process($input, RewardProcessor\Base::FLOW_ELIGIBILITY);
    }

    public function allotCustomerReward($input): array
    {
        $traceCode  =  TraceCode::TURBO_UPI_CUSTOMER_REWARD_ALLOT_REQUEST_RECEIVED;

        $this->redactContactForlogger($traceCode, $input);

        return (new RewardProcessor\Base)->process($input, RewardProcessor\Base::FLOW_ALLOT);
    }

    public function redactContactForlogger($traceCode, $input): void
    {
        if (isset($input['contact'])) {
            $input['contact'] = hash('sha256', $input['contact']);
        }

        $this->trace->info($traceCode,
                        [
                            'input' => $input
                        ]);
    }
}
