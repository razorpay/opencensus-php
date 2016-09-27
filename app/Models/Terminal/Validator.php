<?php

namespace RZP\Models\Terminal;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_ID                 => 'required|alpha_num|size:14',
        Entity::GATEWAY                     => 'required',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes',
        Entity::GATEWAY_RECON_PASSWORD      => 'sometimes|alpha_num',
        Entity::CATEGORY                    => 'sometimes|integer|digits:4',
        Entity::CARD                        => 'sometimes|boolean',
        Entity::NETBANKING                  => 'sometimes|boolean',
        Entity::EMI                         => 'sometimes|boolean',
        Entity::UPI                         => 'sometimes|boolean',
        Entity::EMI_DURATION                => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::SHARED                      => 'sometimes|boolean',
        Entity::GATEWAY_ACQUIRER            => 'sometimes|string|max:30',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
    );

    protected static $editTerminalGateways = array(
        Payment\Gateway::HDFC,
        Payment\Gateway::AXIS_MIGS,
        Payment\Gateway::UPI_ICICI,
    );

    protected static $createValidators = array(
        Entity::GATEWAY, Entity::EMI, Entity::NETWORK_CATEGORY);

    protected static $hdfcTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:hdfc',
        Entity::GATEWAY_MERCHANT_ID         => 'required|integer|digits_between:5,8',
        Entity::GATEWAY_TERMINAL_ID         => 'required|integer|digits:8',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string|max:15',
        Entity::EMI                         => 'sometimes|boolean',
        Entity::EMI_DURATION                => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::GATEWAY_RECON_PASSWORD      => 'sometimes|alpha_num',
    );

    protected static $billdeskTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:billdesk',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|min:2',
    );

    protected static $ebsTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:ebs',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|max:5',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|max:32',
    );

    protected static $axisGeniusTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:axis_genius',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|size:15',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE         => 'required|alhpa_num|size:8',
    );

    protected static $amexTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:amex',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|min:8',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE         => 'required|alpha_num|size:8',
        Entity::GATEWAY_TERMINAL_ID         => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required',
        Entity::EMI                         => 'sometimes|boolean',
        Entity::EMI_DURATION                => 'required_only_if:emi,1|integer|in:3,6,9,12',
    );

    protected static $axisMigsTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:axis_migs',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|min:8',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE         => 'required|alpha_num|size:8',
        Entity::GATEWAY_TERMINAL_ID         => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required',
    );

    protected static $cybersourceTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:cybersource',
        Entity::GATEWAY_TERMINAL_ID         => 'required|string|size:13',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string|min:50',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|max:20',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::GATEWAY_ACQUIRER            => 'required|string',
    );

    protected static $axisMigsEditTerminalRules = array(
        Entity::GATEWAY                     => 'sometimes|in:axis_migs',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::CARD                        => 'sometimes|boolean|in:1',
    );

    protected static $hdfcEditTerminalRules = array(
        Entity::GATEWAY_RECON_PASSWORD      => 'sometimes|alpha_num',
        Entity::GATEWAY                     => 'sometimes|in:hdfc',
        Entity::CARD                        => 'sometimes|boolean|in:1',
    );

    protected static $upiIciciEditTerminalRules = array(
        Entity::GATEWAY                     => 'sometimes|in:upi_icici',
        Entity::UPI                         => 'sometimes|boolean|in:1',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
    );

    protected static $walletPayzappTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:wallet_payzapp',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|size:21',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|integer|digits:8',
        Entity::GATEWAY_TERMINAL_ID         => 'required|integer|digits:8',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string|size:21',
        Entity::GATEWAY_ACCESS_CODE         => 'required|integer|digits:4',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|alpha_num|size:16',
    );

    protected static $walletPayumoneyTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:wallet_payumoney',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|string',
    );

    protected static $walletOlamoneyTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:wallet_olamoney',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|string',
    );

    protected static $walletAirtelmoneyTerminalRules = array(
        Entity::GATEWAY                     => 'required|in:wallet_airtelmoney',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
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
            $input['category'],
            $input[Entity::NETWORK_CATEGORY]);

        $op = $input['gateway'] . '_terminal';

        $var = $this->getRulesVariableName($op);

        if (property_exists(__CLASS__, $var))
        {
            $this->validateInput($op, $input);
        }
    }

    protected function validateEmi($input)
    {
        if (isset($input[Entity::EMI]) === false)
        {
            return;
        }

        if ($input[Entity::MERCHANT_ID] != Merchant\Account::SHARED_ACCOUNT)
        {
            throw new Exception\LogicException(
                'EMI Terminals can only be added to shared merchant account');
        }

        if (!isset($input[Entity::SHARED]) or ($input[Entity::SHARED] !== '1'))
        {
            throw new Exception\LogicException(
                'EMI Terminals must be shared terminals');
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

    /**
     * Does not use custom validator as the other parameters of input are required
     * to decide validity.
     *
     * @param array $input
     * @return void
     * */
    public function validateNetworkCategory($input)
    {
        if (empty($input[Entity::NETWORK_CATEGORY]) === true)
        {
            return;
        }

        if (Category::isNetworkCategoryValid($input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Category provided invalid for gateway',
                Entity::NETWORK_CATEGORY);
            }
    }

    protected function matchGatewayForNewTerminal($new, $existing)
    {
        // If 1 exists, then another should not be added for the same gateway for same emi periods
        if (($new->getGateway() === $existing->getGateway()) and
            ($new->getId() !== $existing->getId()) and
            ($new->isEmiEnabled() === $existing->isEmiEnabled()) and
            ($new->getEmiDuration() === $existing->getEmiDuration()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY);
        }
    }

    public function usedTerminalValidator($terminal, $input)
    {
        if (in_array($terminal->getGateway(), self::$editTerminalGateways))
        {
            $gateway = $terminal->getGateway();
            $this->validateInput($gateway.'_edit_terminal', $input);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'Editing not defined for used terminal of gateway: ' . $terminal->getGateway());
        }
    }
}
