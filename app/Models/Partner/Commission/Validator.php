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
    protected static $analyticsRules = [
        Constants::TO           => 'required|integer',
        Constants::FROM         => 'required|integer',
        Constants::QUERY_TYPE   => 'required|string|custom',
    ];

    protected static $createRules = [
        Entity::FEE         => 'required|integer',
        Entity::TAX         => 'required|integer',
        Entity::MODEL       => 'required|string|custom',
        Entity::TYPE        => 'required|string|in:'.Type::IMPLICIT . ',' . Type::EXPLICIT,
        Entity::DEBIT       => 'required|integer',
        Entity::CREDIT      => 'required|integer',
        Entity::RECORD_ONLY => 'required|integer',
        Entity::CURRENCY    => 'sometimes|string|in:'.Currency::INR . ',' . Currency::MYR,
    ];

    protected static $markForSettlementRules = [
        Constants::FROM       => 'sometimes|integer|custom:start_time',
        Constants::TO         => 'sometimes|integer|custom:end_time',
        Constants::INVOICE_ID => 'required_without:to|string|size:14',
    ];

    protected static $bulkCaptureRules = [
        Constants::PARTNER_IDS        => 'required|array|min:1',
        Constants::PARTNER_IDS . '.*' => 'filled|string|size:14',
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
}
