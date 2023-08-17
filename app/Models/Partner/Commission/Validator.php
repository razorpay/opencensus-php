<?php

namespace RZP\Models\Partner\Commission;

use App;

use Carbon\Carbon;

use RZP\Base;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;
use RZP\Models\Partner\Config;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $analyticsRules                = [
        Constants::TO         => 'required|integer',
        Constants::FROM       => 'required|integer',
        Constants::QUERY_TYPE => 'required|string|custom',
    ];

    protected static $createRules                   = [
        Entity::FEE         => 'required|integer',
        Entity::TAX         => 'required|integer',
        Entity::MODEL       => 'required|string|custom',
        Entity::TYPE        => 'required|string|in:' . Type::IMPLICIT . ',' . Type::EXPLICIT,
        Entity::DEBIT       => 'required|integer',
        Entity::CREDIT      => 'required|integer',
        Entity::RECORD_ONLY => 'required|integer',
        Entity::CURRENCY    => 'sometimes|string|in:' . Currency::INR . ',' . Currency::MYR,
        Entity::SOURCE_ID   => 'sometimes|string|size:14',
        Entity::SOURCE_TYPE => 'sometimes|string|in:' . Constants::PAYMENT . ',' . Constants::REFUND,
    ];

    protected static $markForSettlementRules        = [
        Constants::FROM       => 'sometimes|integer|custom:start_time',
        Constants::TO         => 'sometimes|integer|custom:end_time',
        Constants::INVOICE_ID => 'required_without:to|string|size:14',
    ];

    protected static $bulkCaptureRules              = [
        Constants::PARTNER_IDS        => 'required|array|min:1',
        Constants::PARTNER_IDS . '.*' => 'filled|string|size:14',
    ];

    protected static $partnerConfigsRules            = [
        Config\Entity::IMPLICIT_PLAN_ID    => 'sometimes|nullable|string',
        Config\Entity::EXPLICIT_PLAN_ID    => 'sometimes|nullable|string',
        Config\Entity::COMMISSION_MODEL    => 'required|string',
        Constants::SHOULD_CREDIT_GST       => 'required|boolean',
    ];


    protected static $partnerDetailsRules = [
        Entity::ID                => 'required|string|size:14',
        Constants::TAX_COMPONENTS => 'required|array'
    ];

    protected static $paymentRules                  = [
        Entity::ID             => 'required|string|min:14|max:18', // adding validation for payment public_id, id
        Constants::MERCHANT_ID => 'required|string|size:14'
    ];

    protected static $calculateCommissionRules = [
        Constants::PAYMENT         => 'required|array',
        Constants::PARTNER_CONFIGS => 'required|array',
        Constants::PARTNER_DETAILS => 'required|array'
    ];

    protected static $reverseCommissionForRefundRules = [
        Constants::PAYMENT_ID    => 'required|string|size:14',
        Constants::REFUND_ID     => 'required|string|size:14',
        Constants::REFUND_AMOUNT => 'required|integer'
    ];

    protected static $calculateCommissionValidators = [
        'partner_configs',
        'payment',
        'partner_details'
    ];

    public function validateQueryType($attribute, $value)
    {
        if (Constants::isValidQueryType($value) === false)
        {
            throw new BadRequestValidationFailureException('Invalid query type: ' . $value);
        }
    }

    /**
     * @param $attribute
     * @param $type
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateModel($attribute, $type)
    {
        Config\CommissionModel::validate($type);
    }

    public function validateStartTime($attribute, $value)
    {
        $now = Carbon::now(Timezone::IST)->getTimestamp();

        if ($value > $now)
        {
            throw new BadRequestValidationFailureException('Start time should be less than current time');
        }
    }

    public function validateEndTime($attribute, $value)
    {
        $app = App::getFacadeRoot();

        if ($app['rzp.mode'] === Mode::TEST)
        {
            return;
        }

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        if ($value > $now)
        {
            throw new BadRequestValidationFailureException('End time should be less than current time');
        }
    }

    public function validatePartnerConfigs(array $input)
    {
        $partnerConfigInput = $input[Constants::PARTNER_CONFIGS];

        $this->validateInput('partner_configs', $partnerConfigInput);
    }

    public function validatePartnerDetails(array $input)
    {
        $partnerDetailInput = $input[Constants::PARTNER_DETAILS];

        $this->validateInput('partner_details', $partnerDetailInput);
    }

    public function validatePayment(array $input)
    {
        $paymentInput = $input[Constants::PAYMENT];

        $this->validateInput('payment', $paymentInput);
    }
}
