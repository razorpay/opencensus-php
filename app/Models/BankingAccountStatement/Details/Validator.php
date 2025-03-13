<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    const PRE_FETCH_RULES = 'pre_fetch';
    const STATUS_UPDATE_RULES = 'status_update';
    const UPDATE_STATEMENT_LAST_FETCHED_DATA_INPUT = 'update_statement_last_fetched_data_input';

    protected static $createRules = [
        Entity::BALANCE_ID                          => 'required|size:14',
        Entity::MERCHANT_ID                         => 'required|size:14',
        Entity::CHANNEL                             => 'required|custom',
        Entity::ACCOUNT_NUMBER                      => 'required|string|max:40',
        Entity::STATUS                              => 'sometimes|custom',
        Entity::GATEWAY_BALANCE                     => 'sometimes|nullable|int',
        Entity::STATEMENT_CLOSING_BALANCE           => 'sometimes|nullable|int',
        Entity::ACCOUNT_TYPE                        => 'sometimes|custom'
    ];

    protected static $preFetchRules = [
        Entity::CHANNEL             => 'required|custom',
        Entity::ACCOUNT_NUMBER      => 'required|string|max:40',
    ];

    protected static $updateStatementLastFetchedDataInputRules = [
        'entity_type'                                     => 'required',
        'input'                                           => 'required|array',
        'input' . '.' . Entity::ID                        => 'required|string',
        'input' . '.' . Entity::LAST_STATEMENT_ATTEMPT_AT => 'required|epoch',
    ];

    protected static $statusUpdateRules = [
        Entity::STATUS => 'required|custom'
    ];

    protected function validateChannel($attribute, $channel)
    {
        Channel::validate($channel);
    }

    protected function validateStatus($attributes, $status)
    {
        Status::validate($status);
    }

    protected function validateAccountType($attributes, $status)
    {
        AccountType::validate($status);
    }

    public static function validateXBalanceUpdateDualWriteInput(array $input): void
    {
        $mandatoryFields = [
            Entity::BALANCE_ID,
            Entity::GATEWAY_BALANCE,
            Entity::BALANCE_LAST_FETCHED_AT
        ];

        foreach ($mandatoryFields as $field) {
            if (!array_key_exists($field, $input)) {
                throw new Exception\BadRequestValidationFailureException("The field {$field} is mandatory and must be provided.");
            }
        }

        if (!is_int($input[Entity::GATEWAY_BALANCE])) {
            throw new Exception\BadRequestValidationFailureException("The field " . Entity::GATEWAY_BALANCE . " must be an integer.");
        }

        if (!is_numeric($input[Entity::BALANCE_LAST_FETCHED_AT]) || (int)$input[Entity::BALANCE_LAST_FETCHED_AT] != $input[Entity::BALANCE_LAST_FETCHED_AT]) {
            throw new Exception\BadRequestValidationFailureException("The field " . Entity::BALANCE_LAST_FETCHED_AT . " must be a valid timestamp.");
        }

        if (!empty($input[Entity::GATEWAY_BALANCE_CHANGE_AT]) && !is_numeric($input[Entity::GATEWAY_BALANCE_CHANGE_AT]) || (int)$input[Entity::GATEWAY_BALANCE_CHANGE_AT] != $input[Entity::GATEWAY_BALANCE_CHANGE_AT]) {
            throw new Exception\BadRequestValidationFailureException("The field " . Entity::GATEWAY_BALANCE_CHANGE_AT . " must be a valid timestamp.");
        }
    }
}
