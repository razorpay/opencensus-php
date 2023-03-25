<?php

namespace RZP\Models\Settlement;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Environment;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundTransfer\Rbl\RequestConstants;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT          => 'required',
        Entity::STATUS          => 'required|in:created,failed,processed',
        Entity::FEES            => 'sometimes',
        Entity::TAX             => 'sometimes',
        Entity::IS_NEW_SERVICE  => 'sometimes',
        Entity::CHANNEL         => 'required|string|custom',
    ];

    protected static $batchFetchRules = [
        Entity::BATCH_FUND_TRANSFER_ID => 'required|alpha_num|size:14',
        'h2h'                          => 'required|in:0,1',
    ];

    protected static $settlementAmountRules = [
        'balance_type' => 'sometimes|string|custom',
    ];

    protected static $nodalTransferRules = [
        Entity::GATEWAY     => 'sometimes|filled|string|max:32|custom',
        Entity::AMOUNT      => 'required_without:gateway|integer|min:100|max:100000000000',
        Entity::CHANNEL     => 'required_without:gateway|string|max:32|custom',
        Entity::DESTINATION => 'required|string|max:32|custom'
    ];

    protected static $rblAddBeneficiaryRules = [
        RequestConstants::BEN_IFSC        => 'required|string',
        RequestConstants::BEN_ACCT_NO     => 'required|string',
        RequestConstants::BEN_NAME        => 'required|string',
        RequestConstants::BEN_ADDRESS     => 'required|string',
        RequestConstants::BEN_BANKNAME    => 'required|string',
        RequestConstants::BEN_BRANCHCD    => 'required|string',
        RequestConstants::BEN_BANKCD      => 'required|string',
        RequestConstants::BEN_PAN         => 'required|string',
        RequestConstants::KYC_DOC_NAME    => 'required|string',
        RequestConstants::KYC_DOC_CONTENT => 'required|string',
    ];

    protected static $statusReconcileForApiRules = [
        'fta_ids'         => 'sometimes|array',
        'fta_ids.*'       => 'sometimes|alpha_num|size:14',
        'status'          => 'sometimes|string',
        'duration'        => 'sometimes|integer',
        'failed_response' => 'sometimes|custom',
    ];

    protected static $updateChannelRules = [
        'settlement_ids'   => 'required|array',
        'settlement_ids.*' => 'required|alpha_dash|max:19',
        Entity::CHANNEL    => 'required|string|custom',
    ];

    protected static $retryRules = [
        'settlement_ids'    => 'required|array',
        'settlement_ids.*'  => 'required|alpha_dash|max:20',
        'ignore_time_limit' => 'sometimes',
    ];

    protected static $validChannelRules = [
        Entity::CHANNEL => 'required|string|custom'
    ];

    protected static $canFetchBalanceRules = [
        'balance_' . Entity::CHANNEL    => 'required|string|custom',
    ];

    protected static $settlementVerifyRules = [
        'from'            => 'required_without_all:fta_ids,status|filled|epoch|date_format:U',
        'to'              => 'required_with:to|epoch|date_format:U|after:from',
        'fta_ids'         => 'required_without_all:from,status|array',
        'fta_ids.*'       => 'sometimes|string|size:14',
        'failed_response' => 'sometimes|custom',
        Entity::STATUS    => 'required_without_all:fta_ids,from|string|in:initiated,failed,processed',
    ];

    protected static $settlementInitiateRules = [
        'merchant_ids'        => 'sometimes|array',
        'merchant_ids.*'      => 'required|string|size:14',
        'use_queue'           => 'sometimes|boolean',
        'all'                 => 'sometimes|integer',
        'testSettleTimeStamp' => 'sometimes|integer',
        'logging'             => 'sometimes|boolean',
        'debug'               => 'sometimes|boolean',
        'ignore_time_limit'   => 'sometimes|string',
        'balance_type'        => 'sometimes|string|custom',
        'created_at'          => 'sometimes|epoch',
        'settled_at'          => 'sometimes|epoch',
        'initiated_at'        => 'required_with:settled_at,created_at|epoch',
    ];

    protected static $settlementServiceCreateRules = [
        'merchant_id'               => 'required|string|size:14',
        'channel'                   => 'required|string',
        'balance_type'              => 'required|string|in:primary,commission',
        'amount'                    => 'required|integer',
        'fees'                      => 'required|integer',
        'tax'                       => 'required|integer',
        'settlement_id'             => 'required|string|size:14',
        'status'                    => 'required|string|in:created,processed',
        'type'                      => 'required|string',
        'details'                   => 'required|array',
        'destination_merchant_id'   => 'sometimes|string|size:14',
        'journal_id'                => 'sometimes|string|size:14',
    ];

    protected static $settlementHolidayRules = [
        'year'  =>  'sometimes|digits:4',
    ];

    protected static $settlementTransactionsReplayRules = [
        'merchant_ids'        => 'required|array',
        'merchant_ids.*'      => 'required|string|size:14',
        'balance_type'        => 'required|string|in:primary,commission',
        'source_type'         => 'sometimes',
        'from'                => 'sometimes|epoch',
        'to'                  => 'required_with:from|epoch',
        'transaction_ids'     => 'sometimes|array',
        'transaction_ids.*'   => 'required|string|size:14',
        'initial_ramp'        => 'sometimes|bool',
    ];

    protected static $settlementStatusUpdateRules = [
        'id'                            => 'required|string|size:14',
        'utr'                           => 'sometimes|string',
        'status'                        => 'required|string|in:processed,failed',
        'remarks'                       => 'sometimes|string',
        'redacted_ba'                   => 'required|string',
        'failure_reason'                => 'sometimes|string',
        'trigger_failed_notification'   => 'sometimes|bool'
    ];

    protected static $settlementTransactionsVerifyRules = [
        'transaction_ids'   => 'required|array',
        'transaction_ids.*' => 'required|string|size:14',
    ];

    protected static $settlementsServiceMigrationRules = [
        'merchant_ids'            => 'sometimes|array',
        'merchant_ids.*'          => 'required|string|size:14',
        'migrate_bank_account'    => 'required|bool',
        'migrate_merchant_config' => 'required|bool',
        'via'                     => 'required|string|in:fts,payout'
    ];

    protected static $orgConfigGetRules = [
        'org_id'            => 'required|size:18'
    ];

    protected static $orgConfigCreateRules = [
        'org_id'            => 'required|size:18',
        'config'            => 'required|array',
    ];

    protected static $settlementsStatusReplayRules = [
        'settlement_ids'    => 'required|array',
        'settlement_ids.*'  => 'required|string|size:14',
    ];

    protected static $settlementTransactionSourceDetailRules = [
        'source_id'        => 'sometimes|string',
        'source_type'      => 'required|string',
        'skip'             => 'required|integer',
        'limit'            => 'required|integer',
    ];

    protected static $settlementSmsNotificationRules = [
        'enable'    => 'required|boolean',
    ];

    protected static $settlementBulkMigrationsRules = [
        'limit'              => 'required|integer|max:7000|min:1',
        'offset_id'          => 'sometimes|string|alpha_num|size:14',
    ];

    protected static $settlementsServiceBlockedMigrationRules = [
        'limit'              => 'required|integer|max:7000|min:1',
        'offset_id'          => 'sometimes|string|alpha_num|size:14',
        'from'              => 'sometimes|epoch',
        'to'                => 'required_with:from|epoch',
        'merchant_ids'      => 'sometimes|array',
        'merchant_ids.*'    => 'required|string|size:14',
    ];

    protected static $settlementLedgerInconsistencyDebugRules = [
        'merchant_ids'      => 'sometimes|array',
        'merchant_ids.*'    => 'required|string|size:14',
        'fetch_active_mtu'  => 'sometimes|bool',
        'set_baseline_zero' => 'required_with:fetch_active_mtu|bool',
        'from'              => 'sometimes|epoch',
        'to'                => 'required_with:from|epoch',
    ];

    protected static $adminGenerateGifuFileRules = [
        'merchant_ids'      =>  'required|array',
        'from_timestamp'    =>  'required|integer',
        'to_timestamp'      =>  'required|integer'
    ];

    protected function validateBalanceType($attribute, $value)
    {
        Balance\Type::validateSettlementBalanceType($value);
    }

    protected function validateFailedResponse($attribute, $value)
    {
        $app = App::getFacadeRoot();

        if ($app->environment(Environment::TESTING, Environment::DEV) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'failed_response only be used in testing environment');
        }
    }

    public function validateMerchantIdsBelongToOrg($orgId , $merchantIds)
    {
        foreach ($merchantIds as $merchantId)
        {
            $orgForMid = (new Merchant\Repository)->getMerchantOrg($merchantId);

            if($orgId !== $orgForMid)
                throw new Exception\BadRequestValidationFailureException(
                    'Merchant '.$merchantId.' does not belong to the org specified');

        }
    }

    protected function validateGateway($attribute, $value)
    {
        Payment\Gateway::validateGateway($value);
    }

    protected function validateChannel($attribute, $value)
    {
        if (in_array($value, Channel::getChannels()) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Channel: ' . $value);
        }
    }

    protected function validateBalanceChannel($attribute, $value)
    {
        if (in_array($value, Channel::getChannelsWithFetchBalance()) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Channel: ' . $value);
        }
    }

    protected function validateDestination($attribute, $value)
    {
        if (in_array($value, Channel::getChannels()) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Channel: ' . $value);
        }
    }
}
