<?php

namespace Models\Terminal;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_ID                 => 'required|alpha_num|size:14',
        Entity::GATEWAY                     => 'required',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes',
        Entity::CATEGORY                    => 'sometimes|integer|digits:4',
        Entity::CARD                        => 'sometimes|boolean',
        Entity::NETBANKING                  => 'sometimes|boolean',
        Entity::SHARED                      => 'sometimes|boolean',
    );

    protected static $createValidators = array(
        Entity::GATEWAY);

    protected static $hdfcTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:hdfc',
        Entity::GATEWAY_MERCHANT_ID         => 'required|integer|digits:5',
        Entity::GATEWAY_TERMINAL_ID         => 'required|integer|digits:8',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|integer|digits:8'
    );

    protected static $billdeskTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:billdesk',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|min:2'
    );

    protected static $axisGeniusTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:axis_genius',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|size:15',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE         => 'required|alhpa_num|size:8',
    );

    protected static $axisMigsTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:axis_migs',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|min:8',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE         => 'required|alpha_num|size:8',
        Entity::GATEWAY_TERMINAL_ID         => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required',
    );

    protected static $axisMigsEditTerminalRules = array(
        Entity::GATEWAY                     => 'sometimes|in:axis_migs',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::CARD                        => 'sometimes|boolean|in:1',
    );

    protected function validateGateway($input)
    {
        if (Payment\Gateway::isValidGateway($input['gateway']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid gateway: ' . $input['gateway'],
                Entity::GATEWAY);
        }

        unset(
            $input['card'],
            $input['shared'],
            $input['netbanking'],
            $input['merchant_id'],
            $input['category']);

        $op = $input['gateway'] . '_terminal';

        $var = $this->getRulesVariableName($op);

        if (property_exists(__CLASS__, $var))
        {
            $this->validateInput($op, $input);
        }
    }

    public function validateExistingTerminalsCount($existingTerminals)
    {
        $count = $existingTerminals->count();

        // Check count does not exceed max terminals count
        if ($count > Entity::MAX_TERMINALS_COUNT)
        {
            throw new Exception\LogicException(
                'Terminal count should not exceed max count');
        }
        else if ($count === Entity::MAX_TERMINALS_COUNT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_TERMINAL_MAX_LIMIT_REACHED);
        }
        else if ($count >= 1)
        {
            foreach ($existingTerminals as $existing)
            {
                $this->matchGatewayForNewTerminal($this->entity, $existing);
            }
        }
    }

    protected function matchGatewayForNewTerminal($new, $existing)
    {
        // If 1 exists, then another should not be added for the same gateway
        if (($new->getGateway() === $existing->getGateway()) and
            ($new->getId() !== $existing->getId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY);
        }
    }

    public function usedTerminalValidator($terminal, $input)
    {
        if ($terminal->getGateway() === Payment\Gateway::AXIS_MIGS)
        {
            $this->validateInput('axis_migs_edit_terminal', $input);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'Editing not defined for used terminal of gateway: ' . $terminal->getGateway());
        }
    }
}
