<?php

namespace RZP\Models\Merchant\Balance\BalanceConfig;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Balance;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::BALANCE_ID                          => 'required|string|unique:balance_config',

        Entity::NEGATIVE_LIMIT_AUTO                => 'required|integer|between:'.Entity::DEFAULT_MAX_NEGATIVE.','
                                                        .Entity::CUSTOM_MAX_NEGATIVE,

        Entity::NEGATIVE_LIMIT_MANUAL              => 'integer',

        Entity::TYPE                                => 'required|string|custom',
        Entity::NEGATIVE_TRANSACTION_FLOWS         => 'filled|array',
    ];

    protected static $editRules = [
        Entity::NEGATIVE_LIMIT_AUTO                => 'integer|between:'.Entity::DEFAULT_MAX_NEGATIVE.','
                                                        .Entity::CUSTOM_MAX_NEGATIVE,

        Entity::NEGATIVE_LIMIT_MANUAL              => 'integer',

        Entity::TYPE                                => 'required|string|custom',
        Entity::NEGATIVE_TRANSACTION_FLOWS         => 'filled|array',
    ];

    /**
     * Validate create Api input after preprocessing.
     * Format of input:
     * {
     *   'balance_id' : <>,
     *   'type' : <balance_type>,
     *   'negative_limit' : <limit>,
     *   'negative_transaction_flows' : [<flows,..>]
     * }
     *
     * @param  array $input
     * @throws BadRequestValidationFailureException
     */
    public function validateCreateBalanceConfig(array $input)
    {
        $this->validateInput('create', $input);

        $this->validateManualNegativeLimit($input);

        if (array_key_exists(Entity::NEGATIVE_TRANSACTION_FLOWS, $input) === true)
        {
            $flows = $input[Entity::NEGATIVE_TRANSACTION_FLOWS];

            $type = $input[Entity::TYPE];

            $this->validateNegativeTransactionFlows($flows, $type);
        }
    }

    /**
     * Validate edit Api input after preprocessing.
     * Format of input:
     * {
     *   'type' : <balance_type>,
     *   'negative_limit' : <limit>,
     *   'negative_transaction_flows' : [<flows,..>]
     * }
     *
     * @param  array  $input
     * @param  Entity $balanceConfig
     * @throws BadRequestValidationFailureException
     */
    public function validateEditBalanceConfig(array $input, Entity $balanceConfig)
    {
        $this->validateManualNegativeLimit($input);

        $type = (array_key_exists(Entity::TYPE, $input) === true) ? $input[Entity::TYPE] : $balanceConfig->getType();

        //If the edit input contains type, and the type is different from existing balance_config type,
        // throw BadRequest Exception.
        if ($balanceConfig->getType() !== $type)
        {
            throw new BadRequestValidationFailureException(
                'Cannot change balance type to ['.$type.'] of existing balance config with'.
                ' balance type [' .$balanceConfig->getType().']'
            );
        }

        $this->validateInput('edit', $input);

        if (array_key_exists(Entity::NEGATIVE_TRANSACTION_FLOWS, $input) === true)
        {
            $flows = $input[Entity::NEGATIVE_TRANSACTION_FLOWS];

            $this->validateNegativeTransactionFlows($flows, $type);
        }
    }

    /**
     * @param  $attribute
     * @param  $type
     * @throws BadRequestValidationFailureException
     */
    protected function validateType($attribute, $type)
    {
        if (Balance\Type::exists($type) === false)
        {
            throw new BadRequestValidationFailureException('Invalid type name: ' . $type);
        }
    }

    /**
     * @param  array $inputFlows
     * @param  $balanceType
     * @throws BadRequestValidationFailureException
     */
    protected function validateNegativeTransactionFlows(array $inputFlows, $balanceType)
    {
        if (($inputFlows === null) or
            (empty($inputFlows)))
        {
            return;
        }

        // $inputFlows are input transaction flows
        // intersect with the allowed flows for $balanceType
        $allowedFlows = array_intersect(Balance\Core::NEGATIVE_FLOWS[$balanceType], $inputFlows);

        // check if $validFlows is equal to input $inputFlows
        $areValidFlows = count($allowedFlows) == count($inputFlows);

        if ($areValidFlows === false)
        {
            throw new BadRequestValidationFailureException(
                'Negative Flow ['.implode(',', $inputFlows).'] is not in the allowed flows list.'.
                ' Allowed flows for balance type '.$balanceType.' are [' .implode(',',
                    Balance\Core::NEGATIVE_FLOWS[$balanceType]). ']'
            );
        }
    }

    /**
     * @param array $input
     * @throws BadRequestValidationFailureException
     */
    private function validateManualNegativeLimit(array $input)
    {
        if ((isset($input[Entity::NEGATIVE_LIMIT_AUTO]) === true) and
            (isset($input[Entity::NEGATIVE_LIMIT_MANUAL]) === true) and
            ($input[Entity::NEGATIVE_LIMIT_AUTO] < $input[Entity::NEGATIVE_LIMIT_MANUAL]))
        {
            throw new BadRequestValidationFailureException(
                Entity::NEGATIVE_LIMIT_MANUAL . ' can not be greater than '. Entity::NEGATIVE_LIMIT_AUTO,
                [
                    Entity::NEGATIVE_LIMIT_AUTO     => $input[Entity::NEGATIVE_LIMIT_AUTO],
                    Entity::NEGATIVE_LIMIT_MANUAL   => $input[Entity::NEGATIVE_LIMIT_MANUAL]
                ]);
        }

        if ((isset($input[Entity::NEGATIVE_LIMIT_MANUAL]) === true) and
            ($input[Entity::NEGATIVE_LIMIT_MANUAL] > Entity::CUSTOM_MAX_NEGATIVE))
        {
            throw new BadRequestValidationFailureException(
                'The negative limit manual must be less than or equal to '
                .Entity::CUSTOM_MAX_NEGATIVE,
                [
                    Entity::NEGATIVE_LIMIT_MANUAL   => $input[Entity::NEGATIVE_LIMIT_MANUAL]
                ]);
        }
    }
}
