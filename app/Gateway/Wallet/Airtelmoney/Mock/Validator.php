<?php

namespace RZP\Gateway\Wallet\Airtelmoney\Mock;

use RZP\Models\Base;
use RZP\Gateway\Wallet\Airtelmoney\Constants;
use RZP\Gateway\Wallet\Airtelmoney\RequestFields;
use RZP\Gateway\Wallet\Airtelmoney\ResponseFields;

class Validator extends Base\Validator
{
    protected static $authorizeRules = array(
        RequestFields::MID         => 'required|string',
        RequestFields::SU          => 'required|url',
        RequestFields::FU          => 'required|url',
        RequestFields::TXN_REF_NO  => 'required|string',
        RequestFields::AMT         => 'required|numeric',
        RequestFields::DATE        => 'required|date_format:'.Constants::REQUEST_DATE_FORMAT,
        RequestFields::HASH        => 'required|regex:"^[a-f0-9]+$"',
        RequestFields::CUR         => 'required|in:INR',
        RequestFields::CUST_EMAIL  => 'sometimes|email',
        RequestFields::CUST_MOBILE => 'sometimes|regex:"^[0-9]{10}"',
        RequestFields::END_MID     => 'sometimes|string',
    );

    protected static $refundRules = array(
        RequestFields::MID     => 'required|string',
        RequestFields::AMT     => 'required|numeric',
        RequestFields::TXN_ID  => 'required|string',
        RequestFields::DATE    => 'required|date_format:'.Constants::REQUEST_DATE_FORMAT,
        RequestFields::REMARKS => 'required|string',
    );

    protected static $verifyRules = array(
        RequestFields::MID        => 'required|string',
        RequestFields::TXN_REF_NO => 'required|string',
        RequestFields::DATE       => 'required|date_format:'.Constants::REQUEST_DATE_FORMAT,
    );
}
