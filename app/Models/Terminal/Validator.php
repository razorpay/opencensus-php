<?php

namespace RZP\Models\Terminal;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Feature;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\TpvType;
use RZP\Models\Currency\Currency;
use RZP\Models\Terminal\BankingType;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\CardlessEmi;
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
        Entity::MC_MPAN                     => 'sometimes|string',
        Entity::VISA_MPAN                   => 'sometimes|string',
        Entity::RUPAY_MPAN                  => 'sometimes|string',
        Entity::VPA                         => 'sometimes|string|max:255',
        Entity::CATEGORY                    => 'sometimes|string|numeric|digits:4',
        Entity::CARD                        => 'sometimes|boolean',
        Entity::NETBANKING                  => 'sometimes|boolean',
        Entity::EMANDATE                    => 'sometimes|boolean',
        Entity::NACH                        => 'sometimes|boolean',
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
        Entity::CURRENCY                    => 'sometimes',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::IFSC_CODE                   => 'sometimes|string|size:11',
        Entity::CARDLESS_EMI                => 'sometimes|boolean',
        Entity::PAYLATER                    => 'sometimes|boolean',
        Entity::ENABLED                     => 'sometimes|in:0,1',
        Entity::CAPABILITY                  => 'sometimes|in:0,1,2',
        Entity::NOTES                       => 'sometimes',
        Entity::VIRTUAL_UPI_HANDLE          => 'required_if:type.upi_transfer,1|string',
        Entity::VIRTUAL_UPI_ROOT            => 'required_if:type.upi_transfer,1|string',
        Entity::VIRTUAL_UPI_MERCHANT_PREFIX => 'sometimes_if:type.upi_transfer,1|string',
        Entity::ACCOUNT_TYPE                => 'sometimes|string',
        Entity::CRED                        => 'sometimes|string',
        Entity::APP                         => 'sometimes|string',
        Entity::ORG_ID                      => 'sometimes|string',
        Entity::PLAN_ID                     => 'sometimes',
        Entity::ENABLED_WALLETS             => 'sometimes|array',
        Entity::OFFLINE                     => 'sometimes|string',
    ];

    protected static $gatewayInputRules = [
        Entity::GATEWAY                        => 'not_in:paysecure',
    ];
    protected static $mpansBeforeTokenizationRules = [
        Entity::MC_MPAN                     => 'sometimes|string|size:16',
        Entity::VISA_MPAN                   => 'sometimes|string|size:16',
        Entity::RUPAY_MPAN                  => 'sometimes|string|size:16',
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
        Payment\Gateway::UPI_JUSPAY,
        Payment\Gateway::UPI_YESBANK,
        Payment\Gateway::ENACH_RBL,
        Payment\Gateway::FIRST_DATA,
        Payment\Gateway::CYBERSOURCE,
        Payment\Gateway::UPI_MINDGATE,
        Payment\Gateway::UPI_AXIS,
        Payment\Gateway::UPI_SBI,
        Payment\Gateway::MPGS,
        Payment\Gateway::NETBANKING_CSB,
        Payment\Gateway::NETBANKING_BOB,
        Payment\Gateway::NETBANKING_ICICI,
        Payment\Gateway::NETBANKING_AXIS,
        Payment\Gateway::NETBANKING_INDUSIND,
        Payment\Gateway::NETBANKING_EQUITAS,
        Payment\Gateway::NETBANKING_CANARA,
        Payment\Gateway::NETBANKING_VIJAYA,
        Payment\Gateway::NETBANKING_YESB,
        Payment\Gateway::NETBANKING_SIB,
        Payment\Gateway::NETBANKING_FEDERAL,
        Payment\Gateway::NETBANKING_CUB,
        Payment\Gateway::NETBANKING_SBI,
        Payment\Gateway::NETBANKING_KOTAK,
        Payment\Gateway::NETBANKING_SCB,
        Payment\Gateway::NETBANKING_AUSF,
        Payment\Gateway::NETBANKING_NSDL,
        Payment\Gateway::NETBANKING_DCB,
        Payment\Gateway::NETBANKING_RBL,
        Payment\Gateway::NETBANKING_HDFC,
        Payment\Gateway::ENACH_NPCI_NETBANKING,
        Payment\Gateway::EMI_SBI,
        Payment\Gateway::ICICI,
        Payment\Gateway::WALLET_OLAMONEY,
        Payment\Gateway::PAYTM,
        Payment\Gateway::BAJAJFINSERV,
        Payment\Gateway::WALLET_PHONEPE,
        Payment\Gateway::WALLET_PHONEPESWITCH,
        Payment\Gateway::WALLET_PAYPAL,
        Payment\Gateway::WALLET_FREECHARGE,
        Payment\Gateway::WALLET_BAJAJ,
        Payment\Gateway::WALLET_AMAZONPAY,
        Payment\Gateway::UPI_AIRTEL,
        Payment\Gateway::UPI_KOTAK,
        Payment\Gateway::UPI_RZPRBL,
        Payment\Gateway::ISG,
        Payment\Gateway::PAYLATER,
        Payment\Gateway::CARDLESS_EMI,
        Payment\Gateway::NACH_CITI,
        Payment\Gateway::HDFC_DEBIT_EMI,
        Payment\Gateway::BT_RBL,
        Payment\Gateway::BT_HDFC_ECMS,
        Payment\Gateway::WORLDLINE,
        Payment\Gateway::CRED,
        Payment\Gateway::TWID,
        Payment\Gateway::WALLET_PAYZAPP,
        Payment\Gateway::PAYU,
        Payment\Gateway::NACH_ICICI,
        Payment\Gateway::CASHFREE,
        Payment\Gateway::ZAAKPAY,
        Payment\Gateway::CCAVENUE,
        payment\Gateway::PINELABS,
        Payment\Gateway::FULCRUM,
        Payment\Gateway::NETBANKING_UBI,
        Payment\Gateway::NETBANKING_PNB,
        Payment\Gateway::CHECKOUT_DOT_COM,
        Payment\Gateway::BILLDESK_SIHUB,
        Payment\Gateway::MANDATE_HQ,
        Payment\Gateway::PAYSECURE,
        Payment\Gateway::RUPAY_SIHUB,
        Payment\Gateway::NETBANKING_BDBL,
        Payment\Gateway::NETBANKING_UCO,
        Payment\Gateway::MOBIKWIK,
        Payment\Gateway::NETBANKING_SARASWAT,
        Payment\Gateway::EMERCHANTPAY,
        Payment\Gateway::UMOBILE,
        Payment\Gateway::NETBANKING_DBS,
        Payment\Gateway::INGENICO,
        Payment\Gateway::BILLDESK_OPTIMIZER,
        Payment\Gateway::KOTAK_DEBIT_EMI,
        Payment\Gateway::INDUSIND_DEBIT_EMI,
        Payment\Gateway::HDFC_EZETAP,
        Payment\Gateway::AXIS_TOKENHQ,
        Payment\Gateway::FPX,
        Payment\Gateway::OPTIMIZER_RAZORPAY,
        Payment\Gateway::EGHL,
    ];

    protected static $createValidators = [
        Entity::GATEWAY,
        Entity::EMI,
        Entity::NETWORK_CATEGORY,
        Entity::CURRENCY,
        Entity::GATEWAY_ACQUIRER,
        Entity::MODE,
        Entity::TPV,
        Entity::ENABLED_WALLETS,
    ];

    protected static $updateTerminalsBankValidators = [
        'updateTerminalsBankAndBanksShouldNotBePresentTogether'
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
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::VPA                        => 'required_only_if:type.bharat_qr,1|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::VIRTUAL_UPI_HANDLE         => 'required_if:type.upi_transfer,1|string',
        Entity::VIRTUAL_UPI_ROOT           => 'required_if:type.upi_transfer,1|string',
        Entity::VIRTUAL_UPI_MERCHANT_PREFIX=> 'sometimes_if:type.upi_transfer,1|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::NOTES                      => 'sometimes|string',
    ];

    protected static $vpaLengthRules = [
        Entity::VPA     => 'max:20',
    ];

    protected static $vpaLengthForBqrRules = [
        Entity::VPA     => 'max:40',
    ];

    protected static $upiAirtelTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_airtel',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiKotakTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_kotak',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::VPA                        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiRzprblTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_rzprbl',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::VPA                        => 'required|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiCitiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_citi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiJuspayTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_juspay',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|in:axis',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes',
        Entity::VPA                        => 'required|string',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::EXPECTED                   => 'sometimes_if:type.pay,1|boolean|in:1',
        Entity::MODE                       => 'sometimes',
    ];

    protected static $atomTerminalRules = [
        Entity::GATEWAY                    => 'required|in:atom',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD2 => 'required',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::NOTES                      => 'sometimes|string',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
    ];

    protected static $payuTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:payu',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'required|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::NETBANKING                              => 'sometimes|boolean|in:0,1',
        Entity::CARD                                    => 'sometimes|boolean|in:0,1',
        Entity::UPI                                     => 'sometimes|boolean|in:0,1',
        Entity::VPA                                     => 'sometimes|string',
        Entity::MODE                                    => 'sometimes|in:2',
        Entity::NOTES                                   => 'sometimes|string',
        Entity::EMI                                     => 'sometimes|boolean',
        Entity::EMI_SUBVENTION                          => 'sometimes|in:customer,merchant',
        Entity::ENABLED_WALLETS                         => 'sometimes|array',
        Entity::EMANDATE                                => 'sometimes|boolean|in:0,1',
        Entity::TYPE . 'recurring_3ds'                  => 'sometimes_if:emandate,1|in:1',
        Entity::TYPE . 'recurring_non_3ds'              => 'sometimes_if:emandate,1|in:1',
    ];

    protected static $cashfreeTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:cashfree',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'required|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::MODE                                    => 'sometimes|in:2',
        Entity::NETBANKING                              => 'sometimes|boolean|in:0,1',
        Entity::CARD                                    => 'sometimes|boolean|in:0,1',
        Entity::UPI                                     => 'sometimes|boolean|in:0,1',
        Entity::VPA                                     => 'sometimes|string',
        Entity::NETWORK_CATEGORY                        => 'sometimes|string|max:30',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::NOTES                                   => 'sometimes|string',
    ];

    protected static $zaakpayTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:zaakpay',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE                     => 'sometimes|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::MODE                                    => 'sometimes|in:2',
        Entity::NETWORK_CATEGORY                        => 'sometimes|string|max:30',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed'
    ];

    protected static $checkoutDotComTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:checkout_dot_com',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::INTERNATIONAL                           => 'sometimes|boolean',
        Entity::CURRENCY                                => 'sometimes|array',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
        Entity::TYPE                                    => 'sometimes|array',
    ];

    protected static $checkoutDotComEditTerminalRules = [
        Entity::GATEWAY                                 => 'sometimes|in:checkout_dot_com',
        Entity::GATEWAY_MERCHANT_ID                     => 'sometimes|string',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::INTERNATIONAL                           => 'sometimes|boolean',
        Entity::CURRENCY                                => 'sometimes|array',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
        Entity::TYPE                                    => 'sometimes|array',
    ];

    protected static $ccavenueTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:ccavenue',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'required|string',
        Entity::GATEWAY_ACCESS_CODE                     => 'required|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::MODE                                    => 'sometimes|in:2',
        Entity::NETWORK_CATEGORY                        => 'sometimes|string',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::NETBANKING                              => 'sometimes|boolean|in:0,1',
        Entity::CARD                                    => 'sometimes|boolean|in:0,1',
        Entity::NOTES                                   => 'sometimes|string',
        Entity::ENABLED_WALLETS                         => 'sometimes|array',
        Entity::UPI                                     => 'sometimes|boolean|in:0,1',
    ];

    protected static $pinelabsTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:pinelabs',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'required|string',
        Entity::GATEWAY_ACCESS_CODE                     => 'required|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::MODE                                    => 'sometimes|in:2',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CARD                                    => 'sometimes|boolean|in:0,1',
        Entity::NOTES                                   => 'sometimes|string',
        Entity::UPI                                     => 'sometimes|boolean|in:0,1',
    ];

    protected static $ingenicoTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:ingenico',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'required|string',
        Entity::GATEWAY_ACCESS_CODE                     => 'required|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::MODE                                    => 'sometimes|in:2',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CARD                                    => 'sometimes|boolean|in:0,1',
        Entity::NOTES                                   => 'sometimes|string',
        Entity::NETBANKING                              => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY                        => 'sometimes|string|max:30',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
    ];

    protected static $billdeskOptimizerTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:billdesk_optimizer',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'required|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::MODE                                    => 'sometimes|in:2',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CARD                                    => 'sometimes|boolean|in:0,1',
        Entity::UPI                                     => 'sometimes|boolean|in:0,1',
        Entity::NOTES                                   => 'sometimes|string',
        Entity::NETBANKING                              => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY                        => 'sometimes|string|max:30',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
    ];

    protected static $hdfcTerminalRules = [
        Entity::GATEWAY                    => 'required|in:hdfc',
        Entity::GATEWAY_MERCHANT_ID        => 'required|integer|digits_between:4,8',
        Entity::GATEWAY_TERMINAL_ID        => 'required|alpha_num|size:8',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string|max:15',
        Entity::EMI                        => 'sometimes|boolean',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::EMI_DURATION               => 'required_only_if:emi,1|integer|in:3,6,9,12,18,24',
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
        Entity::EMI_SUBVENTION             => 'sometimes|in:customer,merchant',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CURRENCY                   => 'sometimes|array',
        Entity::CAPABILITY                 => 'sometimes|in:0,2',
        Entity::CORPORATE                  => 'sometimes|int|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::ORG_ID => 'sometimes',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::NOTES                      => 'sometimes|string',
    ];

    protected static $hitachiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:hitachi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string|max:15',
        Entity::GATEWAY_TERMINAL_ID        => 'required|string|max:8',
        Entity::TYPE                       => 'sometimes|array',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::CURRENCY                   => 'sometimes',
        Entity::MC_MPAN                    => 'required_if:type.bharat_qr,1|string',
        Entity::VISA_MPAN                  => 'required_if:type.bharat_qr,1|string',
        Entity::RUPAY_MPAN                 => 'required_if:type.bharat_qr,1|string',
        Entity::EXPECTED                   => 'sometimes_if:type.bharat_qr,1|boolean',
        Entity::ACCOUNT_NUMBER             => 'sometimes_if:type.bharat_qr,1|string|max:50',
        Entity::IFSC_CODE                  => 'sometimes_if:type.bharat_qr,1|string|size:11',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PLAN_ID                    => 'sometimes|string',
    ];

    protected static $mpgsTerminalRules = [
        Entity::GATEWAY                    => 'required|in:mpgs',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string|max:20',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::CURRENCY                   => 'sometimes',
        Entity::CAPABILITY                 => 'sometimes',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $isgTerminalRules = [
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::GATEWAY                    => 'required|in:isg',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string|max:15',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string|max:15',
        Entity::GATEWAY_TERMINAL_ID        => 'required|string|size:8',
        Entity::TYPE                       => 'required|array',
        Entity::TYPE . '.bharat_qr'        => 'sometimes|in:1',
        Entity::TYPE . '.non_recurring'    => 'required|in:1',
        Entity::MODE                       => 'sometimes|integer|in:2,3',
        Entity::MC_MPAN                    => 'sometimes_if:type.bharat_qr,1|string',
        Entity::VISA_MPAN                  => 'sometimes_if:type.bharat_qr,1|string',
        Entity::RUPAY_MPAN                 => 'sometimes_if:type.bharat_qr,1|string',
        Entity::EXPECTED                   => 'sometimes|boolean',
        Entity::ACCOUNT_NUMBER             => 'sometimes_if:type.bharat_qr,1|string|max:50',
        Entity::IFSC_CODE                  => 'sometimes_if:type.bharat_qr,1|string|size:11',
        Entity::GATEWAY_ACCESS_CODE        => 'required_without:mc_mpan|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required_without:mc_mpan|string',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|string|in:kotak',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];


    protected static $aepsIciciTerminalRules = [
        Entity::GATEWAY                    => 'required|in:aeps_icici',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $billdeskTerminalRules = [
        Entity::GATEWAY                    => 'required|in:billdesk',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|min:2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TYPE . '.non_recurring'    => 'sometimes|in:0,1',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $ebsTerminalRules = [
        Entity::GATEWAY                    => 'required|in:ebs',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|max:5',
        Entity::GATEWAY_SECURE_SECRET      => 'required|alpha_num|max:32',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $axisGeniusTerminalRules = [
        Entity::GATEWAY                    => 'required|in:axis_genius',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|size:15',
        Entity::GATEWAY_SECURE_SECRET      => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE        => 'required|alhpa_num|size:8',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
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
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $fulcrumTerminalRules = [
        Entity::MERCHANT_ID                => 'required|alpha_num|size:14',
        Entity::GATEWAY                    => 'required|in:fulcrum',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|size:15',
        Entity::GATEWAY_TERMINAL_ID        => 'required|alpha_num|size:8',
        Entity::MODE                       => 'sometimes|integer|in:1,2', // default value for fulcrum terminal mode is auth_capture(1)
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::CURRENCY                   => 'sometimes|array',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_ACQUIRER           => 'required|in:ratn,axis',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
        Entity::PLAN_ID                    => 'sometimes|string',
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
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $axisMigsTerminalRules = [
        Entity::GATEWAY                    => 'required|in:axis_migs',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|min:6',
        Entity::GATEWAY_SECURE_SECRET      => 'required|alpha_num|size:32',
        Entity::GATEWAY_ACCESS_CODE        => 'required|alpha_num|max:8',
        Entity::GATEWAY_TERMINAL_ID        => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CAPABILITY                 => 'sometimes|in:0,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CURRENCY                   => 'sometimes|array',
        Entity::EMI                        => 'sometimes|boolean',
        Entity::EMI_SUBVENTION             => 'sometimes|in:customer,merchant',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::NOTES                      => 'sometimes|string',
    ];

    protected static $axisTokenhqTerminalRules = [
        Entity::GATEWAY                    => 'required|in:axis_tokenhq',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|min:6',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CAPABILITY                 => 'sometimes|in:0,2',
        Entity::MODE                       => 'sometimes|in:2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CURRENCY                   => 'sometimes|array',
        Entity::EMI                        => 'sometimes|boolean',
        Entity::EMI_SUBVENTION             => 'sometimes|in:customer,merchant',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::NOTES                      => 'sometimes|string',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
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
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CURRENCY                   => 'sometimes_if:gateway_acquirer,axis|array',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::NOTES                      => 'sometimes|string',
    ];

    protected static $emiSbiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:' . Gateway::EMI_SBI,
        Entity::GATEWAY_MERCHANT_ID        => 'required|string|size:9',
        Entity::GATEWAY_TERMINAL_ID        => 'required|string|size:8',
        Entity::ENABLED                    => 'required|in:0,1',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $optimizerRazorpayTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:optimizer_razorpay',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'required|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::TYPE . '.optimizer'                     => 'required|in:1',
        Entity::MODE                                    => 'sometimes|in:2',
        Entity::PROCURER                                => 'required|string|in:merchant',
        Entity::STATUS                                  => 'required|in:pending,activated,deactivated,failed',
        Entity::CARD                                    => 'sometimes|boolean|in:0,1',
        Entity::UPI                                     => 'sometimes|boolean|in:0,1',
        Entity::NOTES                                   => 'sometimes|string',
        Entity::NETBANKING                              => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY                        => 'sometimes|string|max:30',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
    ];

    protected static $emiSbiEditTerminalRules = [
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $axisMigsEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:axis_migs',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|alpha_num|min:6',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|alpha_num|max:8',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|alpha_num|size:32',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::CAPABILITY                 => 'sometimes|in:0,2',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CURRENCY                   => 'sometimes|array',
        Entity::NOTES                      => 'sometimes|string',
    ];

    protected static $axisTokenhqEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:axis_tokenhq',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|alpha_num|min:6',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|alpha_num|max:8',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|alpha_num|size:32',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::CAPABILITY                 => 'sometimes|in:2',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CURRENCY                   => 'sometimes|array',
        Entity::NOTES                      => 'sometimes|string',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
    ];

    protected static $isgEditTerminalRules = [
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::GATEWAY                    => 'sometimes|in:isg',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string|max:15',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|string|size:8',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TYPE . '.bharat_qr'        => 'sometimes|in:1',
        Entity::TYPE . '.non_recurring'    => 'sometimes|in:1',
        Entity::MC_MPAN                    => 'sometimes|string',
        Entity::MODE                       => 'sometimes|integer|in:2,3',
        Entity::VISA_MPAN                  => 'sometimes|string',
        Entity::RUPAY_MPAN                 => 'sometimes|string',
        Entity::EXPECTED                   => 'sometimes|boolean',
        Entity::ACCOUNT_NUMBER             => 'sometimes_if:type.bharat_qr,1|string|max:50',
        Entity::IFSC_CODE                  => 'sometimes_if:type.bharat_qr,1|string|size:11',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4'
    ];

    protected static $atomEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD2  => 'sometimes',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::GATEWAY_SECURE_SECRET2      => 'sometimes|string',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:deactivated,pending,activated',
        Entity::NOTES                       => 'sometimes|string',
        Entity::TPV                         => 'sometimes|in:0,1,2',
        Entity::TYPE                        => 'sometimes|array',
        Entity::TYPE . '.non_recurring'     => 'sometimes|in:1',
        Entity::NETBANKING                  => 'sometimes|boolean|in:0,1',
        Entity::CARD                        => 'sometimes|boolean|in:0,1',
    ];

    protected static $bajajfinservEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:bajajfinserv',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::STATUS                     => 'sometimes|string|in:deactivated,activated',
    ];

    protected static $amexEditTerminalRules = [
        Entity::GATEWAY                     => 'sometimes|in:' . Gateway::AMEX,
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|alpha_num|min:8',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::CATEGORY                    => 'sometimes|string|numeric|digits:4',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $billdeskEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:billdesk',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::NOTES                      => 'sometimes|string',
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
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::CORPORATE                  => 'sometimes|int|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|integer|digits_between:4,8',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|alpha_num|size:8',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string|max:15',
        Entity::NOTES                      => 'sometimes|string',
    ];

    protected static $hitachiEditTerminalRules = [
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|string|max:8',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::EXPECTED                   => 'sometimes|boolean',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|in:ratn',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::MC_MPAN                    => 'sometimes|string',
        Entity::VISA_MPAN                  => 'sometimes|string',
        Entity::RUPAY_MPAN                 => 'sometimes|string',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::IFSC_CODE                  => 'sometimes|string|size:11',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::MODE                       => 'sometimes|in:2,3',
        Entity::CURRENCY                   => 'sometimes|array',
    ];

    protected static $fulcrumEditTerminalRules = [
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|string',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::EXPECTED                   => 'sometimes|boolean',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|in:ratn,axis',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::IFSC_CODE                  => 'sometimes|string|size:11',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::MODE                       => 'sometimes|in:1,2', // mode can be edited to auth_capture(1) or purchase(2)
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4'
    ];

    protected static $payuEditTerminalRules = [
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::EMI                        => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::MODE                       => 'sometimes|in:2',
        Entity::STATUS                     => 'sometimes|string|in:deactivated,activated',
        Entity::UPI                        => 'sometimes|boolean|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::VPA                        => 'sometimes|string',
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
        Entity::EMI_SUBVENTION             => 'sometimes|in:customer,merchant',
        Entity::ENABLED_WALLETS            => 'sometimes|array',
        Entity::EMANDATE                   => 'sometimes|boolean|in:0,1',
        Entity::TYPE . 'recurring_3ds'     => 'sometimes_if:emandate,1|in:1',
        Entity::TYPE . 'recurring_non_3ds' => 'sometimes_if:emandate,1|in:1',
    ];

    protected static $cashfreeEditTerminalRules = [
        Entity::MODE                       => 'sometimes|in:2',
        Entity::STATUS                     => 'sometimes|string|in:deactivated,activated',
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::UPI                        => 'sometimes|boolean|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::VPA                        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
    ];

    protected static $zaakpayEditTerminalRules = [
        Entity::MODE                       => 'sometimes|in:2',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $ccavenueEditTerminalRules = [
        Entity::MODE                       => 'sometimes|in:2',
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
        Entity::ENABLED_WALLETS            => 'sometimes|array',
        Entity::UPI                        => 'sometimes|boolean|in:0,1',
    ];

    protected static $pinelabsEditTerminalRules = [
        Entity::MODE                       => 'sometimes|in:2',
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:0,1',
    ];

    protected static $ingenicoEditTerminalRules = [
        Entity::MODE                       => 'sometimes|in:2',
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
    ];

    protected static $billdeskSihubEditTerminalRules = [
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string|max:15',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::GATEWAY                    => 'sometimes|in:billdesk_sihub',
    ];

    protected static $billdeskOptimizerEditTerminalRules = [
        Entity::MODE                       => 'sometimes|in:2',
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:0,1',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
    ];

    protected static $mandateHqEditTerminalRules = [
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string|max:15',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::GATEWAY                    => 'sometimes|in:mandate_hq',
    ];

    protected static $rupaySihubEditTerminalRules = [
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string|max:15',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::GATEWAY                    => 'sometimes|in:rupay_sihub',
    ];

    protected static $mpgsEditTerminalRules = [
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY                    => 'sometimes|in:mpgs',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::MODE                       => 'sometimes|in:2,3',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::CAPABILITY                 => 'sometimes',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CURRENCY                   => 'sometimes|array',
    ];

    protected static $firstDataEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:first_data',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::MODE                       => 'sometimes|in:2,3',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $paysecureTerminalRules = [
        Entity::GATEWAY                    => 'required|in:paysecure',
        Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|size:15',
        Entity::GATEWAY_TERMINAL_ID        => 'required|alpha_num|size:8',
        Entity::MODE                       => 'required|in:3',
        Entity::CURRENCY                   => 'required|array',
        Entity::CARD                       => 'required|boolean|in:1',
        Entity::MERCHANT_ID                => 'required|alpha_num|size:14',
        Entity::ENABLED                    => 'required|in:0,1',
        Entity::GATEWAY_ACQUIRER           => 'required|in:axis',
        Entity::STATUS                     => 'required|in:pending,activated',
        Entity::ORG_ID                     => 'sometimes',
        Entity::PROCURER                   => 'sometimes',
        Entity::TYPE                       => 'sometimes'
    ];

    protected static $paysecureEditTerminalRules = [
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|string|in:deactivated,activated',
        Entity::CURRENCY                   => 'sometimes|array',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|alpha_num|size:15',
    ];

    protected static $cybersourceEditTerminalRules = [
        Entity::GATEWAY_RECON_PASSWORD     => 'sometimes|alpha_num',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'sometimes|string',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|string',
        Entity::GATEWAY                    => 'sometimes|in:cybersource',
        Entity::CARD                       => 'sometimes|boolean|in:1',
        Entity::INTERNATIONAL              => 'sometimes|boolean',
        Entity::TYPE                       => 'sometimes|array',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CURRENCY                   => 'sometimes_if:gateway_acquirer,axis|array',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|string',
    ];

    protected static $upiIciciEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_icici',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
        Entity::TYPE                       => 'sometimes|array',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
    ];

    protected static $upiMindgateEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_mindgate',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::CATEGORY                   => 'sometimes|string|max:30',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2 => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::NOTES                      => 'sometimes|string',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0',
        Entity::CARD                       => 'sometimes|boolean|in:0',
    ];

    protected static $upiAirtelEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_airtel',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiKotakEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_kotak',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::VPA                        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiRzprblEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_rzprbl',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::VPA                        => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiCitiEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiJuspayEditTerminalRules = [
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
        Entity::VPA                        => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::EXPECTED                   => 'sometimes|boolean|in:0,1',
    ];

    protected static $netbankingIciciEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2    => 'sometimes|string',
        Entity::GATEWAY_ACQUIRER        => 'sometimes|in:icic',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|alpha_num|size:16',
        Entity::NETWORK_CATEGORY        => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER          => 'sometimes|string|max:50',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::TYPE                    => 'sometimes|array',
        Entity::CORPORATE               => 'sometimes|int|in:0,1,2',
        Entity::PROCURER                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingIndusindEditTerminalRules = [
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::NETWORK_CATEGORY        => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER          => 'sometimes|string|max:50',
        Entity::PROCURER                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TYPE                    => 'sometimes|array',
    ];

    protected static $netbankingHdfcEditTerminalRules = [
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletPayzappTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_payzapp',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_TERMINAL_ID        => 'required|integer|digits:8',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletPayzappEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|integer|digits:8',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|integer|digits:8',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|numeric|digits:4',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|alpha_num|size:16',
        Entity::TYPE                       => 'sometimes|array',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletPayumoneyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_payumoney',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletOlamoneyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_olamoney',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2 => 'required|string',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string|in:v2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletPhonepeTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_phonepe',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletPhonepeswitchTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_phonepeswitch',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletPaypalTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:wallet_paypal',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::CURRENCY                                => 'sometimes|array',
        Entity::INTERNATIONAL                           => 'sometimes|boolean',
        Entity::MODE                                    => 'sometimes',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletPaypalEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID                     => 'sometimes|string',
        Entity::CURRENCY                                => 'sometimes|array',
        Entity::INTERNATIONAL                           => 'sometimes|boolean',
        Entity::MODE                                    => 'sometimes',
        Entity::TYPE                                    => 'sometimes|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'sometimes|in:1',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletPhonepeEditTerminalRules = [
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
    ];

    protected static $walletPhonepeswitchEditTerminalRules = [
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $optimizerRazorpayEditTerminalRules = [
        Entity::MODE                       => 'sometimes|in:2',
        Entity::ENABLED                    => 'sometimes|in:0,1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:0,1',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
    ];

    protected static $walletOlamoneyEditTerminalRules = [
        Entity::TYPE                       => 'sometimes|array',
        Entity::TYPE . '.ivr'              => 'required_with:type|in:1,0',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletAirtelmoneyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_airtelmoney',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletFreechargeTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_freecharge',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletFreechargeEditTerminalRules = [
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes',
        Entity::TYPE                       => 'sometimes',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletBajajTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_bajaj',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::ENABLED_WALLETS            => 'sometimes|array',
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
    ];

    protected static $walletBajajEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingIciciTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_icici',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2    => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|alpha_num|size:16',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingCanaraTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_canara',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingCanaraEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID     => 'sometimes|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::PROCURER                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TPV                     => 'sometimes|in:0,1,2',
    ];

    protected static $netbankingEquitasTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_equitas',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|alpha_num|size:16',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingEquitasEditTerminalRules = [
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingCubTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_cub',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];
    protected static $netbankingCbiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_cbi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingIbkTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_ibk',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingKvbTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_kvb',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingSvcTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_svc',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingDcbTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_dcb',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingDcbEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingUjivTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_ujjivan',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingJsbTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_jsb',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingIobTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_iob',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingAusfTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_ausf',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CORPORATE                  => 'sometimes|int|in:0,1,2',
    ];

    protected static $netbankingAusfEditTerminalRules = [
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CORPORATE                  => 'sometimes|int|in:0,1,2',
    ];

    protected static $netbankingDlbTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_dlb',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingTmbTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_tmb',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingNsdlTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_nsdl',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingBdblTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_bdbl',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingBdblEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID     => 'sometimes|string',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TYPE                    => 'sometimes|array',
    ];

    protected static $netbankingSaraswatTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_saraswat',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET   => 'required|string',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingSaraswatEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID     => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|string',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TYPE                    => 'sometimes|array',
    ];

    protected static $netbankingKarnatakaTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_karnataka',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET   => 'required|string',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingKarnatakaEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID     => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|string',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TYPE                    => 'sometimes|array',
    ];

    protected static $netbankingCubEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:netbanking_cub',
        Entity::NETWORK_CATEGORY           => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2 => 'sometimes|string',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingIdbiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_idbi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingVijayaTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_vijaya',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingVijayaEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID     => 'sometimes|string',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingYesbTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_yesb',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET   => 'required|string',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingDbsTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_dbs',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2    => 'required|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingDbsEditTerminalRules = [
        Entity::TYPE                    => 'sometimes|array',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletJiomoneyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_jiomoney',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletSbibuddyTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_sbibuddy',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletMpesaTerminalRules = [
        Entity::GATEWAY                    => 'required|in:wallet_mpesa',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiMindgateTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_mindgate',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::GATEWAY_TERMINAL_PASSWORD2 => 'sometimes|string',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::CATEGORY                   => 'sometimes|string|max:30',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::VPA                        => 'required_only_if:type.bharat_qr,1|string',
        Entity::EXPECTED                   => 'sometimes_if:type.bharat_qr,1|boolean',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::VIRTUAL_UPI_HANDLE         => 'required_if:type.upi_transfer,1|string',
        Entity::VIRTUAL_UPI_ROOT           => 'required_if:type.upi_transfer,1|string',
        Entity::VIRTUAL_UPI_MERCHANT_PREFIX=> 'sometimes_if:type.upi_transfer,1|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::NOTES                      => 'sometimes|string',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
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
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::NOTES                      => 'sometimes|string',
    ];

    protected static $upiAxisEditTerminalRules = [
        Entity::GATEWAY                    => 'sometimes|in:upi_axis',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::VPA                        => 'sometimes|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::NOTES                      => 'sometimes|string',
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
        Entity::EXPECTED                   => 'sometimes_if:type.bharat_qr,1|boolean',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiSbiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_sbi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::UPI                        => 'sometimes|boolean|in:1',
        Entity::VPA                        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiSbiEditTerminalRules = [
        Entity::VPA                        => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $upiYesbankTerminalRules = [
        Entity::GATEWAY                    => 'required|in:upi_yesbank',
        Entity::UPI                        => 'required|boolean|in:1',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TYPE . '.pay'              => 'sometimes|in:1',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::VPA                        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
    ];

    protected static $upiYesbankEditTerminalRules = [
        Entity::VPA                        => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingAirtelTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_airtel',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingAxisTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_axis',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        // The below fields are used only for Emandate terminals, hence "sometimes"
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        // The below fields are used only for optimizer
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::NOTES                      => 'sometimes|string',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',

    ];

    protected static $netbankingAxisEditTerminalRules = [
        // The below fields are used only for Emandate terminals, hence "sometimes"
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::CORPORATE                  => 'sometimes|int|in:0,1,2',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|string',
        // The below fields are used only for optimizer
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::NOTES                      => 'sometimes|string',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::GATEWAY                    => 'sometimes|in:netbanking_axis',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
    ];

    protected static $nachCitiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:nach_citi',
        Entity::NACH                       => 'required|boolean|in:1',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string|alpha_num|max:18',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string|alpha_num|max:11',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $nachCitiEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string|alpha_num|max:18',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string|alpha_num|max:11',
        Entity::TYPE                       => 'sometimes|array',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $nachIciciTerminalRules = [
        Entity::GATEWAY                    => 'required|in:nach_icici',
        Entity::NACH                       => 'required|boolean|in:1',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string|alpha_num|max:18',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string|alpha_num|max:11',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::GATEWAY_ACQUIRER           => 'sometimes|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $nachIciciEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string|alpha_num|max:18',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string|alpha_num|max:11',
        Entity::TYPE                       => 'sometimes|array',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingSibTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_sib',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingSibEditTerminalRules = [
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingUbiTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_ubi',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingUbiEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::CORPORATE                  => 'sometimes|int|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingScbTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_scb',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TPV                        => 'sometimes|in:0,1,2',
    ];

    protected static $netbankingScbEditTerminalRules = [
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingJkbTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_jkb',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingFederalTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_federal',
        Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2     => 'required|string',
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingFederalEditTerminalRules = [
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingRblTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_rbl',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_ACCESS_CODE        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',   // for corporate secrets are in kube-stash
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::CORPORATE                  => 'sometimes|int|in:0,1,2',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingRblEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes|string',
        Entity::CORPORATE                  => 'sometimes|int|in:0,1,2',
        Entity::TYPE                       => 'sometimes|array',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingIndusindTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_indusind',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET      => 'required|string',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TYPE                       => 'sometimes|array',
    ];

    protected static $netbankingPnbTerminalRules = [
        Entity::GATEWAY                    => 'required|in:netbanking_pnb',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingPnbEditTerminalRules = [
        Entity::TPV                         => 'sometimes|in:0,1,2',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TYPE                        => 'sometimes|array',
        Entity::CORPORATE                   => 'sometimes|int|in:0,1,2',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
    ];

    protected static $netbankingCorporationTerminalRules = [
        Entity::GATEWAY                     => 'required|in:netbanking_corporation',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingCorporationEditTerminalRules = [
        Entity::TPV                         => 'sometimes|in:0,1,2',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingCsbTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_CSB,
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|string',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2  => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2      => 'required|string',
        Entity::TPV                         => 'sometimes|in:0,1,2',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingCsbEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2      => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2  => 'sometimes|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::TPV                         => 'sometimes|in:0,1,2',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingBobEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::CORPORATE                   => 'sometimes|int|in:0,1,2',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TPV                         => 'sometimes|in:0,1,2',
    ];

    protected static $netbankingSbiTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_SBI,
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingKotakTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_kotak',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2    => 'sometimes|string',
        Entity::TYPE                    => 'sometimes|array',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::CORPORATE               => 'sometimes|in:0,1,2',
        Entity::ACCOUNT_TYPE            => 'sometimes|string',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingKotakEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET   => 'sometimes|alpha_num|size:16',
        Entity::NETWORK_CATEGORY        => 'sometimes|string|max:30',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::TYPE                    => 'sometimes|array',
        Entity::ACCOUNT_TYPE            => 'sometimes|string',
        Entity::PROCURER                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingSbiEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::TPV                         => 'sometimes|in:0,1,2',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::CORPORATE                   => 'sometimes|int|in:0,1,2',
        Entity::TYPE                        => 'sometimes|array',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingAllahabadTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_ALLAHABAD,
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingAllahabadEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingIdfcEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::TPV                         => 'sometimes|in:0,1,2',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TYPE                        => 'sometimes|array',
    ];

    protected static $netbankingIdfcTerminalRules = [
        Entity::GATEWAY                     => 'required|in:' . Gateway::NETBANKING_IDFC,
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::TPV                         => 'sometimes|in:0,1,2',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::CATEGORY                    => 'sometimes|string|numeric|digits:4',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingUcoTerminalRules = [
        Entity::GATEWAY                 => 'required|in:netbanking_uco',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2    => 'sometimes|string',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $netbankingUcoEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2    => 'sometimes|string',
        Entity::CORPORATE               => 'sometimes|int|in:0,1,2',
        Entity::TPV                     => 'sometimes|in:0,1,2',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::TYPE                    => 'sometimes|array',
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
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $cardFssEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_ACQUIRER            => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::MODE                        => 'sometimes|integer|in:2,3',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::CATEGORY                    => 'sometimes|string|numeric|digits:4',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
    ];

    protected static $iciciTerminalRules = [
             Entity::GATEWAY                    => 'required|in:icici',
             Entity::GATEWAY_MERCHANT_ID        => 'required|alpha_num|size:12',
             Entity::GATEWAY_TERMINAL_ID        => 'required|alpha_num|size:8',
             Entity::GATEWAY_SECURE_SECRET      => 'required|string',
             Entity::GATEWAY_TERMINAL_PASSWORD  => 'required|string',
             Entity::INTERNATIONAL              => 'sometimes|boolean',
             Entity::CURRENCY                   => 'sometimes|array',
            Entity::CARD                       => 'sometimes|boolean|in:1',
            Entity::TYPE                       => 'sometimes|array',
            Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
            Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4',
            Entity::NETWORK_CATEGORY           => 'sometimes|string',
        ];

        protected static $iciciEditTerminalRules = [
            Entity::GATEWAY_MERCHANT_ID        => 'sometimes|alpha_num|size:12',
            Entity::GATEWAY_TERMINAL_ID        => 'sometimes|alpha_num|size:8',
            Entity::GATEWAY_SECURE_SECRET      => 'sometimes|string',
            Entity::GATEWAY_TERMINAL_PASSWORD  => 'sometime|string',
            Entity::INTERNATIONAL              => 'sometimes|boolean',
            Entity::TYPE                       => 'sometimes|array',
            Entity::GATEWAY_ACQUIRER           => 'sometimes|in:icic',
            Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
            Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
            Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
            Entity::CATEGORY                   => 'sometimes|string|numeric|digits:4'
        ];

    protected static $upiHulkEditTerminalRules = [
        Entity::TYPE                       => 'sometimes|array',
        Entity::TPV                        => 'sometimes|in:0,2',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::VPA                        => 'sometimes|string',
        Entity::EXPECTED                   => 'sometimes_if:type.bharat_qr,1|boolean',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes|string|in:proxy,app',
        Entity::ACCOUNT_NUMBER             => 'sometimes|string|max:50',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $paytmTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:paytm',
        Entity::GATEWAY_TERMINAL_ID                     => 'required',
        Entity::GATEWAY_ACCESS_CODE                     => 'required',
        Entity::GATEWAY_MERCHANT_ID                     => 'required',
        Entity::GATEWAY_SECURE_SECRET                   => 'required',
        Entity::TYPE                                    => 'sometimes',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::UPI                                     => 'sometimes|boolean|in:0,1',
        Entity::VPA                                     => 'sometimes|string',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::MODE                                    => 'sometimes|in:2',
        Entity::NETBANKING                              => 'sometimes|boolean|in:0,1',
        Entity::CARD                                    => 'sometimes|boolean|in:0,1',
        Entity::NETWORK_CATEGORY                        => 'sometimes|string|max:30',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
        Entity::TPV                                     => 'sometimes|in:0,1,2',
        Entity::NOTES                                   => 'sometimes|string',
        Entity::ENABLED_WALLETS                         => 'sometimes|array',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
    ];

    protected static $paytmEditTerminalRules = [
        Entity::GATEWAY_TERMINAL_ID        => 'sometimes',
        Entity::GATEWAY_ACCESS_CODE        => 'sometimes',
        Entity::GATEWAY_MERCHANT_ID        => 'sometimes',
        Entity::GATEWAY_MERCHANT_ID2       => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET      => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET2     => 'sometimes',
        Entity::TYPE                       => 'sometimes',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::NETBANKING                 => 'sometimes|boolean|in:0,1',
        Entity::CARD                       => 'sometimes|boolean|in:0,1',
        Entity::UPI                        => 'sometimes|boolean|in:0,1',
        Entity::VPA                        => 'sometimes|string',
        Entity::NOTES                      => 'sometimes|string',
        Entity::NETWORK_CATEGORY           => 'sometimes|string|max:30',
        Entity::TPV                        => 'sometimes|in:0,1,2',
        Entity::ENABLED_WALLETS            => 'sometimes|array',
    ];

    protected static $enachRblTerminalRules = [
        Entity::GATEWAY                     => 'required|in:enach_rbl',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|size:18',
        Entity::GATEWAY_MERCHANT_ID2        => 'required|string',
        Entity::GATEWAY_TERMINAL_ID         => 'required|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|size:11',
        Entity::TYPE                        => 'required|array',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $enachRblEditTerminalRules = [
        Entity::GATEWAY_ACQUIRER            => 'sometimes|in:ratn',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::TYPE                        => 'sometimes|array',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $enachNpciNetbankingTerminalRules = [
        Entity::GATEWAY                     => 'required|in:enach_npci_netbanking',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|string',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::GATEWAY_ACQUIRER            => 'sometimes|string',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $enachNpciNetbankingEditTerminalRules = [
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $editWalletAirtelmoneyTerminalRules = [
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::ACCOUNT_NUMBER              => 'sometimes|string|max:50',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletAmazonpayTerminalRules = [
        Entity::GATEWAY                     => 'required|in:wallet_amazonpay',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_ACCESS_CODE         => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'required|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $walletAmazonpayEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string',
        Entity::NETWORK_CATEGORY            => 'sometimes|string|max:30',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $mobikwikEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $btYesbankTerminalRules = [
        Entity::GATEWAY                     => 'required|in:bt_yesbank',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|max:6',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::BANK_TRANSFER               => 'required|boolean|in:1',
        Entity::ACCOUNT_TYPE                => 'sometimes|string',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $btKotakTerminalRules = [
        Entity::GATEWAY                     => 'required|in:bt_kotak',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|max:6',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::BANK_TRANSFER               => 'bail|required|boolean|in:1',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $btIciciTerminalRules = [
        Entity::GATEWAY                     => 'required|in:bt_icici',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|max:6',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::BANK_TRANSFER               => 'bail|required|boolean|in:1',
        Entity::ACCOUNT_TYPE                => 'sometimes|string',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $btRblTerminalRules = [
        Entity::GATEWAY                     => 'required|in:bt_rbl',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::BANK_TRANSFER               => 'bail|required|boolean|in:1',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $btHdfcEcmsTerminalRules = [
        Entity::GATEWAY                     => 'required|in:bt_hdfc_ecms',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::BANK_TRANSFER               => 'bail|required|boolean|in:1',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $btRblEditTerminalRules = [
        Entity::GATEWAY                     => 'sometimes|in:bt_rbl',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::BANK_TRANSFER               => 'bail|sometimes|boolean|in:1',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $btHdfcEcmsEditTerminalRules = [
        Entity::GATEWAY                     => 'sometimes|in:bt_hdfc_ecms',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'sometimes|array',
        Entity::BANK_TRANSFER               => 'bail|sometimes|boolean|in:1',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $btDashboardTerminalRules = [
        Entity::GATEWAY                     => 'required|in:bt_dashboard',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string|max:6',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::TYPE                        => 'required|array',
        Entity::BANK_TRANSFER               => 'required|boolean|in:1',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $cardlessEmiTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:cardless_emi',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::MODE                                    => 'sometimes|in:1,2,3',
        Entity::CARDLESS_EMI                            => 'required|boolean|in:1',
        Entity::TYPE                                    => 'sometimes|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'sometimes|in:1',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $cardlessEmiEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID                     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::MODE                                    => 'sometimes|in:1,2,3',
        Entity::TYPE                                    => 'sometimes|array',
        Entity::TYPE . '.direct_settlement_with_refund' => 'sometimes|in:1',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::ENABLED                                 => 'sometimes|in:0,1',
        Entity::CATEGORY                                => 'sometimes|string|numeric|digits:4',
    ];

    protected static $credTerminalRules = [
        Entity::GATEWAY                     => 'required|in:cred',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::CRED                        => 'required|boolean|in:1',
        Entity::TYPE                        => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET       => 'required',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $credEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::CRED                        => 'sometimes|boolean|in:1',
        Entity::TYPE                        => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $offlineHdfcTerminalRules = [
        Entity::GATEWAY                                     => 'required|in:offline_hdfc',
        Entity::GATEWAY_MERCHANT_ID                         => 'required|string',
        Entity::GATEWAY_ACQUIRER                            => 'required|string|in:hdfc',
        Entity::OFFLINE                                     => 'required|boolean|in:1',
        Entity::TYPE                                        => 'required|array',
        Entity::TYPE . '.direct_settlement_without_refund'  => 'required|in:1',
        Entity::STATUS                                      => 'sometimes|in:activated,deactivated',
    ];

    protected static $offlineHdfcEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::OFFLINE                     => 'sometimes|boolean|in:1',
        Entity::TYPE                        => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes',
        Entity::STATUS                      => 'sometimes|in:activated,deactivated',
    ];

    protected static $hdfcEzetapTerminalRules = [
        Entity::GATEWAY                                     => 'required|in:hdfc_ezetap',
        Entity::GATEWAY_MERCHANT_ID                         => 'required|string',
        Entity::GATEWAY_ACQUIRER                            => 'sometimes|string|in:hdfc',
        Entity::CARD                                        => 'sometimes|boolean|in:0,1',
        Entity::UPI                                         => 'sometimes|boolean|in:0,1',
        Entity::TYPE                                        => 'required|array',
        Entity::TYPE . '.pos'                               => 'required|in:1',
        Entity::TYPE . '.direct_settlement_with_refund'     => 'required|in:1',
        Entity::STATUS                                      => 'sometimes|in:activated,deactivated',
    ];

    protected static $hdfcEzetapEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::CARD                        => 'sometimes|boolean|in:0,1',
        Entity::UPI                         => 'sometimes|boolean|in:0,1',
        Entity::TYPE                        => 'sometimes|array',
        Entity::STATUS                      => 'sometimes|in:activated,deactivated',
    ];

    protected static $twidTerminalRules = [
        Entity::GATEWAY                     => 'required|in:twid',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::APP                         => 'required|boolean|in:1',
        Entity::TYPE                        => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET       => 'required',
        Entity::GATEWAY_SECURE_SECRET2      => 'required',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $twidEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::APP                         => 'sometimes|boolean|in:1',
        Entity::TYPE                        => 'sometimes|array',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2      => 'sometimes|string',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $paylaterTerminalRules = [
        Entity::GATEWAY                     => 'required|in:paylater',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::PAYLATER                    => 'required|boolean|in:1',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::MODE                        => 'sometimes',
        Entity::TYPE                        => 'sometimes|array',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $paylaterEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2        => 'sometimes|string',
        Entity::GATEWAY_ACQUIRER            => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::MODE                        => 'sometimes',
        Entity::TYPE                        => 'sometimes|array',
        Entity::PROCURER                    => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $updateTerminalsBankRules = [
        Entity::TERMINAL_IDS                => 'required|array',
        Entity::ACTION                      => 'required|string|in:add,remove',
        Entity::BANK                        => 'required_without:banks|string|custom',
        'banks'                             => 'required_without:bank|array|custom',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $assignPlanRules = [
        'input'                         => 'required|array',
        'input.*.'.Entity::TERMINAL_ID  => 'required|string',
        'input.*.'.Entity::PLAN_NAME    => 'required|string',
        'input.*.idempotency_key'       => 'required',
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
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $googlePayTerminalRules = [
        Entity::GATEWAY                    => 'required|in:google_pay',
        Entity::GATEWAY_MERCHANT_ID        => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2       => 'required|string',
        Entity::VPA                        => 'required|string',
        Entity::OMNICHANNEL                => 'required|boolean|in:1',
        Entity::CAPABILITY                 => 'required|in:1',
        Entity::STATUS                     => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $worldlineTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:worldline',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_TERMINAL_ID                     => 'required|string',
        Entity::MC_MPAN                                 => 'required|string|max:255',
        Entity::VISA_MPAN                               => 'required|string',
        Entity::RUPAY_MPAN                              => 'required|string',
        Entity::VPA                                     => 'sometimes|string',
        Entity::EXPECTED                                => 'required|boolean|in:1',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.bharat_qr'                     => 'required|in:1',
        Entity::TYPE . '.non_recurring'                 => 'required|in:1',
        Entity::TYPE . '.direct_settlement_with_refund' => 'required|in:1',
        Entity::CARD                                    => 'sometimes|boolean|in:1',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::STATUS                                  => 'sometimes',
        Entity::ENABLED                                 => 'sometimes',
        Entity::ACCOUNT_NUMBER                          => 'sometimes',
        Entity::IFSC_CODE                               => 'sometimes',
        Entity::GATEWAY_ACQUIRER                        => 'sometimes|string|in:axis',
        Entity::PROCURER                                => 'sometimes|string',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $tokenisationVisaEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID                     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
    ];

    protected static $tokenisationAmexEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID                     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
    ];

    protected static $tokenisationMastercardEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID                     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
    ];

    protected static $tokenisationRupayEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID                     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
    ];

    protected static $tokenisationHdfcEditTerminalRules = [
        Entity::GATEWAY                  => 'required|string|in:tokenisation_hdfc',
        Entity::TYPE                     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID      => 'required|string',
        Entity::GATEWAY_SECURE_SECRET    => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2   => 'required|string',
    ];

    protected static $tokenisationAxisEditTerminalRules = [
        Entity::GATEWAY                  => 'required|string|in:tokenisation_axis',
        Entity::GATEWAY_TERMINAL_ID      => 'sometimes|string',
        Entity::PROCURER                 => 'sometimes|string|in:razorpay,merchant',
    ];

    protected static $worldlineEditTerminalRules = [
        Entity::STATUS                     => 'sometimes|string|in:failed,activated',
        Entity::MC_MPAN                    => 'sometimes|string',
        Entity::VISA_MPAN                  => 'sometimes|string',
        Entity::RUPAY_MPAN                 => 'sometimes|string',
        Entity::VPA                        => 'sometimes|string',
        Entity::PROCURER                   => 'sometimes|string|in:razorpay,merchant',
    ];

    protected static $migrateTerminalsCronRules = [
        Entity::SYNC_STATUS     => 'sometimes|in:not_synced,sync_in_progress,sync_success,sync_failed',
        'count'                 => 'required_with:sync_status|min:1|max:1000',
        'gateway'               => 'sometimes|string',
        'ids'                   => 'required_without:sync_status|array',
        'mode'                  => 'sometimes|string'
    ];

    protected static $instrumentRuleEvalRules = [
        Merchant\Detail\Entity::MERCHANT_ID  => 'required',
        Merchant\Entity::ORG_ID => 'required',
        Merchant\Entity::CATEGORY => 'required',
        Merchant\Entity::CATEGORY2 => 'required',
        Merchant\Detail\Entity::BUSINESS_TYPE => 'required',
        Merchant\Detail\Entity::ACTIVATION_STATUS => 'required',
        Merchant\Entity::WEBSITE => 'sometimes',
        Merchant\Detail\Entity::BUSINESS_SUBCATEGORY => 'required'
    ];

    protected static $hdfcDebitEmiTerminalRules = [
        Entity::GATEWAY              => 'required|in:hdfc_debit_emi',
        Entity::GATEWAY_MERCHANT_ID  => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2 => 'required|string',
        Entity::EMI                  => 'required|boolean',
        Entity::EMI_SUBVENTION       => 'sometimes|in:customer,merchant',
        Entity::STATUS               => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $hdfcDebitEmiEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID  => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2 => 'sometimes|string',
        Entity::EMI                  => 'sometimes|boolean',
        Entity::EMI_SUBVENTION       => 'sometimes|in:customer,merchant',
        Entity::PROCURER             => 'sometimes|string|in:razorpay,merchant',
        Entity::STATUS               => 'sometimes|in:pending,activated,deactivated,failed',
    ];
    protected static $kotakDebitEmiTerminalRules = [
        Entity::GATEWAY               => 'required|in:kotak_debit_emi',
        Entity::GATEWAY_MERCHANT_ID   => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2  => 'required|string',
        Entity::GATEWAY_TERMINAL_ID   => 'required|string',
        Entity::CATEGORY              => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE   => 'required|string',
        Entity::GATEWAY_SECURE_SECRET => 'required|string',
        Entity::EMI                   => 'required|boolean',
        Entity::TYPE                  => 'sometimes|string',
        Entity::EMI_SUBVENTION        => 'sometimes|in:customer,merchant',
        Entity::PLAN_NAME             => 'sometimes|string',
        Entity::STATUS                => 'sometimes|in:pending,activated,deactivated,failed',

    ];
    protected static $kotakDebitEmiEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID   => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2  => 'sometimes|string',
        Entity::CATEGORY              => 'sometimes|string',
        Entity::EMI                   => 'sometimes|boolean',
        Entity::EMI_SUBVENTION        => 'sometimes|in:customer,merchant',
        Entity::STATUS                => 'sometimes|in:pending,activated,deactivated,failed',

    ];

    protected static $indusindDebitEmiTerminalRules = [
        Entity::GATEWAY               => 'required|in:indusind_debit_emi',
        Entity::GATEWAY_ACQUIRER      => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID   => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2  => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID   => 'sometimes|string',
        Entity::CATEGORY              => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE   => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET => 'sometimes|string',
        Entity::MODE                  => 'sometimes|in:3',
        Entity::EMI                   => 'required|boolean',
        Entity::TYPE                  => 'sometimes|string',
        Entity::EMI_SUBVENTION        => 'sometimes|in:customer,merchant',
        Entity::PLAN_NAME             => 'sometimes|string',
        Entity::STATUS                => 'sometimes|in:pending,activated,deactivated,failed',
    ];
    protected static $indusindDebitEmiEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID   => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2  => 'sometimes|string',
        Entity::CATEGORY              => 'sometimes|string',
        Entity::EMI                   => 'sometimes|boolean',
        Entity::EMI_SUBVENTION        => 'sometimes|in:customer,merchant',
        Entity::STATUS                => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    protected static $updateTerminalsBulkRules = [
        'terminal_ids' => 'required|sequential_array',
        'attributes'   => 'sometimes|associative_array',
    ];

    protected static $updateTerminalsBulkAttributesRules = [
        Entity::STATUS               => 'sometimes',
        Entity::ENABLED              => 'present',
    ];

    protected static $netbankingFsbTerminalRules = [
        Entity::GATEWAY                     => 'required|in:netbanking_fsb',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
    ];

    // A gateway is automatic iff terminals for this gateway are created
    // automatically(during payments flow, via API etc).
    // all gateways that are not automatic are classified as manual gateways
    // manual gateways terminals can only be created via dashboard by ops
    // automatic gateway terminals can optionally be created via dasbhoard by ops
    protected static $automaticGateways = [
        Gateway::HITACHI,
        Gateway::WORLDLINE,
    ];

    protected static $manualGatewayMatchAttributes = [
        Entity::GATEWAY,
        Entity::GATEWAY_ACQUIRER,
        Entity::EMI,
        Entity::EMI_DURATION,
        Entity::TYPE,
        Entity::CURRENCY,
        Entity::EMI_SUBVENTION,
        Entity::INTERNATIONAL,
        Entity::VPA,
        Entity::PROCURER,
        Entity::MC_MPAN,
        Entity::VISA_MPAN,
        Entity::RUPAY_MPAN,
        Entity::ACCOUNT_TYPE,
        Entity::CORPORATE
    ];

    protected static $matchAttributesWithGatewayTerminalId = [
        Entity::GATEWAY,
        Entity::GATEWAY_ACQUIRER,
        Entity::EMI,
        Entity::EMI_DURATION,
        Entity::TYPE,
        Entity::CURRENCY,
        Entity::EMI_SUBVENTION,
        Entity::INTERNATIONAL,
        Entity::VPA,
        Entity::PROCURER,
        Entity::MC_MPAN,
        Entity::VISA_MPAN,
        Entity::RUPAY_MPAN,
        Entity::ACCOUNT_TYPE,
        Entity::CORPORATE,
        Entity::GATEWAY_TERMINAL_ID,
    ];

    protected static $automaticGatewayMatchAttributes = [
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
        Entity::RUPAY_MPAN,
        Entity::ACCOUNT_TYPE,
    ];

    protected static $tokenisationVisaTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:tokenisation_visa',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE                     => 'sometimes|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.tokenisation'                  => 'required|in:1',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
    ];

    protected static $tokenisationMastercardTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:tokenisation_mastercard',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE                     => 'sometimes|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.tokenisation'                  => 'required|in:1',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
    ];

    protected static $tokenisationRupayTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:tokenisation_rupay',
        Entity::GATEWAY_MERCHANT_ID                     => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE                     => 'sometimes|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.tokenisation'                  => 'required|in:1',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
    ];

    protected static $tokenisationAxisTerminalRules = [
        Entity::GATEWAY                                 => 'required|in:tokenisation_axis',
        Entity::GATEWAY_MERCHANT_ID                     => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2                    => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID                     => 'required|string',
        Entity::GATEWAY_SECURE_SECRET                   => 'sometimes|string',
        Entity::GATEWAY_ACCESS_CODE                     => 'sometimes|string',
        Entity::TYPE                                    => 'required|array',
        Entity::TYPE . '.tokenisation'                  => 'required|in:1',
        Entity::STATUS                                  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PROCURER                                => 'sometimes|string|in:razorpay,merchant',
        Entity::GATEWAY_TERMINAL_PASSWORD               => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_PASSWORD2              => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2                  => 'sometimes|string',
    ];

    protected static $tokenizeExistingMpansRules = [
        'count'         => 'sometimes|numeric|min:1|max:500',
        'terminal_ids'  => 'sometimes|array',
    ];

    protected static $editTerminalDefaultRules = [
        Entity::STATUS  => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::PLAN_ID => 'sometimes|alpha_num|size:14'
    ];

    protected static $emerchantpayTerminalRules = [
        Entity::GATEWAY                     => 'required|in:emerchantpay',
        Entity::GATEWAY_MERCHANT_ID         => 'required|string',
        Entity::APP                         => 'required|boolean|in:1',
        Entity::GATEWAY_SECURE_SECRET       => 'required|string',
        Entity::GATEWAY_SECURE_SECRET2      => 'required|string',
        Entity::GATEWAY_TERMINAL_ID         => 'required|string',
        Entity::INTERNATIONAL               => 'required|boolean|in:1',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CURRENCY                    => 'sometimes|array',
    ];

    protected static $emerchantpayEditTerminalRules = [
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes|string',
        Entity::APP                         => 'sometimes|boolean|in:1',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes|string',
        Entity::GATEWAY_SECURE_SECRET2      => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes|string',
        Entity::INTERNATIONAL               => 'sometimes|boolean',
        Entity::STATUS                      => 'sometimes|in:pending,activated,deactivated,failed',
        Entity::CURRENCY                    => 'sometimes|array',
    ];

    protected static $umobileCreateTerminalRules = [
        Entity::GATEWAY                                         => 'required|in:umobile',
        Entity::GATEWAY_MERCHANT_ID                             => 'required|string',
        Entity::CARD                                            => 'required|boolean|in:1',
        Entity::GATEWAY_SECURE_SECRET                           => 'required|string',
        Entity::GATEWAY_TERMINAL_ID                             => 'required|string',
        Entity::STATUS                                          => 'sometimes|in:created,pending,activated,deactivated,failed',
        Entity::CURRENCY                                        => 'sometimes|array',
        Entity::MODE                                            => 'required|in:1,2,3',
        Entity::PROCURER                                        => 'sometimes',
        Entity::TYPE                                            => 'required|array',
        Entity::TYPE . Type::DIRECT_SETTLEMENT_WITH_REFUND      => 'required|in:1',
        Entity::TYPE . Type::NON_RECURRING                      => 'required|in:1',
    ];

    protected static $umobileEditTerminalRules = [
        Entity::GATEWAY                                         => 'sometimes|in:umobile',
        Entity::GATEWAY_MERCHANT_ID                             => 'sometimes|string',
        Entity::CARD                                            => 'sometimes|boolean|in:1',
        Entity::GATEWAY_SECURE_SECRET                           => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID                             => 'sometimes|string',
        Entity::STATUS                                          => 'sometimes|in:created,pending,activated,deactivated,failed',
        Entity::CURRENCY                                        => 'sometimes|array',
        Entity::MODE                                            => 'sometimes|in:1,2,3',
        Entity::PROCURER                                        => 'sometimes',
        Entity::TYPE                                            => 'sometimes|array',
        Entity::TYPE . Type::DIRECT_SETTLEMENT_WITH_REFUND      => 'sometimes|in:1',
        Entity::TYPE . Type::NON_RECURRING                      => 'sometimes|in:1',
    ];

    protected static $fpxCreateTerminalRules = [
        Entity::GATEWAY                                         => 'required|in:fpx',
        Entity::GATEWAY_MERCHANT_ID                             => 'required|string',
        Entity::GATEWAY_MERCHANT_ID2                            => 'required|string',
        Entity::STATUS                                          => 'sometimes|in:created,pending,activated,deactivated,failed',
        Entity::CURRENCY                                        => 'sometimes|array',
        Entity::PROCURER                                        => 'sometimes',
    ];

    protected static $fpxEditTerminalRules = [
        Entity::GATEWAY                                         => 'sometimes|in:fpx',
        Entity::GATEWAY_MERCHANT_ID                             => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID2                            => 'sometimes|string',
        Entity::STATUS                                          => 'sometimes|in:created,pending,activated,deactivated,failed',
        Entity::CURRENCY                                        => 'sometimes|array',
        Entity::PROCURER                                        => 'sometimes',
    ];

    protected static $eghlCreateTerminalRules = [
        Entity::GATEWAY                                         => 'required|in:eghl',
        Entity::GATEWAY_MERCHANT_ID                             => 'required|string',
        Entity::GATEWAY_TERMINAL_PASSWORD                       => 'required|string',
        Entity::FPX                                             => 'sometimes|boolean|in:0,1',
        Entity::ENABLED_WALLETS                                 => 'sometimes|array',
        Entity::STATUS                                          => 'sometimes|in:created,pending,activated,deactivated,failed',
        Entity::CURRENCY                                        => 'sometimes|array',
        Entity::PROCURER                                        => 'sometimes',
        Entity::SHARED                                          => 'sometimes|boolean|in:0,1'
    ];

    protected static $eghlEditTerminalRules = [
        Entity::GATEWAY                                         => 'sometimes|in:eghl',
        Entity::GATEWAY_TERMINAL_PASSWORD                       => 'sometimes|string',
        Entity::STATUS                                          => 'sometimes|in:created,pending,activated,deactivated,failed',
        Entity::FPX                                             => 'sometimes|boolean|in:0,1',
        Entity::ENABLED_WALLETS                                 => 'sometimes|array',
        Entity::CURRENCY                                        => 'sometimes|array',
        Entity::PROCURER                                        => 'sometimes',
    ];

    private static $gatewaysWithCommonIndentifiersExceptGatewayTerminalId = [
        Gateway::EMERCHANTPAY
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

        if (( in_array(Type::ONLINE, $type) === true ) and
            ( in_array(Type::OFFLINE, $type) === true ))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Terminal should be either online or offline.',
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


        // Don't unset for paysecure and fulcrum gateway, req is initiated from Terminals Service via merchants/{id}/terminals/internal route
        if (in_array($input['gateway'], [Payment\Gateway::PAYSECURE, Payment\Gateway::FULCRUM]) === false)
        {
            if(isset($input[Entity::GATEWAY_ACQUIRER]))
            {
                $gateway_acquirer = $input[Entity::GATEWAY_ACQUIRER];
            }

            unset(
                $input[Entity::ORG_ID], // unsetting org_id as it is added explicitly in core create() and will be present for all gateways
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
                $input[Entity::ENABLED_WALLETS],
                $input[Entity::MODE],
                $input[Entity::PLAN_ID]);

            if ($input['gateway'] === Payment\Gateway::OFFLINE_HDFC)
            {
                $input[Entity::GATEWAY_ACQUIRER] = $gateway_acquirer;
            }

        }
        $op = $input['gateway'] . '_terminal';
        $var = $this->getRulesVariableName($op);

        if (property_exists(__CLASS__, $var))
        {
            $this->validateInput($op, $input);

            $this->validateVpaLengthIfApplicable($input);
        }
    }

    protected function validateVpaLengthIfApplicable(array $input)
    {
        //
        // $input[Entity::GATEWAY] exists here, already checked in the calling function.
        //
        if ($input[Entity::GATEWAY] !== Gateway::UPI_ICICI)
        {
            return;
        }

        $vpa = $input[Entity::VPA] ?? null;

        if (empty($vpa) === true)
        {
            return;
        }

        $type = $input[Entity::TYPE] ?? [];

        $isBharatQr = (bool)($type[Type::BHARAT_QR] ?? null);

        if ($isBharatQr === true)
        {
            $this->validateInput('vpa_length_for_bqr', [Entity::VPA => $vpa]);

            return;
        }

        $this->validateInput('vpa_length', [Entity::VPA => $vpa]);
    }

    protected function validateTpv($input)
    {
        if (isset($input[Entity::TPV]) === false)
        {
            return;
        }

        $netbanking = $input[Entity::NETBANKING] ?? '0';
        $upi = $input[Entity::UPI] ?? '0';

        if (($netbanking != '1') and
            ($upi != '1'))
        {
            throw new Exception\BadRequestValidationFailureException(
                'tpv is not required and shouldn\'t be sent',
                Entity::TPV);
        }
    }

    protected function validateEnabledWallets($input)
    {

        if (isset($input[Entity::ENABLED_WALLETS]) === false)
        {
            return;
        }

        $gateway = $input[Entity::GATEWAY];

        $enabledWallets = $input[Entity::ENABLED_WALLETS];

        if (in_array($gateway, Payment\Gateway::getAllWalletSupportingGateways()) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'enabled_wallets is not required and shouldn\'t be sent',
                Entity::ENABLED_WALLETS);
        }

        foreach ($enabledWallets as $wallet)
        {
            if (in_array($wallet, Payment\Gateway::getSupportedWalletsForGateway($gateway)) === false) {
                throw new Exception\BadRequestValidationFailureException(
                    'wallets is not supported for the gateway',
                    Entity::ENABLED_WALLETS);
            }
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
            Gateway::WALLET_OPENWALLET,
            Gateway::WALLET_RAZORPAYWALLET, // ?
        ];

        $isNonCardNonMockGateway = ((Gateway::isMethodSupported(Payment\Method::CARD, $gateway)) and
            (in_array($gateway, $nonCardPurchaseExceptions, true)));

        // Amex, OpenWallet, CardlessEmi terminals are always in auth-capture mode
        //
        $authCaptureOnly = [
            Gateway::AMEX,
            Gateway::WALLET_OPENWALLET,
            Gateway::WALLET_RAZORPAYWALLET, // ?
            Gateway::CARDLESS_EMI,
            Gateway::FULCRUM,
        ];

        $PurchaseOnlyGateway = [
            Gateway::WALLET_PAYPAL,
            Gateway::PAYU,
            Gateway::CASHFREE,
            Gateway::CCAVENUE,
            Gateway::PAYTM,
            Gateway::ZAAKPAY,
            Gateway::PINELABS,
            Gateway::INGENICO,
            Gateway::BILLDESK_OPTIMIZER,
            Gateway::AXIS_TOKENHQ,
            Gateway::OPTIMIZER_RAZORPAY
        ];

        //Migs now supports purchase mode as well
        $cardGatewaysWithPurchaseSupport = [
            Gateway::MPGS,
            Gateway::AXIS_MIGS,
            Gateway::ISG,
        ];

        if ((isset($input[Entity::GATEWAY_ACQUIRER]) === true) and ($input[Entity::GATEWAY_ACQUIRER] === Gateway::SEZZLE) and ($gateway === Gateway::CARDLESS_EMI))
        {
            array_push($PurchaseOnlyGateway, Gateway::CARDLESS_EMI);

            unset($authCaptureOnly[array_search(Gateway::CARDLESS_EMI,$authCaptureOnly)]);
        }

        if ((isset($input[Entity::GATEWAY_ACQUIRER]) === true) and ($input[Entity::GATEWAY_ACQUIRER] === Gateway::ACQUIRER_AXIS) and ($gateway === Gateway::FULCRUM))
        {
            array_push($PurchaseOnlyGateway, Gateway::FULCRUM);

            unset($authCaptureOnly[array_search(Gateway::FULCRUM,$authCaptureOnly)]);
        }

        $isPurchaseSupportedCardGateway = ((Gateway::isMethodSupported(Payment\Method::CARD, $gateway)) and
            (in_array($gateway, $cardGatewaysWithPurchaseSupport, true)));

        $isAuthCaptureOnlyGateway = (in_array($gateway, $authCaptureOnly, true));

        $isPurchaseOnlyGateway = (in_array($gateway, $PurchaseOnlyGateway, true));

        if ((($isFirstDataNon3DS === true) or ($isNonCardNonMockGateway === true)) and
            ($mode !== Mode::PURCHASE))
        {
            throw new Exception\BadRequestValidationFailureException(
                'FirstData Non-3DS terminals must be in Purchase mode',
                Entity::GATEWAY);
        }
        else if (($isPurchaseOnlyGateway === true) and
            ($mode !== Mode::PURCHASE))
        {
            throw new Exception\BadRequestValidationFailureException(
                $input['gateway'] . ' terminals must be in Purchase mode',
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
            ($isPurchaseSupportedCardGateway === false) and
            ($isPurchaseOnlyGateway === false) and
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

        if (($input[Entity::GATEWAY] === Gateway::BAJAJ) or
            ($input[Entity::GATEWAY] === Gateway::HDFC_DEBIT_EMI) or
            ($input[Entity::GATEWAY] === Gateway::AXIS_MIGS) or
            ($input[Entity::GATEWAY] === Gateway::PAYU) or
            ($input[Entity::GATEWAY] === Gateway::KOTAK_DEBIT_EMI) or
            ($input[Entity::GATEWAY] === Gateway::INDUSIND_DEBIT_EMI))
        {
            return;
        }

        if ((isset($input[Entity::MERCHANT_ID]) === false))
        {
            throw new Exception\LogicException(
                'EMI Terminals must have merchant id',
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
        $newMatch = array_only($new->toArray(), $this->getMatchAttributes($new));
        $existingMatch = array_only($existing->toArray(), $this->getMatchAttributes($existing));

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

        if ($this->shouldSkipGatewayValidations($terminal, $input) === true)
        {
            return;
        }

        if (in_array($terminal->getGateway(), self::$editTerminalGateways) || in_array($terminal->getGateway(), Gateway::TOKENISATION_GATEWAYS))
        {
            unset($input[Entity::PLAN_ID]);
            $gateway = $terminal->getGateway();
            $this->validateInput($gateway . '_edit_terminal', $input);
        }
        else
        {
            if (isset($input[Entity::STATUS]) === true or isset($input[Entity::PLAN_ID]) === true)
            {
                $this->validateInput('edit_terminal_default', $input);

                return;
            }

            throw new Exception\BadRequestValidationFailureException(
                'Editing not defined for terminal of gateway: ' . $terminal->getGateway());
        }
    }

    public function shouldSkipGatewayValidations($terminal, $input)
    {
        $app = App::getFacadeRoot();

        $ba = $app['basicauth'];

        if (($terminal != null) and ($terminal->merchant->isFeatureEnabled(Feature\Constants::ONLY_DS) === true))
        {
            return false;
        }

        if (($terminal != null) and
            ($terminal->merchant->isFeatureEnabled(Feature\Constants::RAAS) === true) and
            (in_array($terminal->gateway, Payment\Gateway::SKIP_TPV_EDIT_OPTIMIZER_GATEWAYS, true)) and
            (in_array('optimizer', $terminal->getType()) === true))
        {
            return true;
        }

        if(isset($input['terminal_edit_god_mode']) === true &&
            ($input['terminal_edit_god_mode'] === true || $input['terminal_edit_god_mode']) == '1')
        {
            return true;
        }

        return false;
    }

    public function validateUpdateTerminalsBankAndBanksShouldNotBePresentTogether($input)
    {
        if ((isset($input['banks']) === true) and
            (isset($input[Entity::BANK]) === true))
        {
            $message = "'banks' and 'bank' should not be sent at the same time";


            throw new Exception\BadRequestValidationFailureException($message);
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

    protected function getMatchAttributes(Entity $entity)
    {
        $gateway = $entity->getGateway();

        if ((in_array($gateway, self::$automaticGateways) === true) or
            ($entity->getMerchantId() === Merchant\Account::SHARED_ACCOUNT))
        {
           return self::$automaticGatewayMatchAttributes;
        }

        if(self::isGatewayWithCommonIdentifiersExceptGatewayTerminalId($gateway)) {
            return self::$matchAttributesWithGatewayTerminalId;
        }

        return self::$manualGatewayMatchAttributes;
    }

    /**
    * In case of Gateways that have multiple terminals with all the properties same
    * it becomes difficult to differentiate between the terminals.
    * the identifier `gateway_terminal_id` is used to differentiate between them.
    * This method checks the gateway name for which `gateway_terminal_id` will be different
    */
    private static function isGatewayWithCommonIdentifiersExceptGatewayTerminalId($gateway):bool{
        return in_array($gateway, self::$gatewaysWithCommonIndentifiersExceptGatewayTerminalId);
    }

    protected static function validateBanks($attribute, array $value)
    {
        foreach ($value as $bank)
        {
            self::validateBank($attribute, $bank);
        }
    }

    protected static function validateBank($attribute, $value)
    {
        // skipping this check for some Cardless EMI providers as these are not banks and are not added in IFSC repo
        if (CardlessEmi::isNonBankingProvider($value) === true)
        {
            return;
        }

        if (Bank\IFSC::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid bank name in input: '. $value);
        }
    }
}
