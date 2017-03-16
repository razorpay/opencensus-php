<?php

namespace RZP\Models\Terminal;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Currency\Currency;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID                 => 'required|alpha_num|size:14',
        Entity::GATEWAY                     => 'required',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes',
        Entity::GATEWAY_RECON_PASSWORD      => 'sometimes|alpha_num',
        Entity::GATEWAY_CLIENT_CERTIFICATE  => 'sometimes',
        Entity::CATEGORY                    => 'sometimes|integer|digits:4',
        Entity::CARD                        => 'sometimes|boolean',
        Entity::NETBANKING                  => 'sometimes|boolean',
        Entity::EMI                         => 'sometimes|boolean',
        Entity::UPI                         => 'sometimes|boolean',
        Entity::EMI_DURATION                => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::SHARED                      => 'sometimes|boolean',
        Entity::RECURRING                   => 'sometimes|in:0,2',
        Entity::TPV                         => 'sometimes_if:netbanking,1|boolean',
        Entity::GATEWAY_ACQUIRER            => 'sometimes|string|max:30',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::CURRENCY                    => 'sometimes|alpha|size:3',
    ];

    protected static $editTerminalGateways = [
        Payment\Gateway::HDFC,
        Payment\Gateway::CYBERSOURCE,
        Payment\Gateway::AXIS_MIGS,
        Payment\Gateway::UPI_ICICI,
        Payment\Gateway::BILLDESK,
    ];

    protected static $createValidators = [
        Entity::GATEWAY, Entity::EMI, Entity::NETWORK_CATEGORY, Entity::CURRENCY,
    ];

    protected static $reassignRules = [
        Entity::MERCHANT_ID                 => 'required|alpha_num|size:14',
    ];

    protected static $hdfcTerminalRules = [
        Entity::GATEWAY                     => 'required|in:hdfc',
        Entity::GATEWAY_MERCHANT_ID         => 'required|integer|digits_between:5,8',
        Entity::GATEWAY_TERMINAL_ID         => 'required|integer|digits:8',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string|max:15',
        Entity::EMI                         => 'sometimes|boolean',
        Entity::EMI_DURATION                => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::GATEWAY_RECON_PASSWORD      => 'sometimes|alpha_num',
    ];

    protected static $billdeskTerminalRules = [
        Entity::GATEWAY                     => 'required|in:billdesk',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|min:2',
    ];

    protected static $ebsTerminalRules = [
        Entity::GATEWAY                     => 'required|in:ebs',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|max:5',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|max:32',
    ];

    protected static $axisGeniusTerminalRules = [
        Entity::GATEWAY                     => 'required|in:axis_genius',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|size:15',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE         => 'required|alhpa_num|size:8',
    ];

    protected static $firstDataTerminalRules = [
        Entity::GATEWAY                     => 'required|in:first_data',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|min:5',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string|min:5',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string|min:5',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes|string|min:5',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string|min:5',
        Entity::GATEWAY_CLIENT_CERTIFICATE  => 'sometimes|min:20',
        Entity::EMI                         => 'sometimes|boolean',
        Entity::EMI_DURATION                => 'required_only_if:emi,1|integer|in:3,6,9,12',
        Entity::CURRENCY                    => 'sometimes|alpha|size:3'
    ];

    protected static $amexTerminalRules = [
        Entity::GATEWAY                     => 'required|in:amex',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|min:8',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE         => 'required|alpha_num|size:8',
        Entity::GATEWAY_TERMINAL_ID         => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required',
        Entity::EMI                         => 'sometimes|boolean',
        Entity::EMI_DURATION                => 'required_only_if:emi,1|integer|in:3,6,9,12',
    ];

    protected static $axisMigsTerminalRules = [
        Entity::GATEWAY                     => 'required|in:axis_migs',
        Entity::GATEWAY_MERCHANT_ID         => 'required|alpha_num|min:6',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE         => 'required|alpha_num|size:8',
        Entity::GATEWAY_TERMINAL_ID         => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required',
    ];

    protected static $cybersourceTerminalRules = [
        Entity::GATEWAY                     => 'required|in:cybersource',
        Entity::GATEWAY_TERMINAL_ID         => 'required|string|min:10',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string|min:50',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|max:20',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::GATEWAY_ACQUIRER            => 'required|string',
        Entity::RECURRING                   => 'sometimes|in:0,1,2',
        Entity::GATEWAY_RECON_PASSWORD      => 'sometimes|alpha_num',
    ];

    protected static $axisMigsEditTerminalRules = [
        Entity::GATEWAY                     => 'sometimes|in:axis_migs',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::CARD                        => 'sometimes|boolean|in:1',
    ];

    protected static $billdeskEditTerminalRules = [
        Entity::GATEWAY                     => 'sometimes|in:billdesk',
        Entity::TPV                         => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
    ];

    protected static $hdfcEditTerminalRules = [
        Entity::GATEWAY_RECON_PASSWORD      => 'sometimes|alpha_num',
        Entity::GATEWAY                     => 'sometimes|in:hdfc',
        Entity::CARD                        => 'sometimes|boolean|in:1',
    ];

    protected static $cybersourceEditTerminalRules = [
        Entity::GATEWAY_RECON_PASSWORD => 'sometimes|alpha_num',
        Entity::GATEWAY                => 'sometimes|in:cybersource',
        Entity::CARD                   => 'sometimes|boolean|in:1',
    ];

    protected static $upiIciciEditTerminalRules = [
        Entity::GATEWAY                     => 'sometimes|in:upi_icici',
        Entity::UPI                         => 'sometimes|boolean|in:1',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
    ];

    protected static $walletPayzappTerminalRules = [
        Entity::GATEWAY                     => 'required|in:wallet_payzapp',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|size:21',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|integer|digits:8',
        Entity::GATEWAY_TERMINAL_ID         => 'required|integer|digits:8',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string|size:21',
        Entity::GATEWAY_ACCESS_CODE         => 'required|integer|digits:4',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|alpha_num|size:16',
    ];

    protected static $walletPayumoneyTerminalRules = [
        Entity::GATEWAY                     => 'required|in:wallet_payumoney',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|string',
    ];

    protected static $walletOlamoneyTerminalRules = [
        Entity::GATEWAY                     => 'required|in:wallet_olamoney',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
    ];

    protected static $walletAirtelmoneyTerminalRules = [
        Entity::GATEWAY                     => 'required|in:wallet_airtelmoney',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
    ];

    protected static $walletFreechargeTerminalRules = [
        Entity::GATEWAY                     => 'required|in:wallet_freecharge',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
    ];

    protected static $netbankingIciciTerminalRules = [
        Entity::GATEWAY               => 'required|in:netbanking_icici',
        Entity::GATEWAY_MERCHANT_ID2  => 'sometimes|string',
    ];

    protected static $walletJiomoneyTerminalRules = [
        Entity::GATEWAY                   => 'required|in:wallet_jiomoney',
        Entity::GATEWAY_MERCHANT_ID       => 'required|string',
        Entity::GATEWAY_ACCESS_CODE       => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD => 'required|string',
    ];

    protected static $netbankingAirtelTerminalRules = [
        Entity::GATEWAY                     => 'required|in:netbanking_airtel',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
    ];

    protected static $netbankingAxisTerminalRules = [
        Entity::GATEWAY                     => 'required|in:netbanking_axis',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string'
    ];

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
            $input['tpv'],
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
        if (!isset($input[Entity::EMI]) or ($input[Entity::EMI] !== '1'))
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

    protected function validateCurrency($input)
    {
        if ((isset($input['currency']) === true) and
            in_array($input['currency'], Currency::SUPPORTED_CURRENCIES, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED);
        }
    }

    public function validateExistingTerminalsCount($existingTerminals)
    {
        $newTerminal = $this->entity;

        $count = $existingTerminals->count();

        // Check count does not exceed max terminals count
        if (($newTerminal->getMerchantId() !== Merchant\Account::SHARED_ACCOUNT) and
            ($count > Entity::MAX_TERMINALS_COUNT))
        {
            throw new Exception\LogicException(
                'Terminal count should not exceed max count');
        }
        else if (($newTerminal->getMerchantId() !== Merchant\Account::SHARED_ACCOUNT) and
                 ($count === Entity::MAX_TERMINALS_COUNT))
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
     *
     * @throws Exception\BadRequestValidationFailureException
     */
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
                Entity::NETWORK_CATEGORY,
                [$input[Entity::NETWORK_CATEGORY]]);
        }
    }

    protected function matchGatewayForNewTerminal($new, $existing)
    {
        // If 1 exists, then another should not be added for the same gateway for same emi periods
        if (($new->getGateway() === $existing->getGateway()) and
            ($new->getId() !== $existing->getId()) and
            ($new->getGatewayAcquirer() === $existing->getGatewayAcquirer()) and
            ($new->isEmiEnabled() === $existing->isEmiEnabled()) and
            ($new->getEmiDuration() === $existing->getEmiDuration()) and
            ($new->getRecurring() === $existing->getRecurring()) and
            ($new->getNetworkCategory() === $existing->getNetworkCategory()))
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
            $this->validateInput($gateway . '_edit_terminal', $input);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'Editing not defined for used terminal of gateway: ' . $terminal->getGateway());
        }
    }
}
