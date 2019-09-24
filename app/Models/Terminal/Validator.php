<?php

namespace RZP\Models\Terminal;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\TpvType;
use RZP\Models\Currency\Currency;
use RZP\Models\Terminal\BankingType;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Bank;
use RZP\Models\Terminal\Status;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID                 => 'required|alpha_num|size:14',
        Entity::STATUS                      => 'sometimes|string|custom',
        Entity::GATEWAY                     => 'required',
        Entity::PROCURER                    => 'sometimes|in:razorpay,merchant',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD2  => 'sometimes',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET2      => 'sometimes',
        Entity::GATEWAY_RECON_PASSWORD      => 'sometimes|alpha_num',
        Entity::GATEWAY_CLIENT_CERTIFICATE  => 'sometimes',
        Entity::MC_MPAN                     => 'sometimes|string|size:16',
        Entity::VISA_MPAN                   => 'sometimes|string|size:16',
        Entity::RUPAY_MPAN                  => 'sometimes|string|size:16',
        Entity::VPA                         => 'sometimes|string|max:255',
        Entity::CATEGORY                    => 'sometimes|string|numeric|digits:4',
        Entity::CARD                        => 'sometimes|boolean',
        Entity::NETBANKING                  => 'sometimes|boolean',
        Entity::EMANDATE                    => 'sometimes|boolean',
        Entity::EMI                         => 'sometimes|boolean',
        Entity::UPI                         => 'sometimes|boolean',
        Entity::OMNICHANNEL                 => 'sometimes|boolean',
        Entity::BANK_TRANSFER               => 'sometimes|boolean',
        Entity::AEPS                        => 'sometimes|boolean',
        Entity::EMI_DURATION                => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::TYPE                        => 'bail|sometimes|array',
        Entity::MODE                        => 'sometimes|in:1,2,3',
        Entity::TPV                         => 'sometimes|in:0,1,2',
        Entity::INTERNATIONAL               => 'sometimes|boolean',
        Entity::CORPORATE                   => 'sometimes_if:netbanking,1|in:0,1,2',
        Entity::EXPECTED                    => 'sometimes|boolean',
        Entity::EMI_SUBVENTION              => 'sometimes|in:customer,merchant',
        Entity::GATEWAY_ACQUIRER            => 'sometimes|string|max:30',
        Entity::NETWORK_CATEGORY            => 'required_if:netbanking,1|string|max:30',
        Entity::CURRENCY                    => 'sometimes|array|max:50',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::IFSC_CODE                   => 'sometimes|string|size:11',
        Entity::CARDLESS_EMI                => 'sometimes|boolean',
        Entity::PAYLATER                    => 'sometimes|boolean',
        Entity::ENABLED                     => 'sometimes|in:0,1',
        Entity::CAPABILITY                  => 'sometimes|in:0,1,2',
        Entity::NOTES                       => 'sometimes',
    ];

    protected static $editTerminalGateways = [
        Payment\Gateway::ATOM,
        Payment\Gateway::AMEX,
        Payment\Gateway::HDFC,
        Payment\Gateway::HITACHI,
        Payment\Gateway::BILLDESK,
        Payment\Gateway::CARD_FSS,
        Payment\Gateway::AXIS_MIGS,
        Payment\Gateway::UPI_HULK,
        Payment\Gateway::UPI_ICICI,
        Payment\Gateway::UPI_CITI,
        Payment\Gateway::ENACH_RBL,
        Payment\Gateway::FIRST_DATA,
        Payment\Gateway::CYBERSOURCE,
        Payment\Gateway::UPI_MINDGATE,
        Payment\Gateway::UPI_AXIS,
        Payment\Gateway::NETBANKING_CSB,
        Payment\Gateway::NETBANKING_BOB,
        Payment\Gateway::NETBANKING_ICICI,
        Payment\Gateway::NETBANKING_INDUSIND,
        Payment\Gateway::NETBANKING_EQUITAS,
        Payment\Gateway::NETBANKING_CANARA,
        Payment\Gateway::NETBANKING_VIJAYA,
        Payment\Gateway::NETBANKING_YESB,
        Payment\Gateway::NETBANKING_SIB,
        Payment\Gateway::NETBANKING_FEDERAL,
        Payment\Gateway::NETBANKING_CUB,
        Payment\Gateway::NETBANKING_SBI,
        Payment\Gateway::EMI_SBI,
        Payment\Gateway::WALLET_OLAMONEY,
        Payment\Gateway::PAYTM,
        Payment\Gateway::BAJAJFINSERV,
        Payment\Gateway::WALLET_PHONEPE,
        Payment\Gateway::WALLET_PAYPAL,
        Payment\Gateway::UPI_AIRTEL,
        Payment\Gateway::ISG,
        Payment\Gateway::PAYLATER,
        Payment\Gateway::CARDLESS_EMI,
    ];

    protected static $createValidators = [
        Entity::GATEWAY,
        Entity::EMI,
        Entity::NETWORK_CATEGORY,
        Entity::CURRENCY,
        Entity::GATEWAY_ACQUIRER,
        Entity::MODE,
        Entity::TPV,
    ];

    protected static $reassignRules = [
        Entity::MERCHANT_ID                => 'required|alpha_num|size:14',
    ];

    protected static $terminalCheckSecretRules = [
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2      => 'sometimes|string',
    ];

    protected static $upiIciciTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_icici',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::VPA                        => 'required_only_if:type.bharat_qr,1|string|max:20',
        Entity::TYPE                       => 'sometimes|array',
    ];

    protected static $upiAirtelTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_airtel',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
    ];

    protected static $upiCitiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_citi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
    ];

    protected static $atomTerminalRules = [
        Entity::GATEWAY                    => 'required|in:atom',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD2 => 'required',
    ];

    protected static $hdfcTerminalRules = [
        Entity::GATEWAY                    => 'required|in:hdfc',
        Entity::GATEWAY_MERCHANT_ID        => 'required|integer|digits_between:4,8',
        Entity::GATEWAY_TERMINAL_ID        => 'required|integer|digits:8',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string|max:15',
        Entity::EMI                        => 'sometimes|boolean',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::EMI_DURATION               => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
        Entity::EMI_SUBVENTION             => 'sometimes|in:customer,merchant',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CURRENCY                   => 'sometimes|array|max:1',
        Entity::CAPABILITY                 => 'sometimes|in:0,2',
    ];

    protected static $hitachiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:hitachi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string|max:15',
        Entity::GATEWAY_TERMINAL_ID        => 'required|string|max:8',
        Entity::TYPE                       => 'sometimes|array',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::CURRENCY                   => 'sometimes',
        Entity::MC_MPAN                    => 'required_if:type.bharat_qr,1|string|size:16',
        Entity::VISA_MPAN                  => 'required_if:type.bharat_qr,1|string|size:16',
        Entity::RUPAY_MPAN                 => 'required_if:type.bharat_qr,1|string|size:16',
        Entity::EXPECTED                   => 'sometimes_if:type.bharat_qr,1|boolean',
        Entity::ACCOUNT_NUMBER             => 'sometimes_if:type.bharat_qr,1|string|max:50',
        Entity::IFSC_CODE                  => 'sometimes_if:type.bharat_qr,1|string|size:11'
    ];

    protected static $isgTerminalRules = [
        Entity::GATEWAY                    => 'required|in:isg',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string|max:15',
        Entity::GATEWAY_TERMINAL_ID        => 'required|string|size:8',
        Entity::TYPE                       => 'required|array',
        Entity::TYPE . '.bharat_qr'        => 'required|in:1',
        Entity::TYPE . '.non_recurring'    => 'required|in:1',
        Entity::MC_MPAN                    => 'required|string|size:16',
        Entity::VISA_MPAN                  => 'required|string|size:16',
        Entity::RUPAY_MPAN                 => 'required|string|size:16',
        Entity::EXPECTED                   => 'sometimes|boolean',
        Entity::ACCOUNT_NUMBER             => 'sometimes_if:type.bharat_qr,1|string|max:50',
        Entity::IFSC_CODE                  => 'sometimes_if:type.bharat_qr,1|string|size:11'
    ];

    protected static $aepsIciciTerminalRules = [
        Entity::GATEWAY                    => 'required|in:aeps_icici',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num',
    ];

    protected static $billdeskTerminalRules = [
        Entity::GATEWAY                    => 'required|in:billdesk',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|min:2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TYPE . '.non_recurring'    => 'sometimes|in:0,1',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string'
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
        Entity::CURRENCY                   => 'sometimes|array|max:1',
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
        Entity::TYPE                       => 'sometimes|array',
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
        Entity::CAPABILITY                 => 'sometimes|in:0,2',
    ];

    protected static $cybersourceTerminalRules = [
        Entity::GATEWAY                    => 'required|in:cybersource',
        Entity::GATEWAY_TERMINAL_ID        => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
    ];

    protected static $emiSbiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:' . Gateway::EMI_SBI,
        Entity::GATEWAY_MERCHANT_ID        => 'required|string|size:9',
        Entity::GATEWAY_TERMINAL_ID        => 'required|string|size:8',
        Entity::ENABLED                    => 'required|in:0,1',
    ];

    protected static $emiSbiEditTerminalRules = [
        Entity::GATEWAY                    => 'required|in:' . Gateway::EMI_SBI,
        Entity::ENABLED                    => 'required|in:0,1',
    ];

    protected static $axisMigsEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:axis_migs',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::CAPABILITY                 => 'sometimes|in:0,2',
    ];

    protected static $isgEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:isg',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string|max:15',
        Entity::GATEWAY_TERMINAL_ID        => 'somtimes|string|size:8',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TYPE . '.bharat_qr'        => 'sometimes|in:1',
        Entity::TYPE . '.non_recurring'    => 'sometimes|in:1',
        Entity::MC_MPAN                    => 'sometimes|string|size:16',
        Entity::VISA_MPAN                  => 'sometimes|string|size:16',
        Entity::RUPAY_MPAN                 => 'sometimes|string|size:16',
        Entity::EXPECTED                   => 'sometimes|boolean',
        Entity::ACCOUNT_NUMBER             => 'sometimes_if:type.bharat_qr,1|string|max:50',
        Entity::IFSC_CODE                  => 'sometimes_if:type.bharat_qr,1|string|size:11'
    ];

    protected static $atomEditTerminalRules = [
        Entity::GATEWAY                     => 'sometimes|in:atom',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD2  => 'sometimes',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::GATEWAY_SECURE_SECRET2      => 'sometimes|string',
    ];

    protected static $amexEditTerminalRules = [
        Entity::GATEWAY                     => 'sometimes|in:' . Gateway::AMEX,
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|alpha_num|min:8',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
    ];

    protected static $billdeskEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:billdesk',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string'
    ];

    protected static $hdfcEditTerminalRules = [
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
        Entity::GATEWAY                    => 'sometimes|in:hdfc',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::MODE                       => 'sometimes|in:3',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::CAPABILITY                 => 'sometimes',
    ];

    protected static $hitachiEditTerminalRules = [
        Entity::GATEWAY                    => 'required|in:hitachi',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|string|max:8',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::EXPECTED                   => 'sometimes|boolean',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|in:ratn',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::MC_MPAN                    => 'sometimes|string|size:16',
        Entity::VISA_MPAN                  => 'sometimes|string|size:16',
        Entity::RUPAY_MPAN                 => 'sometimes|string|size:16',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::IFSC_CODE                  => 'sometimes|string|size:11'
    ];

    protected static $firstDataEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:first_data',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::MODE                       => 'sometimes|in:2,3',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
    ];

    protected static $cybersourceEditTerminalRules = [
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'sometimes|string',
        Entity::GATEWAY                    => 'sometimes|in:cybersource',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
    ];

    protected static $upiIciciEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_icici',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
        Entity::TYPE                       => 'sometimes|array',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
    ];

     protected static $upiMindgateEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_mindgate',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::GATEWAY_TERMINAL_PASSWORD2 => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
    ];

    protected static $upiAirtelEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_airtel',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
    ];

    protected static $upiCitiEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
    ];

    protected static $netbankingIciciEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID2    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|alpha_num|size:16',
        Entity::NETWORK_CATEGORY        => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER          => 'sometimes|string|max:50',
    ];

    protected static $netbankingIndusindEditTerminalRules = [
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::NETWORK_CATEGORY        => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER          => 'sometimes|string|max:50',
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
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string|in:v2',
    ];

    protected static $walletPhonepeTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_phonepe',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
    ];

    protected static $walletPaypalTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:wallet_paypal',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'required|string',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'required|string',
        Entity::CURRENCY                                => 'sometimes|array',
        Entity::INTERNATIONAL                           => 'sometimes|boolean',
        Entity::MODE                                    => 'sometimes',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
    ];

    protected static $walletPaypalEditTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:wallet_paypal',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID                     => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::CURRENCY                                => 'sometimes|array',
        Entity::INTERNATIONAL                           => 'sometimes|boolean',
        Entity::MODE                                    => 'sometimes',
        Entity::TYPE                                    => 'sometimes|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'sometimes|in:1',
    ];

    protected static $walletPhonepeEditTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_phonepe',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
    ];

    protected static $walletOlamoneyEditTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_olamoney',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TYPE . '.ivr'              => 'required_with:type|in:1,0',
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
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|alpha_num|size:16',
    ];

    protected static $netbankingCanaraTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_canara',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
    ];

    protected static $netbankingCanaraEditTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_canara',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
    ];

    protected static $netbankingEquitasTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_equitas',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|alpha_num|size:16',
    ];

    protected static $netbankingCubTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_cub',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
    ];
    protected static $netbankingCbiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_cbi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
    ];

    protected static $netbankingIbkTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_ibk',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
    ];

    protected static $netbankingCubEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:netbanking_cub',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2 => 'sometimes|string',
    ];

    protected static $netbankingIdbiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_idbi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
    ];

    protected static $netbankingVijayaTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_vijaya',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
    ];

    protected static $netbankingVijayaEditTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_vijaya',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
    ];

    protected static $netbankingYesbTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_yesb',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET   => 'required|string',
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
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::GATEWAY_TERMINAL_PASSWORD2 => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::VPA                        => 'required_only_if:type.bharat_qr,1|string',
        Entity::EXPECTED                   => 'sometimes_if:type.bharat_qr,1|boolean',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
    ];

    protected static $upiAxisTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_axis',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::VPA                        => 'required|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::TYPE                       => 'sometimes|array',
    ];

    protected static $upiAxisEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_axis',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string'
    ];

    protected static $upiHulkTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_hulk',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string|in:proxy,app',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::VPA                        => 'sometimes|string',
        Entity::EXPECTED                   => 'sometimes_if:type.bharat_qr,1|boolean'
    ];

    protected static $upiSbiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_sbi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
    ];

    protected static $upiYesbankTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_yesbank',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'required|array',
        Entity::TYPE . '.pay'              => 'required|in:1',
    ];

    protected static $netbankingAirtelTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_airtel',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
    ];

    protected static $netbankingAxisTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_axis',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        // The below fields are used only for Emandate terminals, hence "sometimes"
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
    ];

    protected static $netbankingSibTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_sib',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
    ];

    protected static $netbankingSibEditTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_sib',
        Entity::TPV                        => 'sometimes|in:0,1,2',
    ];

    protected static $netbankingFederalTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_federal',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string'
    ];

    protected static $netbankingFederalEditTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_federal',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string'
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
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
    ];

    protected static $netbankingCsbTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_CSB,
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
    ];

    protected static $netbankingCsbEditTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_CSB,
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
    ];

    protected static $netbankingBobEditTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_BOB,
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::CORPORATE                   => 'sometimes|int|in:0,1,2',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
    ];

    protected static $netbankingSbiTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_SBI,
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::TYPE                        => 'sometimes|array',
    ];

    protected static $netbankingSbiEditTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_SBI,
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
    ];

    protected static $netbankingAllahabadTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_ALLAHABAD,
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string',
    ];

    protected static $netbankingAllahabadEditTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_ALLAHABAD,
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
    ];

    protected static $netbankingIdfcEditTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_IDFC,
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::TPV                         => 'sometimes|in:0,2',
    ];

    protected static $cardFssTerminalRules = [
        Entity::GATEWAY                     => 'required|in:card_fss',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::MODE                        => 'sometimes|integer|in:2,3',
        Entity::CATEGORY                    => 'sometimes|string|numeric|digits:4',
    ];

    protected static $cardFssEditTerminalRules = [
        Entity::GATEWAY                     => 'required|in:card_fss',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::MODE                        => 'sometimes|integer|in:2,3',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::CATEGORY                    => 'sometimes|string|numeric|digits:4',
    ];

    protected static $upiHulkEditTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_hulk',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::VPA                        => 'sometimes|string',
        Entity::EXPECTED                   => 'sometimes_if:type.bharat_qr,1|boolean',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string|in:proxy,app',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
    ];

    protected static $paytmTerminalRules = [
        Entity::GATEWAY                    => 'required|in:paytm',
        Entity::GATEWAY_TERMINAL_ID        => 'required',
        Entity::GATEWAY_ACCESS_CODE        => 'required',
        Entity::GATEWAY_MERCHANT_ID        => 'required',
        Entity::GATEWAY_SECURE_SECRET      => 'required',
        Entity::TYPE                       => 'sometimes',
    ];

    protected static $paytmEditTerminalRules = [
        Entity::GATEWAY                    => 'required|in:paytm',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes',
        Entity::TYPE                       => 'sometimes',
    ];

    protected static $enachRblTerminalRules = [
        Entity::GATEWAY                     => 'required|in:enach_rbl',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|size:18',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|string',
        Entity::GATEWAY_TERMINAL_ID         => 'required|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|size:11',
        Entity::TYPE                        => 'required|array',
    ];

    protected static $enachRblEditTerminalRules = [
        Entity::GATEWAY                     => 'required|in:enach_rbl',
        Entity::GATEWAY_ACQUIRER            => 'sometimes|in:ratn',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::TYPE                        => 'sometimes|array',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
    ];

    protected static $enachNpciNetbankingTerminalRules = [
        Entity::GATEWAY                     => 'required|in:enach_npci_netbanking',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|string',
        Entity::TYPE                        => 'required|array',
    ];

    protected static $editWalletAirtelmoneyTerminalRules = [
        Entity::GATEWAY                     => 'required|in:wallet_airtelmoney',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
    ];

    protected static $walletAmazonpayTerminalRules = [
        Entity::GATEWAY                     => 'required|in:wallet_amazonpay',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string',
    ];

    protected static $btYesbankTerminalRules = [
        Entity::GATEWAY                     => 'required|in:bt_yesbank',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|max:6',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::BANK_TRANSFER               => 'required|boolean|in:1',
    ];

    protected static $btKotakTerminalRules = [
        Entity::GATEWAY                     => 'required|in:bt_kotak',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|max:6',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::BANK_TRANSFER               => 'bail|required|boolean|in:1',
    ];

    protected static $btDashboardTerminalRules = [
        Entity::GATEWAY                     => 'required|in:bt_dashboard',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|max:6',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::BANK_TRANSFER               => 'required|boolean|in:1',
    ];

    protected static $cardlessEmiTerminalRules = [
        Entity::GATEWAY                     => 'required|in:cardless_emi',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::CARDLESS_EMI                => 'required|boolean|in:1',
        Entity::TYPE                        => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
    ];

    protected static $cardlessEmiEditTerminalRules = [
        Entity::GATEWAY                     => 'required|in:cardless_emi',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
    ];

    protected static $paylaterTerminalRules = [
        Entity::GATEWAY                     => 'required|in:paylater',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|string',
        Entity::PAYLATER                    => 'required|boolean|in:1',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string',
    ];

    protected static $paylaterEditTerminalRules = [
        Entity::GATEWAY                     => 'required|in:paylater',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_ACQUIRER            => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string',
    ];

    protected static $updateTerminalsBankRules = [
        Entity::TERMINAL_IDS                => 'required|array',
        Entity::ACTION                      => 'required|string|in:add,remove',
        Entity::BANK                        => 'required|string|custom',
    ];

    protected static $bajajfinservTerminalRules = [
        Entity::GATEWAY                    => 'required|in:bajajfinserv',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::EMI                        => 'required|boolean|in:1',
        Entity::EMI_SUBVENTION             => 'required|string|in:merchant,customer',
        Entity::TYPE                       => 'sometimes|array',
    ];

    protected static $googlePayTerminalRules = [
        Entity::GATEWAY                    => 'required|in:google_pay',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::VPA                        => 'required|string',
        Entity::OMNICHANNEL                => 'required|boolean|in:1',
        Entity::CAPABILITY                 => 'required|in:1'
    ];

    protected static $worldlineTerminalRules = [
        Entity::GATEWAY                    => 'required|in:worldline',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_TERMINAL_ID        => 'required|string',
        Entity::MC_MPAN                    => 'required|string|max:255',
        Entity::VISA_MPAN                  => 'required|string',
        Entity::RUPAY_MPAN                 => 'required|string',
        Entity::VPA                        => 'required|string',
        Entity::EXPECTED                   => 'sometimes|boolean',
        Entity::TYPE                       => 'required|array',
        Entity::TYPE . '.bharat_qr'        => 'required|in:1',
        Entity::TYPE . '.non_recurring'    => 'required|in:1',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
    ];

    protected static $worldlineEditTerminalRules = [
        Entity::MC_MPAN                    => 'sometimes|string',
        Entity::VISA_MPAN                  => 'sometimes|string',
        Entity::RUPAY_MPAN                 => 'sometimes|string',
        Entity::VPA                        => 'sometimes|string',
    ];

    protected static $matchAttributes = [
        Entity::GATEWAY,
        Entity::GATEWAY_ACQUIRER,
        Entity::EMI,
        Entity::EMI_DURATION,
        Entity::TYPE,
        Entity::CURRENCY,
        Entity::NETWORK_CATEGORY,
        Entity::CATEGORY,
        Entity::EMI_SUBVENTION,
        Entity::INTERNATIONAL,
        Entity::VPA,
        Entity::PROCURER,
        Entity::MC_MPAN,
        Entity::VISA_MPAN,
        Entity::RUPAY_MPAN
    ];

    public function validateType()
    {
        $type = $this->entity->getType();

        if (( in_array(Type::DIRECT_SETTLEMENT_WITH_REFUND, $type) === true ) and
            ( in_array(Type::DIRECT_SETTLEMENT_WITHOUT_REFUND, $type) === true ))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Direct Settlement Terminal should be either with refund enabled or without refund.',
                Entity::TYPE);
        }

        if ($this->entity->isBankTransferEnabled() === false)
        {
            return;
        }

        if ( in_array(Type::NON_RECURRING, $type ) === false )
        {
            throw new Exception\BadRequestValidationFailureException(
                'Bank Transfer Terminal cannot be Recurring.',
                Entity::TYPE);
        }

        if (( in_array(Type::NUMERIC_ACCOUNT, $type ) === true ) xor
            ( in_array(Type::ALPHA_NUMERIC_ACCOUNT, $type ) === true ))
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException(
            'Bank Transfer Terminal should be either Numeric or Alpha Numeric.',
            Entity::TYPE);
    }

    protected function validateStatus(string $attribute, string $value)
    {
        if (Status::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid terminal status: ' . $value);
        }
    }

    protected function validateGateway($input)
    {
        Payment\Gateway::validateGateway($input['gateway']);

        unset(
            $input[Entity::TPV],
            $input[Entity::CARD],
            $input[Entity::SHARED],
            $input[Entity::CATEGORY],
            $input[Entity::CORPORATE],
            $input[Entity::BANKING_TYPES],
            $input[Entity::NETBANKING],
            $input[Entity::EMANDATE],
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

    protected function validateTpv($input)
    {
        if (isset($input[Entity::TPV]) === false)
        {
            return;
        }

        $netbanking = $input[Entity::NETBANKING] ?? '0';
        $upi = $input[Entity::UPI] ?? '0';

        if (($netbanking !== '1') and
            ($upi !== '1'))
        {
            throw new Exception\BadRequestValidationFailureException(
                'tpv is not required and shouldn\'t be sent',
                Entity::TPV);
        }
    }

    protected function validateMode($input)
    {
        // TODO: Adding this for backward compatibility
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

        // Migs, Amex, OpenWallet, CardlessEmi, PayPal terminals are always in auth-capture mode
        //
        $authCaptureOnly = [
            Gateway::AXIS_MIGS,
            Gateway::AMEX,
            Gateway::WALLET_OPENWALLET,
            Gateway::CARDLESS_EMI,
            Gateway::WALLET_PAYPAL,
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

        if ($input[Entity::GATEWAY] === Gateway::BAJAJ)
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
        if (isset($input['currency']) === true)
        {
            $currency = array_unique((array) $input['currency']);

            if (count(array_intersect($currency, Currency::SUPPORTED_CURRENCIES)) !== count($currency))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED);
            }
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

    protected function matchGatewayForNewTerminal(Entity $new, Entity $existing)
    {
        $newMatch = array_only($new->toArray(), self::$matchAttributes);
        $existingMatch = array_only($existing->toArray(), self::$matchAttributes);

        // Need to sort the keys to ensure we can use strict check in the below condition.
        ksort($newMatch);
        ksort($existingMatch);

        $newId = $new->getId();
        $existingId = $existing->getId();

        if (($newMatch === $existingMatch) and
            ($newId !== $existingId))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY,
                null,
                [
                    'new_id'            => $newId,
                    'existing_id'       => $existingId,
                    'new_match'         => $newMatch,
                    'existing_match'    => $existingMatch,
                ]);
        }
    }

    public function editTerminalValidator($terminal, $input)
    {
        if (in_array($terminal->getGateway(), self::$editTerminalGateways))
        {
            $gateway = $terminal->getGateway();
            $this->validateInput($gateway . '_edit_terminal', $input);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'Editing not defined for terminal of gateway: ' . $terminal->getGateway());
        }
    }

    protected static function getMethod($input)
    {
        if (empty($input[Entity::CARD]) === false)
        {
            return Method::CARD;
        }

        //
        // This is kept before netbanking on purpose to avoid
        // manual errors where both emandate and netbanking is set.
        //
        if (empty($input[Entity::EMANDATE]) === false)
        {
            return Method::EMANDATE;
        }

        if (empty($input[Entity::NETBANKING]) === false)
        {
            return Method::NETBANKING;
        }

        if (empty($input[Entity::EMI]) === false)
        {
            return Method::EMI;
        }

        if (empty($input[Entity::CARDLESS_EMI]) === false)
        {
            return Method::CARDLESS_EMI;
        }

        if (empty($input[Entity::PAYLATER]) === false)
        {
            return Method::PAYLATER;
        }

        if (empty($input[Entity::UPI]) === false)
        {
            return Method::UPI;
        }

        return null;
    }

    protected static function validateBank($attribute, $value)
    {
        if (Bank\IFSC::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid bank name in input: '. $value);
        }
    }
}
