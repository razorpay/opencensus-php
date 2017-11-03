<?php

namespace RZP\Models\Settlement;

use RZP\Base;
use RZP\Exception;
use RZP\Models\FundTransfer\Rbl\RequestConstants;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT          => 'required',
        Entity::STATUS          => 'required|in:created,failed,processed',
        Entity::FEES            => 'sometimes',
        Entity::TAX             => 'sometimes',
        Entity::CHANNEL         => 'required|string|custom',
    ];

    protected static $batchFetchRules = [
        Entity::BATCH_FUND_TRANSFER_ID => 'required|alpha_num|size:14',
    ];

    protected static $nodalTransferRules = [
        Entity::AMOUNT  => 'required|integer|min:100|max:10000000000',
        Entity::CHANNEL => 'required|string'
    ];

    protected static $rblAddBeneficiaryRules = [
        Entity::CHANNEL                   => 'required|string',
        RequestConstants::BEN_IFSC        => 'required_if:channel,rbl|string',
        RequestConstants::BEN_ACCT_NO     => 'required_if:channel,rbl|integer',
        RequestConstants::BEN_NAME        => 'required_if:channel,rbl|string',
        RequestConstants::BEN_ADDRESS     => 'required_if:channel,rbl|string',
        RequestConstants::BEN_BANKNAME    => 'required_if:channel,rbl|string',
        RequestConstants::BEN_BRANCHCD    => 'required_if:channel,rbl|string',
        RequestConstants::BEN_BANKCD      => 'required_if:channel,rbl|string',
        RequestConstants::BEN_PAN         => 'required_if:channel,rbl|string',
        RequestConstants::KYC_DOC_NAME    => 'required_if:channel,rbl|string',
        RequestConstants::KYC_DOC_CONTENT => 'required_if:channel,rbl|string',
    ];

    protected static $retryRules = [
        'settlement_ids'   => 'required|array',
        'settlement_ids.*' => 'required|alpha_dash|max:20',
    ];

    protected function validateChannel($attribute, $value)
    {
        if (in_array($value, Channel::getChannels()) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Channel: ' . $value);
        }
    }
}
