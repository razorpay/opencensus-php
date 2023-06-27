<?php

namespace RZP\Models\Payment;

use App;
use RZP\Exception;

use RZP\Models\Currency\Currency;
use RZP\Models\Emi;
use RZP\Constants\Country;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Settlement;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use Razorpay\IFSC\IFSC as BaseIFSC;
use RZP\Models\Payment\Processor\Upi;
use RZP\Models\Merchant\Methods\Entity;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Payment\Processor\IntlBankTransfer;

class Gateway
{
    const AMEX                   = 'amex';
    const ATOM                   = 'atom';
    const PAYU                   = 'payu';
    const CASHFREE               = 'cashfree';
    const ZAAKPAY                = 'zaakpay';
    const CCAVENUE               = 'ccavenue';
    const PINELABS               = 'pinelabs';
    const INGENICO               = 'ingenico';
    const BILLDESK_OPTIMIZER     = 'billdesk_optimizer';
    const BHARAT_QR              = 'bharat_qr';
    const AXIS_GENIUS            = 'axis_genius';
    const AXIS_MIGS              = 'axis_migs';
    const AXIS_TOKENHQ           = 'axis_tokenhq';
    const BILLDESK               = 'billdesk';
    const MPI_BLADE              = 'mpi_blade';
    const MPI_ENSTAGE            = 'mpi_enstage';
    const CYBERSOURCE            = 'cybersource';
    const FPX                    = 'fpx';
    const EBS                    = 'ebs';
    const ICICI                  = 'icici';
    const ICICI_EMI              = 'icici_emi';
    const INDUSIND               = 'indusind';
    const KOTAK                  = 'kotak';
    const YESB                   = 'yesb';
    const RBL                    = 'rbl';
    const RBL_JSW                = 'rbl_jsw';
    const AXIS                   = 'axis';
    const IDFC                   = 'idfc';
    const ESIGNER_DIGIO          = 'esigner_digio';
    const ESIGNER_LEGALDESK      = 'esigner_legaldesk';
    const ENACH_RBL              = 'enach_rbl';
    const ENACH_NPCI_NETBANKING  = 'enach_npci_netbanking';
    const FIRST_DATA             = 'first_data';
    const FULCRUM                = 'fulcrum';
    const HDFC                   = 'hdfc';
    const SBIN                   = 'sbin';
    const HITACHI                = 'hitachi';
    const MOBIKWIK               = 'mobikwik';
    const NETBANKING_SIB         = 'netbanking_sib';
    const NETBANKING_CBI         = 'netbanking_cbi';
    const NETBANKING_AIRTEL      = 'netbanking_airtel';
    const NETBANKING_AXIS        = 'netbanking_axis';
    const NETBANKING_IDFC        = 'netbanking_idfc';
    const NETBANKING_UBI         = 'netbanking_ubi';
    const NETBANKING_SCB         = 'netbanking_scb';
    const NETBANKING_JKB         = 'netbanking_jkb';
    const NETBANKING_FEDERAL     = 'netbanking_federal';
    const NETBANKING_EQUITAS     = 'netbanking_equitas';
    const NETBANKING_BOB         = 'netbanking_bob';
    const NETBANKING_BOB_V2      = 'netbanking_bob_v2';
    const NETBANKING_VIJAYA      = 'netbanking_vijaya';
    const NETBANKING_HDFC        = 'netbanking_hdfc';
    const NETBANKING_CUB         = 'netbanking_cub';
    const NETBANKING_IBK         = 'netbanking_ibk';
    const NETBANKING_IDBI        = 'netbanking_idbi';
    const NETBANKING_CORPORATION = 'netbanking_corporation';
    const NETBANKING_ICICI       = 'netbanking_icici';
    const NETBANKING_INDUSIND    = 'netbanking_indusind';
    const NETBANKING_KOTAK       = 'netbanking_kotak';
    const NETBANKING_RBL         = 'netbanking_rbl';
    const NETBANKING_CSB         = 'netbanking_csb';
    const NETBANKING_PNB         = 'netbanking_pnb';
    const NETBANKING_OBC         = 'netbanking_obc';
    const NETBANKING_SBI         = 'netbanking_sbi';
    const NETBANKING_ALLAHABAD   = 'netbanking_allahabad';
    const NETBANKING_CANARA      = 'netbanking_canara';
    const NETBANKING_YESB        = 'netbanking_yesb';
    const NETBANKING_KVB         = 'netbanking_kvb';
    const NETBANKING_SVC         = 'netbanking_svc';
    const NETBANKING_JSB         = 'netbanking_jsb';
    const NETBANKING_IOB         = 'netbanking_iob';
    const NETBANKING_FSB         = 'netbanking_fsb';
    const NETBANKING_DCB         = 'netbanking_dcb';
    const NETBANKING_AUSF        = 'netbanking_ausf';
    const NETBANKING_DLB         = 'netbanking_dlb';
    const NETBANKING_NSDL        = 'netbanking_nsdl';
    const NETBANKING_BDBL        = 'netbanking_bdbl';
    const NETBANKING_SARASWAT    = 'netbanking_saraswat';
    const NETBANKING_UCO         = 'netbanking_uco';
    const NETBANKING_UJJIVAN     = 'netbanking_ujjivan';
    const NETBANKING_TMB         = 'netbanking_tmb';
    const NETBANKING_KARNATAKA   = 'netbanking_karnataka';
    const NETBANKING_DBS         = 'netbanking_dbs';
    const NACH_CITI              = 'nach_citi';
    const NACH_ICICI             = 'nach_icici';
    const PAYTM                  = 'paytm';
    const SEZZLE                 = 'sezzle';
    const SHARP                  = 'sharp';
    const UPI_MINDGATE           = 'upi_mindgate';
    const UPI_SBI                = 'upi_sbi';
    const UPI_AXIS               = 'upi_axis';
    const UPI_ICICI              = 'upi_icici';
    const UPI_HULK               = 'upi_hulk';
    const UPI_RBL                = 'upi_rbl';
    const UPI_AXISOLIVE          = 'upi_axisolive';
    const UPI_YESBANK            = 'upi_yesbank';
    const UPI_KOTAK              = 'upi_kotak';
    const UPI_RZPRBL             = 'upi_rzprbl';
    const AEPS_ICICI             = 'aeps_icici';
    const ISG                    = 'isg';
    const PAYSECURE              = 'paysecure';
    const UPI_AIRTEL             = 'upi_airtel';
    const WORLDLINE              = 'worldline';
    const HDFC_EZETAP            = 'hdfc_ezetap';
    const UPI_CITI               = 'upi_citi';
    const UPI_JUSPAY             = 'upi_juspay';
    const BILLDESK_SIHUB         = 'billdesk_sihub';
    const MANDATE_HQ             = 'mandate_hq';
    const RUPAY_SIHUB            = 'rupay_sihub';
    const EGHL                   = 'eghl';

    const CARD_FSS               = 'card_fss';
    const CHECKOUT_DOT_COM       = 'checkout_dot_com';

    const WALLET_AIRTELMONEY        = 'wallet_airtelmoney';
    const WALLET_AMAZONPAY          = 'wallet_amazonpay';
    const WALLET_FREECHARGE         = 'wallet_freecharge';
    const WALLET_BAJAJ              = 'wallet_bajaj';
    const WALLET_JIOMONEY           = 'wallet_jiomoney';
    const WALLET_SBIBUDDY           = 'wallet_sbibuddy';
    const WALLET_MPESA              = 'wallet_mpesa';
    const WALLET_OLAMONEY           = 'wallet_olamoney';
    const WALLET_OPENWALLET         = 'wallet_openwallet';
    const WALLET_RAZORPAYWALLET     = 'wallet_razorpaywallet';
    const WALLET_PAYUMONEY          = 'wallet_payumoney';
    const WALLET_PAYZAPP            = 'wallet_payzapp';
    const WALLET_PHONEPE            = 'wallet_phonepe';
    const WALLET_PHONEPESWITCH      = 'wallet_phonepeswitch';
    const WALLET_PAYPAL             = 'wallet_paypal';

    const CARDLESS_EMI       = 'cardless_emi';
    const PAYLATER           = 'paylater';
    const GETSIMPL           = 'getsimpl';
    const PAYLATER_ICICI     = 'paylater_icici';
    const CRED               = 'cred';
    const OFFLINE_HDFC       = 'offline_hdfc';
    const TWID               = 'twid';
    const LAZYPAY            = 'lazypay';
    const TRUSTLY            = 'trustly';
    const EMERCHANTPAY       = 'emerchantpay';
    const POLI               = 'poli';
    const SOFORT             = 'sofort';
    const GIROPAY            = 'giropay';
    const UMOBILE            = 'umobile';

    const ACQUIRER_HDFC         = 'hdfc';
    const ACQUIRER_ICIC         = 'icic';
    const ACQUIRER_AXIS         = 'axis';
    const ACQUIRER_AMEX         = 'amex';
    const ACQUIRER_FSS          = 'fss';
    const ACQUIRER_RATN         = 'ratn';
    const ACQUIRER_YESB         = 'yesb';
    const ACQUIRER_BARB         = 'barb';
    const ACQUIRER_SBIN         = 'sbin';
    const ACQUIRER_CITI         = 'citi';
    const ACQUIRER_KOTAK        = 'kotak';
    const ACQUIRER_OCBC         = 'ocbc';

    const NOT_SUPPORTED      = 'not_supported';
    const SUPPORTED          = 'supported';
    const NODAL_YESBANK      = 'nodal_yesbank';
    const NODAL_ICICI        = 'nodal_icici';

    // M2P - Integration for Fund Transfers to Debit Cards
    const M2P                = 'm2p';

    const BT_YESBANK         = 'bt_yesbank';
    const BT_KOTAK           = 'bt_kotak';
    const BT_ICICI           = 'bt_icici';
    const BT_DASHBOARD       = 'bt_dashboard';
    const BT_RBL             = 'bt_rbl';
    const BT_RBL_JSW         = 'bt_rbl_jsw';
    const BT_HDFC_ECMS       = 'bt_hdfc_ecms';

    // this is a dummy gateway. this is required to save MIDs & TIDs of a merchant.
    const EMI_SBI            = 'emi_sbi';
    const EMI_HSBC           = 'emi_hsbc';
    const BAJAJFINSERV       = 'bajajfinserv';
    const GOOGLE_PAY         = 'google_pay';
    const VISA_SAFE_CLICK    = 'visasafeclick';

    // Debit emi gateways
    const HDFC_DEBIT_EMI     = 'hdfc_debit_emi';
    const KOTAK_DEBIT_EMI    = 'kotak_debit_emi';
    const INDUSIND_DEBIT_EMI = 'indusind_debit_emi';
    const CURRENCY_CLOUD     = 'currency_cloud';

    const VA_SWIFT = 'swift';
    const VA_USD   = 'usd';
    const SWIFT = 'SWIFT';
    const OPTIMIZER_RAZORPAY = "optimizer_razorpay";

    //
    // Constant used to store the response of various refund functions, used to prepare response for scrooge/
    // success and status_code defined the status of refund and also category of refund if it is retriable or not.
    //
    // Stores boolean value indicating refund was successful or not
    const SUCCESS                   = 'success';
    // Stores error code if refund is failed at gateway side
    const STATUS_CODE               = 'status_code';
    // Stores gateway through which refund is processed. Say for FTA refunds, it will be yesbank
    const REFUND_GATEWAY            = 'refund_gateway';
    // Stores array of gateway related keys such as refund_id, auth_code
    const GATEWAY_KEYS              = 'gateway_keys';
    // Stores raw gateway response in string format.
    const GATEWAY_RESPONSE          = 'gateway_response';
    const GATEWAY_VERIFY_RESPONSE   = 'gateway_verify_response';

    //
    // If for a merchant, the esigner gateway is not assigned via config,
    // the below gateway would be used
    //
    const DEFAULT_ESIGNER_GATEWAY = self::ESIGNER_LEGALDESK;

    const BAJAJ = 'bajajfinserv';

    const MPGS = 'mpgs';


    //
    // Direct Settlement improved org name
    //
    const NPCI      = 'NPCI';
    const PAYPAL    = 'paypal';

    // tokenisation gateways
    const TOKENISATION_VISA        = 'tokenisation_visa';
    const TOKENISATION_MASTERCARD  = 'tokenisation_mastercard';
    const TOKENISATION_RUPAY       = 'tokenisation_rupay';
    const TOKENISATION_HDFC        = 'tokenisation_hdfc';
    const TOKENISATION_AMEX        = 'tokenisation_amex';
    const TOKENISATION_AXIS        = 'tokenisation_axis';

    const GATEWAY_ACQUIRERS = [
        self::AXIS_MIGS    => [self::ACQUIRER_AXIS, self::ACQUIRER_HDFC],
        self::HDFC         => [self::ACQUIRER_HDFC],
        self::CYBERSOURCE  => [self::ACQUIRER_AXIS, self::ACQUIRER_HDFC, self::ACQUIRER_YESB],
        self::FIRST_DATA   => [self::ACQUIRER_ICIC],
        self::AMEX         => [self::ACQUIRER_AMEX],
        self::AEPS_ICICI   => [self::ACQUIRER_ICIC],
        self::CARD_FSS     => [self::ACQUIRER_FSS, self::ACQUIRER_BARB, self::ACQUIRER_SBIN],
        self::HITACHI      => [self::ACQUIRER_RATN],
        self::ENACH_RBL    => [self::ACQUIRER_RATN],
        self::UPI_HULK     => [self::ACQUIRER_HDFC],
        self::CARDLESS_EMI => [CardlessEmi::ZESTMONEY, CardlessEmi::EARLYSALARY, CardlessEmi::FLEXMONEY, CardlessEmi::WALNUT369, CardlessEmi::SEZZLE],
        self::PAYLATER     => [PayLater::EPAYLATER, PayLater::GETSIMPL, PayLater::ICICI, PayLater::FLEXMONEY, Paylater::LAZYPAY],
        self::WORLDLINE    => [self::ACQUIRER_AXIS],
        self::MPGS         => [self::ACQUIRER_HDFC, self::ACQUIRER_AXIS, self::ACQUIRER_AMEX, self::ACQUIRER_ICIC, self::ACQUIRER_OCBC],
        self::UPI_JUSPAY   => [self::ACQUIRER_AXIS],
        self::PAYU         => [self::PAYU],
        self::HDFC_EZETAP  => [self::ACQUIRER_HDFC],
        self::PAYSECURE    => [self::ACQUIRER_AXIS],
        self::AXIS_TOKENHQ => [self::ACQUIRER_AXIS],
        self::ICICI        => [self::ACQUIRER_ICIC],
    ];

    const GATEWAY_ACQUIRER_COUNTRY_MAP = [
        Country::IN    => [
            self::ACQUIRER_AXIS,
            self::ACQUIRER_HDFC,
            self::ACQUIRER_YESB,
            self::ACQUIRER_ICIC,
            self::ACQUIRER_FSS,
            self::ACQUIRER_BARB,
            self::ACQUIRER_SBIN,
            self::ACQUIRER_RATN,
            CardlessEmi::ZESTMONEY,
            CardlessEmi::EARLYSALARY,
            CardlessEmi::FLEXMONEY,
            CardlessEmi::WALNUT369,
            CardlessEmi::SEZZLE,
            PayLater::EPAYLATER,
            PayLater::GETSIMPL,
            PayLater::ICICI,
            PayLater::FLEXMONEY,
            Paylater::LAZYPAY,
            self::ACQUIRER_AMEX,
            self::PAYU
        ],
        Country::MY    => [self::ACQUIRER_OCBC]
    ];

    const POWER_WALLETS = [
        Wallet::MOBIKWIK,
        Wallet::PAYUMONEY,
        // Wallet::OLAMONEY,
        Wallet::FREECHARGE,
        // Wallet::MPESA,
    ];

    const TOKENISATION_GATEWAYS = [
        self::TOKENISATION_VISA,
        self::TOKENISATION_MASTERCARD,
        self::TOKENISATION_RUPAY,
        self::TOKENISATION_HDFC,
        self::TOKENISATION_AMEX,
        self::TOKENISATION_AXIS,
    ];

    const TOKENISATION_CRYPTOGRAM_NOT_REQUIRED_GATEWAYS = [
        self::AXIS_TOKENHQ,
    ];

    //
    // Temporarily uses a different constant. Ideally POWER_WALLETS should be
    // used. Once auto debit functionality is implemented for all power wallets
    // @todo: Deprecate it.
    //
    const AUTO_DEBIT_POWER_WALLETS = array(
        self::WALLET_FREECHARGE,
    );

    /**
     * These are the wallets that support both
     * auth as well as power wallet flow
     */
    const AUTH_AND_POWER_WALLETS = [
        // Commenting this out temporarily
        // Wallet::MPESA,
    ];

    const TOPUP_GATEWAYS = [
        self::MOBIKWIK,
        self::WALLET_PAYUMONEY,
        self::WALLET_OLAMONEY,
        self::WALLET_FREECHARGE,
        self::WALLET_BAJAJ,
        self::SHARP,
    ];

    const REFUND_TIMEOUT_HANDLED_GATEWAYS = [
        self::WALLET_FREECHARGE,
        self::BILLDESK,
    ];

    const MULTIPLE_TERMINALS_FOR_SAME_GATEWAY_MERCHANT_GATEWAYS = [
        self::WORLDLINE,
        self::HDFC,
    ];

    // TODO: Add gateway and gateway_acquirer map to fix
    // this for other card gateways
    // If you're adding any new DS Gateway then also add gateway bank/org name in DIRECT_SETTLEMENT_ORG_NAME map
    const DIRECT_SETTLEMENT_GATEWAYS = [
        self::AMEX                  => self::AMEX,
        self::MPGS                  => self::AMEX,
        self::AXIS_MIGS             => [
            'default'           => self::HDFC,
            self::ACQUIRER_HDFC => self::HDFC,
            self::ACQUIRER_AXIS => self::AXIS,
            self::ACQUIRER_YESB => self::YESB,
        ],
        self::CARDLESS_EMI => [
            'default'    => self::SEZZLE,
            self::SEZZLE => self::SEZZLE
        ],
        self::CYBERSOURCE           => [
            'default'           => self::HDFC,
            self::ACQUIRER_HDFC => self::HDFC,
            self::ACQUIRER_AXIS => self::AXIS,
        ],
        self::HDFC                  => self::HDFC,
        self::BT_HDFC_ECMS          => self::HDFC,
        self::ISG                   => [
            'default'           => self::HDFC,
            self::ACQUIRER_HDFC => self::HDFC,
        ],
        self::BILLDESK              => self::BILLDESK,
        self::NETBANKING_AXIS       => self::AXIS,
        self::NETBANKING_HDFC       => self::HDFC,
        self::NETBANKING_ICICI      => self::ICICI,
        self::NETBANKING_KOTAK      => self::KOTAK,
        self::NETBANKING_RBL        => self::RBL,
        self::PAYTM                 => self::PAYTM,
        self::UPI_AXIS              => self::AXIS,
        self::UPI_ICICI             => self::ICICI,
        self::UPI_MINDGATE          => self::HDFC,
        self::WALLET_PAYPAL         => self::WALLET_PAYPAL,
        self::WORLDLINE             => [
            'default'           => self::AXIS,
            self::ACQUIRER_AXIS => self::AXIS,
        ],
        self::WALLET_PAYZAPP        => self::WALLET_PAYZAPP,
        self::ENACH_NPCI_NETBANKING => self::ENACH_NPCI_NETBANKING,
        self::PAYU                  => self::PAYU,
        self::ATOM                  => self::ATOM,
        self::CASHFREE              => self::CASHFREE,
        self::ZAAKPAY               => self::ZAAKPAY,
        self::NETBANKING_YESB       => self::YESB,
        self::CCAVENUE              => self::CCAVENUE,
        self::PINELABS              => self::PINELABS,
        self::INGENICO              => self::INGENICO,
        self::BILLDESK_OPTIMIZER    => self::BILLDESK_OPTIMIZER,
        self::NETBANKING_IDFC       => self::IDFC,
        self::PAYSECURE             => self::ACQUIRER_AXIS,
        self::NETBANKING_SBI        => self::SBIN,
        self::NETBANKING_INDUSIND   => self::INDUSIND,
        self::OFFLINE_HDFC          => self::HDFC,
        self::HDFC_EZETAP           => self::HDFC,
        self::UMOBILE               => self::UMOBILE,
        self::FPX                   => self::FPX,
        self::OPTIMIZER_RAZORPAY    => self::OPTIMIZER_RAZORPAY
    ];

    // Map of DS settlement entity with DS Bank/org name
    const DIRECT_SETTLEMENT_ORG_NAME = [
        self::AMEX                  => self::AMEX,
        self::HDFC                  => self::HDFC,
        self::AXIS                  => self::AXIS,
        self::BILLDESK              => self::BILLDESK,
        self::ICICI                 => self::ICICI,
        self::INDUSIND              => self::INDUSIND,
        self::KOTAK                 => self::KOTAK,
        self::RBL                   => self::RBL,
        self::RBL_JSW               => self::RBL_JSW,
        self::PAYTM                 => self::PAYTM,
        self::PAYU                  => self::PAYU,
        self::CASHFREE              => self::CASHFREE,
        self::YESB                  => self::YESB,
        self::CCAVENUE              => self::CCAVENUE,
        self::ATOM                  => self::ATOM,
        self::IDFC                  => self::IDFC,
        self::SBIN                  => self::SBIN,
        self::SEZZLE                => self::SEZZLE,
        self::WALLET_PAYPAL         => self::PAYPAL,
        self::WALLET_PAYZAPP        => self::HDFC,
        self::ENACH_NPCI_NETBANKING => self::NPCI,
        self::ZAAKPAY               => self::ZAAKPAY,
        self::PINELABS              => self::PINELABS,
        self::INGENICO              => self::INGENICO,
        self::BILLDESK_OPTIMIZER    => self::BILLDESK_OPTIMIZER,
        self::OFFLINE_HDFC          => self::HDFC,
        self::HDFC_EZETAP           =>self::HDFC,
        self::PAYSECURE             => self::AXIS,
        self::UMOBILE               => self::UMOBILE,
        self::FPX                   => self::FPX,
        self::OPTIMIZER_RAZORPAY    => self::OPTIMIZER_RAZORPAY
    ];



     /**
     * These are the gateways that support
     * multiple international apps flows
     */
    const MULTIPLE_APPS_SUPPORTED_GATEWAYS = [
        self::EMERCHANTPAY,
    ];

    /**
     * These are the apps that require
     * address for processing
     */
    const ADDRESS_REQUIRED_APPS= [
        self::TRUSTLY,
        self::POLI,
        self::SOFORT,
        self::GIROPAY,
    ];

    /**
     * These are the apps that process
     * international payments
     */
    const INTERNATIONAL_ENABLED_APPS= [
        self::TRUSTLY,
        self::POLI,
        self::SOFORT,
        self::GIROPAY,
    ];

    /**
     * These are the apps that require
     * dynamic currency conversion for processing
     */
    const DCC_REQUIRED_APPS= [
        self::TRUSTLY,
        self::POLI,
        self::SOFORT,
        self::GIROPAY,
    ];

    /**
     * Currency at 0 index for each app will be chosen
     * as default currency, i.e selected by default on
     * frontend and others will be shown in dropdown
     * to choose from
     */

    const CURRENCIES_SUPPORTED_BY_APPS = [
        self::TRUSTLY   => [Currency::EUR,Currency::GBP],
        self::POLI      => [Currency::AUD],
        self::SOFORT    => [Currency::EUR],
        self::GIROPAY   => [Currency::EUR],
    ];

    const REFUND_NOT_SUPPORTED_APPS = [
        self::POLI
    ];

    /**
     * These are the gateways that require
     * address and name for processing the payment.
     * address and name is collected on basis of feature flag address_name_required
     * ADDRESS_REQUIRED_APPS Map Contains apps which comes under gateway emerchantpay
     */

    /* Exceptionally Address will be collected from gateway via API Call for Currency Cloud
     * since its a Bank Transfer and we will know details of Sender from gateway directly and
     * we will store in address table
     */

    const ADDRESS_NAME_REQUIRED_GATEWAYS = [
        self::EMERCHANTPAY,
        self::CURRENCY_CLOUD
    ];

    /**
    * Gateways for which we can validate the refunds
    * if they are successful after they are 'initiated'
    */
    const UNKNOWN_REFUNDS_VALIDATION_GATEWAYS = [
        self::WALLET_FREECHARGE
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    const MCC_FILTER_GATEWAYS = [
        self::HDFC,
        self::HITACHI,
        self::CARD_FSS,
    ];

    /**
     * Gateways for which we are skipping auth code validation to authorize the Payments
     * As these gateways are not sending auth code once payment done.
     */
    const SKIP_AUTH_CODE_GATEWAYS = [
        self::CASHFREE,
        self::PAYU,
        self::PAYTM,
        self::CCAVENUE,
        self::ZAAKPAY,
        self::BILLDESK_OPTIMIZER,
        self::INDUSIND_DEBIT_EMI,
        self::PINELABS,
    ];

    const OPTIMIZER_CARD_GATEWAYS = [
        self::CASHFREE,
        self::PAYU,
        self::CCAVENUE,
        self::ZAAKPAY,
        self::PINELABS,
        self::INGENICO,
        self::BILLDESK_OPTIMIZER,
        self::OPTIMIZER_RAZORPAY,
    ];

    const OPTIMIZER_TOKENIZATION_SUPPORTED_GATEWAYS = [
        self::CASHFREE,
        self::PAYU,
        self::CCAVENUE,
        self::ZAAKPAY,
        self::PINELABS,
        self::INGENICO,
        self::BILLDESK_OPTIMIZER,
        self::PAYTM,
        self::OPTIMIZER_RAZORPAY,
    ];

    const SKIP_TPV_EDIT_OPTIMIZER_GATEWAYS = [
        self::UPI_MINDGATE,
        self::UPI_AXIS
    ];

    /**
    * Gateways for which we may need to force authorize payments
    * since their verify API's stop working after a certain time
    */
    const FORCE_AUTHORIZE_GATEWAYS = [
        self::UPI_SBI,
        self::CARD_FSS,
        self::AXIS_MIGS,
        self::FIRST_DATA,
        self::WALLET_JIOMONEY,
        self::NETBANKING_RBL,
        self::NETBANKING_INDUSIND,
        self::NETBANKING_PNB,
        self::NETBANKING_OBC,
        self::NETBANKING_ICICI,
        self::NETBANKING_AXIS,
        self::NETBANKING_AIRTEL,
        self::WALLET_AIRTELMONEY,
        self::WALLET_OPENWALLET,
        self::WALLET_RAZORPAYWALLET,
        self::CARDLESS_EMI,
        self::NETBANKING_CORPORATION,
        self::HITACHI,
        self::NETBANKING_SBI,
        self::HDFC_DEBIT_EMI,
        self::KOTAK_DEBIT_EMI,
        self::FULCRUM,

        // UPI HULK is TEMPORARY, As payment are still failed on hulk and we can't do much there,
        //If you are seeing this after Sep'18, Please report to gateway payments team
        self::UPI_HULK,
        self::UPI_ICICI,
        self::UPI_MINDGATE,
        self::UPI_AXIS,
        self::NETBANKING_SVC,
        self::ATOM,
        self::UPI_AIRTEL,
        self::NETBANKING_KVB,
        self::NETBANKING_YESB,
        self::NETBANKING_IOB,
        self::NETBANKING_JSB,
        self::NETBANKING_DCB,
        self::NETBANKING_IDFC,
        self::NETBANKING_CBI,
        self::NETBANKING_UBI,
        self::NETBANKING_JKB,
        self::NETBANKING_KOTAK,
        self::NETBANKING_SIB,
        self::NETBANKING_SCB,
        self::NETBANKING_AUSF,
        self::NETBANKING_DLB,
        self::NETBANKING_NSDL,
        self::TWID,
        self::NETBANKING_CSB,
        self::NETBANKING_BDBL,
        self::NETBANKING_UCO,
        self::NETBANKING_DBS,
        self::NETBANKING_SARASWAT,
        self::NETBANKING_HDFC,
        self::NETBANKING_UJJIVAN,
        self::OFFLINE_HDFC,
        self::NETBANKING_TMB,
        self::ISG,
        self::UPI_YESBANK,
    ];

    const FORCE_AUTHORIZE_FAILED_SYNC_GATEWAYS = [
        Payment\Gateway::KOTAK_DEBIT_EMI,
    ];

    /**
     * Allow force authorization on Gateways
     * which are onboarded on ART for reconciliation
     */
    const ART_FORCE_AUTHORIZE_UPI_GATEWAYS = [
        self::UPI_SBI,
        self::UPI_ICICI,
        self::UPI_YESBANK,
    ];

    /**
     * List of gateways that we wish to attempt this with.
     * This should eventually cover all API based refund
     * gateways.
     *
     * These gateways should have verifyRefund implemented.
     * and be allowed to perform it.
     * */
    const REFUND_RETRY_GATEWAYS = [
        Payment\Gateway::GETSIMPL,
        Payment\Gateway::WALLET_PAYPAL,
        Payment\Gateway::CYBERSOURCE,
        Payment\Gateway::BILLDESK,
        Payment\Gateway::EBS,
        Payment\Gateway::HDFC,
        Payment\Gateway::MOBIKWIK,
        Payment\Gateway::WALLET_OLAMONEY,
        Payment\Gateway::AXIS_MIGS,
        Payment\Gateway::AMEX,
        Payment\Gateway::WALLET_JIOMONEY,
        Payment\Gateway::WALLET_SBIBUDDY,
        Payment\Gateway::WALLET_AIRTELMONEY,
        Payment\Gateway::FIRST_DATA,
        Payment\Gateway::UPI_ICICI,
        Payment\Gateway::UPI_AXIS,
        Payment\Gateway::UPI_AXISOLIVE,
        Payment\Gateway::WALLET_PAYZAPP,
        Payment\Gateway::WALLET_MPESA,
        Payment\Gateway::CARD_FSS,
        Payment\Gateway::ICICI,
        Payment\Gateway::WALLET_PAYUMONEY,
        Payment\Gateway::WALLET_FREECHARGE,
        Payment\Gateway::WALLET_AMAZONPAY,
        Payment\Gateway::WALLET_OPENWALLET,
        Payment\Gateway::WALLET_RAZORPAYWALLET,
        Payment\Gateway::UPI_MINDGATE,
        Payment\Gateway::HITACHI,
        Payment\Gateway::UPI_HULK,
        Payment\Gateway::NETBANKING_AIRTEL,
        Payment\Gateway::NETBANKING_PNB,
        Payment\Gateway::ATOM,
        Payment\Gateway::SHARP,
        Payment\Gateway::UPI_AIRTEL,
        Payment\Gateway::CARDLESS_EMI,
        Payment\Gateway::PAYTM,
        Payment\Gateway::PAYSECURE,
        Payment\Gateway::CRED,
        Payment\Gateway::UPI_YESBANK,
        Payment\Gateway::NETBANKING_DLB,
        Payment\Gateway::NETBANKING_TMB,
        Payment\Gateway::NETBANKING_UJJIVAN,
        Payment\Gateway::TWID,
    ];

    // Bank such as Netbanking Canara enforces to send fee in request.
    const FEE_IN_AUTHORIZE_GATEWAYS = [
      Payment\Gateway::NETBANKING_CANARA
    ];

    // Please keep this list sorted. The list of Live Banks in API E-Mandate is available at https://www.npci.org.in/nach-e-mandates-new

    // banks supported by enach_npci_netbanking gateway for auth type netbanking
    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    const ENACH_NPCI_NB_AUTH_NETBANKING_BANKS = [
        IFSC::AIRP,
        IFSC::ANDB,
        IFSC::APGB,
        IFSC::AUBL,
        Netbanking::BARB_R,
        IFSC::BDBL,
        IFSC::CBIN,
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CNRB,
        IFSC::COSB,
        IFSC::CSBK,
        IFSC::DBSS,
        IFSC::DCBL,
        IFSC::DEUT,
        IFSC::DLXB,
        IFSC::ESFB,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::HSBC,
        IFSC::IBKL,
        IFSC::ICIC,
        IFSC::IDFB,
        IFSC::IDIB,
        IFSC::INDB,
        IFSC::IOBA,
        IFSC::JIOP,
        IFSC::JSFB,
        IFSC::KARB,
        IFSC::KKBK,
        IFSC::KVBL,
        IFSC::KVGB,
        IFSC::MAHB,
        IFSC::ORBC,
        Netbanking::PUNB_R,
        IFSC::PSIB,
        IFSC::PYTM,
        IFSC::RATN,
        IFSC::SBIN,
        IFSC::SCBL,
        IFSC::SIBL,
        IFSC::SYNB,
        IFSC::TMBL,
        IFSC::UBIN,
        IFSC::UCBA,
        IFSC::UJVN,
        IFSC::USFB,
        IFSC::UTBI,
        IFSC::UTIB,
        IFSC::VARA,
        IFSC::YESB,
    ];

    // banks supported by enach_npci_netbanking gateway for auth type card
    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    const ENACH_NPCI_NB_AUTH_CARD_BANKS = [
        IFSC::ACUX,
        IFSC::AIRP,
        IFSC::ANDB,
        IFSC::AUBL,
        Netbanking::BARB_R,
        IFSC::BKID,
        IFSC::CBIN,
        IFSC::CITI,
        IFSC::CLBL,
        IFSC::CNRB,
        IFSC::CNSX,
        IFSC::CSBK,
        IFSC::DBSS,
        IFSC::DCBL,
        IFSC::DEUT,
        IFSC::DLXB,
        IFSC::ESFB,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::IBKL,
        IFSC::ICIC,
        IFSC::IDFB,
        IFSC::IDIB,
        IFSC::INDB,
        IFSC::JSFB,
        IFSC::JUCX,
        IFSC::KARB,
        IFSC::KKBK,
        IFSC::KNSB,
        IFSC::MAHB,
        IFSC::MHSX,
        IFSC::NCBL,
        Netbanking::PUNB_R,
        IFSC::PYTM,
        IFSC::RATN,
        IFSC::SBIN,
        IFSC::SCBL,
        IFSC::SHIX,
        IFSC::SIBL,
        IFSC::SPCB,
        IFSC::SURY,
        IFSC::TMBL,
        IFSC::UBIN,
        IFSC::USFB,
        IFSC::UTBI,
        IFSC::UTIB,
        IFSC::YESB,
        IFSC::ZCBL,
        IFSC::PSIB,
    ];

    // disabled for all auth types
    const EMANDATE_REGISTRATION_DISABLED_BANKS = [
        IFSC::UTBI,
        IFSC::ORBC,
        IFSC::ANDB,
    ];

    const NB_EMANDATE_REGISTRATION_DISABLED_BANKS = [
        IFSC::JIOP,
        IFSC::PSIB,
        IFSC::SYNB,
        IFSC::UJVN,
    ];

    const EMANDATE_NB_DIRECT_BANKS = [
        IFSC::ICIC,
        IFSC::UTIB,
        IFSC::HDFC,
        IFSC::SBIN
    ];

    const EMANDATE_DIRECT_INTEGRATION_GATEWAYS = [
        Gateway::NETBANKING_AXIS,
        Gateway::NETBANKING_ICICI,
        Gateway::NETBANKING_HDFC,
        Gateway::NETBANKING_SBI,
    ];

    const EMANDATE_NB_DIRECT_DEBIT_BANK = [
        IFSC::ICIC,
        IFSC::HDFC,
    ];

    const EMANDATE_NB_DIRECT_DEBIT_GATEWAY = [
        Gateway::NETBANKING_ICICI,
        Gateway::NETBANKING_HDFC,
    ];

    // The 2 commented banks are mentioned at the bottom
    // with their retail versions
    // Please keep this list sorted
    // You can find the latest PDF version
    // at https://www.npci.org.in/nach-e-mandates

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    const EMANDATE_AADHAAR_BANKS = [
        IFSC::AACX,
        IFSC::ABDX,
        IFSC::ABHY,
        IFSC::ABSB,
        IFSC::ACBX,
        IFSC::ACKX,
        IFSC::ACUX,
        IFSC::ADBX,
        IFSC::ADCC,
        IFSC::ADCX,
        IFSC::AGCX,
        IFSC::AGUX,
        IFSC::AHMX,
        IFSC::AHUX,
        IFSC::AJKB,
        IFSC::AJPX,
        IFSC::AJSX,
        IFSC::ALAX,
        IFSC::ALLX,
        IFSC::AMAX,
        IFSC::AMCB,
        IFSC::AMRX,
        IFSC::ANDB,
        IFSC::ANSX,
        IFSC::APBL,
        IFSC::APCX,
        IFSC::APGB,
        IFSC::APGX,
        IFSC::APJX,
        IFSC::APMC,
        IFSC::APMX,
        IFSC::APNX,
        IFSC::APRX,
        IFSC::APSX,
        IFSC::ASBL,
        IFSC::ASBX,
        IFSC::ASHX,
        IFSC::ASKX,
        IFSC::ASOX,
        IFSC::ASSX,
        IFSC::AUBL,
        IFSC::AUCB,
        IFSC::AUCX,
        IFSC::AVDX,
        IFSC::AWCX,
        IFSC::BACB,
        IFSC::BACX,
        IFSC::BANX,
        // IFSC::BARB,
        IFSC::BARX,
        IFSC::BASX,
        IFSC::BBLX,
        IFSC::BCBM,
        IFSC::BDUX,
        IFSC::BGBX,
        IFSC::BHAX,
        IFSC::BHCX,
        IFSC::BHDX,
        IFSC::BHJX,
        IFSC::BHMX,
        IFSC::BHOX,
        IFSC::BHSX,
        IFSC::BHUX,
        IFSC::BJUX,
        IFSC::BKCX,
        IFSC::BKDN,
        IFSC::BKDX,
        IFSC::BKID,
        IFSC::BMCB,
        IFSC::BMPX,
        IFSC::BNBX,
        IFSC::BNPA,
        IFSC::BNSX,
        IFSC::BORX,
        IFSC::BRMX,
        IFSC::BSBX,
        IFSC::BURX,
        IFSC::BUZX,
        IFSC::CALX,
        IFSC::CBHX,
        IFSC::CBIN,
        IFSC::CHAS,
        IFSC::CHAX,
        IFSC::CHBX,
        IFSC::CHDX,
        IFSC::CHSX,
        IFSC::CHTX,
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CJAX,
        IFSC::CMCX,
        IFSC::CMLX,
        IFSC::CNRB,
        IFSC::COCX,
        IFSC::CORP,
        IFSC::COSB,
        IFSC::CSBK,
        IFSC::CSBX,
        IFSC::CTBX,
        IFSC::CURX,
        IFSC::CZCX,
        IFSC::DBAX,
        IFSC::DBSS,
        IFSC::DCBL,
        IFSC::DCDX,
        IFSC::DCKX,
        IFSC::DDBX,
        IFSC::DENS,
        IFSC::DEUT,
        IFSC::DEVX,
        IFSC::DGBX,
        IFSC::DHUX,
        IFSC::DICX,
        IFSC::DLXB,
        IFSC::DNSB,
        IFSC::DOBX,
        IFSC::DSCB,
        IFSC::DSPX,
        IFSC::DSUX,
        IFSC::DTCX,
        IFSC::EDSX,
        IFSC::ESFB,
        IFSC::EUCX,
        IFSC::FDRL,
        IFSC::FGCB,
        IFSC::FINX,
        IFSC::FSCX,
        IFSC::GACX,
        IFSC::GCBX,
        IFSC::GCUX,
        IFSC::GDCX,
        IFSC::GHPX,
        IFSC::GNCX,
        IFSC::GPCX,
        IFSC::GRAX,
        IFSC::GSBX,
        IFSC::GSCB,
        IFSC::GSSX,
        IFSC::GUCX,
        IFSC::HAMX,
        IFSC::HDFC,
        IFSC::HMNX,
        IFSC::HPCX,
        IFSC::HPSX,
        IFSC::HSBC,
        IFSC::HSDX,
        IFSC::HUCH,
        IFSC::IBKL,
        IFSC::ICBL,
        IFSC::ICIC,
        IFSC::ICMX,
        IFSC::IDFB,
        IFSC::IDIB,
        IFSC::IDUX,
        IFSC::IMPX,
        IFSC::INDB,
        IFSC::IOBA,
        IFSC::IPCX,
        IFSC::IPSX,
        IFSC::ISMX,
        IFSC::ITCX,
        IFSC::ITDX,
        IFSC::IUCB,
        IFSC::JANA,
        IFSC::JASB,
        IFSC::JCDX,
        IFSC::JHAX,
        IFSC::JMCX,
        IFSC::JMHX,
        IFSC::JMPX,
        IFSC::JODX,
        IFSC::JONX,
        IFSC::JPCB,
        IFSC::JSBL,
        IFSC::JSBP,
        IFSC::JSCX,
        IFSC::JSMX,
        IFSC::JUCX,
        IFSC::JVCX,
        IFSC::KAAX,
        IFSC::KAIJ,
        IFSC::KALX,
        IFSC::KAMX,
        IFSC::KARB,
        IFSC::KARX,
        IFSC::KASX,
        IFSC::KBCX,
        IFSC::KCOB,
        IFSC::KCUB,
        IFSC::KCUX,
        IFSC::KDCX,
        IFSC::KDIX,
        IFSC::KHAX,
        IFSC::KHUX,
        IFSC::KKBK,
        IFSC::KKMX,
        IFSC::KLGB,
        IFSC::KMCX,
        IFSC::KMSX,
        IFSC::KNBX,
        IFSC::KNPX,
        IFSC::KOCX,
        IFSC::KOYX,
        IFSC::KRDX,
        IFSC::KRNX,
        IFSC::KSCB,
        IFSC::KSUX,
        IFSC::KTBX,
        IFSC::KUCX,
        IFSC::KUKX.
        IFSC::KUNS,
        IFSC::KVBL,
        IFSC::KVCX,
        IFSC::KVGB,
        IFSC::LATX,
        IFSC::LBMX,
        IFSC::LCCX,
        IFSC::LDPX,
        IFSC::LKMX,
        IFSC::LOKX,
        IFSC::MABL,
        IFSC::MAHB,
        IFSC::MAKX,
        IFSC::MALX,
        IFSC::MBCX,
        IFSC::MDEX,
        IFSC::MERX,
        IFSC::MGBX,
        IFSC::MGCX,
        IFSC::MHNX,
        IFSC::MHSX,
        IFSC::MLCG,
        IFSC::MOGX,
        IFSC::MPCX,
        IFSC::MPDX,
        IFSC::MPRX,
        IFSC::MRTX,
        IFSC::MSAX,
        IFSC::MSBL,
        IFSC::MSCI,
        IFSC::MSCX,
        IFSC::MSNU,
        IFSC::MSNX,
        IFSC::MSOX,
        IFSC::MUSX,
        IFSC::MVCX,
        IFSC::MYAX,
        IFSC::MZCX,
        IFSC::NAIX,
        IFSC::NALX,
        IFSC::NASX,
        IFSC::NAVX,
        IFSC::NAWX,
        IFSC::NBBX,
        IFSC::NBMX,
        IFSC::NCBX,
        IFSC::NCCX,
        IFSC::NDCX,
        IFSC::NGRX,
        IFSC::NGSX,
        IFSC::NICB,
        IFSC::NILX,
        IFSC::NJCX,
        IFSC::NJSX,
        IFSC::NMCB,
        IFSC::NMCX,
        IFSC::NOBX,
        IFSC::NOIX,
        IFSC::NSBB,
        IFSC::NSBX,
        IFSC::NSGX,
        IFSC::NSIX,
        IFSC::NTBL,
        IFSC::NVSX,
        IFSC::ODGB,
        IFSC::OIBA,
        IFSC::OMCX,
        IFSC::ONSX,
        IFSC::ORBC,
        IFSC::OSMX,
        IFSC::PABX,
        IFSC::PALX,
        IFSC::PASX,
        IFSC::PATX,
        IFSC::PBGX,
        IFSC::PCBL,
        IFSC::PCTX,
        IFSC::PCUX,
        IFSC::PDUX,
        IFSC::PGBX,
        IFSC::PGCX,
        IFSC::PJSB,
        IFSC::PLUX,
        IFSC::PMCB,
        IFSC::PMEC,
        IFSC::PMNX,
        IFSC::PNSX,
        IFSC::PPBX,
        IFSC::PRPX,
        IFSC::PRTH,
        IFSC::PSBX,
        IFSC::PSCX,
        IFSC::PSRX,
        IFSC::PTCX,
        IFSC::PTSX,
        IFSC::PUBX,
        IFSC::PUGX,
        // IFSC::PUNB,
        IFSC::PVCX,
        IFSC::PYTM,
        IFSC::QUCX,
        IFSC::RACX,
        IFSC::RAKX,
        IFSC::RAMX,
        IFSC::RATN,
        IFSC::RBBX,
        IFSC::RCCX,
        IFSC::RCUX,
        IFSC::RDNX,
        IFSC::REBX,
        IFSC::RECX,
        IFSC::RGCX,
        IFSC::RGSX,
        IFSC::RNSX,
        IFSC::RRSX,
        IFSC::RZSX,
        IFSC::SACX,
        IFSC::SADX,
        IFSC::SAGX,
        IFSC::SASA,
        IFSC::SATX,
        IFSC::SAVX,
        IFSC::SBMX,
        IFSC::SBNX,
        IFSC::SBUX,
        IFSC::SCBL,
        IFSC::SCCX,
        IFSC::SCNX,
        IFSC::SCSX,
        IFSC::SCUX,
        IFSC::SDBX,
        IFSC::SDCB,
        IFSC::SDCX,
        IFSC::SDHX,
        IFSC::SDSX,
        IFSC::SEUX,
        IFSC::SEWX,
        IFSC::SGSX,
        IFSC::SHCX,
        IFSC::SHUX,
        IFSC::SIBL,
        IFSC::SIRX,
        IFSC::SISX,
        IFSC::SJSX,
        IFSC::SKCX,
        IFSC::SKNX,
        IFSC::SKUX,
        IFSC::SMNX,
        IFSC::SMUX,
        IFSC::SMVC,
        IFSC::SNAX,
        IFSC::SNBX,
        IFSC::SNCX,
        IFSC::SNDX,
        IFSC::SNGX,
        IFSC::SNKX,
        IFSC::SONX,
        IFSC::SPBX,
        IFSC::SPCB,
        IFSC::SPCX,
        IFSC::SPSX,
        IFSC::SRCB,
        IFSC::SSBX,
        IFSC::SSDX,
        IFSC::SSKX,
        IFSC::SSLX,
        IFSC::STRX,
        IFSC::SULX,
        IFSC::SUMX,
        IFSC::SURY,
        IFSC::SUTB,
        IFSC::SVCB,
        IFSC::SVNX,
        IFSC::SVSX,
        IFSC::SWMX,
        IFSC::SYNB,
        IFSC::TACX,
        IFSC::TADX,
        IFSC::TAMX,
        IFSC::TASX,
        IFSC::TBCX,
        IFSC::TBMX,
        IFSC::TBSB,
        IFSC::TBSX,
        IFSC::TCUB,
        IFSC::TDCB,
        IFSC::TDIX,
        IFSC::TDMX,
        IFSC::TECX,
        IFSC::TEHX,
        IFSC::TGMB,
        IFSC::THOX,
        IFSC::TIRX,
        IFSC::TJNX,
        IFSC::TJSB,
        IFSC::TKUX,
        IFSC::TMBL,
        IFSC::TMSX,
        IFSC::TNKX,
        IFSC::TNMX,
        IFSC::TPDX,
        IFSC::TSAB,
        IFSC::TSDX,
        IFSC::TSIX,
        IFSC::TSUX,
        IFSC::TTLX,
        IFSC::TTUX,
        IFSC::TUCL,
        IFSC::TUMX,
        IFSC::TUNX,
        IFSC::TUOX,
        IFSC::TVDX,
        IFSC::TVPX,
        IFSC::UBBX,
        IFSC::UBGX,
        IFSC::UBIN,
        IFSC::UCBA,
        IFSC::UCBS,
        IFSC::UCBX,
        IFSC::UCCX,
        IFSC::UCDX,
        IFSC::UCUX,
        IFSC::UJVN,
        IFSC::UKGX,
        IFSC::UMAX,
        IFSC::UMCX,
        IFSC::UMSX,
        IFSC::UNIX,
        IFSC::UNSX,
        IFSC::UROX,
        IFSC::USFB,
        IFSC::UTIB,
        IFSC::UTZX,
        IFSC::UUCX,
        IFSC::VARA,
        IFSC::VCBX,
        IFSC::VCCX,
        IFSC::VDYX,
        IFSC::VEDX,
        IFSC::VERX,
        IFSC::VIJX,
        IFSC::VIKX,
        IFSC::VJSX,
        IFSC::VSBX,
        IFSC::VSCX,
        IFSC::VUCX,
        IFSC::VVCX,
        IFSC::WAIX,
        IFSC::WKGX,
        IFSC::XJKG,
        IFSC::YADX,
        IFSC::YESB,
        IFSC::YLNX,
        IFSC::ZSBL,
        IFSC::ZSGX,
        IFSC::ZSHX,
        IFSC::ZSMX,
        Netbanking::BARB_R,
        Netbanking::PUNB_R,
    ];

    const AADHAAR_EMANDATE_REGISTRATION_DISABLED_BANKS = [
        IFSC::ANDB,
        IFSC::BGBX,
        IFSC::BHSX,
        IFSC::BKDN,
        IFSC::BNPA,
        IFSC::CHAS,
        IFSC::CHAX,
        IFSC::CHDX,
        IFSC::CHSX,
        IFSC::CITI,
        IFSC::CMCX,
        IFSC::CORP,
        IFSC::CURX,
        IFSC::DBSS,
        IFSC::DCDX,
        IFSC::DCKX,
        IFSC::DGBX,
        IFSC::DICX,
        IFSC::ESFB,
        IFSC::FGCB,
        IFSC::FSCX,
        IFSC::GCUX,
        IFSC::GDCX,
        IFSC::GSSX,
        IFSC::IUCB,
        IFSC::JSCX,
        IFSC::JUCX,
        IFSC::KAAX,
        IFSC::KAIJ,
        IFSC::KARX,
        IFSC::KASX,
        IFSC::KCOB,
        IFSC::KHAX,
        IFSC::KNPX,
        IFSC::KOCX,
        IFSC::KRDX,
        IFSC::KUNS,
        IFSC::KVGB,
        IFSC::LCCX,
        IFSC::MBCX,
        IFSC::MERX,
        IFSC::MHSX,
        IFSC::MOGX,
        IFSC::MPRX,
        IFSC::MSNU,
        IFSC::NAIX,
        IFSC::NALX,
        IFSC::NCBX,
        IFSC::NDCX,
        IFSC::NOBX,
        IFSC::NOIX,
        IFSC::NSGX,
        IFSC::PABX,
        IFSC::PMCB,
        IFSC::PRTH,
        IFSC::PSRX,
        IFSC::PUGX,
        IFSC::RAMX,
        IFSC::RNSX,
        IFSC::SAGX,
        IFSC::SCCX,
        IFSC::SDBX,
        IFSC::SHUX,
        IFSC::SJSX,
        IFSC::SSDX,
        IFSC::STRX,
        IFSC::SVNX,
        IFSC::SWMX,
        IFSC::SYNB,
        IFSC::TADX,
        IFSC::TBCX,
        IFSC::TDIX,
        IFSC::TECX,
        IFSC::TKUX,
        IFSC::TPDX,
        IFSC::TSAB,
        IFSC::TSDX,
        IFSC::TSIX,
        IFSC::TVDX,
        IFSC::UCBS,
        IFSC::UCBX,
        IFSC::UCUX,
        IFSC::UKGX,
        IFSC::UMSX,
        //todo: After removing USFB from Disable banks list needs to add condition to route through UJVN.
        IFSC::UJVN,
        IFSC::USFB,
        IFSC::UTZX,
        IFSC::UUCX,
        IFSC::VEDX,
        IFSC::VIJX,
        //Netbanking::BARB_R,
    ];

    // Esigner Digio is added here just for test cases
    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    const EMANDATE_AADHAAR_GATEWAYS = [
        Gateway::ESIGNER_DIGIO,
        Gateway::ESIGNER_LEGALDESK,
        Gateway::ENACH_RBL,
    ];

    const GATEWAY_TO_SETTLEMENT_CURRENCY_MAPPING = [
        self::CHECKOUT_DOT_COM => [Currency::USD],
        self::EMERCHANTPAY => [Currency::EUR,Currency::GBP,Currency::AUD],
        self::CURRENCY_CLOUD => [Currency::USD, Currency::EUR, Currency::GBP, Currency::AUD, Currency::CAD],
    ];

    public static $scroogeFileBasedRefundGatewaysWithTimestamps = [
        Payment\Gateway::NETBANKING_VIJAYA      => 1575484200,
        Payment\Gateway::NETBANKING_OBC         => 1575484200,
        Payment\Gateway::NETBANKING_CANARA      => 1575484200,
        Payment\Gateway::NETBANKING_CORPORATION => 1575484200,
        Payment\Gateway::NETBANKING_RBL         => 1575982238,
        Payment\Gateway::NETBANKING_CUB         => 1575982238,
        Payment\Gateway::NETBANKING_SIB         => 1575982238,
        Payment\Gateway::NETBANKING_SCB         => 1576002600,
        Payment\Gateway::NETBANKING_ALLAHABAD   => 1576002600,
        Payment\Gateway::NETBANKING_BOB         => 1576060200,
        Payment\Gateway::NETBANKING_FEDERAL     => 1576060200,
        Payment\Gateway::NETBANKING_YESB        => 1576060200,
        Payment\Gateway::NETBANKING_INDUSIND    => 1576060200,
        Payment\Gateway::NETBANKING_CBI         => 1576060200,
        Payment\Gateway::NETBANKING_KVB         => 1576060200,
        Payment\Gateway::NETBANKING_IDFC        => 1576060200,
        Payment\Gateway::NETBANKING_ICICI       => 1576146600,
        Payment\Gateway::NETBANKING_AXIS        => 1576146600,
        Payment\Gateway::NETBANKING_EQUITAS     => 1576578600,
        Payment\Gateway::NETBANKING_IBK         => 1576578600,
        Payment\Gateway::UPI_SBI                => 1576578600,
        Payment\Gateway::NETBANKING_HDFC        => 1577097000,
        Payment\Gateway::NETBANKING_KOTAK       => 1578479400,
        Payment\Gateway::NETBANKING_CSB         => 1588694400,
        Payment\Gateway::NETBANKING_SBI         => 1618237799,
        Payment\Gateway::NETBANKING_SVC         => 1578479400,
        Payment\Gateway::PAYLATER_ICICI         => 1593685800,
        Payment\Gateway::NETBANKING_IDBI        => 1578479400,
        Payment\Gateway::NETBANKING_IOB         => 1578479400,
        Payment\Gateway::NETBANKING_FSB         => 1591900200,
        Payment\Gateway::NETBANKING_JKB         => 1593685800,
        Payment\Gateway::NETBANKING_DCB         => 1593907200,
        Payment\Gateway::NETBANKING_UBI         => 1607059163,
        Payment\Gateway::NETBANKING_PNB         => 1609936200,
        Payment\Gateway::NETBANKING_DLB         => 1609936200,
        Payment\Gateway::NETBANKING_TMB         => 1640249582,
        Payment\Gateway::NETBANKING_KARNATAKA   => 1640249582,
        Payment\Gateway::NETBANKING_NSDL        => 1618511400,
        Payment\Gateway::NETBANKING_BDBL        => 1618511400,
        Payment\Gateway::NETBANKING_SARASWAT    => 1618511400,
        Payment\Gateway::NETBANKING_UCO         => 1618511400,
        Payment\Gateway::NETBANKING_UJJIVAN     => 1618511400,
        Payment\Gateway::NETBANKING_DBS         => 1618511400,
        Payment\Gateway::UPI_AIRTEL             => 1675967400,
        Payment\Gateway::UPI_YESBANK            => 1675967400,
    ];

    public static $channels = [
        self::AMEX                => Settlement\Channel::KOTAK,
        self::ATOM                => Settlement\Channel::ATOM,
        self::AXIS_GENIUS         => Settlement\Channel::KOTAK,
        self::AXIS_MIGS           => Settlement\Channel::KOTAK,
        self::MPI_BLADE           => Settlement\Channel::KOTAK,
        self::MPI_ENSTAGE         => Settlement\Channel::KOTAK,
        self::BILLDESK            => Settlement\Channel::KOTAK,
        self::EBS                 => Settlement\Channel::KOTAK,
        self::ENACH_RBL           => Settlement\Channel::KOTAK,
        self::HDFC                => Settlement\Channel::KOTAK,
        self::MOBIKWIK            => Settlement\Channel::KOTAK,
        self::PAYTM               => Settlement\Channel::KOTAK,
        self::SHARP               => Settlement\Channel::KOTAK,
        self::NETBANKING_HDFC     => Settlement\Channel::KOTAK,
        self::NETBANKING_KOTAK    => Settlement\Channel::KOTAK,
        self::NETBANKING_ICICI    => Settlement\Channel::KOTAK,
        self::NETBANKING_AIRTEL   => Settlement\Channel::KOTAK,
        self::NETBANKING_AXIS     => Settlement\Channel::KOTAK,
        self::NETBANKING_FEDERAL  => Settlement\Channel::KOTAK,
        self::NETBANKING_RBL      => Settlement\Channel::KOTAK,
        self::NETBANKING_INDUSIND => Settlement\Channel::KOTAK,
        self::NETBANKING_PNB      => Settlement\Channel::KOTAK,
        self::WALLET_PAYZAPP      => Settlement\Channel::KOTAK,
        self::WALLET_PAYUMONEY    => Settlement\Channel::KOTAK,
        self::WALLET_OLAMONEY     => Settlement\Channel::KOTAK,
        self::WALLET_FREECHARGE   => Settlement\Channel::KOTAK,
        self::WALLET_AIRTELMONEY  => Settlement\Channel::KOTAK,
        self::WALLET_JIOMONEY     => Settlement\Channel::KOTAK,
        self::WALLET_OPENWALLET   => Settlement\Channel::KOTAK,
        self::WALLET_RAZORPAYWALLET => Settlement\Channel::KOTAK,
        self::WALLET_MPESA        => Settlement\Channel::KOTAK,
        self::FIRST_DATA          => Settlement\Channel::KOTAK,
        self::UPI_MINDGATE        => Settlement\Channel::KOTAK,
        self::UPI_ICICI           => Settlement\Channel::KOTAK,
        self::UPI_AXIS            => Settlement\Channel::KOTAK,
        self::UPI_HULK            => Settlement\Channel::KOTAK,
        self::AEPS_ICICI          => Settlement\Channel::KOTAK,
        self::CYBERSOURCE         => Settlement\Channel::KOTAK,
        self::HITACHI             => Settlement\Channel::KOTAK,
        self::PAYSECURE           => Settlement\Channel::KOTAK,
    ];

    /**
     * Mapping of method to gateways supporting that method
     * either in live or test mode.
     *
     * @var array
     */
    public static $methodMap = [
        Method::CARD => [
            self::HDFC,
            self::AXIS_MIGS,
            self::AXIS_GENIUS,
            self::PAYTM,
            self::AMEX,
            self::CYBERSOURCE,
            self::PAYSECURE,
            self::FIRST_DATA,
            self::MPI_BLADE,
            self::MPI_ENSTAGE,
            self::HITACHI,
            self::CARD_FSS,
            self::ICICI,
            self::MPGS,
            self::ISG,
            self::PAYU,
            self::CASHFREE,
            self::ZAAKPAY,
            self::CCAVENUE,
            self::PINELABS,
            self::CHECKOUT_DOT_COM,
            self::FULCRUM,
            self::INGENICO,
            self::BILLDESK_OPTIMIZER,
            self::HDFC_EZETAP,
            self::OPTIMIZER_RAZORPAY,
        ],

        Method::NETBANKING => [
            self::PAYTM,
            self::BILLDESK,
            self::EBS,
            self::ATOM,
            self::PAYU,
            self::CASHFREE,
            self::CCAVENUE,
            self::ZAAKPAY,
            self::NETBANKING_SIB,
            self::NETBANKING_CBI,
            self::NETBANKING_IDFC,
            self::NETBANKING_ICICI,
            self::NETBANKING_CUB,
            self::NETBANKING_IBK,
            self::NETBANKING_IDBI,
            self::NETBANKING_BOB,
            self::NETBANKING_HDFC,
            self::NETBANKING_KOTAK,
            self::NETBANKING_AIRTEL,
            self::NETBANKING_UBI,
            self::NETBANKING_SCB,
            self::NETBANKING_JKB,
            self::NETBANKING_AXIS,
            self::NETBANKING_FEDERAL,
            self::NETBANKING_RBL,
            self::NETBANKING_INDUSIND,
            self::NETBANKING_PNB,
            self::NETBANKING_CSB,
            self::NETBANKING_ALLAHABAD,
            self::NETBANKING_EQUITAS,
            self::NETBANKING_SBI,
            self::NETBANKING_CANARA,
            self::NETBANKING_YESB,
            self::NETBANKING_KVB,
            self::NETBANKING_SVC,
            self::NETBANKING_JSB,
            self::NETBANKING_IOB,
            self::NETBANKING_FSB,
            self::NETBANKING_DCB,
            self::NETBANKING_AUSF,
            self::NETBANKING_DLB,
            self::NETBANKING_NSDL,
            self::NETBANKING_BDBL,
            self::NETBANKING_UJJIVAN,
            self::NETBANKING_SARASWAT,
            self::NETBANKING_UCO,
            self::NETBANKING_TMB,
            self::NETBANKING_KARNATAKA,
            self::NETBANKING_DBS,
            self::INGENICO,
            self::BILLDESK_OPTIMIZER,
            self::OPTIMIZER_RAZORPAY,
        ],

        //
        // We cannot add this here as generateMethod()
        // in terminal entity uses it to fill the method
        // attribute in the entity. Keeping this here will
        // set both netbanking and emandate attributes,
        // which is not the intended flow.
        // Hence, we will ensure that it gets explicitly set
        // during the terminal creation, so that it does not
        // go via generator method.
        //
        // Method::EMANDATE    => [
        //     self::NETBANKING_ICICI,
        //     self::NETBANKING_HDFC,
        //     self::NETBANKING_AXIS,
        // ],

        Method::WALLET => [
            self::MOBIKWIK,
            self::PAYTM,
            self::WALLET_OLAMONEY,
            self::WALLET_PAYZAPP,
            self::WALLET_PAYUMONEY,
            self::WALLET_AIRTELMONEY,
            self::WALLET_FREECHARGE,
            self::WALLET_BAJAJ,
            self::WALLET_JIOMONEY,
            self::WALLET_SBIBUDDY,
            self::WALLET_OPENWALLET,
            self::WALLET_RAZORPAYWALLET,
            self::WALLET_MPESA,
            self::WALLET_AMAZONPAY,
            self::WALLET_PHONEPE,
            self::WALLET_PHONEPESWITCH,
            self::WALLET_PAYPAL
        ],

        Method::EMI => [
            self::HITACHI,
            self::AMEX,
            self::HDFC,
            self::FIRST_DATA,
            self::HDFC_DEBIT_EMI,
            self::KOTAK_DEBIT_EMI,
            self::INDUSIND_DEBIT_EMI,
        ],

        Method::UPI => [
            self::UPI_MINDGATE,
            self::UPI_ICICI,
            self::UPI_AXIS,
            self::UPI_SBI,
            self::UPI_HULK,
            self::UPI_YESBANK,
            self::UPI_AIRTEL,
            self::UPI_CITI,
            self::UPI_JUSPAY,
            self::UPI_AXISOLIVE,
            self::CASHFREE,
            self::PAYU,
            self::PAYTM,
            self::PINELABS,
            self::HDFC_EZETAP,
            self::BILLDESK_OPTIMIZER,
            self::CCAVENUE,
            self::OPTIMIZER_RAZORPAY,
        ],

        Method::AEPS => [
            self::AEPS_ICICI,
        ],

        Method::CARDLESS_EMI => [
            self::CARDLESS_EMI,
        ],

        Method::PAYLATER => [
            self::PAYLATER,
        ],

        Method::OFFLINE => [
            self::OFFLINE_HDFC,
        ],

        Method::APP => [
            self::CRED,
            self::TWID,
            self::EMERCHANTPAY,
        ],
        Method::FPX => [
            self::FPX
        ]
    ];

    const CARD_GATEWAYS_LIVE = [
        self::HDFC,
        self::AXIS_MIGS,
        self::AMEX,
        self::CYBERSOURCE,
        self::FIRST_DATA,
    ];

    const SHARED_NETBANKING_GATEWAYS_LIVE = [
        self::BILLDESK,
        self::EBS,
        self::ATOM
    ];

    const BANK_TRANSFER_REFUND_GATEWAYS = [
        self::ENACH_RBL,
        self::NETBANKING_HDFC,
        self::NETBANKING_AXIS,
        self::ENACH_NPCI_NETBANKING,
        self::NETBANKING_SBI,
        self::NACH_CITI,
        self::NACH_ICICI,
    ];

    const UPI_TRANSFER_REFUND_GATEWAYS = [
       self::UPI_MINDGATE,
       self::UPI_ICICI,
    ];

    /**
     * Card gateways which support auth and capture mechanism for at
     * least one card network.
     *
     * @var array
     */
    public static $authAndCapture = [
        self::HDFC                  => [
            self::NOT_SUPPORTED => [Network::MAES, Network::RUPAY, Network::DICL]
        ],
        self::AXIS_MIGS             => [],
        self::AMEX                  => [],
        self::CYBERSOURCE           => [
            self::NOT_SUPPORTED => [Network::RUPAY]
        ],
        self::PAYSECURE             => [],
        self::FIRST_DATA            => [
            self::NOT_SUPPORTED => [Network::MAES, Network::RUPAY]
        ],
        self::WALLET_OPENWALLET     => [],
        self::WALLET_RAZORPAYWALLET => [],
        self::HITACHI               => [],
        self::MPGS                  => [],
        self::ISG                   => [],
        self::CHECKOUT_DOT_COM      => [],
    ];

    /**
     * Card gateways which support purchase mechanism for at
     * least one card network.
     *
     * @var array
     */
    public static $gatewayNetworkPurchaseSupport = [
        self::HITACHI                 => [
            self::NOT_SUPPORTED     => [Network::RUPAY],
            self::SUPPORTED         => [],
        ],
        self::PAYSECURE             => [
            self::NOT_SUPPORTED     => [Network::RUPAY],
            self::SUPPORTED         => [Network::RUPAY  => [self::ACQUIRER_AXIS]]
        ],
        self::UMOBILE               => [
            self::NOT_SUPPORTED     =>  [],
            self::SUPPORTED         =>  []
        ]
    ];

    public static $bankTransferProviderGateway = [
        Provider::YESBANK   => self::BT_YESBANK,
        Provider::KOTAK     => self::BT_KOTAK,
        Provider::DASHBOARD => self::BT_DASHBOARD,
        Provider::ICICI     => self::BT_ICICI,
        Provider::RBL       => self::BT_RBL,
        Provider::RBL_JSW   => self::BT_RBL_JSW,
        Provider::HDFC_ECMS => self::BT_HDFC_ECMS,
    ];

    //
    // Temporary, since bank transfers will be refactored to use terminals too
    // TODO: Remove when above refactor is done
    protected static $nonTerminalGateways = [
        self::BT_YESBANK,
        self::BT_KOTAK,
        self::BT_DASHBOARD,
        self::BT_ICICI,
        self::BT_RBL,
        self::BT_RBL_JSW,
        self::BT_HDFC_ECMS,
    ];

    /**
     * Card gateways which support full auth reversal
     *
     * @var array
     */
    public static $reverseSupportedGateways = [
        self::CYBERSOURCE,
        self::AXIS_MIGS,
        self::AMEX,
        self::WALLET_OPENWALLET,
        self::WALLET_RAZORPAYWALLET,
        self::HITACHI,
        self::CARDLESS_EMI,
    ];

    /**
     * Gateway Acquirers which support auth reversal
     *
     * @var array
     */
    public static $reverseSupportedGatewayAcquirers = [
        PayLater::FLEXMONEY,
    ];

    /**
     * For async gateways, we mark the payment as created and return
     * the response immediately. The payment is authorized over a webhook
     * or some other async medium. Checkout currently long-polls for
     * the payment to be authorized.
     * @var array
     */
    public static $asynchronous = [
        self::UPI_MINDGATE,
        self::UPI_ICICI,
        self::UPI_HULK,
        self::UPI_SBI,
        self::SHARP,
        self::UPI_AXIS,
        self::UPI_AXISOLIVE,
        self::UPI_RBL,
        self::UPI_YESBANK,
        self::UPI_AIRTEL,
        self::UPI_CITI,
        self::UPI_JUSPAY,
        self::UPI_KOTAK,
        self::UPI_RZPRBL,
        self::WALLET_PHONEPE,
        self::CRED,
        self::CASHFREE,
        self::PAYU,
        self::PAYTM,
        self::PINELABS,
        self::BILLDESK_OPTIMIZER,
        self::CCAVENUE,
        self::OPTIMIZER_RAZORPAY,
    ];

    public static $immediateVerifyGateways = [
        self::BAJAJFINSERV,
    ];

    public static $s2sGateways = [
        self::HDFC_DEBIT_EMI,
        self::KOTAK_DEBIT_EMI,
        self::INDUSIND_DEBIT_EMI,
    ];

    public static $verifyMissingGateways = [
        self::HDFC_DEBIT_EMI,
        self::KOTAK_DEBIT_EMI,
    ];

    public static $otpPostFormSubmitGateways = [
        self::HDFC_DEBIT_EMI,
        self::KOTAK_DEBIT_EMI,
        self::INDUSIND_DEBIT_EMI,
        self::BAJAJ,
        self::ICICI,
        self::BILLDESK_OPTIMIZER,
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $headless = [
       self::CYBERSOURCE => [
            Network::VISA,
            Network::MC,
        ],
        self::HITACHI => [
            Network::MC,
            Network::VISA,
            Network::MAES,
            Network::RUPAY,
        ],
        self::HDFC => [
            Network::MC,
            Network::VISA,
            Network::MAES,
            Network::DICL,
            Network::RUPAY,
        ],
        self::FIRST_DATA => [
            Network::MC,
            Network::VISA,
            Network::MAES,
        ],
        self::CARD_FSS => [
            Network::MC,
            Network::VISA,
        ],
        self::PAYSECURE => [
            Network::RUPAY,
        ],
        self::MPI_BLADE => [
            Network::MC,
            Network::VISA
        ],
        self::SHARP => [
            Network::VISA,
            Network::MC,
        ],
        self::PAYU => [
            Network::VISA,
            Network::MC,
            Network::RUPAY,
        ],
        self::BILLDESK_OPTIMIZER => [
            Network::VISA,
            Network::MC,
            Network::RUPAY,
            Network::AMEX,
            Network::DICL,
        ],
        self::PAYTM => [
            Network::VISA,
            Network::MC,
            Network::RUPAY,
        ],
        self::CASHFREE => [
            Network::VISA,
            Network::MC,
            Network::RUPAY,
        ]
    ];

    // Some gateways are not dependent on the network and only
    // depends on the issuer. For example: HDFC Debit EMI
    // Here, whichever the network the card is, if the issuer is HDFC
    // and the method is EMI, the gateway is supported
    public static $ignoreCardNetworkSupport = [
        Issuer::HDFC => [
            self::HDFC_DEBIT_EMI,
        ],
        Issuer::KKBK =>[
            self::KOTAK_DEBIT_EMI
        ],
        Issuer::INDB =>[
            self::INDUSIND_DEBIT_EMI
        ]
    ];

    /**
     * Each card gateway only support specific card networks.
     * This maintains a map of gateway to card network which
     * is used in gateway and terminal selection logic
     *
     * @var array
     */

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $cardNetworkMap = [
        self::HDFC => [
            Network::MC,
            Network::VISA,
            Network::MAES,
            Network::DICL,
            Network::RUPAY,
            Network::UNKNOWN
        ],
        self::AXIS_MIGS => [
            Network::MC,
            Network::VISA
        ],
        self::AXIS_GENIUS => [
            Network::MC,
            Network::VISA
        ],
        self::AMEX => [
            Network::AMEX
        ],
        self::BAJAJ => [
            Network::BAJAJ
        ],
        self::MPI_BLADE => [
            Network::MC,
            Network::VISA
        ],
        self::MPI_ENSTAGE => [
            Network::MC,
            Network::VISA
        ],
        self::PAYTM => [
            Network::MC,
            Network::VISA,
            Network::DICL,
        ],
        self::SHARP => [
            Network::MC,
            Network::VISA,
            Network::MAES,
            Network::AMEX,
            Network::DICL,
            Network::RUPAY,
            Network::UNKNOWN
        ],
        self::CYBERSOURCE => [
            Network::MC,
            Network::VISA,
        ],
        self::FULCRUM => [
            Network::MC,
            Network::VISA,
        ],
        self::HITACHI => [
            Network::MC,
            Network::VISA,
            Network::MAES,
            Network::RUPAY,
        ],
        self::FIRST_DATA => [
            Network::MC,
            Network::VISA,
            Network::MAES,
            Network::RUPAY,
        ],
        self::CARD_FSS => [
            Network::MC,
            Network::VISA,
            Network::RUPAY,
        ],
        self::ICICI => [
            Network::MC,
            Network::VISA,
            Network::RUPAY,
        ],
        self::MPGS => [
            Network::MC,
            Network::VISA,
            Network::AMEX,
        ],
        self::PAYSECURE => [
            Network::RUPAY,
        ],
        self::ISG => [
            Network::MC,
            Network::VISA,
            Network::RUPAY,
        ],
        self::PAYU => [
            Network::MC,
            Network::VISA,
            Network::AMEX,
            Network::DICL,
            Network::MAES,
            Network::RUPAY,
        ],
        self:: CASHFREE => [
            Network::MC,
            Network::VISA,
            Network::AMEX,
            Network::RUPAY,
            Network::DICL,
            Network::DISC,
        ],
        self:: CCAVENUE => [
            Network::MC,
            Network::VISA,
            Network::AMEX,
            Network::RUPAY,
            Network::DICL,
            Network::DISC,
        ],
        self:: ZAAKPAY => [
            Network::MC,
            Network::VISA,
            Network::AMEX,
            Network::RUPAY,
            Network::DICL,
            Network::DISC,
        ],
        self:: PINELABS => [
            Network::MC,
            Network::VISA,
            Network::AMEX,
            Network::DICL,
            Network::RUPAY,
        ],
        self:: CHECKOUT_DOT_COM =>[
            Network::MC,
            Network::VISA,
            Network::AMEX,
            Network::DISC,
            Network::DICL,
            Network::JCB,
        ],
        self::INGENICO => [
            Network::MC,
            Network::AMEX,
            Network::MAES,
            Network::VISA,
            Network::RUPAY,
        ],
        self::BILLDESK_OPTIMIZER => [
            Network::MC,
            Network::AMEX,
            Network::VISA,
            Network::RUPAY,
            Network::DICL,
            Network::MAES,
        ],
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $bharatQrCardNetwork = [
        // IMP: Order of networks matter!
        /*
         * Blocking all card networks for BharatQr as shared card terminals needs to be disabled due to compliance
         */
        //self::HITACHI => [
        //    Network::VISA,
        //    Network::MC,
        //    Network::RUPAY,
        //],
        //self::PAYSECURE => [
        //    Network::RUPAY,
        //],
        //self::ISG => [
        //    Network::VISA,
        //    Network::MC,
        //    Network::RUPAY,
        //],
        //self::WORLDLINE => [
        //    Network::VISA,
        //    Network::MC,
        //    Network::RUPAY,
        //],
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $cardNetworkRecurringMap = [
        self::HITACHI => [
            Network::VISA,
            Network::MC,
        ],
    ];

    public static $walletToGatewayMap = [
        Wallet::OLAMONEY           => Gateway::WALLET_OLAMONEY,
        Wallet::PAYTM              => Gateway::PAYTM,
        Wallet::MOBIKWIK           => Gateway::MOBIKWIK,
        Wallet::PAYZAPP            => Gateway::WALLET_PAYZAPP,
        Wallet::PAYUMONEY          => Gateway::WALLET_PAYUMONEY,
        Wallet::AIRTELMONEY        => Gateway::WALLET_AIRTELMONEY,
        Wallet::FREECHARGE         => Gateway::WALLET_FREECHARGE,
        Wallet::BAJAJPAY           => Gateway::WALLET_BAJAJ,
        Wallet::JIOMONEY           => Gateway::WALLET_JIOMONEY,
        Wallet::SBIBUDDY           => Gateway::WALLET_SBIBUDDY,
        Wallet::OPENWALLET         => Gateway::WALLET_OPENWALLET,
        Wallet::RAZORPAYWALLET     => Gateway::WALLET_RAZORPAYWALLET,
        Wallet::MPESA              => Gateway::WALLET_MPESA,
        Wallet::AMAZONPAY          => Gateway::WALLET_AMAZONPAY,
        Wallet::PHONEPE            => Gateway::WALLET_PHONEPE,
        Wallet::PHONEPE_SWITCH     => Gateway::WALLET_PHONEPESWITCH,
        Wallet::PAYPAL             => Gateway::WALLET_PAYPAL,
    ];

    public static $upiToGatewayMap = [
        Upi::HDFC  => Gateway::UPI_MINDGATE,
        Upi::ICIC  => Gateway::UPI_ICICI,
        Upi::SBIN  => Gateway::UPI_SBI,
        Upi::UTIB  => Gateway::UPI_AXIS,
        Upi::YESB  => Gateway::UPI_YESBANK,
    ];

    public static $acquirerToCodeMap = [
        self::ACQUIRER_HDFC => IFSC::HDFC,
        self::ACQUIRER_ICIC => IFSC::ICIC,
        self::ACQUIRER_AXIS => IFSC::UTIB,
        self::ACQUIRER_AMEX => Network::AMEX,
        self::ACQUIRER_RATN => IFSC::RATN,
        self::ACQUIRER_BARB => IFSC::BARB,
        self::ACQUIRER_SBIN => IFSC::SBIN,
        self::ACQUIRER_KOTAK => IFSC::KKBK
    ];

    /**
     * @deprecated
     * List of gateways for which we run verification checks for all
     * failed payments on a continuous basis.
     *
     * @var array
     */
    public static $verifyEnabled = [
        self::AXIS_MIGS,
        self::BILLDESK,
        self::EBS,
        self::MOBIKWIK,
        self::PAYTM,
        self::HDFC,
        self::AMEX,
        self::NETBANKING_HDFC,
        self::NETBANKING_KOTAK,
        self::NETBANKING_ICICI,
        self::NETBANKING_AIRTEL,
        self::NETBANKING_AXIS,
        self::NETBANKING_FEDERAL,
        self::NETBANKING_INDUSIND,
        self::NETBANKING_PNB,
        self::WALLET_PAYZAPP,
        self::FIRST_DATA,
        self::CYBERSOURCE,
        self::PAYSECURE,
        self::WALLET_PAYUMONEY,
        self::WALLET_AIRTELMONEY,
        self::WALLET_OLAMONEY,
        self::WALLET_FREECHARGE,
        self::WALLET_JIOMONEY,
        self::WALLET_SBIBUDDY,
        self::WALLET_MPESA,
        self::CARDLESS_EMI,
        self::PAYLATER,
        self::FULCRUM,
        self::OFFLINE_HDFC,
    ];

    public static $verifyDisabled = [
        self::WALLET_OPENWALLET,
        self::WALLET_RAZORPAYWALLET,
        self::NETBANKING_RBL,
        self::NETBANKING_ALLAHABAD,
        self::NETBANKING_CORPORATION,
        self::NETBANKING_IDFC,
        self::NETBANKING_VIJAYA,
        self::NETBANKING_CBI,
        self::HDFC_DEBIT_EMI,
        self::KOTAK_DEBIT_EMI,
        self::NETBANKING_SVC,
        self::NETBANKING_JSB,
        self::NETBANKING_IDBI,
        self::NETBANKING_FSB,
        self::NETBANKING_DCB,
        self::NETBANKING_IBK,
        self::NETBANKING_TMB,
        self::NACH_CITI,
        self::NACH_ICICI,
        self::NETBANKING_BDBL,
        self::NETBANKING_KARNATAKA,
        self::NETBANKING_UJJIVAN,
        self::NETBANKING_SARASWAT,
    ];

    public static $captureVerifyEnabled = [
        self::HITACHI,
        self::FULCRUM,
        self::AXIS_MIGS,
        self::CYBERSOURCE,
        self::FIRST_DATA,
        self::CARD_FSS,
        self::HDFC,
        self::UPI_AXIS,
        self::UPI_HULK,
        self::UPI_RBL,
        self::UPI_SBI,
        self::UPI_YESBANK,
        self::UPI_MINDGATE,
        self::UPI_ICICI,
        self::BAJAJ,
        self::AMEX,
        self::ISG,
    ];

    // We do not report capture verify for some gateway even if they fail, as there are integration issues currently
    public static $captureVerifyReportDisabledGateways = [
        self::UPI_AXIS,
        self::UPI_ICICI,
    ];

    public static $captureVerifyQREnabledGateways = [
        self::UPI_MINDGATE,
        self::UPI_ICICI,
        self::ISG
    ];

    // This is to configure the threshold used to block gateways if we get timeout errors in verify.
    // TODO: Revisit this once for correct numbers, high number can create issue by clogging cron
    public static $verifyBlockThresholdGateways = [
        self::UPI_AXIS       => 50,
        self::UPI_ICICI      => 300,
        self::UPI_MINDGATE   => 100,
    ];

    // This is to configure the time interval in which we count errors to block gateways if we get timeout errors in verify.
    public static $verifyBlockBucketIntervalGateways = [
        self::UPI_ICICI      => 180,  // 3 minutes
    ];

    // This is to configure the block time for a gateway if we timeout errors cross the threshold in verify.
    public static $verifyBlockTimeGateways = [
        self::UPI_ICICI      => 300,  // 5 minutes
    ];

    /**
     * List of gateways that support recurring payments
     *
     * @var array
     */

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $recurringGateways = [
        Gateway::CYBERSOURCE,
        Gateway::FIRST_DATA,
        Gateway::AXIS_MIGS,
        Gateway::HDFC,
        Gateway::HITACHI,
        Gateway::NETBANKING_ICICI,
        Gateway::NETBANKING_AXIS,
        Gateway::NETBANKING_HDFC,
        Gateway::NETBANKING_SBI,
        Gateway::ESIGNER_DIGIO,
        Gateway::ESIGNER_LEGALDESK,
        Gateway::ENACH_RBL,
        Gateway::ENACH_NPCI_NETBANKING,
        Gateway::UPI_MINDGATE,
        Gateway::NACH_CITI,
        Gateway::NACH_ICICI,
        Gateway::AMEX,
        Gateway::UPI_ICICI,
        Gateway::FULCRUM,
        Gateway::CHECKOUT_DOT_COM,
        Gateway::PAYU,
    ];

    public static $cardMandateGateways = [
        Gateway::BILLDESK_SIHUB,
        Gateway::MANDATE_HQ,
    ];

    public static $upiRecurringGateways = [
        Gateway::UPI_MINDGATE,
        Gateway::UPI_ICICI,
    ];

    public static $recurringCardNetworks = [
        Network::MC,
        Network::VISA,
        Network::RUPAY,
        Network::AMEX
    ];

    public static $recurringDebitCardBanks = [
        IFSC::ICIC,
        IFSC::CITI,
        IFSC::KKBK,
        IFSC::CNRB,
        IFSC::HSBC,
        IFSC::ESFB,
        IFSC::CIUB,
        IFSC::KVBL,
        IFSC::UTIB,
        IFSC::IDIB,
        IFSC::MAHB,
        IFSC::IOBA,
        IFSC::FDRL,
        IFSC::SBIN,
        IFSC::RATN,
        IFSC::INDB,
        IFSC::HDFC,
        IFSC::BARB,
        IFSC::ALLA,
        IFSC::BKID,
        IFSC::UBIN,
        IFSC::IBKL,
        IFSC::SYNB,
        IFSC::IDFB,
        IFSC::SCBL,
        IFSC::YESB,
        IFSC::ANDB,
        IFSC::CORP,
        IFSC::AUBL,
        IFSC::BKDN,
        IFSC::VIJB,
        IFSC::PUNB,
        IFSC::STCB,
        IFSC::ASBL,
        IFSC::JSBL,
        IFSC::PSIB,
        IFSC::SRCB,
        IFSC::UCBA,
    ];

    public static $directDebitCardNetworks = [
        Network::VISA,
        Network::MC,
        Network::MAES,
        Network::AMEX,
    ];

    public static $bharatQrGateways = [
        self::UPI_ICICI,
        self::HITACHI,
        self::SHARP,
        self::UPI_HULK,
        self::UPI_MINDGATE,
        self::ISG,
        self::WORLDLINE,
        self::UPI_YESBANK,
    ];

    public static $upiTransferGateway = [
        self::UPI_MINDGATE,
        self::UPI_ICICI,
    ];

    public static $partialRefundDisabledGateways = [
        self::HDFC_DEBIT_EMI,
        self::KOTAK_DEBIT_EMI,
        self::INDUSIND_DEBIT_EMI,
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $authTypeToEmandateGatewayMap = [
        AuthType::NETBANKING  => [
            Gateway::NETBANKING_AXIS,
            Gateway::NETBANKING_ICICI,
            Gateway::NETBANKING_HDFC,
            Gateway::NETBANKING_SBI,
            Gateway::ENACH_NPCI_NETBANKING,
            Gateway::PAYU,
        ],
        AuthType::DEBITCARD =>  [
            Gateway::ENACH_NPCI_NETBANKING,
            Gateway::PAYU,
        ],
        AuthType::AADHAAR     => self::EMANDATE_AADHAAR_GATEWAYS,
        AuthType::AADHAAR_FP  => self::EMANDATE_AADHAAR_GATEWAYS,
    ];

    /**
     * @todo: https://razorpay.atlassian.net/projects/GL/issues/GL-315
     *
     * @var array
     */
    public static $zeroRupeeEmandateBanks = [
        IFSC::AACX,
        IFSC::ABDX,
        IFSC::ABHY,
        IFSC::ABSB,
        IFSC::ACBX,
        IFSC::ACKX,
        IFSC::ACUX,
        IFSC::ADBX,
        IFSC::ADCC,
        IFSC::ADCX,
        IFSC::AGCX,
        IFSC::AGUX,
        IFSC::AHMX,
        IFSC::AHUX,
        IFSC::AIRP,
        IFSC::AJKB,
        IFSC::AJPX,
        IFSC::AJSX,
        IFSC::ALAX,
        IFSC::ALLX,
        IFSC::AMAX,
        IFSC::AMCB,
        IFSC::AMRX,
        IFSC::ANDB,
        IFSC::ANSX,
        IFSC::APBL,
        IFSC::APCX,
        IFSC::APGB,
        IFSC::APGX,
        IFSC::APJX,
        IFSC::APMC,
        IFSC::APMX,
        IFSC::APNX,
        IFSC::APRX,
        IFSC::APSX,
        IFSC::ASBL,
        IFSC::ASBX,
        IFSC::ASHX,
        IFSC::ASKX,
        IFSC::ASOX,
        IFSC::ASSX,
        IFSC::AUBL,
        IFSC::AUCB,
        IFSC::AUCX,
        IFSC::AVDX,
        IFSC::AWCX,
        IFSC::BACB,
        IFSC::BACX,
        IFSC::BANX,
        NETBANKING::BARB_R,
        IFSC::BARB,
        IFSC::BARX,
        IFSC::BASX,
        IFSC::BBLX,
        IFSC::BCBM,
        IFSC::BDBL,
        IFSC::BDUX,
        IFSC::BGBX,
        IFSC::BHAX,
        IFSC::BHCX,
        IFSC::BHDX,
        IFSC::BHJX,
        IFSC::BHMX,
        IFSC::BHOX,
        IFSC::BHUX,
        IFSC::BJUX,
        IFSC::BKCX,
        IFSC::BKDN,
        IFSC::BKDX,
        IFSC::BKID,
        IFSC::BMCB,
        IFSC::BMPX,
        IFSC::BNBX,
        IFSC::BNSX,
        IFSC::BORX,
        IFSC::BRMX,
        IFSC::BSBX,
        IFSC::BURX,
        IFSC::BUZX,
        IFSC::CALX,
        IFSC::CBHX,
        IFSC::CBIN,
        IFSC::CHBX,
        IFSC::CHTX,
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CJAX,
        IFSC::CLBL,
        IFSC::CMLX,
        IFSC::CNRB,
        IFSC::CNSX,
        IFSC::COCX,
        IFSC::CORP,
        IFSC::COSB,
        IFSC::CSBK,
        IFSC::CSBX,
        IFSC::CTBX,
        IFSC::CZCX,
        IFSC::DBAX,
        IFSC::DBSS,
        IFSC::DCBL,
        IFSC::DDBX,
        IFSC::DENS,
        IFSC::DEUT,
        IFSC::DEVX,
        IFSC::DHUX,
        IFSC::DLXB,
        IFSC::DNSB,
        IFSC::DOBX,
        IFSC::DSCB,
        IFSC::DSPX,
        IFSC::DSUX,
        IFSC::DTCX,
        IFSC::EDSX,
        IFSC::ESFB,
        IFSC::EUCX,
        IFSC::FDRL,
        IFSC::FINX,
        IFSC::GACX,
        IFSC::GCBX,
        IFSC::GHPX,
        IFSC::GNCX,
        IFSC::GPCX,
        IFSC::GRAX,
        IFSC::GSBX,
        IFSC::GSCB,
        IFSC::GUCX,
        IFSC::HAMX,
        IFSC::HDFC,
        IFSC::HMNX,
        IFSC::HPCX,
        IFSC::HPSX,
        IFSC::HSBC,
        IFSC::HSDX,
        IFSC::HUCH,
        IFSC::IBKL,
        IFSC::ICBL,
        IFSC::ICIC,
        IFSC::ICMX,
        IFSC::IDFB,
        IFSC::IDIB,
        IFSC::IDUX,
        IFSC::IMPX,
        IFSC::INDB,
        IFSC::IOBA,
        IFSC::IPCX,
        IFSC::IPSX,
        IFSC::ISMX,
        IFSC::ITCX,
        IFSC::ITDX,
        IFSC::JANA,
        IFSC::JASB,
        IFSC::JCDX,
        IFSC::JHAX,
        IFSC::JIOP,
        IFSC::JMCX,
        IFSC::JMHX,
        IFSC::JMPX,
        IFSC::JODX,
        IFSC::JONX,
        IFSC::JPCB,
        IFSC::JSBL,
        IFSC::JSBP,
        IFSC::JSFB,
        IFSC::JSMX,
        IFSC::JUCX,
        IFSC::JVCX,
        IFSC::KAIJ,
        IFSC::KALX,
        IFSC::KAMX,
        IFSC::KARB,
        IFSC::KBCX,
        IFSC::KCUB,
        IFSC::KCUX,
        IFSC::KDCX,
        IFSC::KDIX,
        IFSC::KHUX,
        IFSC::KKBK,
        IFSC::KKMX,
        IFSC::KLGB,
        IFSC::KMCX,
        IFSC::KMSX,
        IFSC::KNBX,
        IFSC::KNSB,
        IFSC::KOYX,
        IFSC::KRNX,
        IFSC::KSCB,
        IFSC::KSUX,
        IFSC::KTBX,
        IFSC::KUCX,
        IFSC::KUKX,
        IFSC::KVBL,
        IFSC::KVCX,
        IFSC::KVGB,
        IFSC::LATX,
        IFSC::LBMX,
        IFSC::LDPX,
        IFSC::LKMX,
        IFSC::LOKX,
        IFSC::MABL,
        IFSC::MAHB,
        IFSC::MAKX,
        IFSC::MALX,
        IFSC::MDEX,
        IFSC::MGBX,
        IFSC::MGCX,
        IFSC::MHNX,
        IFSC::MHSX,
        IFSC::MLCG,
        IFSC::MPCX,
        IFSC::MPDX,
        IFSC::MRTX,
        IFSC::MSAX,
        IFSC::MSBL,
        IFSC::MSCI,
        IFSC::MSCX,
        IFSC::MSNX,
        IFSC::MSOX,
        IFSC::MUSX,
        IFSC::MVCX,
        IFSC::MYAX,
        IFSC::MZCX,
        IFSC::NASX,
        IFSC::NAVX,
        IFSC::NAWX,
        IFSC::NBBX,
        IFSC::NBMX,
        IFSC::NCBL,
        IFSC::NCCX,
        IFSC::NGRX,
        IFSC::NGSX,
        IFSC::NICB,
        IFSC::NILX,
        IFSC::NJCX,
        IFSC::NJSX,
        IFSC::NMCB,
        IFSC::NMCX,
        IFSC::NSBB,
        IFSC::NSBX,
        IFSC::NSIX,
        IFSC::NTBL,
        IFSC::NVSX,
        IFSC::ODGB,
        IFSC::OIBA,
        IFSC::OMCX,
        IFSC::ONSX,
        IFSC::ORBC,
        IFSC::OSMX,
        IFSC::PALX,
        IFSC::PASX,
        IFSC::PATX,
        IFSC::PBGX,
        IFSC::PCBL,
        IFSC::PCTX,
        IFSC::PCUX,
        IFSC::PDUX,
        IFSC::PGBX,
        IFSC::PGCX,
        IFSC::PJSB,
        IFSC::PLUX,
        IFSC::PMEC,
        IFSC::PMNX,
        IFSC::PNSX,
        IFSC::PPBX,
        IFSC::PRPX,
        IFSC::PSBX,
        IFSC::PSCX,
        IFSC::PSIB,
        IFSC::PTCX,
        IFSC::PTSX,
        IFSC::PUBX,
        NETBANKING::PUNB_R,
        IFSC::PUNB,
        IFSC::PVCX,
        IFSC::PYTM,
        IFSC::QUCX,
        IFSC::RACX,
        IFSC::RAKX,
        IFSC::RATN,
        IFSC::RBBX,
        IFSC::RCCX,
        IFSC::RCUX,
        IFSC::RDNX,
        IFSC::REBX,
        IFSC::RECX,
        IFSC::RGCX,
        IFSC::RGSX,
        IFSC::RRSX,
        IFSC::RZSX,
        IFSC::SACX,
        IFSC::SADX,
        IFSC::SASA,
        IFSC::SATX,
        IFSC::SAVX,
        IFSC::SBIN,
        IFSC::SBMX,
        IFSC::SBNX,
        IFSC::SBUX,
        IFSC::SCBL,
        IFSC::SCNX,
        IFSC::SCSX,
        IFSC::SCUX,
        IFSC::SDCB,
        IFSC::SDCX,
        IFSC::SDHX,
        IFSC::SDSX,
        IFSC::SEUX,
        IFSC::SEWX,
        IFSC::SGSX,
        IFSC::SHCX,
        IFSC::SHIX,
        IFSC::SIBL,
        IFSC::SIRX,
        IFSC::SISX,
        IFSC::SKCX,
        IFSC::SKNX,
        IFSC::SKUX,
        IFSC::SMNX,
        IFSC::SMUX,
        IFSC::SMVC,
        IFSC::SNAX,
        IFSC::SNBX,
        IFSC::SNCX,
        IFSC::SNDX,
        IFSC::SNGX,
        IFSC::SNKX,
        IFSC::SONX,
        IFSC::SPBX,
        IFSC::SPCB,
        IFSC::SPCX,
        IFSC::SPSX,
        IFSC::SRCB,
        IFSC::SSBX,
        IFSC::SSKX,
        IFSC::SSLX,
        IFSC::SULX,
        IFSC::SUMX,
        IFSC::SURY,
        IFSC::SUTB,
        IFSC::SVCB,
        IFSC::SVSX,
        IFSC::SYNB,
        IFSC::TACX,
        IFSC::TAMX,
        IFSC::TASX,
        IFSC::TBMX,
        IFSC::TBSB,
        IFSC::TBSX,
        IFSC::TCUB,
        IFSC::TDCB,
        IFSC::TDMX,
        IFSC::TEHX,
        IFSC::TGMB,
        IFSC::THOX,
        IFSC::TIRX,
        IFSC::TJNX,
        IFSC::TJSB,
        IFSC::TMBL,
        IFSC::TMSX,
        IFSC::TNKX,
        IFSC::TNMX,
        IFSC::TSUX,
        IFSC::TTLX,
        IFSC::TTUX,
        IFSC::TUCL,
        IFSC::TUMX,
        IFSC::TUNX,
        IFSC::TUOX,
        IFSC::TVPX,
        IFSC::UBBX,
        IFSC::UBGX,
        IFSC::UBIN,
        IFSC::UCBA,
        IFSC::UCCX,
        IFSC::UCDX,
        IFSC::UJVN,
        IFSC::UMAX,
        IFSC::UMCX,
        IFSC::UNIX,
        IFSC::UNSX,
        IFSC::UROX,
        IFSC::USFB,
        IFSC::UTBI,
        IFSC::UTIB,
        IFSC::VARA,
        IFSC::VCBX,
        IFSC::VCCX,
        IFSC::VDYX,
        IFSC::VERX,
        IFSC::VIKX,
        IFSC::VJSX,
        IFSC::VSBX,
        IFSC::VSCX,
        IFSC::VUCX,
        IFSC::VVCX,
        IFSC::WAIX,
        IFSC::WKGX,
        IFSC::XJKG,
        IFSC::YADX,
        IFSC::YESB,
        IFSC::YLNX,
        IFSC::ZCBL,
        IFSC::ZSBL,
        IFSC::ZSGX,
        IFSC::ZSHX,
        IFSC::ZSMX,
        ];

    /**
     * List of gateways and the banks that they support
     * for e-mandate. This list is required because some
     * gateways might support more than one bank for
     * e-mandate.
     *
     * @var array
     */

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $gatewaysEmandateBanksMap = [
        Gateway::NETBANKING_ICICI      => [
            AuthType::NETBANKING       => [ IFSC::ICIC],
        ],
        Gateway::NETBANKING_AXIS       => [
            AuthType::NETBANKING       => [IFSC::UTIB],
        ],
        Gateway::NETBANKING_HDFC       => [
            AuthType::NETBANKING       => [IFSC::HDFC],
        ],
        Gateway::NETBANKING_SBI        => [
            AuthType::NETBANKING       => [IFSC::SBIN],
        ],
        Gateway::ENACH_NPCI_NETBANKING => [
            AuthType::NETBANKING       => self::ENACH_NPCI_NB_AUTH_NETBANKING_BANKS,
            AuthType::DEBITCARD        => self::ENACH_NPCI_NB_AUTH_CARD_BANKS,
        ],
        Gateway::ENACH_RBL             => [
            AuthType::AADHAAR          => self::EMANDATE_AADHAAR_BANKS,
            AuthType::AADHAAR_FP       => self::EMANDATE_AADHAAR_BANKS,
        ],
        // For now, not maintaining gateway wise bank list
        Gateway::PAYU                  => [
            AuthType::NETBANKING       => self::ENACH_NPCI_NB_AUTH_NETBANKING_BANKS,
            AuthType::DEBITCARD        => self::ENACH_NPCI_NB_AUTH_CARD_BANKS,
        ],
        // This is added here just for test cases
        // We are using UTIB in test cases
        Gateway::ESIGNER_DIGIO         => [
            AuthType::AADHAAR          => [IFSC::UTIB],
            AuthType::AADHAAR_FP       => [IFSC::UTIB],
        ],
        Gateway::ESIGNER_LEGALDESK     => [
            AuthType::AADHAAR          => [IFSC::UTIB],
            AuthType::AADHAAR_FP       => [IFSC::UTIB],
        ],
    ];

    /**
     * List of netbanking gateways that process recurring payments through file send
     *
     * @var array
     */
    public static $fileBasedEMandateDebitGateways = [
        Gateway::NETBANKING_HDFC,
        Gateway::NETBANKING_AXIS,
        Gateway::ENACH_RBL,
        Gateway::ENACH_NPCI_NETBANKING,
        Gateway::NETBANKING_SBI,
    ];

    public static $createGatewayEntityForDebitPaymentDuringPaymentFlow = [
        Gateway::ENACH_NPCI_NETBANKING,
    ];

    /**
     * List of netbanking gateways that process emandate registration through file send
     *
     * @var array
     */
    public static $fileBasedEMandateRegistrationGateways = [
        Gateway::NETBANKING_HDFC,
        Gateway::ENACH_RBL,
        Gateway::NETBANKING_SBI,
    ];

    /**
     * List of netbanking gateways that give emandate registration/debit status through webhooks
     * This is because for some banks they will give final token status in async mode even if,
     * transaction was completed.
     * Similarly for debit txns, gateway will give terminal status through webhooks.
     *
     * @var array
     */
    public static $apiBasedAsyncEMandateGateways = [
        Gateway::PAYU,
    ];

    /**
     * List of gateways which give s2s callback where we do not validate
     * payment callback hash
     *
     * @var array
     */
    public static $s2sCallbackGateways = [
        // Corporate response is provided through
        // s2s callback.
        Gateway::NETBANKING_AXIS,

        Gateway::BILLDESK,
        Gateway::UPI_MINDGATE,
        Gateway::UPI_SBI,
        Gateway::UPI_ICICI,
        Gateway::UPI_HULK,
        Gateway::WALLET_OLAMONEY,
        Gateway::NETBANKING_CORPORATION,
        Gateway::SHARP,
        Gateway::UPI_AXIS,
        Gateway::UPI_AXISOLIVE,
        Gateway::UPI_RBL,
        Gateway::UPI_YESBANK,
        Gateway::CASHFREE,
        Gateway::UPI_AIRTEL,
        Gateway::WALLET_PHONEPE,
        Gateway::UPI_CITI,
        Gateway::UPI_JUSPAY,
        Gateway::UPI_KOTAK,
        Gateway::UPI_RZPRBL,
        Gateway::PAYU,
        Gateway::PAYTM,
        // Cybersource does not make s2s callback, Google Pay makes s2s callback for payments
        // that went through tokenization gateways.
        Gateway::CYBERSOURCE,
        Gateway::CRED,
        Gateway::ATOM,
        Gateway::NETBANKING_RBL,
        Gateway::NETBANKING_HDFC,
        Gateway::BILLDESK_OPTIMIZER,
        Gateway::CCAVENUE,
        Gateway::OPTIMIZER_RAZORPAY,
    ];

    /**
     * List of gateways which give static callback where we do not validate
     * payment callback hash
     *
     * @var array
     *
     * @todo: Add all static callback gateways here once migrated to new flow to handle static callback.
     */
    public static $staticCallbackGateways = [
        Gateway::NETBANKING_KVB,
        Gateway::NETBANKING_CANARA,
        Gateway::ESIGNER_LEGALDESK,
        Gateway::NETBANKING_KOTAK,
        Gateway::NETBANKING_IBK,
        Gateway::NETBANKING_UCO,
        Gateway::NETBANKING_RBL,
        Gateway::NETBANKING_HDFC,
        Gateway::PAYU,
        Gateway::UPI_KOTAK,
    ];

    /**
     * List of gateways that use redirect flow so as to redirect the s2s merchant back to rzp and continue the payment.
     * done for netbanking_svc like gateways which has requirement where the redirect request has to come from
     * razorpay directly instead of merchant.
     *
     * @var array
     */
    public static $netbankingS2SRedirectGateways = [
        Gateway::NETBANKING_SVC,
    ];

    public static $webhooksEnabledGateways = [
        Gateway::ATOM,
        Gateway::WALLET_PHONEPE,
        Gateway::PAYU,
    ];

    /**
     * List of gateways which support S2S mandate callbacks.
     *
     * @var array
     */
    public static $s2sMandateCallbackGateways = [
        Gateway::UPI_MINDGATE,
        Gateway::UPI_ICICI,
    ];

    /**
     * Card gateways which support international payments
     *
     * @var array
     */
    public static $internationalCardGateways = [
        Gateway::MPI_BLADE,
        Gateway::HDFC,
        Gateway::AXIS_MIGS,
        Gateway::AMEX,
        Gateway::CYBERSOURCE,
        Gateway::HITACHI,
        Gateway::CHECKOUT_DOT_COM,
    ];

    /**
     * Gateways/Apps which supports void refunds for AVS failed
     *
     * @var array
     */
    public static $internationalAVSVoidSupported = [
        Gateway::HITACHI,
    ];

     /**
     * Gateways/Apps which support international payments
     *
     * @var array
     */
    public static $internationalGateways = [
        Gateway::EMERCHANTPAY,
        Gateway::TRUSTLY,
        Gateway::POLI,
        Gateway::SOFORT,
        Gateway::GIROPAY,
    ];

    /**
     * For the banks that need a claims file to be generated,
     * we have a list of banks that support this feature
     * @var array
     */
    public static $claimsFileToBank = [
        IFSC::KKBK,
        IFSC::UTIB,
        IFSC::FDRL,
        IFSC::RATN,
        IFSC::INDB,
        IFSC::KVBL,
    ];

    /**
     * Some card networks are only supported partially for one or two gateway.
     *
     * @var array
     */
    public static $partiallySupportedCardNetworks = [
        Network::MAES,
        Network::RUPAY,
        Network::DICL
    ];

  /**
     * Gateways which will be allowed a safeRetry.
     *
     * @var array
     */
    public static $safeRetryGateways = [
        Payment\Gateway::FIRST_DATA,
        Payment\Gateway::AXIS_MIGS
    ];

    /**
     * For the banks we have direct tie-ups with,
     * here we list down the mapping from bank to netbanking gateway name.
     * There is no standardized bank gateway naming that we follow. IFSC
     * code option was discarded because it's not readable in general in code.
     *
     * @var array
     */

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $netbankingToGatewayMap = [
        //corp banks
        Netbanking::ICIC_C => Gateway::NETBANKING_ICICI,
        Netbanking::UTIB_C => Gateway::NETBANKING_AXIS,
        Netbanking::BARB_C => Gateway::NETBANKING_BOB,
        Netbanking::PUNB_C => Gateway::NETBANKING_PNB,
        Netbanking::KKBK_C => Gateway::NETBANKING_KOTAK,
        Netbanking::IDIB_C => Gateway::NETBANKING_IBK,
        Netbanking::RATN_C => Gateway::NETBANKING_RBL,
        Netbanking::HDFC_C => Gateway::NETBANKING_HDFC,
        Netbanking::AUBL_C => Gateway::NETBANKING_AUSF,

        // retail banks
        IFSC::IDFB         => Gateway::NETBANKING_IDFC,
        IFSC::ICIC         => Gateway::NETBANKING_ICICI,
        IFSC::HDFC         => Gateway::NETBANKING_HDFC,
        IFSC::CORP         => Gateway::NETBANKING_UBI,
        IFSC::AIRP         => Gateway::NETBANKING_AIRTEL,
        IFSC::UBIN         => Gateway::NETBANKING_UBI,
        IFSC::SCBL         => Gateway::NETBANKING_SCB,
        IFSC::JAKA         => Gateway::NETBANKING_JKB,
        IFSC::SIBL         => Gateway::NETBANKING_SIB,
        IFSC::CBIN         => Gateway::NETBANKING_CBI,
        IFSC::FDRL         => Gateway::NETBANKING_FEDERAL,
        IFSC::INDB         => Gateway::NETBANKING_INDUSIND,
        IFSC::KKBK         => Gateway::NETBANKING_KOTAK,
        IFSC::UTIB         => Gateway::NETBANKING_AXIS,
        IFSC::RATN         => Gateway::NETBANKING_RBL,
        IFSC::ORBC         => Gateway::NETBANKING_OBC,
        IFSC::CIUB         => Gateway::NETBANKING_CUB,
        IFSC::IDIB         => Gateway::NETBANKING_IBK,
        IFSC::IBKL         => Gateway::NETBANKING_IDBI,
        IFSC::CSBK         => Gateway::NETBANKING_CSB,
        IFSC::ALLA         => Gateway::NETBANKING_IBK,
        IFSC::CNRB         => Gateway::NETBANKING_CANARA,
        IFSC::ESFB         => Gateway::NETBANKING_EQUITAS,
        IFSC::SBIN         => Gateway::NETBANKING_SBI,
        IFSC::VIJB         => Gateway::NETBANKING_BOB,
        IFSC::YESB         => Gateway::NETBANKING_YESB,
        Netbanking::PUNB_R => Gateway::NETBANKING_PNB,
        Netbanking::BARB_R => Gateway::NETBANKING_BOB,
        IFSC::KVBL         => Gateway::NETBANKING_KVB,
        IFSC::SVCB         => Gateway::NETBANKING_SVC,
        IFSC::JSFB         => Gateway::NETBANKING_JSB,
        IFSC::SBBJ         => Gateway::NETBANKING_SBI,
        IFSC::SBHY         => Gateway::NETBANKING_SBI,
        IFSC::SBMY         => Gateway::NETBANKING_SBI,
        IFSC::STBP         => Gateway::NETBANKING_SBI,
        IFSC::SBTR         => Gateway::NETBANKING_SBI,
        IFSC::IOBA         => Gateway::NETBANKING_IOB,
        IFSC::FSFB         => Gateway::NETBANKING_FSB,
        IFSC::DCBL         => Gateway::NETBANKING_DCB,
        IFSC::ANDB         => Gateway::NETBANKING_UBI,
        IFSC::SYNB         => Gateway::NETBANKING_CANARA,
        IFSC::AUBL         => Gateway::NETBANKING_AUSF,
        IFSC::DLXB         => Gateway::NETBANKING_DLB,
        IFSC::NSPB         => Gateway::NETBANKING_NSDL,
        IFSC::BDBL         => Gateway::NETBANKING_BDBL,
        IFSC::SRCB         => Gateway::NETBANKING_SARASWAT,
        IFSC::UCBA         => Gateway::NETBANKING_UCO,
        IFSC::UJVN         => Gateway::NETBANKING_UJJIVAN,
        IFSC::TMBL         => Gateway::NETBANKING_TMB,
        IFSC::KARB         => Gateway::NETBANKING_KARNATAKA,
        IFSC::DBSS         => Gateway::NETBANKING_DBS,
        Netbanking::LAVB_R => Gateway::NETBANKING_DBS,
    ];

    /**
     * For the banks that require a refundfile generated everyday,
     * we map IFSC codes to Gateways
     *
     * @var array
     */
    public static $refundFileNetbankingGateways = [
        IFSC::ICIC          => Gateway::NETBANKING_ICICI,
        IFSC::IDIB          => Gateway::NETBANKING_IBK,
        IFSC::HDFC          => Gateway::NETBANKING_HDFC,
        IFSC::CORP          => Gateway::NETBANKING_CORPORATION,
        IFSC::KKBK          => Gateway::NETBANKING_KOTAK,
        IFSC::UTIB          => Gateway::NETBANKING_AXIS,
        IFSC::FDRL          => Gateway::NETBANKING_FEDERAL,
        IFSC::RATN          => Gateway::NETBANKING_RBL,
        IFSC::INDB          => Gateway::NETBANKING_INDUSIND,
        IFSC::ALLA          => Gateway::NETBANKING_ALLAHABAD,
        IFSC::CNRB          => Gateway::NETBANKING_CANARA,
        IFSC::IDFB          => Gateway::NETBANKING_IDFC,
        IFSC::ESFB          => Gateway::NETBANKING_EQUITAS,
        IFSC::VIJB          => Gateway::NETBANKING_BOB,
        Netbanking::PUNB_R  => Gateway::NETBANKING_PNB,
        Netbanking::BARB_R  => Gateway::NETBANKING_BOB,
        IFSC::SBIN          => Gateway::NETBANKING_SBI,
    ];

    /**
     * @var array
     * Refunds for the netbanking gateways in this list are reconciled automatically while generating the RefundsFile.
     */
    public static $refundsReconcileNetbankingGateways = [
        IFSC::ICIC => Gateway::NETBANKING_ICICI,
        IFSC::HDFC => Gateway::NETBANKING_HDFC,
        IFSC::CORP => Gateway::NETBANKING_CORPORATION,
        IFSC::KKBK => Gateway::NETBANKING_KOTAK,
        IFSC::UTIB => Gateway::NETBANKING_AXIS,
        IFSC::FDRL => Gateway::NETBANKING_FEDERAL,
        IFSC::RATN => Gateway::NETBANKING_RBL,
        IFSC::INDB => Gateway::NETBANKING_INDUSIND,
        IFSC::ALLA => Gateway::NETBANKING_ALLAHABAD,
        IFSC::CNRB => Gateway::NETBANKING_CANARA,
        IFSC::IDFB => Gateway::NETBANKING_IDFC,
        IFSC::ESFB => Gateway::NETBANKING_EQUITAS,
        IFSC::VIJB => Gateway::NETBANKING_BOB,
        IFSC::ORBC => Gateway::NETBANKING_OBC,
        IFSC::CSBK => Gateway::NETBANKING_CSB,
        IFSC::CBIN => Gateway::NETBANKING_CBI,
        Netbanking::PUNB_R => Gateway::NETBANKING_PNB,
        Netbanking::BARB_R => Gateway::NETBANKING_BOB,
        IFSC::CIUB => Gateway::NETBANKING_CUB,
        IFSC::SIBL => Gateway::NETBANKING_SIB,
        IFSC::YESB => Gateway::NETBANKING_YESB,
        IFSC::KVBL => Gateway::NETBANKING_KVB,
        IFSC::SCBL => Gateway::NETBANKING_SCB,
        IFSC::SVCB => Gateway::NETBANKING_SVC,
        IFSC::IOBA => Gateway::NETBANKING_IOB,
        IFSC::FSFB => Gateway::NETBANKING_FSB,
        IFSC::JAKA => Gateway::NETBANKING_JKB,
        IFSC::IBKL => Gateway::NETBANKING_IDBI,
        IFSC::DCBL => Gateway::NETBANKING_DCB,
        IFSC::UBIN => Gateway::NETBANKING_UBI,
        IFSC::IDIB => Gateway::NETBANKING_IBK,
        IFSC::AUBL => Gateway::NETBANKING_AUSF,
        IFSC::JSFB => Gateway::NETBANKING_JSB,
        IFSC::DLXB => Gateway::NETBANKING_DLB,
        IFSC::NSPB => Gateway::NETBANKING_NSDL,
        IFSC::BDBL => Gateway::NETBANKING_BDBL,
        IFSC::SRCB => Gateway::NETBANKING_SARASWAT,
        IFSC::UCBA => Gateway::NETBANKING_UCO,
        IFSC::TMBL => Gateway::NETBANKING_TMB,
        IFSC::KARB => Gateway::NETBANKING_KARNATAKA,
        IFSC::UJVN => Gateway::NETBANKING_UJJIVAN,
        Netbanking::HDFC_C =>Gateway::NETBANKING_HDFC,
        IFSC::DBSS         => Gateway::NETBANKING_DBS,
        Netbanking::LAVB_R => Gateway::NETBANKING_DBS,
    ];

    /**
     * List of gateways which support html get methods for browser redirection using form.
     *
     * @var array
     */
    public static $gatewaysSupportingGetRedirectForm = [
        Gateway::ATOM,
        Gateway::NETBANKING_CORPORATION,
        Gateway::NETBANKING_KVB,
        Gateway::NETBANKING_SVC,
        Gateway::NETBANKING_FSB,
    ];

    /**
     * List of gateways which support netbanking, either in test or live mode.
     *
     * @var array
     */
    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $netbankingGateways = [
        Gateway::BILLDESK,
        Gateway::EBS,
        Gateway::PAYTM,
        Gateway::ATOM,
        Gateway::PAYU,
        Gateway::CASHFREE,
        Gateway::CCAVENUE,
        Gateway::ZAAKPAY,
        Gateway::INGENICO,
        Gateway::BILLDESK_OPTIMIZER,
        Gateway::OPTIMIZER_RAZORPAY,
    ];

    /**
     * List of gateways which support tokenization.
     */
    public static $tokenizationGateways = [
        Gateway::CYBERSOURCE,
    ];

    public static $emiBanks = [
        IFSC::HDFC,
        IFSC::HDFC_DC,
        IFSC::HSBC,
        IFSC::ICIC,
        IFSC::INDB,
        IFSC::KKBK,
        IFSC::RATN,
        IFSC::SCBL,
        IFSC::UTIB,
        IFSC::YESB,
        IFSC::CITI,
        IFSC::SBIN,
        IFSC::BARB,
        IFSC::FDRL,
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $emiBanksUsingCardTerminals = [
        IFSC::INDB,
        IFSC::KKBK,
        IFSC::RATN,
        IFSC::UTIB,
        IFSC::SCBL,
        IFSC::ICIC,
        IFSC::YESB,
        IFSC::SBIN,
        IFSC::CITI,
        IFSC::BARB,
        IFSC::HSBC,
        IFSC::STCB,
        IFSC::IDFB,
        IFSC::FDRL,
    ];

    public static $emiBanksUsingCardAndEmiTerminals = [
        IFSC::UTIB,
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $emiBankToGatewayMapForRouteService = [
        IFSC::HDFC => Gateway::HDFC,
        IFSC::HSBC => Gateway::FIRST_DATA,
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $emiBankToGatewayMap = [
        IFSC::HDFC => [
            Emi\Type::CREDIT => Gateway::HDFC,
            Emi\Type::DEBIT  => Gateway::HDFC_DEBIT_EMI,
        ],
        IFSC::HSBC => [
            Emi\Type::CREDIT => Gateway::FIRST_DATA
        ],
        IFSC::KKBK => [
            Emi\Type::DEBIT => Gateway::KOTAK_DEBIT_EMI,
        ],
        IFSC::INDB => [
            Emi\Type::DEBIT => Gateway::INDUSIND_DEBIT_EMI,
        ]
    ];

    /**
     * This variable defines the mapping of gateway acquirer and the
     * supported ifsc on that acquirer
     */

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $gatewayAcquirerIfscMapping = [
        Gateway::CARD_FSS => [
            self::ACQUIRER_FSS => [
                IFSC::IOBA,
                IFSC::ANDB,
                IFSC::SYNB,
                IFSC::SURY,
                IFSC::CBIN,
            ]
        ],

        Gateway::HDFC => [
            self::ACQUIRER_HDFC => [
                IFSC::HDFC,
            ]
        ],
    ];

    /**
     * gateway will not be returned from preferences if the payment amount is less than the amount in this array
     * @TODO: Replace paylater value with PayLater::MIN_AMOUNTS & cardless_emi value with CardlessEmi::MIN_AMOUNTS
     *        whenever product folks give a go-ahead.
     *
     * @var array
     */
    public static $minAmountForMethodAndGateway = [
        Payment\Method::PAYLATER => [
            PayLater::HDFC => '100000'
        ],
        Payment\Method::CARDLESS_EMI => [
            CardlessEmi::WALNUT369 => '90000',
            CardlessEmi::HCIN => '50000',
        ],
    ];

    public static $customProviderMapping = [
        Entity::DEBIT_EMI_PROVIDERS =>[
            IFSC::FDRL => [
                CardlessEmi::POWERED_BY => [
                    Payment\Entity::METHOD   => Method::CARDLESS_EMI,
                    Payment\Entity::PROVIDER => CardlessEmi::FLEXMONEY
                ],
                CardlessEmi::META => [
                    CardlessEmi::FLOW => CardlessEmi::PAN
                ],
            ],
            IFSC::KKBK => [
                CardlessEmi::POWERED_BY => [
                    Payment\Entity::METHOD   => Method::CARDLESS_EMI,
                    Payment\Entity::PROVIDER => CardlessEmi::FLEXMONEY
                ],
                CardlessEmi::META => [
                    CardlessEmi::FLOW => CardlessEmi::PAN
                ]
            ],
            IFSC::ICIC => [
                CardlessEmi::POWERED_BY => [
                    Payment\Entity::METHOD   => Method::CARDLESS_EMI,
                    Payment\Entity::PROVIDER => CardlessEmi::FLEXMONEY
                ],
                CardlessEmi::META => [
                    CardlessEmi::FLOW => CardlessEmi::PAN
                ]
            ],
            IFSC::BARB => [
                CardlessEmi::POWERED_BY => [
                    Payment\Entity::METHOD   => Method::CARDLESS_EMI,
                    Payment\Entity::PROVIDER => CardlessEmi::FLEXMONEY
                ],
                CardlessEmi::META => [
                    CardlessEmi::FLOW => CardlessEmi::PAN
                ]
            ],
        ]
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $onlyAuthorizationGateway = [
        Gateway::HITACHI,
        Gateway::FULCRUM,
        Gateway::ENACH_RBL,
    ];

    public static $authorizationAuthenticationGatewayMap = [
        Gateway::HITACHI          => Gateway::MPI_BLADE,
        Gateway::FULCRUM          => Gateway::MPI_BLADE,
        Gateway::CYBERSOURCE      => Gateway::CYBERSOURCE,
        Gateway::FIRST_DATA       => Gateway::FIRST_DATA,
        Gateway::AXIS_MIGS        => Gateway::AXIS_MIGS,
        Gateway::PAYU             => Gateway::PAYU,
        Gateway::CASHFREE         => Gateway::CASHFREE,
        Gateway::ZAAKPAY          => Gateway::ZAAKPAY,
        Gateway::CCAVENUE         => Gateway::CCAVENUE,
        Gateway::PINELABS         => Gateway::PINELABS,
        Gateway::CHECKOUT_DOT_COM => Gateway::CHECKOUT_DOT_COM,
        Gateway::INGENICO         => Gateway::INGENICO,
        Gateway::BILLDESK_OPTIMIZER => Gateway::BILLDESK_OPTIMIZER,
        Gateway::ICICI            => Gateway::ICICI,
        Gateway::OPTIMIZER_RAZORPAY => Gateway::OPTIMIZER_RAZORPAY,
    ];

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $subscriptionOverOneYearGateways = [
        Gateway::AXIS_MIGS
    ];


    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $upiIntentGateways = [
        Gateway::UPI_ICICI,
        Gateway::UPI_HULK,
        Gateway::UPI_MINDGATE,
        Gateway::UPI_AXIS,
        Gateway::UPI_RBL,
        Gateway::UPI_AIRTEL,
        Gateway::UPI_JUSPAY,
        Gateway::UPI_SBI,
        Gateway::UPI_AIRTEL,
        Gateway::UPI_YESBANK,
        Gateway::UPI_AXISOLIVE,
        Gateway::UPI_KOTAK,
        Gateway::UPI_RZPRBL,
        Gateway::CASHFREE,
        Gateway::PAYTM,
        Gateway::PAYU,
        Gateway::BILLDESK_OPTIMIZER,
        Gateway::CCAVENUE,
        Gateway::OPTIMIZER_RAZORPAY,
    ];

    public static $upiQrGateways = [
        Gateway::UPI_MINDGATE,
    ];

    public static $sequenceNoBasedRefundGateways = [
        Gateway::NETBANKING_SBI
    ];

    public static $upiValidateVpaTerminals = [
        Mode::LIVE => [
            'BZuiTusQVjb1a4',
            'CrTfneH0erizag',
            'CrWje4EiFnXUE8',
            '6KTOhwf4XBOMns',
            'AK6NMmzbL6FPe4'
        ],
        Mode::TEST => [
            '1000SharpTrmnl',
        ],
    ];

    public static $redirectFlowProvider = [
        CardlessEmi::FLEXMONEY,
        CardlessEmi::WALNUT369,
        CardlessEmi::SEZZLE,
    ];

    public static $checkAccountSkipProvider = [
        CardlessEmi::WALNUT369,
        CardlessEmi::SEZZLE,
    ];

    public static $verifyClientOnS2s = [
        Gateway::UPI_CITI,
    ];

    public static $contactMandatoryGateways = [
        Gateway::HDFC_DEBIT_EMI,
        Gateway::KOTAK_DEBIT_EMI,
        Gateway::INDUSIND_DEBIT_EMI
    ];

    public static $upiOtmGateways = [
        Gateway::UPI_MINDGATE,
    ];

    // all supported wallets including external
    public static $supportedWallets = [
        self::WALLET_AIRTELMONEY  =>  [
            Wallet::AIRTELMONEY
        ],
        self::WALLET_FREECHARGE  =>  [
            Wallet::FREECHARGE
        ],
        self::WALLET_BAJAJ  =>  [
            Wallet::BAJAJPAY
        ],
        self::WALLET_AMAZONPAY  =>  [
            Wallet::AMAZONPAY
        ],
        self::WALLET_JIOMONEY  =>  [
            Wallet::JIOMONEY
        ],
        self::WALLET_SBIBUDDY  =>  [
            Wallet::SBIBUDDY
        ],
        self::WALLET_OLAMONEY =>  [
            Wallet::OLAMONEY
        ],
        self::WALLET_MPESA  =>  [
            Wallet::MPESA
        ],
        self::WALLET_OPENWALLET  =>  [
            Wallet::OPENWALLET
        ],
        self::WALLET_RAZORPAYWALLET  =>  [
            Wallet::RAZORPAYWALLET
        ],
        self::WALLET_PAYUMONEY  =>  [
            Wallet::PAYUMONEY
        ],
        self::WALLET_PAYZAPP  =>  [
            Wallet::PAYZAPP
        ],
        self::WALLET_PHONEPE  =>  [
            Wallet::PHONEPE
        ],
        self::WALLET_PAYPAL  =>  [
            Wallet::PAYPAL
        ],
        self::PAYTM  =>  [
            Wallet::PAYTM
        ],
        self::MOBIKWIK  =>  [
            Wallet::MOBIKWIK
        ],
        self::WALLET_PHONEPESWITCH  =>  [
            Wallet::PHONEPE_SWITCH
        ],
        self::PAYU  =>  [
            Wallet::ITZCASH, Wallet::AIRTELMONEY, Wallet::FREECHARGE, Wallet::OXIGEN, Wallet::PAYZAPP, Wallet::AMEXEASYCLICK,
            Wallet::OLAMONEY, Wallet::PAYCASH, Wallet::JIOMONEY, Wallet::AMAZONPAY, Wallet::CITIBANKREWARDS, Wallet::PAYTM, Wallet::PHONEPE
        ],
        self::CCAVENUE  =>  [
            Wallet::FREECHARGE, Wallet::ITZCASH, Wallet::JIOMONEY, Wallet::MOBIKWIK, Wallet::PAYTM
        ],
        self::EGHL  => [
            Wallet::MCASH, Wallet::BOOST, Wallet::TOUCHNGO, Wallet::GRABPAY
        ]
    ];

    // These gateways do not support power wallet flow. for example, freecharge behaves as powerwallet in razorpay,
    // but for payu and ccavenue powerwallet flow is not supported for freecharge.
    protected static $GatewaysWithoutPowerWalletSupport = [
        self::PAYU,
        self::CCAVENUE,
    ];

    // Address collection is required for recurring payments routed through this international gateway
   const INTERNATIONAL_RECURRING_ADDRESS_REQUIRED = [
        Gateway::CHECKOUT_DOT_COM
    ];

    // Gateway token2 field is expected in cps response for these gateways
    const CPS_GATEWAY_TOKEN2_REQUIRED = [
        Gateway::CHECKOUT_DOT_COM
    ];

    const INTERNATIONAL_BANK_TRANSFER_SUPPORTED_CURRENCIES = [
        Currency::USD, Currency::AUD, Currency::CAD, Currency::HRK, Currency::DKK,
        Currency::CZK, Currency::EUR, Currency::HKD, Currency::HUF, Currency::ILS,
        Currency::KES, Currency::MXN, Currency::NZD, Currency::NOK, Currency::QAR,
        Currency::RUB, Currency::SAR, Currency::SGD, Currency::ZAR, Currency::SEK,
        Currency::CHF, Currency::THB, Currency::GBP, Currency::AED
    ];

    // List of Debit Emi Gateways that support the OTP flow for a given payment
    public static $OtpSupportDebitEmiGateways =[
        Payment\Gateway::KOTAK_DEBIT_EMI,
        Payment\Gateway::INDUSIND_DEBIT_EMI
    ];

    const CURRENCIES_SUPPORTED_BY_INTL_BANK_TRANSFER_BY_MODE = [
        IntlBankTransfer::SWIFT => [
            Currency::USD, Currency::AUD, Currency::CAD, Currency::HRK, Currency::DKK,
            Currency::CZK, Currency::EUR, Currency::HKD, Currency::HUF, Currency::ILS,
            Currency::KES, Currency::MXN, Currency::NZD, Currency::NOK, Currency::QAR,
            Currency::RUB, Currency::SAR, Currency::SGD, Currency::ZAR, Currency::SEK,
            Currency::CHF, Currency::THB, Currency::GBP, Currency::AED
        ],
        IntlBankTransfer::ACH => [Currency::USD]
    ];

    const CURRENCY_TO_MODE_MAPPING_FOR_INTL_BANK_TRANSFER = [
        Currency::USD              => IntlBankTransfer::ACH,
        self::SWIFT                => IntlBankTransfer::SWIFT
    ];

    const MODE_TO_VA_CURRENCY_ACCOUNT_MAPPING_FOR_INTL_BANK_TRANSFER = [
        IntlBankTransfer::SWIFT => self::SWIFT,
        IntlBankTransfer::ACH => Currency::USD
    ];

    const OPGSP_SETTLEMENT_GATEWAYS = [
        self::EMERCHANTPAY,
        self::CURRENCY_CLOUD,
        self::CHECKOUT_DOT_COM,
    ];

    public static function isNonTerminalGateway(string $gateway)
    {
        return in_array($gateway, self::$nonTerminalGateways, true);
    }

    public static function isCardlessEmiProviderAndRedirectFlowProvider($provider)
    {
        return ((in_array($provider, Payment\Gateway::$redirectFlowProvider, true) === true) or
                (in_array(CardlessEmi::getProviderForBank($provider), Payment\Gateway::$redirectFlowProvider, true) === true));
    }

    public static function isCardlessEmiSkipCheckAccountProvider($provider)
    {
        return ((in_array($provider, Payment\Gateway::$checkAccountSkipProvider, true) === true) or
            (in_array(CardlessEmi::getProviderForBank($provider), Payment\Gateway::$checkAccountSkipProvider, true) === true));
    }

    public static function isCardlessEmiPlanValidationApplicable($input, $payment, $mode)
    {
        if (Payment\Gateway::isCardlessEmiSkipCheckAccountProvider($input[Payment\Entity::PROVIDER]))
        {
            return false;
        }

        if (($input[Payment\Entity::PROVIDER] === CardlessEmi::EARLYSALARY) and
            ($payment->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::REDIRECT_TO_EARLYSALARY)))
        {
            return false;
        }

        if (($input[Payment\Entity::PROVIDER] === CardlessEmi::ZESTMONEY) and
            ($payment->getCpsRoute() === Payment\Entity::NB_PLUS_SERVICE))
        {
            return false;
        }

        if (($input[Payment\Entity::PROVIDER] === CardlessEmi::ZESTMONEY) and
            ($mode == Mode::TEST) )
        {
            return false;
        }

        return true;
    }

    public static function getAcquirerName(string $acquirer)
    {
        $code = self::$acquirerToCodeMap[$acquirer];

        if ($code === 'AMEX')
        {
            return Network::getFullName($code);
        }

        return BaseIFSC::getBankName($code);
    }

    public static function isDirectNetbankingGateway(string $gateway)
    {
        $directNetbankingGateways = array_values(self::$netbankingToGatewayMap);

        return in_array($gateway, $directNetbankingGateways, true);
    }

    public static function getBankForDirectNetbankingGateway(string $gateway)
    {
        $gatewayToBankMap = array_flip(self::$netbankingToGatewayMap);

        return $gatewayToBankMap[$gateway];
    }

    public static function isRecurringGateway($gateway): bool
    {
        return in_array($gateway, self::$recurringGateways, true);
    }

    public static function isCardMandateGateways($gateway): bool
    {
        return in_array($gateway, self::$cardMandateGateways, true);
    }

    public static function isOnlyAuthorizationGateway($gateway): bool
    {
        return in_array($gateway, self::$onlyAuthorizationGateway, true);
    }

    public static function authorizationToAuthenticationGateway($gateway, $default = null)
    {
        return self::$authorizationAuthenticationGatewayMap[$gateway] ?? $default;
    }

    public static function isZeroRupeeFlowSupported($bank): bool
    {
        return in_array($bank, self::$zeroRupeeEmandateBanks, true);
    }

    public static function isUpiIntentFlowSupported($gateway): bool
    {
        return in_array($gateway, self::$upiIntentGateways, true);
    }

    public static function isIssuerSupportedForPinAuthType($issuer, $gateway, $acquirer)
    {
        $pinAuthGateways = self::$gatewayAcquirerIfscMapping;

        if ((isset($pinAuthGateways[$gateway][$acquirer]) === true) and
            (in_array($issuer, $pinAuthGateways[$gateway][$acquirer], true) === true))
        {
            return true;
        }

        return false;
    }

    public static function isStaticCallbackGateway($gateway)
    {
        return in_array($gateway, self::$staticCallbackGateways, true);
    }

    public static function isWebhookEnabledGateway($gateway)
    {
        return in_array($gateway, self::$webhooksEnabledGateways, true);
    }

    public static function isNetbankingS2SRedirectGateway($gateway)
    {
        return in_array($gateway, self::$netbankingS2SRedirectGateways, true);
    }

    /**
     * Checks whether the bank requires a file-based system to register for eMandate
     *
     * @param string $gateway
     *
     * @return bool
     */

    public static function isFileBasedEMandateRegistrationGateway(string $gateway): bool
    {
        return (in_array($gateway, self::$fileBasedEMandateRegistrationGateways) === true);
    }

    public static function isApiBasedAsyncEMandateGateway($gateway): bool
    {
        if (empty($gateway) === true)
        {
            return false;
        }

        return (in_array($gateway, self::$apiBasedAsyncEMandateGateways) === true);
    }

    /**
     * @param string $gateway
     *
     * @return bool
     */

    public static function isFileBasedEMandateDebitGateway(string $gateway): bool
    {
        return (in_array($gateway, self::$fileBasedEMandateDebitGateways) === true);
    }

    public static function shouldCreateEnachGatewayEntity(string $gateway): bool
    {
        return (in_array($gateway, self::$createGatewayEntityForDebitPaymentDuringPaymentFlow) === true);
    }

    public static function isSupportedEmandateBank($bank): bool
    {
        $banks = self::getAllEMandateBanks();

        return (in_array($bank, $banks, true) === true);
    }

    public static function isSupportedEmandateDirectIntegrationGateway($gateway): bool
    {
        $gateways = self::EMANDATE_DIRECT_INTEGRATION_GATEWAYS;

        return (in_array($gateway, $gateways, true) === true);
    }

    public static function getAllEMandateBanks(): array
    {
        $banks = [];

        foreach (self::getEmandateAuthTypeToBankMap() as $emandateBanks)
        {
             $banks = array_merge($banks, $emandateBanks);
        }

        return array_values(array_unique($banks));
    }

    public static function getBharatQrCardNetworks(): array
    {
        $networks = [];

        foreach (self::$bharatQrCardNetwork as $bharatQrGateways)
        {
            $networks = array_merge($networks, $bharatQrGateways);
        }

        return array_values(array_unique($networks));
    }

    public static function getZeroRupeeEmandateBanks(): array
    {
        return self::$zeroRupeeEmandateBanks;
    }

    public static function getAvailableEmandateBanksForAuthType(string $authType): array
    {
        $banks = [];

        $emandateBanks = self::getEmandateAuthTypeToBankMap();

        if (isset($emandateBanks[$authType]) === true)
        {
            $banks = $emandateBanks[$authType];
        }

        if ($authType === Payment\AuthType::AADHAAR)
        {
            $banks = Payment\Gateway::removeAadhaarEmandateRegistrationDisabledBanks($banks);
        }

        return $banks;
    }

    public static function getEmandateGatewaysForAuthType(string $authType): array
    {
        $gateways = [];

        if (isset(self::$authTypeToEmandateGatewayMap[$authType]) === true)
        {
            $gateways = self::$authTypeToEmandateGatewayMap[$authType];
        }

        return $gateways;
    }

    public static function getAvailableEmandateBanks()
    {
        $emandateBanks = [];

        $emandateBanksMap = self::getEmandateAuthTypeToBankMap();

        foreach ($emandateBanksMap as $authType => $banks)
        {
            $emandateBanks = array_merge($emandateBanks, $banks);
        }

        return array_values(array_unique($emandateBanks));
    }

    public static function getChannel($gateway)
    {
        return self::$channels[$gateway];
    }

    public static function isValidGateway($gateway)
    {
        return (defined(__CLASS__ . '::' . strtoupper($gateway)));
    }

    public static function isValidBharatQrGateway($gateway)
    {
        return in_array($gateway , self::$bharatQrGateways, true);
    }

    public static function isValidUpiTransferGateway($gateway)
    {
        return in_array($gateway , self::$upiTransferGateway, true);
    }

    public static function isValidGatewayAcquirer(string $gatewayAcquirer)
    {
        return array_key_exists($gatewayAcquirer, self::GATEWAY_ACQUIRERS);
    }

    public static function isValidAcquirerForGateway(string $gatewayAcquirer, string $gateway): bool
    {
        $validAcquirersForGateway = self::GATEWAY_ACQUIRERS[$gateway];

        return in_array($gatewayAcquirer, $validAcquirersForGateway, true);
    }

    public static function getGatewayForWallet($wallet)
    {
        return self::$walletToGatewayMap[$wallet];
    }

    public static function getWalletForGateway($gateway)
    {
        if (in_array($gateway, self::$methodMap[Method::WALLET]) === false)
        {
            throw new Exception\LogicException(
                'Unknown wallet gateway',
                null,
                [
                    'gateway' => $gateway,
                ]);
        }

        return array_flip(self::$walletToGatewayMap)[$gateway];
    }

    public static function validateGateway($gateway)
    {
        if (self::isValidGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway is invalid',
                'gateway',
                [
                    'gateway' => $gateway
                ]);
        }
    }

    public static function isMethodSupported($method, $gateway)
    {
        return (in_array($gateway, self::$methodMap[$method]));
    }

    public static function isGatewaySupportingGetRedirectForm($gateway)
    {
        return (in_array($gateway, self::$gatewaysSupportingGetRedirectForm, true));
    }

    public static function isGatewayPhonepeSwitch($gateway)
    {
        return ($gateway === Payment\Gateway::WALLET_PHONEPESWITCH);
    }

    /**
     * If network code is null, the function returns back whether the
     * given gateway has support for authAndCapture or not.
     * If network code is not null, the functions returns back whether
     * the given gateway has support for authAndCapture for the given
     * network.
     *
     * @param string $gateway
     * @param string $networkCode
     * @return bool
     */
    public static function supportsAuthAndCapture($gateway, $networkCode = null): bool
    {
        $arrayKeys = array_keys(self::$authAndCapture);

        $supportsAuthAndCapture = in_array($gateway, $arrayKeys, true);

        if ($supportsAuthAndCapture === false)
        {
            return false;
        }
        else
        {
            if ($networkCode === null)
            {
                return $supportsAuthAndCapture;
            }
            else
            {
                return self::supportsAuthAndCaptureForNetwork($gateway, $networkCode);
            }
        }
    }

    /**
     * If network code is null, the function returns back whether the
     * given gateway has support for Purchase or not.
     * If network code is not null, the functions returns back whether
     * the given gateway has support for Purchase for the given
     * network.
     *
     * @param string $gateway
     * @param string $networkCode
     * @return bool
     */
    public static function supportsPurchase($gateway, $networkCode = null, $acquirer=null): bool
    {
        $supportsPurchase = isset(self::$gatewayNetworkPurchaseSupport[$gateway]);

        if ($supportsPurchase === true)
        {
            return self::isNetworkSupportedForPurchase($gateway, $networkCode, $acquirer);
        }

        return true;
    }

    /**
     * supportsReverse checks if gateway or the acquirer supports auth payments reversals
     *
     * @param $gateway
     * @param $gatewayAcquirer
     * @return bool
     */
    public static function supportsReverse($gateway, $gatewayAcquirer)
    {
        return ((in_array($gateway, self::$reverseSupportedGateways, true) === true) ||
                (in_array($gatewayAcquirer, self::$reverseSupportedGatewayAcquirers, true) === true));
    }

    /**
     * Whether the gateway supports async payments
     * @param  string $gateway
     * @return boolean
     */
    public static function supportsAsync($gateway)
    {
        return in_array($gateway, self::$asynchronous, true);
    }

    public static function supportsHeadlessBrowser($gateway, $networkCode)
    {
        if ((isset(self::$headless[$gateway]) === true) and
            (in_array($networkCode, self::$headless[$gateway], true) === true))
        {
            return true;
        }

        return false;
    }

    public static function supportsAuthAndCaptureForNetwork($gateway, $networkCode)
    {
        // This means that all the networks are supported by the gateway for authAndCapture.
        if (isset(self::$authAndCapture[$gateway][self::NOT_SUPPORTED]) === false)
        {
            return true;
        }

        // Get all the networks which are NOT supported by the gateway for authAndCapture.
        $notSupportedNetworks = self::$authAndCapture[$gateway][self::NOT_SUPPORTED];

        // If a given network is in the list of notSupportedNetworks, it means that the network
        // is not supported by the gateway for authAndCapture.
        if (in_array($networkCode, $notSupportedNetworks))
        {
            return false;
        }

        return true;
    }

    public static function isNetworkSupportedForPurchase($gateway, $networkCode, $acquirer)
    {
        // This means that all the networks are supported by the gateway for Purchase.
        if ((isset(self::$gatewayNetworkPurchaseSupport[$gateway][self::NOT_SUPPORTED]) === false) or
            ($networkCode === null))
        {
            return true;
        }

        //Some gateways may support purchase for only a selected few acquirers for purchase
        $partialSupportedNetworks = self::$gatewayNetworkPurchaseSupport[$gateway][self::SUPPORTED];

        if(in_array($networkCode, $partialSupportedNetworks, true) === true)
        {
            $supportedAcquirers = $partialSupportedNetworks[$networkCode];

            if(in_array($acquirer, $supportedAcquirers, true) === true)
            {
                return true;
            }
        }


        // Get all the networks which are NOT supported by the gateway for Purchase.
        $notSupportedNetworks = self::$gatewayNetworkPurchaseSupport[$gateway][self::NOT_SUPPORTED];

        // If a given network is in the list of notSupportedNetworks, it means that the network
        // is not supported by the gateway for Purchase.
        return (in_array($networkCode, $notSupportedNetworks, true) === false);
    }

    public static function isPowerWallet($wallet)
    {
        return (in_array($wallet, self::POWER_WALLETS));
    }

    public static function isAutoDebitPowerWallet($gateway)
    {
        return (in_array($gateway, self::AUTO_DEBIT_POWER_WALLETS, true));
    }

    public static function isAuthAndPowerWallet(string $wallet)
    {
        return (in_array($wallet, self::AUTH_AND_POWER_WALLETS, true));
    }

    public static function canGatewayTopup($gateway)
    {
        return (in_array($gateway, self::TOPUP_GATEWAYS));
    }

    /**
     * - If the gateway is not present in the cardNetworkMap OR if gateway
     *   is present but does not support the network, [supported = false]
     * - If supported = true AND recurring = true and if gateway is present in cardNetworkRecurringMap,
     *     - supported = true/false based on whether the gateway supports the network.
     *   The reason why we let it be true even if the gateway is not present in cardNetworkRecurringMap
     *   is because there are very few gateways which would have a different set of networks for
     *   supporting recurring. Most gateways support the same set of networks as present in cardNetworkMap.
     *
     * @param      $network
     * @param      $gateway
     * @param bool $recurring
     *
     * @return bool
     */
    public static function isCardNetworkSupported(string $network, string $gateway, $issuer, bool $recurring = false)
    {
        if ((isset(Gateway::$ignoreCardNetworkSupport[$issuer]) === true) AND
            (in_array($gateway, Gateway::$ignoreCardNetworkSupport[$issuer]) === true))
        {
            return true;
        }

        $supported = ((array_key_exists($gateway, self::$cardNetworkMap) === true) and
                      (in_array($network, self::$cardNetworkMap[$gateway], true) === true));

        if (($supported === true) and
            ($recurring === true) and
            (isset(self::$cardNetworkRecurringMap[$gateway]) === true))
        {
            $supported = (in_array($network, self::$cardNetworkRecurringMap[$gateway], true) === true);
        }

        return $supported;
    }

    public static function isBharatQrCardNetworkSupported(string $network, string $gateway)
    {
        return ((array_key_exists($gateway, self::$bharatQrCardNetwork) === true) and
                (in_array($network, self::$bharatQrCardNetwork[$gateway], true) === true));
    }

    public static function getNetworksSupportedForCardRecurring(): array
    {
        return self::$recurringCardNetworks;
    }

    public static function isDirectDebitSupported(string $networkCode): bool
    {
        return (in_array($networkCode, self::$directDebitCardNetworks, true) === true);
    }

    public static function getIssuersSupportedForDebitCardRecurring(): array
    {
        return self::$recurringDebitCardBanks;
    }

    public static function isDirectDebitEmandateBank(string $bank): bool
    {
        return (in_array($bank, self::EMANDATE_NB_DIRECT_DEBIT_BANK, true) === true);
    }

    public static function isDirectDebitEmandateGateway(string $gateway): bool
    {
        return (in_array($gateway, self::EMANDATE_NB_DIRECT_DEBIT_GATEWAY, true) === true);
    }

    public static function getExclusiveNetworksForGateway(string $gateway)
    {
        $exclusiveNetworks = self::$cardNetworkMap[$gateway];

        foreach (self::CARD_GATEWAYS_LIVE as $cardGateway)
        {
            if ($gateway !== $cardGateway)
            {
                $networks = self::$cardNetworkMap[$cardGateway];

                $exclusiveNetworks = array_diff($exclusiveNetworks, $networks);
            }
        }

        // Filter out the UNKNOWN network if present
        $exclusiveNetworks = array_filter($exclusiveNetworks, function ($network)
        {
            return ($network !== Network::UNKNOWN);
        }, ARRAY_FILTER_USE_BOTH);

        $exclusiveNetworks = array_values($exclusiveNetworks);

        return $exclusiveNetworks;
    }

    public static function isNetworkExclusiveToGateway(string $network, string $gateway)
    {
        $exclusiveNetworks = self::getExclusiveNetworksForGateway($gateway);

        return in_array($network, $exclusiveNetworks, true) === true;
    }

    public static function getGatewaysForNetbankingBank($bank, $isTPV = false): array
    {
        $gateways = [];

        // Check for direct netbanking gateway
        if (Netbanking::isNetbankingBankDirectlySupported($bank) === true)
        {
            $gateways[] = self::$netbankingToGatewayMap[$bank];
        }

        // Add netbanking gateways that support bank
        foreach (self::$netbankingGateways as $netbankingGateway)
        {
            if (Netbanking::isBankSupportedByGateway($bank, $netbankingGateway, $isTPV) === true)
            {
                $gateways[] = $netbankingGateway;
            }
        }

        return $gateways;
    }

    public static function getGatewaysForNetbankingBankIndexed($bank)
    {
        $gateways = [];

        // Check for direct netbanking gateway
        if (Netbanking::isNetbankingBankDirectlySupported($bank) === true)
        {
            $gateways['direct'] = self::$netbankingToGatewayMap[$bank];
        }

        // Add netbanking gateways that support bank
        foreach (self::$netbankingGateways as $netbankingGateway)
        {
            if (Netbanking::isBankSupportedByGateway($bank, $netbankingGateway))
            {
                $gateways['gateway'][] = $netbankingGateway;
            }
        }

        return $gateways;
    }

    public static function getEmandateAuthTypeToBankMap()
    {
        $netbankingBanks = array_unique(
                                         array_merge(
                                             self::EMANDATE_NB_DIRECT_BANKS,
                                             self::ENACH_NPCI_NB_AUTH_NETBANKING_BANKS
                                          )
                           );

        $netbankingBanks = array_values($netbankingBanks);

        return [
            AuthType::NETBANKING  => $netbankingBanks,
            AuthType::AADHAAR     => self::EMANDATE_AADHAAR_BANKS,
            AuthType::AADHAAR_FP  => self::EMANDATE_AADHAAR_BANKS,
            AuthType::DEBITCARD   => self::ENACH_NPCI_NB_AUTH_CARD_BANKS,
        ];
    }

    public static function getTerminalsForValidateVpaForMode(string $mode)
    {
        $config = config('gateway.validate_vpa_terminal_ids');

        try
        {
            $tids = str_getcsv($config[$mode]);

            if (count($tids) === 0)
            {
                throw new Exception\RuntimeException('At least one terminal id is needed');
            }

            return array_map(
                function($tid)
                {
                    $trimmed = trim($tid);

                    if (strlen($trimmed) !== Entity::ID_LENGTH)
                    {
                        throw new Exception\RuntimeException('Invalid length for terminal Id');
                    }
                    return $trimmed;
                },
                $tids);
        }
        catch (\Throwable $throwable)
        {
            // As a fallback, we are still relying on older implementation
            // This is to ignore any human error with envs on production
            return self::$upiValidateVpaTerminals[$mode];
        }
    }

    public static function isCaptureVerifyEnabledGateway($gateway)
    {
        return (in_array($gateway, Payment\Gateway::$captureVerifyEnabled, true) === true);
    }

    public static function isCaptureVerifyQREnabledGateways($gateway)
    {
        return (in_array($gateway, Payment\Gateway::$captureVerifyQREnabledGateways, true) === true);
    }

    public static function isCaptureVerifyReportEnabledGateways($gateway)
    {
        return (in_array($gateway, Payment\Gateway::$captureVerifyReportDisabledGateways, true) === false);
    }

    public static function removeEmandateRegistrationDisabledBanks(array $banks)
    {
        return array_diff($banks, static::EMANDATE_REGISTRATION_DISABLED_BANKS);
    }

    public static function removeNetbankingEmandateRegistrationDisabledBanks(array $banks)
    {
        return array_diff($banks, static::NB_EMANDATE_REGISTRATION_DISABLED_BANKS);
    }
    /*
     * This change is for having a separate list for registration disabled banks as the bank may support
     * debits but might be temporarily blocked for registrations and should be removed from the list to be re-enabled.
     */
    public static function removeAadhaarEmandateRegistrationDisabledBanks(array $banks)
    {
        return array_diff($banks, static::AADHAAR_EMANDATE_REGISTRATION_DISABLED_BANKS);
    }

    /**
     * Enach through NPCI has mandated that additional information has to be displayed
     * when rendering the response page to the user.
     * emandate_details contains this additional information. In this flow
     * we open a different view based on the requirements set by NPCI after callback
     *
     * @param $input
     * @return bool
     */
    public static function isNachNbResponseFlow($input)
    {
        if ((empty($input) === false) and (isset($input['emandate_details']) === true))
        {
            return true;
        }

        return false;
    }

    public static function isAutoDebitPowerWalletSupported($payment)
    {
        $gateway = $payment->getGateway();

        if ($gateway === Method::PAYLATER)
        {
            $gateway = $payment->getWallet();
        }

        // We support power wallet flow if we can topup and autodebit.
        // Generally, power wallets allow topup of requests.
        if (self::isAutoDebitPowerWallet($gateway) === true)
        {
            return true;
        }

        return false;
    }

    public static function shouldAlwaysRouteThroughCardPaymentService($gateway)
    {
        $gateways = [
            self::PAYTM,
            self::ISG,
            self::HITACHI,
            self::HDFC,
            self::CYBERSOURCE,
            self::CARD_FSS,
            self::AXIS_MIGS,
            self::AMEX,
            self::MPGS,
            self::PAYSECURE,
            self::FIRST_DATA,
            self::PAYU,
            self::CASHFREE,
            self::ZAAKPAY,
            self::FULCRUM,
            self::CCAVENUE,
            self::PINELABS,
            self::CHECKOUT_DOT_COM,
            self::INGENICO,
            self::BILLDESK_OPTIMIZER,
            self::KOTAK_DEBIT_EMI,
            self::INDUSIND_DEBIT_EMI,
            self::AXIS_TOKENHQ,
            self::ICICI,
            self::OPTIMIZER_RAZORPAY,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function isUpiOtmSupportedGateway($gateway): bool
    {
        return in_array($gateway, self::$upiOtmGateways, true);
    }

    public static function isUpiRecurringSupportedGateway($gateway): bool
    {
        return in_array($gateway, self::$upiRecurringGateways, true);
    }

    public static function isCardPaymentServiceGateway($gateway)
    {
        $gateways = [
            self::AXIS_MIGS,
            self::CARD_FSS,
            self::CYBERSOURCE,
            self::FIRST_DATA,
            self::HDFC,
            self::HITACHI,
            self::MPGS,
            self::MPI_BLADE,
            self::MPI_ENSTAGE,
            self::PAYTM,
            self::AMEX,
            self::PAYSECURE,
            self::ISG,
            self::PAYU,
            self::CASHFREE,
            self::ZAAKPAY,
            self::FULCRUM,
            self::CCAVENUE,
            self::PINELABS,
            self::CHECKOUT_DOT_COM,
            self::INGENICO,
            self::BILLDESK_OPTIMIZER,
            self::KOTAK_DEBIT_EMI,
            self::INDUSIND_DEBIT_EMI,
            self::AXIS_TOKENHQ,
            self::ICICI,
            self::OPTIMIZER_RAZORPAY,
        ];

        return (in_array($gateway, $gateways, true));
    }

    /**
     * Method to filter gateways which provide empty callbacks
     * @param $gateway
     * @return bool
     */
    public static function isGatewayCallbackEmpty($gateway): bool
    {
        $gateways = [
            self::CHECKOUT_DOT_COM,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function gatewaysAlwaysRoutedThroughNbplusService($gateway, $bankCode, $method, $payment = null): bool
    {
        $gateways = [
            self::NETBANKING_SVC,
            self::NETBANKING_IDBI,
            self::NETBANKING_JKB,
            self::NETBANKING_IOB,
            self::NETBANKING_FSB,
            self::PAYU,
            self::ZAAKPAY,
            self::CASHFREE,
            self::NETBANKING_DCB,
            self::NETBANKING_IBK,
            self::NETBANKING_UBI,
            self::NETBANKING_AUSF,
            self::NETBANKING_DLB,
            self::NETBANKING_SCB,
            self::NETBANKING_FEDERAL,
            self::NETBANKING_NSDL,
            self::NETBANKING_YESB,
            self::NETBANKING_CUB,
            self::NETBANKING_SIB,
            self::CCAVENUE,
            self::NETBANKING_INDUSIND,
            self::NETBANKING_RBL,
            self::NETBANKING_CBI,
            self::NETBANKING_CSB,
            self::NETBANKING_KVB,
            self::TWID,
            self::NETBANKING_BDBL,
            self::NETBANKING_UJJIVAN,
            self::NETBANKING_SARASWAT,
            self::NETBANKING_UCO,
            self::EMERCHANTPAY,
            self::NETBANKING_TMB,
            self::NETBANKING_KARNATAKA,
            self::NETBANKING_CANARA,
            self::NETBANKING_DBS,
            self::INGENICO,
            self::NETBANKING_ICICI,
            self::BILLDESK_OPTIMIZER,
            self::OPTIMIZER_RAZORPAY,
        ];

        $isRouted = in_array($gateway, $gateways, true);

        if ($isRouted === true)
        {
            return $isRouted;
        }

        $gatewayWithBankCodes = [
            self::NETBANKING_KOTAK => [Payment\Processor\Netbanking::KKBK_C],
            self::NETBANKING_HDFC  => [Payment\Processor\Netbanking::HDFC_C],
        ];

        $isRouted = ((in_array($gateway, array_keys($gatewayWithBankCodes), true)) and (in_array($bankCode, $gatewayWithBankCodes[$gateway], true)));

        if ($isRouted === true)
        {
            return $isRouted;
        }

        $acquirerGateways = [
            self::CARDLESS_EMI => [
                CardlessEmi::WALNUT369,
                CardlessEmi::SEZZLE,
                CardlessEmi::ZESTMONEY,
            ],
            self::PAYLATER     => [
                Paylater::LAZYPAY,
            ],
        ];

        if($payment !== null && in_array($gateway, array_keys($acquirerGateways), true))
        {
            $gateways = $acquirerGateways[$gateway];

            $gateway = $payment->getWallet();

            return (in_array($gateway, $gateways, true));
        }

        $gateways = [
            Method::NETBANKING => [
                self::ATOM,
                self::BILLDESK,
                self::NETBANKING_BOB,
                self::NETBANKING_IDFC,
                self::NETBANKING_PNB,
                self::NETBANKING_SBI,
                self::NETBANKING_AXIS,
                self::NETBANKING_HDFC,
            ],
            Method::WALLET => [
                self::WALLET_AMAZONPAY,
                self::WALLET_BAJAJ,
                self::WALLET_PAYZAPP,
                self::WALLET_PAYPAL,
            ]
        ];

        if ((isset($gateways[$method]) === true) and
            (in_array($gateway, $gateways[$method], true) === true))
        {
            return true;
        }

        return false;
    }

    public static function gatewaysPartiallyMigratedToNbPlusWithBankCode($gateway)
    {
        $gatewayPartiallyMigrated = [
            self::NETBANKING_BOB,
            self::NETBANKING_HDFC,
        ];

        return (in_array($gateway, $gatewayPartiallyMigrated, true));
    }

    public static function gatewayMigratedToNbPlusOnTerminalLevel($gateway): bool
    {
        $gateways = [
            self::NETBANKING_KOTAK,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function isNbPlusServiceGateway($gateway, $payment = null): bool
    {
        $gateways = [
            self::ATOM,
            self::NETBANKING_CSB,
            self::NETBANKING_CUB,
            self::NETBANKING_SVC,
            self::NETBANKING_ALLAHABAD,
            self::NETBANKING_KVB,
            self::NETBANKING_INDUSIND,
            self::NETBANKING_ICICI,
            self::NETBANKING_HDFC,
            self::NETBANKING_JSB,
            self::BILLDESK,
            self::NETBANKING_YESB,
            self::NETBANKING_SIB,
            self::NETBANKING_IDBI,
            self::NETBANKING_BOB,
            self::NETBANKING_JKB,
            self::NETBANKING_IOB,
            self::NETBANKING_FSB,
            self::PAYTM,
            self::PAYU,
            self::ZAAKPAY,
            self::CASHFREE,
            self::NETBANKING_IDFC,
            self::NETBANKING_OBC,
            self::NETBANKING_DCB,
            self::NETBANKING_UBI,
            self::NETBANKING_RBL,
            self::NETBANKING_SCB,
            self::NETBANKING_IBK,
            self::NETBANKING_CBI,
            self::CCAVENUE,
            self::NETBANKING_FEDERAL,
            self::NETBANKING_CANARA,
            self::NETBANKING_KOTAK,
            self::NETBANKING_AUSF,
            self::NETBANKING_DLB,
            self::NETBANKING_SBI,
            self::NETBANKING_NSDL,
            self::TWID,
            self::NETBANKING_PNB,
            self::NETBANKING_BDBL,
            self::NETBANKING_UJJIVAN,
            self::NETBANKING_SARASWAT,
            self::NETBANKING_UCO,
            self::EMERCHANTPAY,
            self::NETBANKING_TMB,
            self::NETBANKING_KARNATAKA,
            self::WALLET_FREECHARGE,
            self::NETBANKING_DBS,
            self::NETBANKING_HDFC,
            self::INGENICO,
            self::WALLET_PAYZAPP,
            self::WALLET_PHONEPE,
            self::WALLET_AMAZONPAY,
            self::NETBANKING_AXIS,
            self::NETBANKING_AIRTEL,
            self::BILLDESK_OPTIMIZER,
            self::NETBANKING_EQUITAS,
            self::WALLET_BAJAJ,
            self::WALLET_PAYPAL,
            self::OPTIMIZER_RAZORPAY,
        ];

        $acquirerGateways = [
            self::CARDLESS_EMI => [
                CardlessEmi::WALNUT369,
                CardlessEmi::SEZZLE,
                CardlessEmi::ZESTMONEY,
            ],
            self::PAYLATER     => [
                Paylater::LAZYPAY,
            ],
        ];

        if(($gateway === self::ENACH_NPCI_NETBANKING) and ($payment['recurring_type'] === 'initial'))
        {
            return true;
        }

        if($payment !== null && in_array($gateway, array_keys($acquirerGateways), true))
        {
            $gateways = $acquirerGateways[$gateway];

            $gateway = $payment->getWallet();
        }

        return (in_array($gateway, $gateways, true));
    }

    public static function canRunOtpFlowViaNbPlus($payment)
    {
        $gateways = [
            self::WALLET_FREECHARGE,
            self::WALLET_BAJAJ,
        ];

        $gateway = $payment[Payment\Entity::GATEWAY];

        $acquirerGateways = [
            self::PAYLATER     => [
                Paylater::LAZYPAY,
            ],
        ];

        if(in_array($gateway, array_keys($acquirerGateways), true))
        {
            $gateways = $acquirerGateways[$gateway];

            $gateway = $payment[Payment\Entity::WALLET];
        }

        return ((in_array($gateway, $gateways, true)) and ($payment[Payment\Entity::CPS_ROUTE] === Payment\Entity::NB_PLUS_SERVICE));
    }

    public static function shouldSkipDebit($payment)
    {
        $gateways = [
            Gateway::WALLET_BAJAJ,
        ];

        $gateway = $payment[Payment\Entity::GATEWAY];

        $acquirerGateways = [
            self::PAYLATER     => [
                Paylater::LAZYPAY,
            ],
        ];

        if(in_array($gateway, array_keys($acquirerGateways), true))
        {
            $gateways = $acquirerGateways[$gateway];

            $gateway = $payment[Payment\Entity::WALLET];
        }

        return (in_array($gateway, $gateways, true));
    }

    /**
     * Some gateways, for example sbi netbanking expect us to send the sequence no or the order in which the refunds
     * were created. If a payment p1 has three refunds, they would expect us to track the order in which they are created
     * r1, r2, r3.
     *
     * @param $payment
     * @return bool
     *
     */

    public static function isSequenceNoBasedRefund($payment)
    {
        $gateway = $payment->getGateway();

        $method = $payment->getMethod();

        return ((in_array($gateway, self::$sequenceNoBasedRefundGateways, true) === true) and
                ($method === Method::NETBANKING));
    }

    public static function isUpiPaymentServiceGateway($gateway): bool
    {
        $gateways = [
            self::UPI_AIRTEL,
            self::UPI_YESBANK,
            self::UPI_JUSPAY,
            self::UPI_SBI,
            self::UPI_ICICI,
            self::UPI_AXIS,
            self::UPI_MINDGATE,
            self::UPI_KOTAK,
            self::UPI_AXISOLIVE,
            self::UPI_RZPRBL,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function isUpiPaymentServicePreProcessGateway($gateway): bool
    {
        $gateways = [
            self::UPI_AIRTEL,
            self::UPI_YESBANK,
            self::UPI_KOTAK,
            self::UPI_AXISOLIVE,
            self::UPI_RZPRBL,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function isUpiPaymentServiceFullyRamped($gateway): bool
    {
        $gateways = [
            self::UPI_KOTAK,
            self::UPI_AXISOLIVE,
            self::UPI_RZPRBL,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function isUnexpectedPaymentOnCallbackDisabled($gateway): bool
    {
        $gateways = [
            self::UPI_KOTAK,
            self::UPI_RZPRBL,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function getSupportedWalletsForGateway($gateway)
    {
        return self::$supportedWallets[$gateway];
    }

    public static function getAllWalletSupportingGateways()
    {
        return array_keys(self::$supportedWallets);
    }

    public static function isPowerWalletNotSupportedForGateway($gateway)
    {
        return in_array($gateway, self::$GatewaysWithoutPowerWalletSupport);
    }

    public static function isDCCRequiredApp($app)
    {
        return (in_array($app, self::DCC_REQUIRED_APPS, true));
    }

    public static function isRefundNotSupportedByApp($app)
    {
        return (in_array($app, self::REFUND_NOT_SUPPORTED_APPS, true));
    }

    public static function getSupportedCurrenciesByApp($app) : array
    {
        if((array_key_exists($app,self::CURRENCIES_SUPPORTED_BY_APPS)) === true){
            return self::CURRENCIES_SUPPORTED_BY_APPS[$app];
        }
        return [];
    }

   /*
    * Used at Settlement/Bucket/core.php
    * For Getting Remitter Name from Address Table during OPGSP Settlement Meta Data Creation.
    * @param $gateway
    * @return bool
    */
    public static function isAddressAndNameRequiredGateway($gateway) : bool
    {
        return (in_array($gateway, self::ADDRESS_NAME_REQUIRED_GATEWAYS, true));
    }

    /*
     * Gateways where payment success/failure is not known until we hit their Inquiry API.
     * These gateways do not support callback flow.
     *
     * @param $method - payment method
     * @param $gateway - payment gateway
     * @param $errorCode - error code used to define pending status
     *
     * @return bool
     */
    public static function isTransactionPendingGateway($method, $gateway, $errorCode) : bool
    {
        $methodGatewayMap = [
            Method::UPI => [
                self::PINELABS => ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
            ],
        ];

        if (isset($methodGatewayMap[$method][$gateway]) === true)
        {
            return $methodGatewayMap[$method][$gateway] === $errorCode;
        }

        return false;
    }

    /**
     * Checks if address collection is required for international recurring payments for the $gateway
     * @param $gateway
     * @return bool
     */
    public static function isInternationalRecurringAddressRequired($gateway) : bool
    {
        return (in_array($gateway, self::INTERNATIONAL_RECURRING_ADDRESS_REQUIRED, true));
    }

    /**
     * Checks if gateway_token2 field is required to be present in cps response
     * @param $gateway
     * @return bool
     */
    public static function isCPSGatewayToken2Required($gateway) : bool
    {
        return (in_array($gateway, self::CPS_GATEWAY_TOKEN2_REQUIRED, true));
    }

    public static function getSettlementCurrencyOfPaymentByGateway(Payment\Entity $payment)
    {
        $gateway = $payment->getGateway();
        $gatewayCurrency = $payment->getGatewayCurrency();

        if(array_key_exists($gateway,self::GATEWAY_TO_SETTLEMENT_CURRENCY_MAPPING) === false)
        {
            return null;
        }

        if(in_array($gatewayCurrency,self::GATEWAY_TO_SETTLEMENT_CURRENCY_MAPPING[$gateway]) === true)
        {
            return $payment->getGatewayCurrency();
        }

        return self::GATEWAY_TO_SETTLEMENT_CURRENCY_MAPPING[$gateway][0];
    }

    public static function getSettlementCurrencyByGateway($gateway, $currency)
    {
        if(array_key_exists($gateway,self::GATEWAY_TO_SETTLEMENT_CURRENCY_MAPPING) === false)
        {
            return null;
        }

        if(in_array($currency,self::GATEWAY_TO_SETTLEMENT_CURRENCY_MAPPING[$gateway]) === true)
        {
            return $currency;
        }

        return self::GATEWAY_TO_SETTLEMENT_CURRENCY_MAPPING[$gateway][0];
    }

    public static function isVACurrencySupportedForInternationalBankTransfer($currency) : bool
    {
        return ((array_key_exists($currency,self::CURRENCY_TO_MODE_MAPPING_FOR_INTL_BANK_TRANSFER)) === true);
    }

    public static function getSupportedCurrenciesForIntlBankTransferByMode($mode) : array
    {
        if((array_key_exists($mode,self::CURRENCIES_SUPPORTED_BY_INTL_BANK_TRANSFER_BY_MODE)) === true){
            return self::CURRENCIES_SUPPORTED_BY_INTL_BANK_TRANSFER_BY_MODE[$mode];
        }
        return [];
    }

    /**
     * @throws Exception\BadRequestException when mapping for supported currencies don't exist in intlBankTransfer
     */
    public static function getIntlBankTransferModeByCurrency($currency) : string
    {
        if((array_key_exists($currency,self::CURRENCY_TO_MODE_MAPPING_FOR_INTL_BANK_TRANSFER)) === true)
        {
            return self::CURRENCY_TO_MODE_MAPPING_FOR_INTL_BANK_TRANSFER[$currency];
        }
        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_MODE_NOT_SUPPORTED, [
            'currency' => $currency
        ]);
    }
    /**
     List of Debit Emi Gateways
     */
    public static $debitEmiGateways = array(
        IFSC::KKBK => Gateway::KOTAK_DEBIT_EMI,
        IFSC::INDB => Gateway::INDUSIND_DEBIT_EMI
    );

    /**
     * List of card networks not supported on respective gateways
     *
     * @param $gateway
     * @param $cardNetwork
     * @return bool
     */
    public static function isCardNetworkUnsupportedOnGateway($gateway, $cardNetwork): bool
    {
        if ((empty($gateway) === true) or
            (empty($cardNetwork) === true))
        {
            return false;
        }

        $unsupportedList = [
            self::HITACHI   => [
                Network::AMEX,
                Network::DICL,
            ],
        ];

        if ((isset($unsupportedList[$gateway]) === true) and
            (in_array($cardNetwork, $unsupportedList[$gateway], true) === true))
        {
            return true;
        }

        return false;
    }

       /*
    * Used at Settlement/Bucket/core.php
    * @param $gateway
    * @return bool
    */
    public static function isOPGSPSettlementGateway($gateway) : bool
    {
        return (in_array($gateway, self::OPGSP_SETTLEMENT_GATEWAYS, true));
    }

}
