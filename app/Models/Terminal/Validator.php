<?php

namespace RZP\Models\Terminal;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Currency\Currency;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;

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
        Entity::AEPS                        => 'sometimes|boolean',
        Entity::EMI_DURATION                => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::TYPE                        => 'sometimes|array',
        Entity::MODE                        => 'sometimes|in:1,2,3',
        Entity::INTERNATIONAL               => 'sometimes|boolean',
        Entity::TPV                         => 'sometimes_if:netbanking,1|in:0,1,2',
        Entity::CORPORATE                   => 'sometimes_if:netbanking,1|boolean',
        Entity::EMI_SUBVENTION              => 'sometimes|in:customer,merchant',
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
        Payment\Gateway::FIRST_DATA,
        Payment\Gateway::NETBANKING_INDUSIND,
    ];

    protected static $createValidators = [
        Entity::GATEWAY,
        Entity::EMI,
        Entity::NETWORK_CATEGORY,
        Entity::CURRENCY,
        Entity::GATEWAY_ACQUIRER,
        Entity::MODE,
    ];

    protected static $reassignRules = [
        Entity::MERCHANT_ID                => 'required|alpha_num|size:14',
    ];

    protected static $hdfcTerminalRules = [
        Entity::GATEWAY                    => 'required|in:hdfc',
        Entity::GATEWAY_MERCHANT_ID        => 'required|integer|digits_between:5,8',
        Entity::GATEWAY_TERMINAL_ID        => 'required|integer|digits:8',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string|max:15',
        Entity::EMI                        => 'sometimes|boolean',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::EMI_DURATION               => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
        Entity::EMI_SUBVENTION             => 'sometimes|in:customer,merchant',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CURRENCY                   => 'sometimes|alpha|size:3',
    ];

    protected static $aepsIciciTerminalRules = [
        Entity::GATEWAY                    => 'required|in:aeps_icici',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num',
    ];

    protected static $billdeskTerminalRules = [
        Entity::GATEWAY                    => 'required|in:billdesk',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|min:2',
    ];

    protected static $ebsTerminalRules = [
        Entity::GATEWAY                    => 'required|in:ebs',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|max:5',
        Entity::GATEWAY_SECURE_SECRET      => 'required|alpha_num|max:32',
    ];

    protected static $axisGeniusTerminalRules = [
        Entity::GATEWAY                    => 'required|in:axis_genius',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|size:15',
        Entity::GATEWAY_SECURE_SECRET      => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE        => 'required|alhpa_num|size:8',
    ];

    protected static $firstDataTerminalRules = [
        Entity::GATEWAY                    => 'required|in:first_data',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|min:5',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string|min:5',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string|min:5',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string|min:5',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string|min:5',
        Entity::GATEWAY_CLIENT_CERTIFICATE => 'sometimes|min:20',
        Entity::TYPE                       => 'sometimes|array',
        Entity::MODE                       => 'sometimes|integer|in:2,3',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::EMI                        => 'sometimes|boolean',
        Entity::EMI_DURATION               => 'required_only_if:emi,1|integer|in:3,6,9,12',
        Entity::EMI_SUBVENTION             => 'sometimes|in:customer,merchant',
        Entity::CURRENCY                   => 'sometimes|alpha|size:3',
    ];

    protected static $amexTerminalRules = [
        Entity::GATEWAY                    => 'required|in:amex',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|min:8',
        Entity::GATEWAY_SECURE_SECRET      => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE        => 'required|alpha_num|size:8',
        Entity::GATEWAY_TERMINAL_ID        => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required',
        Entity::EMI                        => 'sometimes|boolean',
        Entity::EMI_DURATION               => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::EMI_SUBVENTION             => 'sometimes|in:customer,merchant',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
    ];

    protected static $axisMigsTerminalRules = [
        Entity::GATEWAY                    => 'required|in:axis_migs',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|min:6',
        Entity::GATEWAY_SECURE_SECRET      => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE        => 'required|alpha_num|size:8',
        Entity::GATEWAY_TERMINAL_ID        => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
    ];

    protected static $cybersourceTerminalRules = [
        Entity::GATEWAY                    => 'required|in:cybersource',
        Entity::GATEWAY_TERMINAL_ID        => 'required|string|min:10',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string|min:50',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string|max:20',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
    ];

    protected static $axisMigsEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:axis_migs',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
    ];

    protected static $billdeskEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:billdesk',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
    ];

    protected static $hdfcEditTerminalRules = [
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
        Entity::GATEWAY                    => 'sometimes|in:hdfc',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
    ];

    protected static $firstDataEditTerminalRules = [
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
    ];

    protected static $cybersourceEditTerminalRules = [
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY                    => 'sometimes|in:cybersource',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
    ];

    protected static $upiIciciEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_icici',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
    ];

    protected static $netbankingIciciEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID2    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|alpha_num|size:16',
    ];

    protected static $netbankingIndusindEditTerminalRules = [
        Entity::TPV                     => 'sometimes|in:0,1,2'
    ];

    protected static $walletPayzappTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_payzapp',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string|size:21',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|integer|digits:8',
        Entity::GATEWAY_TERMINAL_ID        => 'required|integer|digits:8',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string|size:21',
        Entity::GATEWAY_ACCESS_CODE        => 'required|integer|digits:4',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|alpha_num|size:16',
    ];

    protected static $walletPayumoneyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_payumoney',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
    ];

    protected static $walletOlamoneyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_olamoney',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
    ];

    protected static $walletAirtelmoneyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_airtelmoney',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
    ];

    protected static $walletFreechargeTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_freecharge',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes',
    ];

    protected static $netbankingIciciTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_icici',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2    => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|alpha_num|size:16'
    ];

    protected static $walletJiomoneyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_jiomoney',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
    ];

    protected static $walletSbibuddyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_sbibuddy',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
    ];

    protected static $walletMpesaTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_mpesa',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
    ];

    protected static $upiMindgateTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_mindgate',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
    ];

    protected static $netbankingAirtelTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_airtel',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
    ];

    protected static $netbankingAxisTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_axis',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        // The below fields are used only for Emandate terminals, hence "sometimes"
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
    ];

    protected static $netbankingFederalTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_federal',
    ];

    protected static $netbankingRblTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_rbl',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
    ];

    protected static $netbankingIndusindTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_indusind',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
    ];

    protected static $netbankingPnbTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_pnb',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
    ];

    protected static $netbankingCorporationTerminalRules = [
        Entity::GATEWAY                     => 'required|in:netbanking_corporation',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|size:3',
        Entity::GATEWAY_SECURE_SECRET       => 'required|alpha_num|max:32',
    ];

    protected function validateGateway($input)
    {
        Payment\Gateway::validateGateway($input['gateway']);

        unset(
            $input[Entity::TPV],
            $input[Entity::CARD],
            $input[Entity::SHARED],
            $input[Entity::CATEGORY],
            $input[Entity::CORPORATE],
            $input[Entity::NETBANKING],
            $input[Entity::MERCHANT_ID],
            $input[Entity::NETWORK_CATEGORY],
            $input[Entity::GATEWAY_ACQUIRER],
            $input[Entity::MODE]);

        $op = $input['gateway'] . '_terminal';

        $var = $this->getRulesVariableName($op);

        if (property_exists(__CLASS__, $var))
        {
            $this->validateInput($op, $input);
        }
    }

    protected function validateMode($input)
    {
        // Adding this for backward compatibility
        // Will remove when dashboard starts sending both fields
        // Tests will also need to be updated
        if ((isset($input[Entity::MODE]) === false) or
            (isset($input[Entity::TYPE]) === false))
        {
            return;
        }

        $gateway = $input[Entity::GATEWAY];

        $type = $input[Entity::TYPE];

        $mode = (int) $input[Entity::MODE];

        // FirstData N3DS terminals are always in purchase mode
        //
        $isFirstDataNon3DS = (($gateway === Gateway::FIRST_DATA) and
                              (Type::isApplicableType($type, Type::RECURRING_NON_3DS)));

        // Most non-card gateways have terminals only in purchase mode
        //
        // Exceptions are Sharp (which is a test gateway),
        // OpenWallet (which is a mock gateway), and Atom.
        $nonCardPurchaseExceptions = [
            Gateway::SHARP,
            Gateway::ATOM,
            Gateway::WALLET_OPENWALLET
        ];

        $isNonCardNonMockGateway = ((Gateway::isMethodSupported(Payment\Method::CARD, $gateway)) and
                                    (in_array($gateway, $nonCardPurchaseExceptions, true)));

        // Migs, Amex, and OpenWallet terminals are always in auth-capture mode
        //
        $authCaptureOnly = [
            Gateway::AXIS_MIGS,
            Gateway::AMEX,
            Gateway::WALLET_OPENWALLET
        ];

        $isAuthCaptureOnlyGateway = (in_array($gateway, $authCaptureOnly, true));

        if ((($isFirstDataNon3DS === true) or ($isNonCardNonMockGateway === true)) and
            ($mode !== Mode::PURCHASE))
        {
            throw new Exception\BadRequestValidationFailureException(
                'FirstData Non-3DS terminals must be in Purchase mode',
                Entity::GATEWAY);
        }
        else if (($isAuthCaptureOnlyGateway === true) and
                 ($mode !== Mode::AUTH_CAPTURE))
        {
            throw new Exception\BadRequestValidationFailureException(
                $input['gateway'] . ' terminals must be in AuthCapture mode',
                Entity::GATEWAY);
        }
        else if (($isFirstDataNon3DS === false) and
                 ($isNonCardNonMockGateway === false) and
                 ($isAuthCaptureOnlyGateway === false) and
                 ($mode !== Mode::DUAL))
        {
            throw new Exception\BadRequestValidationFailureException(
                $input['gateway'] . ' terminals must be in Dual mode',
                Entity::GATEWAY);
        }
    }

    protected function validateEmi($input)
    {
        if (!isset($input[Entity::EMI]) or ($input[Entity::EMI] !== '1'))
        {
            return;
        }

        if ($input[Entity::MERCHANT_ID] !== Merchant\Account::SHARED_ACCOUNT)
        {
            throw new Exception\LogicException(
                'EMI Terminals can only be added to shared merchant account',
                null,
                [
                    'input' => $input
                ]);
        }

        if ((isset($input[Entity::MERCHANT_ID]) === false) or
            ($input[Entity::MERCHANT_ID] !== Merchant\Account::SHARED_ACCOUNT))
        {
            throw new Exception\LogicException(
                'EMI Terminals must be shared terminals',
                null,
                [
                    'input' => $input
                ]);
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

    protected function validateGatewayAcquirer(array $input)
    {
        $gateway = $input[Entity::GATEWAY];

        // Don't validate acquirer if it's not a gateway which needs acquirer
        if (isset(Payment\Gateway::GATEWAY_ACQUIRERS[$gateway]) === false)
        {
            return;
        }

        // Gateway acquirer is required
        if (empty($input[Entity::GATEWAY_ACQUIRER]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway acquirer is required for ' . $gateway);
        }

        $gatewayAcquirer = $input[Entity::GATEWAY_ACQUIRER];

        $validGatewayAcquirers = Payment\Gateway::GATEWAY_ACQUIRERS[$gateway];

        if (in_array($gatewayAcquirer, $validGatewayAcquirers, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $gatewayAcquirer . ' is not a valid acquirer for ' . $gateway);
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
                'Terminal count should not exceed max count',
                null,
                [
                    'count'                 => $count,
                    'max'                   => Entity::MAX_TERMINALS_COUNT,
                    'terminal_id'           => $newTerminal->getId(),
                    'terminal_merchant_id'  => $newTerminal->getMerchantId(),
                ]);
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

        $networkCategory = $input[Entity::NETWORK_CATEGORY];

        $method = self::getMethod($input);

        $gateway = $input[Entity::GATEWAY];

        if (Category::isNetworkCategoryValid($networkCategory, $method, $gateway) === false)
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
            ($new->getType() === $existing->getType()) and
            ($new->getCurrency() === $existing->getCurrency()) and
            ($new->getNetworkCategory() === $existing->getNetworkCategory()) and
            ($new->getEmiSubvention() === $existing->getEmiSubvention()))
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

    protected static function getMethod($input)
    {
        if (empty($input[Entity::CARD]) === false)
        {
            return Method::CARD;
        }

        if (empty($input[Entity::NETBANKING]) === false)
        {
            return Method::NETBANKING;
        }

        if (empty($input[Entity::EMI]) === false)
        {
            return Method::EMI;
        }

        return null;
    }
}
