<?php

namespace RZP\Models\BankingAccountStatement;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\Status;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccountStatement\Generator\SupportedFormats;

class Validator extends Base\Validator
{
    const ACCOUNT_STATEMENT_GENERATE = 'accountStatementGenerate';

    const SOURCE_UPDATE = 'sourceUpdate';

    const FETCH_MISSING_STATEMENTS = 'fetchMissingStatements';

    const AUTOMATE_ACCOUNT_STATEMENT_RECON = 'automateAccountStatementRecon';

    const DETECT_MISSING_STATEMENTS = 'detect_missing_statements';

    protected static $createRules = [
        Entity::CHANNEL             => 'required|string|custom',
        Entity::ACCOUNT_NUMBER      => 'required|string|max:40',
        Entity::BANK_TRANSACTION_ID => 'required|string',
        Entity::AMOUNT              => 'required|integer',
        Entity::CURRENCY            => 'required|size:3',
        Entity::TYPE                => 'required|string|custom',
        Entity::DESCRIPTION         => 'required|string',
        Entity::CATEGORY            => 'sometimes|nullable|string|custom',
        Entity::BANK_SERIAL_NUMBER  => 'required|string',
        Entity::BANK_INSTRUMENT_ID  => 'sometimes|nullable|string',
        Entity::BALANCE             => 'required|integer',
        Entity::BALANCE_CURRENCY    => 'required|size:3',
        Entity::POSTED_DATE         => 'required|integer',
        Entity::TRANSACTION_DATE    => 'required|integer',
    ];

    protected static $accountStatementGenerateRules = [
        Entity::CHANNEL              => 'required|string|custom',
        Entity::ACCOUNT_NUMBER       => 'required|string|between:5,40',
        Entity::FROM_DATE            => 'required|epoch',
        Entity::TO_DATE              => 'required|epoch',
        Entity::FORMAT               => 'required|string|custom',
        Entity::SEND_EMAIL           => 'required|boolean',
        Entity::TO_EMAIL_LIST        => 'required_if:send_email,1|array',
        Entity::TO_EMAIL_LIST . '.*' => 'filled|email'
    ];

    protected static $fetchMissingStatementsRules = [
        Entity::CHANNEL              => 'required|string|custom',
        Entity::ACCOUNT_NUMBER       => 'required|string|between:5,40',
        Entity::FROM_DATE            => 'required|epoch',
        Entity::TO_DATE              => 'required|epoch',
        Entity::SAVE_IN_REDIS        => 'required|boolean'
    ];

    protected static $automateAccountStatementReconRules = [
        Entity::CHANNEL            => 'required|string|custom',
        Constants::ACCOUNT_NUMBERS => 'sometimes|array',
        Entity::SAVE_IN_REDIS      => 'required|boolean',
        'new_cron_setup'           => 'sometimes|boolean',
        'monitoring_cron'          => 'sometimes|boolean',
        Entity::FROM_DATE          => 'sometimes|epoch',
        Entity::TO_DATE            => 'sometimes|epoch',
        Constants::RECON_LIMIT     => 'sometimes|int',
    ];

    protected static $accountStatementGenerateValidators = [
        'channel_format'
    ];

    protected static $fetchMissingStatementsValidators = [
        'date_range'
    ];

    protected static $sourceUpdateRules = [
        'payout_id'     => 'required|unsigned_id',
        'debit_bas_id'  => 'required|unsigned_id',
        'credit_bas_id' => 'sometimes|unsigned_id',
        'end_status'    => 'required|in:processed,reversed'
    ];

    protected static $insertStatementRules = [
        Entity::CHANNEL        => 'required|string|custom',
        Entity::ACCOUNT_NUMBER => 'required|string|max:40',
        Constants::ACTION      => 'required|in:insert,fetch,dry_run'
    ];

    protected static $detectMissingStatementsRules = [
        Entity::CHANNEL                         => 'required|string|custom',
        Constants::ACCOUNT_NUMBERS              => 'required|array',
        Constants::ACCOUNT_NUMBERS . '*'        => 'required|string|between:5,40',
        Constants::SUSPECTED_MISMATCH_TIMESTAMP => 'sometimes|epoch'
    ];

    protected static $cleanUpConfigRules = [
        Entity::CHANNEL        => 'required|string|custom',
        Entity::ACCOUNT_NUMBER => 'required|string|max:40',
        Entity::FROM_DATE      => 'required|epoch',
        Entity::TO_DATE        => 'required|epoch',
    ];

    public function validateCreditBas($current_status, array $input)
    {
        // note current_status is status of payout currently
        // end_status is something which is expected after manual linking

        $end_status = array_pull($input, 'end_status');

        // state machine doesn't allow reversed to reversed or processed to processed , so skipping that
        if (($current_status !== $end_status))
        {
            // state machine doesn't allow reversed to failed, so skipping that
            if (!(
                    ($end_status === Status::REVERSED) and
                    ($current_status === Status::FAILED)
                 ))
            {
                Status::validateStatusUpdate($end_status, $current_status);
            }
        }

        if ($end_status === Status::REVERSED)
        {
            if (isset($input['credit_bas_id']) === false)
            {
                throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_CREDIT_BAS_ID_MISSING,
                                                               null,
                                                               []);
            }
        }
    }

    protected function validateChannelFormat($input)
    {
        $channel = array_pull($input, Entity::CHANNEL);

        $format = array_pull($input, Entity::FORMAT);

        if ((empty($channel) === false) and
            (empty($format) === false))
        {
            SupportedFormats::validateChannelFormat($channel, $format);
        }
    }

    protected function validateFormat($attribute, $format)
    {
        SupportedFormats::validateFormat($format);
    }

    protected function validateChannel($attribute, $channel)
    {
        Channel::validate($channel);
    }

    protected function validateType($attribute, $type)
    {
        Type::validate($type);
    }

    protected function validateCategory($attribute, $category)
    {
        Category::validate($category);
    }

    protected function validateDateRange($input)
    {
        if (($input[Entity::FROM_DATE] > $input[Entity::TO_DATE]) === true)
        {
            throw new BadRequestValidationFailureException('Given date range is invalid.');
        }

        $secondsPerDay = Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;

        // we are allowing for a date range of 7 days
        $allowedDateDiff = 7 * $secondsPerDay;

        if (($input[Entity::TO_DATE] - $input[Entity::FROM_DATE]) > $allowedDateDiff)
        {
            throw new BadRequestValidationFailureException('Given date range exceeds the threshold of 7 days.');
        }
    }
}
