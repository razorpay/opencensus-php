<?php

namespace RZP\Models\Payout\Configurations\DirectAccounts\PayoutModeConfig;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\Dcs\Configurations\Constants as DcsConstants;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createPayoutModeConfig($input)
    {
        $this->trace->info(
            TraceCode::DIRECT_ACCOUNTS_PAYOUT_MODE_CONFIG_CREATE_REQUEST,
            [
                "input" => $input
            ]);

        (new Validator())->validateInput(Validator::CREATE_PAYOUT_MODE_CONFIG, $input);

        $merchantId = $input[Constants::MERCHANT_ID];

        $fields = $input[Constants::FIELDS];

        $key = DcsConstants::DirectAccountsPayoutModeConfig;

        $response = [];

        try
        {
            $dcsConfigService = app('dcs_config_service');

            $dcsConfigService->createConfiguration($key, $merchantId, $fields, $this->mode);

            $response["success"] = true;
        }
        catch(\Throwable $throwable)
        {
            $this->trace->traceException(
                $throwable,
                Trace::ERROR,
                TraceCode::DIRECT_ACCOUNTS_PAYOUT_MODE_CONFIG_CREATE_REQUEST_FAILED,
                [
                    'merchant_id' => $merchantId,
                ]);

            $response["success"] = false;
        }

        return $response;
    }

    public function editPayoutModeConfig($input)
    {
        $this->trace->info(
            TraceCode::DIRECT_ACCOUNTS_PAYOUT_MODE_CONFIG_EDIT_REQUEST,
            [
                "input" => $input
            ]);

        (new Validator())->validateInput(Validator::EDIT_PAYOUT_MODE_CONFIG, $input);

        $merchantId = $input[Constants::MERCHANT_ID];

        $fields = $input[Constants::FIELDS];

        $key = DcsConstants::DirectAccountsPayoutModeConfig;

        $response = [];

        try
        {
            $dcsConfigService = app('dcs_config_service');

            $dcsConfigService->editConfiguration($key, $merchantId, $fields, $this->mode);

            $response["success"] = true;
        }
        catch(\Throwable $throwable)
        {
            $this->trace->traceException(
                $throwable,
                Trace::ERROR,
                TraceCode::DIRECT_ACCOUNTS_PAYOUT_MODE_CONFIG_EDIT_REQUEST_FAILED,
                [
                    'merchant_id' => $merchantId,
                ]);

            $response["success"] = false;
        }

        return $response;
    }

    public function fetchPayoutModeConfig($input)
    {
        $this->trace->info(
            TraceCode::DIRECT_ACCOUNTS_PAYOUT_MODE_CONFIG_FETCH_REQUEST,
            [
                "input" => $input
            ]);

        (new Validator())->validateInput(Validator::FETCH_PAYOUT_MODE_CONFIG, $input);

        $merchantId = $input[Constants::MERCHANT_ID];

        $key = DcsConstants::DirectAccountsPayoutModeConfig;

        $fields = Constants::DIRECT_ACCOUNTS_PAYOUT_MODE_CONFIG_FIELDS;

        $dcsConfigService = app('dcs_config_service');

        $config = $dcsConfigService->fetchConfiguration($key, $merchantId, $fields, $this->mode);

        $res = [];

        if (isset($config[Constants::ALLOWED_UPI_CHANNELS]) === true)
        {
            $res[Constants::ALLOWED_UPI_CHANNELS] = [];

            foreach ($config[Constants::ALLOWED_UPI_CHANNELS] as $allowedChannel)
            {
                $res[Constants::ALLOWED_UPI_CHANNELS][] = $allowedChannel;
            }
        }

        return $res;
    }

    public function checkIfUpiDirectAccountChannelEnabledForMerchant($merchantId, $channel): bool
    {
        try
        {
            $params = [
                Constants::MERCHANT_ID => $merchantId,
            ];

            $config = $this->fetchPayoutModeConfig($params);

            if (isset($config[Constants::ALLOWED_UPI_CHANNELS]) === true)
            {
                return in_array($channel, $config[Constants::ALLOWED_UPI_CHANNELS], true);
            }
        }
        catch(\Throwable $throwable)
        {
            $this->trace->traceException(
                $throwable,
                Trace::ERROR,
                TraceCode::DIRECT_ACCOUNTS_PAYOUT_MODE_CONFIG_FETCH_REQUEST_FAILED,
                [
                    'merchant_id' => $merchantId,
                ]);
        }

        return false;
    }
}
