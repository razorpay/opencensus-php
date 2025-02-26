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
use RZP\Trace\TraceCode;

class Gateway
{
    const AMEX                   = 'amex';
    const ATOM                   = 'atom';
    const PAYU                   = 'payu';
    const CASHFREE               = 'cashfree';
    const PHONEPE                = 'phonepe';
    const ZAAKPAY                = 'zaakpay';
    const CCAVENUE               = 'ccavenue';
    const PINELABS               = 'pinelabs';
    const LYRA                   = 'lyra';
    const INGENICO               = 'ingenico';
    const BILLDESK_OPTIMIZER     = 'billdesk_optimizer';
    const EASEBUZZ_OPTIMIZER     = 'easebuzz_optimizer';
    const CHECKOUT_DOT_COM_OPTIMIZER = 'checkout_dot_com_optimizer';
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
    const YESBANK                = 'yesbank';
    const JKBANK                 = 'jkbank';

    const ESIGNER_DIGIO          = 'esigner_digio';
    const ESIGNER_LEGALDESK      = 'esigner_legaldesk';
    const ENACH_RBL              = 'enach_rbl';
    const ENACH_NPCI_NETBANKING  = 'enach_npci_netbanking';
    const FIRST_DATA             = 'first_data';
    const FULCRUM                = 'fulcrum';
    const MGPS                   = 'mgps';
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
    const CARDLESS_EMI_LIQUILOANS = 'cardless_emi_liquiloans';
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
    const UPI_HDFCMINTOAK        = 'upi_hdfcmintoak';
    const UPI_MINDGATE           = 'upi_mindgate';
    const UPI_SBI                = 'upi_sbi';
    const UPI_AXIS               = 'upi_axis';
    const UPI_ICICI              = 'upi_icici';
    const UPI_JKBANK             = 'upi_jkbank';
    const UPI_HULK               = 'upi_hulk';
    const UPI_RBL                = 'upi_rbl';
    const UPI_AXISOLIVE          = 'upi_axisolive';
    const UPI_YESBANK            = 'upi_yesbank';
    const UPI_KOTAK              = 'upi_kotak';
    const UPI_RZPRBL             = 'upi_rzprbl';
    const UPI_RZPAPB             = 'upi_rzpapb';
    const UPI_RZPAXIS            = 'upi_rzpaxis';
    const UPI_MINDEED            = 'upi_mindeed';
    const AEPS_ICICI             = 'aeps_icici';
    const ISG                    = 'isg';
    const PAYSECURE              = 'paysecure';
    const UPI_AIRTEL             = 'upi_airtel';
    const WORLDLINE              = 'worldline';
    const HDFC_EZETAP            = 'hdfc_ezetap';
    const HDFC_POS               = 'hdfc_pos';
    const UPI_CITI               = 'upi_citi';
    const UPI_JUSPAY             = 'upi_juspay';
    const BILLDESK_SIHUB         = 'billdesk_sihub';
    const MANDATE_HQ             = 'mandate_hq';
    const RUPAY_SIHUB            = 'rupay_sihub';
    const EGHL                   = 'eghl';

    const TNGD                   = 'tngd';
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
    const RZPXPOSTPAID       = 'rzpx_postpaid';
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
    const AMAZONPAY          = 'amazonpay';

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
    const BT_AXIS            = 'bt_axis';
    const BT_IDFC            = 'bt_idfc';
    const BT_IBL             = 'bt_ibl';

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
    const ICICI_DEBIT_EMI    = 'icici_debit_emi';
    const CURRENCY_CLOUD     = 'currency_cloud';
    const PING_PONG     = 'ping_pong';

    const VA_SWIFT = 'swift';
    const VA_USD   = 'usd';
    const SWIFT = 'SWIFT';
    const OPTIMIZER_RAZORPAY = "optimizer_razorpay";

    const EMANDATE = 'emandate';
    const AUTH_TYPES = 'auth_types';

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
    const ATOME     = 'atome';

    // tokenisation gateways
    const TOKENISATION_VISA        = 'tokenisation_visa';
    const TOKENISATION_MASTERCARD  = 'tokenisation_mastercard';
    const TOKENISATION_RUPAY       = 'tokenisation_rupay';
    const TOKENISATION_HDFC        = 'tokenisation_hdfc';
    const TOKENISATION_AMEX        = 'tokenisation_amex';
    const TOKENISATION_AXIS        = 'tokenisation_axis';
    const ALT_MASTERCARD           = 'alt_mastercard';

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
        self::CARDLESS_EMI => [CardlessEmi::ZESTMONEY, CardlessEmi::EARLYSALARY, CardlessEmi::FLEXMONEY, CardlessEmi::WALNUT369, CardlessEmi::SEZZLE, CardlessEmi::LIQUILOANS, CardlessEmi::INSTANT_EMI],
        self::PAYLATER     => [PayLater::EPAYLATER, PayLater::GETSIMPL, PayLater::ICICI, PayLater::FLEXMONEY, Paylater::LAZYPAY, Paylater::AMAZONPAY, PayLater::RZPXPOSTPAID],
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
            CardlessEmi::LIQUILOANS,
            CardlessEmi::INSTANT_EMI,
            PayLater::EPAYLATER,
            PayLater::GETSIMPL,
            PayLater::ICICI,
            PayLater::FLEXMONEY,
            Paylater::LAZYPAY,
            PayLater::AMAZONPAY,
            PayLater::RZPXPOSTPAID,
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

    const CARD_GATEWAYS = [
        self::TOKENISATION_VISA,
        self::TOKENISATION_MASTERCARD,
        self::TOKENISATION_RUPAY,
        self::TOKENISATION_HDFC,
        self::TOKENISATION_AMEX,
        self::TOKENISATION_AXIS,
        self::ALT_MASTERCARD
    ];

    const TOKENISATION_CRYPTOGRAM_NOT_REQUIRED_GATEWAYS = [
        self::AXIS_TOKENHQ,
        self::ICICI
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
        self::PAYTM,
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
        self::PAYLATER => [
            self::RZPXPOSTPAID => self::RZPXPOSTPAID,
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
        self::UPI_JKBANK            => self::JKBANK,
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
        self::PHONEPE               => self::PHONEPE,
        self::ZAAKPAY               => self::ZAAKPAY,
        self::NETBANKING_YESB       => self::YESB,
        self::CCAVENUE              => self::CCAVENUE,
        self::PINELABS              => self::PINELABS,
        self::INGENICO              => self::INGENICO,
        self::BILLDESK_OPTIMIZER    => self::BILLDESK_OPTIMIZER,
        self::CHECKOUT_DOT_COM_OPTIMIZER => self::CHECKOUT_DOT_COM_OPTIMIZER,
        self::NETBANKING_IDFC       => self::IDFC,
        self::PAYSECURE             => self::ACQUIRER_AXIS,
        self::NETBANKING_SBI        => self::SBIN,
        self::NETBANKING_INDUSIND   => self::INDUSIND,
        self::OFFLINE_HDFC          => self::HDFC,
        self::HDFC_EZETAP           => self::HDFC,
        self::UMOBILE               => self::UMOBILE,
        self::FPX                   => self::FPX,
        self::OPTIMIZER_RAZORPAY    => self::OPTIMIZER_RAZORPAY,
        self::WALLET_RAZORPAYWALLET => [
            'default'           => self::WALLET_RAZORPAYWALLET,
        ],
        self::WALLET_OPENWALLET     => [
            'default'           => self::WALLET_OPENWALLET,
        ],
        self::FIRST_DATA => [
            'default'               => self::FIRST_DATA,
            self::ACQUIRER_ICIC     => self::FIRST_DATA,
        ],
        self::EASEBUZZ_OPTIMIZER    => self::EASEBUZZ_OPTIMIZER,
        self::BT_AXIS               => self::AXIS,
        self::BT_IDFC               => self::IDFC,
        self::BT_YESBANK            => self::YESB,
        self::UPI_YESBANK           => self::YESB,
        self::BT_RBL                => self::RBL,
        self::BT_IBL                => self::INDUSIND,
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
        self::CHECKOUT_DOT_COM_OPTIMIZER => self::CHECKOUT_DOT_COM_OPTIMIZER,
        self::OFFLINE_HDFC          => self::HDFC,
        self::HDFC_EZETAP           =>self::HDFC,
        self::PAYSECURE             => self::AXIS,
        self::UMOBILE               => self::UMOBILE,
        self::FPX                   => self::FPX,
        self::OPTIMIZER_RAZORPAY    => self::OPTIMIZER_RAZORPAY,
        self::WALLET_RAZORPAYWALLET => self::WALLET_RAZORPAYWALLET,
        self::WALLET_OPENWALLET     => self::WALLET_OPENWALLET,
        self::FIRST_DATA            => self::FIRST_DATA,
        self::EASEBUZZ_OPTIMIZER    => self::EASEBUZZ_OPTIMIZER,
        self::RZPXPOSTPAID          => self::RZPXPOSTPAID,
        self::BT_AXIS               => self::AXIS,
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
        self::CURRENCY_CLOUD,
        self::PING_PONG
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
        self::EASEBUZZ_OPTIMIZER,
    ];

    const OPTIMIZER_CARD_GATEWAYS = [
        self::CASHFREE,
        self::PAYU,
        self::CCAVENUE,
        self::ZAAKPAY,
        self::PINELABS,
        self::INGENICO,
        self::BILLDESK_OPTIMIZER,
        self::CHECKOUT_DOT_COM_OPTIMIZER,
        self::OPTIMIZER_RAZORPAY,
        self::EASEBUZZ_OPTIMIZER
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
        self::EASEBUZZ_OPTIMIZER
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
        self::HDFC,
        self::UPI_KOTAK,
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
        self::NETBANKING_IBK,
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
        self::UPI_RZPAPB,
        self::AMEX,
        self::UPI_RZPAXIS,
        self::LYRA,
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
     * List of gateway for netbanking
     * Where in mis we get gateway amount,gateway fees and gateway tax
     */
    const UPDATE_NETBANKING_GATEWAY_FEES_TAX_AMOUNT =[
        self::ATOM,
        self::BILLDESK,
        self::NETBANKING_FEDERAL
    ];
     /**
     * List of gateway for wallet
     * Where in mis we get gateway amount,gateway fees and gateway tax
     */
    const UPDATE_WALLET_GATEWAY_FEES_TAX_AMOUNT =[
        self::WALLET_AMAZONPAY,
        self::WALLET_FREECHARGE,
        self::MOBIKWIK,
        self::WALLET_OLAMONEY,
        self::WALLET_PHONEPE,
        self::WALLET_PAYZAPP,
        self::WALLET_PHONEPESWITCH
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
        IFSC::ANDB,
        IFSC::AIRP,
        IFSC::APGB,
        IFSC::AUBL,
        IFSC::BARB,
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
        IFSC::ESAF,
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
        IFSC::JAKA,
        IFSC::JSFB,
        IFSC::KARB,
        IFSC::KKBK,
        IFSC::KVBL,
        IFSC::KVGB,
        IFSC::MAHB,
        IFSC::NSPB,
        IFSC::PSIB,
        IFSC::PUNB,
        Netbanking::PUNB_R,
        IFSC::PYTM,
        IFSC::RATN,
        IFSC::SBIN,
        IFSC::SCBL,
        IFSC::SIBL,
        IFSC::STCB,
        IFSC::SURY,
        IFSC::TMBL,
        IFSC::UBIN,
        IFSC::UCBA,
        IFSC::USFB,
        IFSC::UTIB,
        IFSC::VARA,
        IFSC::YESB,
    ];

    // Left side codes are actual ifsc codes of bank & right side codes are NPCI accepted bank code
    // eg: customers have ESMF as ifsc code (ESMF0001401) but NPCI accepts ESAF as code and redirect to it
    const ENACH_NPCI_BANKS_WITH_DIFFERENT_IFSC_CODE_MAPPING = [
        IFSC::ESMF => IFSC::ESAF,
        IFSC::UJVN => IFSC::USFB
    ];

    // Left side codes are NPCI accepted codes & right side codes are actual ifsc code of bank after merging
    // eg: customers bank is ZSHX & Npci accepts ZSHX code and redirect it, but ifsc code is ICIC00ZSBHA for customer
    const ENACH_NPCI_NB_MERGED_BANK_CODE_MAPPING = [
        IFSC::AACX => IFSC::HDFC,
        IFSC::ABCX => IFSC::YESB,
        IFSC::ABDX => IFSC::YESB,
        IFSC::ABSB => IFSC::SVCB,
        IFSC::ABUX => IFSC::HDFC,
        IFSC::ACAX => IFSC::HDFC,
        IFSC::ACBX => IFSC::HDFC,
        IFSC::ACCX => IFSC::IBKL,
        IFSC::ACKX => IFSC::HDFC,
        IFSC::ACOX => IFSC::HDFC,
        IFSC::ACUB => IFSC::HDFC,
        IFSC::ACUX => IFSC::ICIC,
        IFSC::ADBX => IFSC::GSCB,
        IFSC::ADCX => IFSC::HDFC,
        IFSC::ADDX => IFSC::APBL,
        IFSC::AGCX => IFSC::YESB,
        IFSC::AGDX => IFSC::ICIC,
        IFSC::AGRX => IFSC::KKBK,
        IFSC::AGSX => IFSC::HDFC,
        IFSC::AGUX => IFSC::UTIB,
        IFSC::AGVX => IFSC::UTBI,
        IFSC::AHMX => IFSC::ICIC,
        IFSC::AHUX => IFSC::IBKL,
        IFSC::AJKB => IFSC::AKJB,
        IFSC::AJMX => IFSC::RSCB,
        IFSC::AJNX => IFSC::YESB,
        IFSC::AJPX => IFSC::HDFC,
        IFSC::AJSX => IFSC::ICIC,
        IFSC::AKMX => IFSC::HDFC,
        IFSC::AKOX => IFSC::YESB,
        IFSC::ALAX => IFSC::HDFC,
        IFSC::ALIX => IFSC::ICIC,
        IFSC::ALWX => IFSC::RSCB,
        IFSC::AMAX => IFSC::ICIC,
        IFSC::AMBX => IFSC::UTIB,
        IFSC::AMCX => IFSC::UTIB,
        IFSC::AMMX => IFSC::HDFC,
        IFSC::AMNX => IFSC::HDFC,
        IFSC::AMRX => IFSC::GSCB,
        IFSC::AMSB => IFSC::IBKL,
        IFSC::AMSX => IFSC::HDFC,
        IFSC::ANBX => IFSC::IBKL,
        IFSC::ANMX => IFSC::HDFC,
        IFSC::ANSX => IFSC::HDFC,
        IFSC::ANUX => IFSC::HDFC,
        IFSC::APCX => IFSC::UTIB,
        IFSC::APGX => IFSC::SBIN,
        IFSC::APJX => IFSC::HDFC,
        IFSC::APMX => IFSC::UTIB,
        IFSC::APNX => IFSC::HDFC,
        IFSC::APRX => IFSC::SBIN,
        IFSC::APSX => IFSC::HDFC,
        IFSC::ARCX => IFSC::YESB,
        IFSC::ARMX => IFSC::ICIC,
        IFSC::ARYX => IFSC::BKID,
        IFSC::ASBX => IFSC::SVCB,
        IFSC::ASHX => IFSC::IBKL,
        IFSC::ASKX => IFSC::SVCB,
        IFSC::ASNX => IFSC::UTIB,
        IFSC::ASOX => IFSC::YESB,
        IFSC::ASSX => IFSC::ICIC,
        IFSC::AUBX => IFSC::YESB,
        IFSC::AUCX => IFSC::IBKL,
        IFSC::AUGX => IFSC::ALLA,
        IFSC::AURX => IFSC::IBKL,
        IFSC::AVDX => IFSC::YESB,
        IFSC::AWCX => IFSC::FDRL,
        IFSC::AZAX => IFSC::UTIB,
        IFSC::AZSX => IFSC::YESB,
        IFSC::AZUX => IFSC::IBKL,
        IFSC::BACX => IFSC::HDFC,
        IFSC::BADX => IFSC::WBSC,
        IFSC::BAGX => IFSC::YESB,
        IFSC::BALX => IFSC::HDFC,
        IFSC::BANX => IFSC::ICIC,
        IFSC::BARX => IFSC::KKBK,
        IFSC::BASX => IFSC::ICIC,
        IFSC::BAUX => IFSC::IBKL,
        IFSC::BAVX => IFSC::HDFC,
        IFSC::BAWX => IFSC::ICIC,
        IFSC::BBLX => IFSC::KKBK,
        IFSC::BBRX => IFSC::KSCB,
        IFSC::BBSX => IFSC::YESB,
        IFSC::BBUX => IFSC::IBKL,
        IFSC::BBVX => IFSC::HDFC,
        IFSC::BCBX => IFSC::HDFC,
        IFSC::BCCB => IFSC::INDB,
        IFSC::BCCX => IFSC::UTIB,
        IFSC::BCEX => IFSC::RSCB,
        IFSC::BCOX => IFSC::IBKL,
        IFSC::BCUB => IFSC::HDFC,
        IFSC::BCUX => IFSC::IBKL,
        IFSC::BDBX => IFSC::IBKL,
        IFSC::BDCX => IFSC::KSCB,
        IFSC::BDDX => IFSC::SRCB,
        IFSC::BDIX => IFSC::IBKL,
        IFSC::BDNX => IFSC::HDFC,
        IFSC::BDOX => IFSC::ICIC,
        IFSC::BDUX => IFSC::YESB,
        IFSC::BEDX => IFSC::UTIB,
        IFSC::BELX => IFSC::IBKL,
        IFSC::BFUX => IFSC::HDFC,
        IFSC::BGBX => IFSC::UCBA,
        IFSC::BGCX => IFSC::IBKL,
        IFSC::BGGX => IFSC::BARB,
        IFSC::BGUX => IFSC::IBKL,
        IFSC::BGVX => IFSC::UTBI,
        IFSC::BHAX => IFSC::HDFC,
        IFSC::BHBX => IFSC::HDFC,
        IFSC::BHCX => IFSC::UTIB,
        IFSC::BHDX => IFSC::YESB,
        IFSC::BHGX => IFSC::IBKL,
        IFSC::BHJX => IFSC::FDRL,
        IFSC::BHMX => IFSC::YESB,
        IFSC::BHOX => IFSC::CBIN,
        IFSC::BHRX => IFSC::RSCB,
        IFSC::BHSX => IFSC::SVCB,
        IFSC::BHTX => IFSC::UTIB,
        IFSC::BHUX => IFSC::HDFC,
        IFSC::BHWX => IFSC::UTIB,
        IFSC::BJUX => IFSC::HDFC,
        IFSC::BKCX => IFSC::BKID,
        IFSC::BKDX => IFSC::GSCB,
        IFSC::BKSX => IFSC::RSCB,
        IFSC::BLGX => IFSC::IBKL,
        IFSC::BMCB => IFSC::UTIB,
        IFSC::BMCX => IFSC::RSCB,
        IFSC::BMPX => IFSC::HDFC,
        IFSC::BMSX => IFSC::HDFC,
        IFSC::BNBX => IFSC::UTIB,
        IFSC::BNCX => IFSC::WBSC,
        IFSC::BNKX => IFSC::YESB,
        IFSC::BNSX => IFSC::HDFC,
        IFSC::BOCX => IFSC::YESB,
        IFSC::BODX => IFSC::IBKL,
        IFSC::BORX => IFSC::HDFC,
        IFSC::BOTX => IFSC::IBKL,
        IFSC::BPCX => IFSC::HDFC,
        IFSC::BPSX => IFSC::IBKL,
        IFSC::BRCX => IFSC::YESB,
        IFSC::BRDX => IFSC::GSCB,
        IFSC::BRGX => IFSC::BARB,
        IFSC::BRMX => IFSC::HDFC,
        IFSC::BRSX => IFSC::HDFC,
        IFSC::BRUX => IFSC::GSCB,
        IFSC::BSBX => IFSC::HDFC,
        IFSC::BSCX => IFSC::HDFC,
        IFSC::BTCX => IFSC::HDFC,
        IFSC::BTUX => IFSC::ICIC,
        IFSC::BUBX => IFSC::HDFC,
        IFSC::BUCX => IFSC::HDFC,
        IFSC::BUGX => IFSC::BARB,
        IFSC::BUNX => IFSC::RSCB,
        IFSC::BURX => IFSC::HDFC,
        IFSC::BUSX => IFSC::YESB,
        IFSC::BUZX => IFSC::ICIC,
        IFSC::BVNX => IFSC::GSCB,
        IFSC::BVSX => IFSC::IBKL,
        IFSC::CALX => IFSC::FDRL,
        IFSC::CBHX => IFSC::RSCB,
        IFSC::CCBX => IFSC::HDFC,
        IFSC::CCCX => IFSC::TNSC,
        IFSC::CCMX => IFSC::GSCB,
        IFSC::CDCX => IFSC::TNSC,
        IFSC::CEBX => IFSC::RSCB,
        IFSC::CGBX => IFSC::SBIN,
        IFSC::CGGX => IFSC::ANDB,
        IFSC::CHAX => IFSC::IBKL,
        IFSC::CHBX => IFSC::HDFC,
        IFSC::CHCX => IFSC::RSCB,
        IFSC::CHDX => IFSC::APBL,
        IFSC::CHIX => IFSC::HDFC,
        IFSC::CHKX => IFSC::RSCB,
        IFSC::CHPX => IFSC::UTIB,
        IFSC::CHRX => IFSC::IBKL,
        IFSC::CHSX => IFSC::UTIB,
        IFSC::CHTX => IFSC::KKBK,
        IFSC::CIDX => IFSC::KSCB,
        IFSC::CITX => IFSC::KSCB,
        IFSC::CJAX => IFSC::YESB,
        IFSC::CJMX => IFSC::UTIB,
        IFSC::CMCB => IFSC::HDFC,
        IFSC::CMCX => IFSC::ICIC,
        IFSC::CMDX => IFSC::TNSC,
        IFSC::CMLX => IFSC::HDFC,
        IFSC::CMPX => IFSC::CBIN,
        IFSC::CNSX => IFSC::ICIC,
        IFSC::COCX => IFSC::HDFC,
        IFSC::COLX => IFSC::MAHB,
        IFSC::COMX => IFSC::IBKL,
        IFSC::CONX => IFSC::ICIC,
        IFSC::CPDX => IFSC::YESB,
        IFSC::CRBX => IFSC::IBKL,
        IFSC::CRSX => IFSC::CBIN,
        IFSC::CTBX => IFSC::IBKL,
        IFSC::CTOX => IFSC::RSCB,
        IFSC::CTUX => IFSC::HDFC,
        IFSC::CUCX => IFSC::SVCB,
        IFSC::CURX => IFSC::YESB,
        IFSC::CUTX => IFSC::IBKL,
        IFSC::CZUX => IFSC::HDFC,
        IFSC::DAAX => IFSC::SVCB,
        IFSC::DAHX => IFSC::HDFC,
        IFSC::DAUX => IFSC::ICIC,
        IFSC::DBAX => IFSC::HDFC,
        IFSC::DCBX => IFSC::TNSC,
        IFSC::DCCX => IFSC::APBL,
        IFSC::DCDX => IFSC::APBL,
        IFSC::DCEX => IFSC::APBL,
        IFSC::DCKX => IFSC::APBL,
        IFSC::DCMX => IFSC::ICIC,
        IFSC::DCNX => IFSC::KKBK,
        IFSC::DCPX => IFSC::ICIC,
        IFSC::DCSX => IFSC::ICIC,
        IFSC::DCTX => IFSC::ICIC,
        IFSC::DCUX => IFSC::HDFC,
        IFSC::DDBX => IFSC::TNSC,
        IFSC::DDCX => IFSC::WBSC,
        IFSC::DDDX => IFSC::WBSC,
        IFSC::DDHX => IFSC::YESB,
        IFSC::DEGX => IFSC::BKDN,
        IFSC::DENS => IFSC::YESB,
        IFSC::DEOX => IFSC::DEOB,
        IFSC::DEUX => IFSC::ICIC,
        IFSC::DEVX => IFSC::YESB,
        IFSC::DGBX => IFSC::SBIN,
        IFSC::DHBX => IFSC::UTIB,
        IFSC::DHKX => IFSC::BKID,
        IFSC::DHUX => IFSC::HDFC,
        IFSC::DIBX => IFSC::ICIC,
        IFSC::DICX => IFSC::APBL,
        IFSC::DISX => IFSC::ICIC,
        IFSC::DIUX => IFSC::HDFC,
        IFSC::DKSX => IFSC::RSCB,
        IFSC::DMCB => IFSC::IBKL,
        IFSC::DMCX => IFSC::HDFC,
        IFSC::DMKB => IFSC::DMKJ,
        IFSC::DNDC => IFSC::IBKL,
        IFSC::DNSX => IFSC::HDFC,
        IFSC::DOBX => IFSC::IBKL,
        IFSC::DSBX => IFSC::HDFC,
        IFSC::DSCB => IFSC::DLSC,
        IFSC::DSHX => IFSC::ICIC,
        IFSC::DSPX => IFSC::IBKL,
        IFSC::DSUX => IFSC::SVCB,
        IFSC::DTCX => IFSC::ICIC,
        IFSC::DTPX => IFSC::ICIC,
        IFSC::DUCX => IFSC::HDFC,
        IFSC::DUNX => IFSC::RSCB,
        IFSC::DVDX => IFSC::KSCB,
        IFSC::DYPX => IFSC::IBKL,
        IFSC::ECBL => IFSC::HDFC,
        IFSC::EDBX => IFSC::SBIN,
        IFSC::EDCX => IFSC::TNSC,
        IFSC::EDSX => IFSC::UBIN,
        IFSC::ESAF => IFSC::ESMF,
        IFSC::ETCX => IFSC::ICIC,
        IFSC::ETDX => IFSC::ICIC,
        IFSC::EUCX => IFSC::ICIC,
        IFSC::EWCX => IFSC::HDFC,
        IFSC::FCBX => IFSC::UTIB,
        IFSC::FCCX => IFSC::UTIB,
        IFSC::FCOX => IFSC::ICIC,
        IFSC::FDFX => IFSC::ICIC,
        IFSC::FEKX => IFSC::FDRL,
        IFSC::FGCB => IFSC::HDFC,
        IFSC::FINF => IFSC::FSFB,
        IFSC::FINX => IFSC::YESB,
        IFSC::FMCX => IFSC::YESB,
        IFSC::FRIX => IFSC::UTIB,
        IFSC::FSCX => IFSC::UTIB,
        IFSC::FZCX => IFSC::UTIB,
        IFSC::FZSX => IFSC::ICIC,
        IFSC::GACX => IFSC::GSCB,
        IFSC::GADX => IFSC::ICIC,
        IFSC::GAMX => IFSC::ICIC,
        IFSC::GANX => IFSC::HDFC,
        IFSC::GCCX => IFSC::UTIB,
        IFSC::GCUL => IFSC::IBKL,
        IFSC::GCUX => IFSC::HDFC,
        IFSC::GDCX => IFSC::APBL,
        IFSC::GDUX => IFSC::UTIB,
        IFSC::GGCX => IFSC::IBKL,
        IFSC::GHPX => IFSC::HDFC,
        IFSC::GKNX => IFSC::RSCB,
        IFSC::GMBX => IFSC::YESB,
        IFSC::GMCX => IFSC::HDFC,
        IFSC::GMUX => IFSC::IBKL,
        IFSC::GNCX => IFSC::YESB,
        IFSC::GNSX => IFSC::HDFC,
        IFSC::GODX => IFSC::HDFC,
        IFSC::GOSX => IFSC::HDFC,
        IFSC::GPCX => IFSC::IBKL,
        IFSC::GPOX => IFSC::SBIN,
        IFSC::GRAX => IFSC::IBKL,
        IFSC::GSBL => IFSC::YESB,
        IFSC::GSBX => IFSC::YESB,
        IFSC::GSSX => IFSC::SVCB,
        IFSC::GUBX => IFSC::YESB,
        IFSC::GUCX => IFSC::HDFC,
        IFSC::GUOX => IFSC::UTIB,
        IFSC::HAMX => IFSC::ICIC,
        IFSC::HANX => IFSC::RSCB,
        IFSC::HCBL => IFSC::SVCB,
        IFSC::HCBX => IFSC::HCBL,
        IFSC::HCLX => IFSC::YESB,
        IFSC::HDCX => IFSC::APBL,
        IFSC::HGBX => IFSC::PUNB,
        IFSC::HINX => IFSC::IBKL,
        IFSC::HISX => IFSC::UTIB,
        IFSC::HMBX => IFSC::PUNB,
        IFSC::HMNX => IFSC::IBKL,
        IFSC::HOCX => IFSC::UTIB,
        IFSC::HOOX => IFSC::WBSC,
        IFSC::HPCX => IFSC::HDFC,
        IFSC::HPSX => IFSC::YESB,
        IFSC::HSBX => IFSC::ICIC,
        IFSC::HSCX => IFSC::UTIB,
        IFSC::HSDX => IFSC::UTIB,
        IFSC::HSSX => IFSC::YESB,
        IFSC::HUBX => IFSC::SVCB,
        IFSC::HUCX => IFSC::HDFC,
        IFSC::HUTX => IFSC::IBKL,
        IFSC::ICBL => IFSC::IBKL,
        IFSC::ICHX => IFSC::HDFC,
        IFSC::ICMX => IFSC::YESB,
        IFSC::IDUX => IFSC::UTIB,
        IFSC::IMCX => IFSC::YESB,
        IFSC::IMPX => IFSC::UTIB,
        IFSC::INCX => IFSC::YESB,
        IFSC::INDX => IFSC::HDFC,
        IFSC::IPCX => IFSC::CBIN,
        IFSC::IPPB => IFSC::IPOS,
        IFSC::IPSX => IFSC::ICIC,
        IFSC::ISBX => IFSC::UTIB,
        IFSC::ISMX => IFSC::HDFC,
        IFSC::ITCX => IFSC::HDFC,
        IFSC::ITDX => IFSC::IBKL,
        IFSC::IUCB => IFSC::HDFC,
        IFSC::IUCX => IFSC::YESB,
        IFSC::JACX => IFSC::HDFC,
        IFSC::JALX => IFSC::YESB,
        IFSC::JAMX => IFSC::UTIB,
        IFSC::JANX => IFSC::HDFC,
        IFSC::JASX => IFSC::HDFC,
        IFSC::JAUX => IFSC::SVCB,
        IFSC::JBHX => IFSC::CBIN,
        IFSC::JBIX => IFSC::UTIB,
        IFSC::JBMX => IFSC::CBIN,
        IFSC::JCBX => IFSC::HDFC,
        IFSC::JCCB => IFSC::RSCB,
        IFSC::JCCX => IFSC::UTIB,
        IFSC::JCDX => IFSC::GSCB,
        IFSC::JCHX => IFSC::CBIN,
        IFSC::JCPX => IFSC::ICIC,
        IFSC::JCUX => IFSC::KKBK,
        IFSC::JDCX => IFSC::ICIC,
        IFSC::JDEX => IFSC::CBIN,
        IFSC::JGBX => IFSC::BKID,
        IFSC::JGCX => IFSC::YESB,
        IFSC::JGWX => IFSC::CBIN,
        IFSC::JHAX => IFSC::HDFC,
        IFSC::JHSX => IFSC::CBIN,
        IFSC::JHUX => IFSC::RSCB,
        IFSC::JIBX => IFSC::CBIN,
        IFSC::JICX => IFSC::CBIN,
        IFSC::JIDX => IFSC::CBIN,
        IFSC::JIGX => IFSC::CBIN,
        IFSC::JIKX => IFSC::CBIN,
        IFSC::JIMX => IFSC::CBIN,
        IFSC::JINX => IFSC::UTIB,
        IFSC::JIOX => IFSC::CBIN,
        IFSC::JIRX => IFSC::CBIN,
        IFSC::JISX => IFSC::CBIN,
        IFSC::JIVX => IFSC::IBKL,
        IFSC::JJCX => IFSC::UTIB,
        IFSC::JJHX => IFSC::CBIN,
        IFSC::JKAX => IFSC::UTIB,
        IFSC::JKCX => IFSC::HDFC,
        IFSC::JKDX => IFSC::CBIN,
        IFSC::JKEX => IFSC::RSCB,
        IFSC::JKHX => IFSC::CBIN,
        IFSC::JKMX => IFSC::UTIB,
        IFSC::JKRX => IFSC::CBIN,
        IFSC::JKSX => IFSC::UTIB,
        IFSC::JLCX => IFSC::RSCB,
        IFSC::JLDX => IFSC::IBKL,
        IFSC::JLNX => IFSC::HDFC,
        IFSC::JLSX => IFSC::CBIN,
        IFSC::JLWX => IFSC::HDFC,
        IFSC::JMAX => IFSC::CBIN,
        IFSC::JMBX => IFSC::CBIN,
        IFSC::JMCX => IFSC::HDFC,
        IFSC::JMDX => IFSC::CBIN,
        IFSC::JMHX => IFSC::HDFC,
        IFSC::JMOX => IFSC::CBIN,
        IFSC::JMPX => IFSC::HDFC,
        IFSC::JMSX => IFSC::HDFC,
        IFSC::JMYX => IFSC::UTIB,
        IFSC::JNAX => IFSC::CBIN,
        IFSC::JNDX => IFSC::GSCB,
        IFSC::JNSX => IFSC::HDFC,
        IFSC::JODX => IFSC::RSCB,
        IFSC::JONX => IFSC::HDFC,
        IFSC::JOWX => IFSC::UTIB,
        IFSC::JPAX => IFSC::CBIN,
        IFSC::JPCX => IFSC::WBSC,
        IFSC::JRAX => IFSC::CBIN,
        IFSC::JRKX => IFSC::CBIN,
        IFSC::JRNX => IFSC::UTIB,
        IFSC::JRSX => IFSC::HDFC,
        IFSC::JSAB => IFSC::IBKL,
        IFSC::JSAX => IFSC::IBKL,
        IFSC::JSBX => IFSC::YESB,
        IFSC::JSCX => IFSC::IBKL,
        IFSC::JSDX => IFSC::CBIN,
        IFSC::JSEX => IFSC::CBIN,
        IFSC::JSHX => IFSC::CBIN,
        IFSC::JSKX => IFSC::UTIB,
        IFSC::JSMX => IFSC::SVCB,
        IFSC::JSOX => IFSC::CBIN,
        IFSC::JSRX => IFSC::CBIN,
        IFSC::JSTX => IFSC::CBIN,
        IFSC::JSVX => IFSC::CBIN,
        IFSC::JSWX => IFSC::HDFC,
        IFSC::JTIX => IFSC::CBIN,
        IFSC::JUCX => IFSC::HDFC,
        IFSC::JUSX => IFSC::ICIC,
        IFSC::JVCX => IFSC::YESB,
        IFSC::KAAX => IFSC::APBL,
        IFSC::KACX => IFSC::GSCB,
        IFSC::KADX => IFSC::IBKL,
        IFSC::KAGX => IFSC::IBKL,
        IFSC::KALX => IFSC::HDFC,
        IFSC::KAMX => IFSC::IBKL,
        IFSC::KANX => IFSC::KSCB,
        IFSC::KARX => IFSC::YESB,
        IFSC::KASX => IFSC::HDFC,
        IFSC::KATX => IFSC::INDB,
        IFSC::KAYX => IFSC::TNSC,
        IFSC::KBCX => IFSC::IBKL,
        IFSC::KBNX => IFSC::GSCB,
        IFSC::KBSX => IFSC::HDFC,
        IFSC::KCBX => IFSC::ICIC,
        IFSC::KCCX => IFSC::TNSC,
        IFSC::KCDX => IFSC::KSCB,
        IFSC::KCEX => IFSC::RSCB,
        IFSC::KCOB => IFSC::KANG,
        IFSC::KCUB => IFSC::YESB,
        IFSC::KCUX => IFSC::IBKL,
        IFSC::KDBX => IFSC::APBL,
        IFSC::KDCX => IFSC::IBKL,
        IFSC::KDIX => IFSC::YESB,
        IFSC::KDNX => IFSC::HDFC,
        IFSC::KDUX => IFSC::KSCB,
        IFSC::KEJX => IFSC::UTIB,
        IFSC::KEMX => IFSC::IBKL,
        IFSC::KESX => IFSC::IBKL,
        IFSC::KGBX => IFSC::SBIN,
        IFSC::KGDX => IFSC::IBKL,
        IFSC::KGSX => IFSC::UBIN,
        IFSC::KHAX => IFSC::YESB,
        IFSC::KHCX => IFSC::ICIC,
        IFSC::KHDX => IFSC::GSCB,
        IFSC::KHNX => IFSC::HDFC,
        IFSC::KHTX => IFSC::YESB,
        IFSC::KHUX => IFSC::HDFC,
        IFSC::KICX => IFSC::UTIB,
        IFSC::KKMX => IFSC::HDFC,
        IFSC::KKSX => IFSC::IBKL,
        IFSC::KLMX => IFSC::YESB,
        IFSC::KMCB => IFSC::KKBK,
        IFSC::KMCX => IFSC::UTIB,
        IFSC::KMNX => IFSC::YESB,
        IFSC::KMSX => IFSC::HDFC,
        IFSC::KNBX => IFSC::IBKL,
        IFSC::KNCX => IFSC::UTIB,
        IFSC::KNNX => IFSC::IBKL,
        IFSC::KNPX => IFSC::UTIB,
        IFSC::KNSB => IFSC::ICIC,
        IFSC::KNSX => IFSC::UTIB,
        IFSC::KOBX => IFSC::SVCB,
        IFSC::KOCX => IFSC::IBKL,
        IFSC::KODX => IFSC::IBKL,
        IFSC::KOSX => IFSC::HDFC,
        IFSC::KOTX => IFSC::GSCB,
        IFSC::KOYX => IFSC::HDFC,
        IFSC::KPCX => IFSC::IBKL,
        IFSC::KRCX => IFSC::KSCB,
        IFSC::KRDX => IFSC::APBL,
        IFSC::KRIX => IFSC::HDFC,
        IFSC::KRMX => IFSC::KKBK,
        IFSC::KRNX => IFSC::KKBK,
        IFSC::KRTX => IFSC::UTIB,
        IFSC::KSBX => IFSC::IBKL,
        IFSC::KSCX => IFSC::IBKL,
        IFSC::KSMX => IFSC::GSCB,
        IFSC::KSNX => IFSC::HDFC,
        IFSC::KSTX => IFSC::IBKL,
        IFSC::KSUX => IFSC::HDFC,
        IFSC::KTBX => IFSC::UTIB,
        IFSC::KTCX => IFSC::UTIB,
        IFSC::KTDX => IFSC::YESB,
        IFSC::KUBX => IFSC::UTIB,
        IFSC::KUCX => IFSC::HDFC,
        IFSC::KUKX => IFSC::HDFC,
        IFSC::KULX => IFSC::IBKL,
        IFSC::KUMX => IFSC::TNSC,
        IFSC::KUNS => IFSC::KNSB,
        IFSC::KURX => IFSC::UTIB,
        IFSC::KUTX => IFSC::IBKL,
        IFSC::KVCX => IFSC::HDFC,
        IFSC::KYDX => IFSC::KSCB,
        IFSC::LATX => IFSC::IBKL,
        IFSC::LBMX => IFSC::ICIC,
        IFSC::LCBX => IFSC::HDFC,
        IFSC::LCCX => IFSC::UTIB,
        IFSC::LDCX => IFSC::IBKL,
        IFSC::LDPX => IFSC::UTIB,
        IFSC::LDRX => IFSC::SBIN,
        IFSC::LECX => IFSC::IBKL,
        IFSC::LICB => IFSC::HDFC,
        IFSC::LKHX => IFSC::YESB,
        IFSC::LKMX => IFSC::IBKL,
        IFSC::LMNX => IFSC::YESB,
        IFSC::LNSX => IFSC::UTIB,
        IFSC::LOKX => IFSC::YESB,
        IFSC::LONX => IFSC::HDFC,
        IFSC::LUCX => IFSC::SRCB,
        IFSC::LULX => IFSC::ICIC,
        IFSC::MABL => IFSC::HDFC,
        IFSC::MACX => IFSC::HDFC,
        IFSC::MADX => IFSC::SBIN,
        IFSC::MAHX => IFSC::SVCB,
        IFSC::MAJX => IFSC::ICIC,
        IFSC::MAKX => IFSC::YESB,
        IFSC::MALX => IFSC::HDFC,
        IFSC::MAMX => IFSC::UTIB,
        IFSC::MANX => IFSC::YESB,
        IFSC::MAPX => IFSC::ICIC,
        IFSC::MASX => IFSC::IBKL,
        IFSC::MAUX => IFSC::KKBK,
        IFSC::MAVX => IFSC::KKBK,
        IFSC::MAWX => IFSC::UTIB,
        IFSC::MAYX => IFSC::ICIC,
        IFSC::MBCX => IFSC::WBSC,
        IFSC::MBGX => IFSC::PUNB,
        IFSC::MBLX => IFSC::ICIC,
        IFSC::MCAX => IFSC::ICIC,
        IFSC::MCCX => IFSC::UTIB,
        IFSC::MCDX => IFSC::ICIC,
        IFSC::MCLX => IFSC::UTIB,
        IFSC::MCOX => IFSC::UTIB,
        IFSC::MCSX => IFSC::KKBK,
        IFSC::MCUX => IFSC::HDFC,
        IFSC::MDCX => IFSC::TNSC,
        IFSC::MDEX => IFSC::HDFC,
        IFSC::MDGX => IFSC::SBIN,
        IFSC::MDIX => IFSC::KSCB,
        IFSC::MDMX => IFSC::YESB,
        IFSC::MDPX => IFSC::ICIC,
        IFSC::MEDX => IFSC::APBL,
        IFSC::MERX => IFSC::SBIN,
        IFSC::MEUX => IFSC::FDRL,
        IFSC::MFCX => IFSC::IBKL,
        IFSC::MFUX => IFSC::HDFC,
        IFSC::MGBX => IFSC::MAHB,
        IFSC::MGCB => IFSC::IBKL,
        IFSC::MGCX => IFSC::HDFC,
        IFSC::MGDX => IFSC::IBKL,
        IFSC::MGRB => IFSC::SBIN,
        IFSC::MGSX => IFSC::UTIB,
        IFSC::MGUX => IFSC::HDFC,
        IFSC::MHCX => IFSC::UTIB,
        IFSC::MHMX => IFSC::IBKL,
        IFSC::MHNX => IFSC::YESB,
        IFSC::MHSX => IFSC::HDFC,
        IFSC::MHUX => IFSC::ICIC,
        IFSC::MIZX => IFSC::YESB,
        IFSC::MJCX => IFSC::YESB,
        IFSC::MKUX => IFSC::HDFC,
        IFSC::MKYX => IFSC::GSCB,
        IFSC::MLDX => IFSC::WBSC,
        IFSC::MMCX => IFSC::KKBK,
        IFSC::MMMX => IFSC::HDFC,
        IFSC::MNBX => IFSC::HDFC,
        IFSC::MNCX => IFSC::UTIB,
        IFSC::MNSX => IFSC::IBKL,
        IFSC::MOGX => IFSC::UTIB,
        IFSC::MPCX => IFSC::UTIB,
        IFSC::MPDX => IFSC::IBKL,
        IFSC::MPRX => IFSC::CBIN,
        IFSC::MRBX => IFSC::UTBI,
        IFSC::MRTX => IFSC::IBKL,
        IFSC::MSAX => IFSC::SRCB,
        IFSC::MSBL => IFSC::HDFC,
        IFSC::MSBX => IFSC::IBKL,
        IFSC::MSCX => IFSC::YESB,
        IFSC::MSNX => IFSC::GSCB,
        IFSC::MSOX => IFSC::HDFC,
        IFSC::MSSX => IFSC::IBKL,
        IFSC::MUBX => IFSC::IBKL,
        IFSC::MUCX => IFSC::HDFC,
        IFSC::MUDX => IFSC::HDFC,
        IFSC::MUNX => IFSC::UTIB,
        IFSC::MUPX => IFSC::ICIC,
        IFSC::MURX => IFSC::WBSC,
        IFSC::MUSX => IFSC::HDFC,
        IFSC::MVCX => IFSC::HDFC,
        IFSC::MVIX => IFSC::HDFC,
        IFSC::MYAX => IFSC::YESB,
        IFSC::MYCX => IFSC::YESB,
        IFSC::MYSX => IFSC::KSCB,
        IFSC::MZCX => IFSC::YESB,
        IFSC::MZRX => IFSC::SBIN,
        IFSC::NABX => IFSC::HDFC,
        IFSC::NACX => IFSC::IBKL,
        IFSC::NADX => IFSC::IBKL,
        IFSC::NAGX => IFSC::SBIN,
        IFSC::NAIX => IFSC::YESB,
        IFSC::NALX => IFSC::UTIB,
        IFSC::NANX => IFSC::UTIB,
        IFSC::NASX => IFSC::ICIC,
        IFSC::NAUX => IFSC::RSCB,
        IFSC::NAVX => IFSC::HDFC,
        IFSC::NAWX => IFSC::IBKL,
        IFSC::NBBX => IFSC::UTIB,
        IFSC::NBCX => IFSC::UTIB,
        IFSC::NBMX => IFSC::HDFC,
        IFSC::NCBL => IFSC::KKBK,
        IFSC::NCBX => IFSC::UTIB,
        IFSC::NCCX => IFSC::HDFC,
        IFSC::NCOX => IFSC::HDFC,
        IFSC::NCUX => IFSC::FDRL,
        IFSC::NDCB => IFSC::IBKL,
        IFSC::NDCX => IFSC::TNSC,
        IFSC::NDDX => IFSC::WBSC,
        IFSC::NDGX => IFSC::HDFC,
        IFSC::NDIX => IFSC::ICIC,
        IFSC::NDOX => IFSC::YESB,
        IFSC::NEYX => IFSC::KKBK,
        IFSC::NGBX => IFSC::YESB,
        IFSC::NGKX => IFSC::INDB,
        IFSC::NGNX => IFSC::HDFC,
        IFSC::NGRX => IFSC::IBKL,
        IFSC::NGSX => IFSC::HDFC,
        IFSC::NGUX => IFSC::HDFC,
        IFSC::NIDX => IFSC::ICIC,
        IFSC::NILX => IFSC::HDFC,
        IFSC::NIRX => IFSC::HDFC,
        IFSC::NIUX => IFSC::IBKL,
        IFSC::NJCX => IFSC::ICIC,
        IFSC::NJGX => IFSC::BKID,
        IFSC::NJMX => IFSC::HDFC,
        IFSC::NJSX => IFSC::IBKL,
        IFSC::NLGX => IFSC::APBL,
        IFSC::NLUX => IFSC::IBKL,
        IFSC::NMCX => IFSC::IBKL,
        IFSC::NNCX => IFSC::GSCB,
        IFSC::NNSX => IFSC::IBKL,
        IFSC::NOBX => IFSC::YESB,
        IFSC::NOIX => IFSC::INDB,
        IFSC::NPCX => IFSC::UTIB,
        IFSC::NPKX => IFSC::KKBK,
        IFSC::NRDX => IFSC::YESB,
        IFSC::NRMX => IFSC::UTIB,
        IFSC::NSBB => IFSC::IBKL,
        IFSC::NSBX => IFSC::IBKL,
        IFSC::NSCX => IFSC::UTIB,
        IFSC::NSGX => IFSC::YESB,
        IFSC::NSIX => IFSC::IBKL,
        IFSC::NSJX => IFSC::HDFC,
        IFSC::NSPX => IFSC::ICIC,
        IFSC::NSRX => IFSC::HDFC,
        IFSC::NUBX => IFSC::HDFC,
        IFSC::NUCX => IFSC::KKBK,
        IFSC::NVCX => IFSC::HDFC,
        IFSC::NVSX => IFSC::YESB,
        IFSC::NWCX => IFSC::YESB,
        IFSC::ODCX => IFSC::YESB,
        IFSC::ODGB => IFSC::IOBA,
        IFSC::OIBA => IFSC::DOHB,
        IFSC::OMCX => IFSC::HDFC,
        IFSC::ONSX => IFSC::YESB,
        IFSC::OSMX => IFSC::HDFC,
        IFSC::PABX => IFSC::IDIB,
        IFSC::PACX => IFSC::RSCB,
        IFSC::PADX => IFSC::GSCB,
        IFSC::PALX => IFSC::HDFC,
        IFSC::PANX => IFSC::GSCB,
        IFSC::PARX => IFSC::YESB,
        IFSC::PASX => IFSC::UCBA,
        IFSC::PATX => IFSC::IBKL,
        IFSC::PAYX => IFSC::IBKL,
        IFSC::PBGX => IFSC::IDIB,
        IFSC::PCBL => IFSC::HDFC,
        IFSC::PCBX => IFSC::HDFC,
        IFSC::PCCX => IFSC::UTIB,
        IFSC::PCLX => IFSC::ICIC,
        IFSC::PCMX => IFSC::YESB,
        IFSC::PCOX => IFSC::SVCB,
        IFSC::PCPX => IFSC::IBKL,
        IFSC::PCSX => IFSC::TNSC,
        IFSC::PCTX => IFSC::HDFC,
        IFSC::PCUX => IFSC::YESB,
        IFSC::PDBX => IFSC::ICIC,
        IFSC::PDCX => IFSC::HDFC,
        IFSC::PDNX => IFSC::IOBA,
        IFSC::PDSX => IFSC::KKBK,
        IFSC::PDUX => IFSC::PUCB,
        IFSC::PGBX => IFSC::PKGB,
        IFSC::PGCX => IFSC::KKBK,
        IFSC::PGRX => IFSC::HDFC,
        IFSC::PGTX => IFSC::HDFC,
        IFSC::PITX => IFSC::IBKL,
        IFSC::PKBX => IFSC::YESB,
        IFSC::PKDX => IFSC::UTIB,
        IFSC::PLOX => IFSC::UTIB,
        IFSC::PLUX => IFSC::UTIB,
        IFSC::PMCX => IFSC::HDFC,
        IFSC::PMNX => IFSC::YESB,
        IFSC::PNCX => IFSC::YESB,
        IFSC::PNMX => IFSC::IBKL,
        IFSC::PNSX => IFSC::HDFC,
        IFSC::PPBX => IFSC::IBKL,
        IFSC::PPCX => IFSC::HDFC,
        IFSC::PRCX => IFSC::IBKL,
        IFSC::PREX => IFSC::HDFC,
        IFSC::PROX => IFSC::HDFC,
        IFSC::PRPX => IFSC::YESB,
        IFSC::PRSX => IFSC::HDFC,
        IFSC::PSBX => IFSC::YESB,
        IFSC::PSCX => IFSC::UTIB,
        IFSC::PSRX => IFSC::APBL,
        IFSC::PSSX => IFSC::IBKL,
        IFSC::PTCX => IFSC::UTIB,
        IFSC::PTNX => IFSC::HDFC,
        IFSC::PTSX => IFSC::GSCB,
        IFSC::PUBX => IFSC::HDFC,
        IFSC::PUCX => IFSC::YESB,
        IFSC::PUDX => IFSC::TNSC,
        IFSC::PUGX => IFSC::PUNB,
        IFSC::PURX => IFSC::SBIN,
        IFSC::PUUX => IFSC::YESB,
        IFSC::PVAX => IFSC::IBKL,
        IFSC::PVCX => IFSC::ICIC,
        IFSC::PWUX => IFSC::YESB,
        IFSC::PYCX => IFSC::IBKL,
        IFSC::QNBX => IFSC::HDFC,
        IFSC::QUCX => IFSC::IBKL,
        IFSC::RACX => IFSC::ICIC,
        IFSC::RAEX => IFSC::UTIB,
        IFSC::RAJX => IFSC::YESB,
        IFSC::RAKX => IFSC::IBKL,
        IFSC::RAMX => IFSC::HDFC,
        IFSC::RANX => IFSC::HDFC,
        IFSC::RAUX => IFSC::YESB,
        IFSC::RBBX => IFSC::IBKL,
        IFSC::RBCX => IFSC::RBIS,
        IFSC::RCBX => IFSC::HDFC,
        IFSC::RCCX => IFSC::UTIB,
        IFSC::RCDX => IFSC::TNSC,
        IFSC::RCMX => IFSC::YESB,
        IFSC::RCUX => IFSC::KKBK,
        IFSC::RDCX => IFSC::IBKL,
        IFSC::RDNX => IFSC::HDFC,
        IFSC::REBX => IFSC::IBKL,
        IFSC::RECX => IFSC::YESB,
        IFSC::REWX => IFSC::UTIB,
        IFSC::RGCX => IFSC::IBKL,
        IFSC::RGSX => IFSC::RSBL,
        IFSC::RHMX => IFSC::HDFC,
        IFSC::RJCX => IFSC::WBSC,
        IFSC::RJJX => IFSC::HDFC,
        IFSC::RJNX => IFSC::GSCB,
        IFSC::RJTX => IFSC::GSCB,
        IFSC::RLUX => IFSC::HDFC,
        IFSC::RNBX => IFSC::HDFC,
        IFSC::RNDX => IFSC::IBKL,
        IFSC::RNGX => IFSC::KKBK,
        IFSC::RNSX => IFSC::YESB,
        IFSC::ROCX => IFSC::IBKL,
        IFSC::ROHX => IFSC::UTIB,
        IFSC::RPUX => IFSC::ICIC,
        IFSC::RRSX => IFSC::YESB,
        IFSC::RSBX => IFSC::HDFC,
        IFSC::RSSX => IFSC::HDFC,
        IFSC::RSUX => IFSC::IBKL,
        IFSC::RSVX => IFSC::IBKL,
        IFSC::RUCX => IFSC::UTIB,
        IFSC::RUKX => IFSC::KKBK,
        IFSC::RUMX => IFSC::HDFC,
        IFSC::RZSX => IFSC::ICIC,
        IFSC::SABX => IFSC::KKBK,
        IFSC::SACB => IFSC::ICIC,
        IFSC::SACX => IFSC::HDFC,
        IFSC::SADX => IFSC::GSCB,
        IFSC::SAGX => IFSC::SBIN,
        IFSC::SAHX => IFSC::HDFC,
        IFSC::SAIX => IFSC::HDFC,
        IFSC::SALX => IFSC::GSCB,
        IFSC::SAMX => IFSC::SRCB,
        IFSC::SANX => IFSC::UTIB,
        IFSC::SAOX => IFSC::INDB,
        IFSC::SAPX => IFSC::ICIC,
        IFSC::SARX => IFSC::UTIB,
        IFSC::SASA => IFSC::SVCB,
        IFSC::SASX => IFSC::UTIB,
        IFSC::SATX => IFSC::YESB,
        IFSC::SAVX => IFSC::HDFC,
        IFSC::SAWX => IFSC::RSCB,
        IFSC::SBCX => IFSC::ICIC,
        IFSC::SBKX => IFSC::HDFC,
        IFSC::SBLD => IFSC::HDFC,
        IFSC::SBLX => IFSC::YESB,
        IFSC::SBMX => IFSC::INDB,
        IFSC::SBNX => IFSC::GSCB,
        IFSC::SBPX => IFSC::UTIB,
        IFSC::SBSX => IFSC::HDFC,
        IFSC::SBUJ => IFSC::HDFC,
        IFSC::SBUX => IFSC::ICIC,
        IFSC::SCBX => IFSC::RSCB,
        IFSC::SCCX => IFSC::TNSC,
        IFSC::SCDX => IFSC::IBKL,
        IFSC::SCIX => IFSC::HDFC,
        IFSC::SCNX => IFSC::HDFC,
        IFSC::SCOB => IFSC::IBKL,
        IFSC::SCOX => IFSC::KSCB,
        IFSC::SCPX => IFSC::UTIB,
        IFSC::SCSX => IFSC::INDB,
        IFSC::SCUX => IFSC::HDFC,
        IFSC::SDBX => IFSC::HDFC,
        IFSC::SDCX => IFSC::HDFC,
        IFSC::SDHX => IFSC::HDFC,
        IFSC::SDSX => IFSC::IBKL,
        IFSC::SDTX => IFSC::YESB,
        IFSC::SDUX => IFSC::HDFC,
        IFSC::SENX => IFSC::UTIB,
        IFSC::SEUX => IFSC::ICIC,
        IFSC::SEWX => IFSC::HDFC,
        IFSC::SGDX => IFSC::YESB,
        IFSC::SGLX => IFSC::UTIB,
        IFSC::SGSX => IFSC::HDFC,
        IFSC::SGUX => IFSC::HDFC,
        IFSC::SHAX => IFSC::SVCB,
        IFSC::SHBX => IFSC::HDFC,
        IFSC::SHCX => IFSC::YESB,
        IFSC::SHEX => IFSC::HDFC,
        IFSC::SHGX => IFSC::SVCB,
        IFSC::SHIX => IFSC::SMCB,
        IFSC::SHKX => IFSC::SKSB,
        IFSC::SHMX => IFSC::YESB,
        IFSC::SHNX => IFSC::UTIB,
        IFSC::SHOX => IFSC::HDFC,
        IFSC::SHRX => IFSC::YESB,
        IFSC::SHSX => IFSC::HDFC,
        IFSC::SHUX => IFSC::HDFC,
        IFSC::SIBX => IFSC::RSCB,
        IFSC::SICX => IFSC::UTIB,
        IFSC::SIDX => IFSC::HDFC,
        IFSC::SIHX => IFSC::HDFC,
        IFSC::SIKX => IFSC::HDFC,
        IFSC::SINX => IFSC::GSCB,
        IFSC::SIRX => IFSC::SVCB,
        IFSC::SISX => IFSC::UTIB,
        IFSC::SITX => IFSC::IBKL,
        IFSC::SIWX => IFSC::IBKL,
        IFSC::SJGX => IFSC::PSIB,
        IFSC::SJSX => IFSC::SJSB,
        IFSC::SKCX => IFSC::YESB,
        IFSC::SKKX => IFSC::IBKL,
        IFSC::SKNX => IFSC::HDFC,
        IFSC::SKUX => IFSC::IBKL,
        IFSC::SLAX => IFSC::HDFC,
        IFSC::SLCX => IFSC::HDFC,
        IFSC::SMBX => IFSC::IBKL,
        IFSC::SMCX => IFSC::HDFC,
        IFSC::SMEX => IFSC::HDFC,
        IFSC::SMMX => IFSC::HDFC,
        IFSC::SMNX => IFSC::HDFC,
        IFSC::SMPX => IFSC::HDFC,
        IFSC::SMSX => IFSC::IBKL,
        IFSC::SMTX => IFSC::HDFC,
        IFSC::SMUX => IFSC::HDFC,
        IFSC::SMVC => IFSC::INDB,
        IFSC::SNAX => IFSC::HDFC,
        IFSC::SNBX => IFSC::GSCB,
        IFSC::SNCX => IFSC::IBKL,
        IFSC::SNDX => IFSC::YESB,
        IFSC::SNGX => IFSC::HDFC,
        IFSC::SNLX => IFSC::SVCB,
        IFSC::SNPX => IFSC::UTIB,
        IFSC::SNSV => IFSC::UTIB,
        IFSC::SNSX => IFSC::YESB,
        IFSC::SOBX => IFSC::SVCB,
        IFSC::SOLX => IFSC::YESB,
        IFSC::SONX => IFSC::HDFC,
        IFSC::SPBX => IFSC::IDIB,
        IFSC::SPCX => IFSC::KKBK,
        IFSC::SPNX => IFSC::IBKL,
        IFSC::SPSX => IFSC::IBKL,
        IFSC::SPTX => IFSC::IBKL,
        IFSC::SRCX => IFSC::HDFC,
        IFSC::SREX => IFSC::HDFC,
        IFSC::SRGX => IFSC::SVCB,
        IFSC::SRHX => IFSC::YESB,
        IFSC::SRSX => IFSC::IBKL,
        IFSC::SSBL => IFSC::YESB,
        IFSC::SSBX => IFSC::HDFC,
        IFSC::SSDX => IFSC::HDFC,
        IFSC::SSHX => IFSC::YESB,
        IFSC::SSKX => IFSC::IBKL,
        IFSC::SSLX => IFSC::HDFC,
        IFSC::SSNX => IFSC::INDB,
        IFSC::SSOX => IFSC::ICIC,
        IFSC::SSSX => IFSC::HDFC,
        IFSC::SSWX => IFSC::UTIB,
        IFSC::STCX => IFSC::IBKL,
        IFSC::STDX => IFSC::IBKL,
        IFSC::STRX => IFSC::HDFC,
        IFSC::SUBX => IFSC::PUNB,
        IFSC::SUCX => IFSC::HDFC,
        IFSC::SUDX => IFSC::GSCB,
        IFSC::SULX => IFSC::ICIC,
        IFSC::SUMX => IFSC::YESB,
        IFSC::SUNB => IFSC::HDFC,
        IFSC::SURX => IFSC::ICIC,
        IFSC::SUSX => IFSC::IBKL,
        IFSC::SUVX => IFSC::HDFC,
        IFSC::SVAX => IFSC::IBKL,
        IFSC::SVCX => IFSC::IBKL,
        IFSC::SVGX => IFSC::TNSC,
        IFSC::SVNX => IFSC::ICIC,
        IFSC::SVOX => IFSC::YESB,
        IFSC::SVRX => IFSC::YESB,
        IFSC::SVSX => IFSC::PMEC,
        IFSC::SWMX => IFSC::HDFC,
        IFSC::SWSX => IFSC::HDFC,
        IFSC::TACX => IFSC::YESB,
        IFSC::TADX => IFSC::APBL,
        IFSC::TAMX => IFSC::UTIB,
        IFSC::TAPX => IFSC::HDFC,
        IFSC::TASX => IFSC::HDFC,
        IFSC::TBCX => IFSC::IBKL,
        IFSC::TBDX => IFSC::UTIB,
        IFSC::TBHX => IFSC::UTIB,
        IFSC::TBMX => IFSC::GSCB,
        IFSC::TBPX => IFSC::IBKL,
        IFSC::TBSX => IFSC::YESB,
        IFSC::TBTX => IFSC::IBKL,
        IFSC::TBUX => IFSC::IBKL,
        IFSC::TCBX => IFSC::YESB,
        IFSC::TCCX => IFSC::HDFC,
        IFSC::TCHX => IFSC::IBKL,
        IFSC::TDBX => IFSC::KSCB,
        IFSC::TDCX => IFSC::IBKL,
        IFSC::TDIX => IFSC::APBL,
        IFSC::TDMX => IFSC::HDFC,
        IFSC::TECX => IFSC::HDFC,
        IFSC::TEHX => IFSC::IBKL,
        IFSC::TEMX => IFSC::INDB,
        IFSC::TESX => IFSC::YESB,
        IFSC::TETX => IFSC::HDFC,
        IFSC::TFCX => IFSC::UTIB,
        IFSC::TGBX => IFSC::UTBI,
        IFSC::TGCG => IFSC::IBKL,
        IFSC::TGCX => IFSC::WBSC,
        IFSC::TGDX => IFSC::UTIB,
        IFSC::TGNX => IFSC::HDFC,
        IFSC::TGUX => IFSC::HDFC,
        IFSC::THCX => IFSC::IBKL,
        IFSC::THMX => IFSC::HDFC,
        IFSC::THOX => IFSC::TNSC,
        IFSC::THRX => IFSC::IBKL,
        IFSC::THWX => IFSC::WBSC,
        IFSC::TIDX => IFSC::TNSC,
        IFSC::TIRX => IFSC::TNSC,
        IFSC::TJAX => IFSC::RSCB,
        IFSC::TJBX => IFSC::IBKL,
        IFSC::TJCX => IFSC::TNSC,
        IFSC::TJDX => IFSC::ICIC,
        IFSC::TJMX => IFSC::IBKL,
        IFSC::TJNX => IFSC::GSCB,
        IFSC::TKAX => IFSC::GSCB,
        IFSC::TKCX => IFSC::IBKL,
        IFSC::TKDX => IFSC::UTIB,
        IFSC::TKTX => IFSC::UTIB,
        IFSC::TKUX => IFSC::HDFC,
        IFSC::TLPX => IFSC::HDFC,
        IFSC::TMAX => IFSC::HDFC,
        IFSC::TMBX => IFSC::IBKL,
        IFSC::TMCX => IFSC::IBKL,
        IFSC::TMNX => IFSC::KKBK,
        IFSC::TMPX => IFSC::IBKL,
        IFSC::TMSC => IFSC::KKBK,
        IFSC::TMSX => IFSC::KKBK,
        IFSC::TMTX => IFSC::IBKL,
        IFSC::TMUX => IFSC::HDFC,
        IFSC::TNBX => IFSC::GSCB,
        IFSC::TNCX => IFSC::IBKL,
        IFSC::TNDC => IFSC::IBKL,
        IFSC::TNEX => IFSC::INDB,
        IFSC::TNHX => IFSC::HDFC,
        IFSC::TNIX => IFSC::TNSC,
        IFSC::TNKX => IFSC::HDFC,
        IFSC::TNMX => IFSC::IBKL,
        IFSC::TNUX => IFSC::HDFC,
        IFSC::TOCX => IFSC::IBKL,
        IFSC::TPCX => IFSC::IBKL,
        IFSC::TPDX => IFSC::APBL,
        IFSC::TPSX => IFSC::ICIC,
        IFSC::TPUX => IFSC::IBKL,
        IFSC::TRAX => IFSC::YESB,
        IFSC::TRDX => IFSC::TNSC,
        IFSC::TSAX => IFSC::ICIC,
        IFSC::TSBX => IFSC::IBKL,
        IFSC::TSCX => IFSC::HDFC,
        IFSC::TSDX => IFSC::APBL,
        IFSC::TSIX => IFSC::IBKL,
        IFSC::TSKX => IFSC::UTIB,
        IFSC::TSMX => IFSC::GSCB,
        IFSC::TSNX => IFSC::IBKL,
        IFSC::TSPX => IFSC::GSCB,
        IFSC::TSUX => IFSC::GSCB,
        IFSC::TTBX => IFSC::IBKL,
        IFSC::TTCX => IFSC::UTIB,
        IFSC::TTGX => IFSC::IBKL,
        IFSC::TTUX => IFSC::ICIC,
        IFSC::TUBX => IFSC::HDFC,
        IFSC::TUCL => IFSC::HDFC,
        IFSC::TUCX => IFSC::HDFC,
        IFSC::TUDX => IFSC::KKBK,
        IFSC::TUMX => IFSC::ICIC,
        IFSC::TUNX => IFSC::HDFC,
        IFSC::TUOX => IFSC::IBKL,
        IFSC::TURX => IFSC::YESB,
        IFSC::TVDX => IFSC::APBL,
        IFSC::TVPX => IFSC::HDFC,
        IFSC::TVUX => IFSC::ICIC,
        IFSC::TYCX => IFSC::INDB,
        IFSC::UBBX => IFSC::YESB,
        IFSC::UBGX => IFSC::CBIN,
        IFSC::UCBX => IFSC::IBKL,
        IFSC::UCCX => IFSC::RSCB,
        IFSC::UCDX => IFSC::YESB,
        IFSC::UCUX => IFSC::HDFC,
        IFSC::UGBX => IFSC::SBIN,
        IFSC::UICX => IFSC::UTIB,
        IFSC::UJSX => IFSC::GSCB,
        IFSC::UKGX => IFSC::CBIN,
        IFSC::UMAX => IFSC::YESB,
        IFSC::UMCX => IFSC::HDFC,
        IFSC::UMSX => IFSC::YESB,
        IFSC::UMUX => IFSC::YESB,
        IFSC::UNAX => IFSC::HDFC,
        IFSC::UNIX => IFSC::GSCB,
        IFSC::UNMX => IFSC::YESB,
        IFSC::UNSX => IFSC::GSCB,
        IFSC::UPCX => IFSC::ICIC,
        IFSC::UPNX => IFSC::YESB,
        IFSC::URCX => IFSC::ICIC,
        IFSC::URDX => IFSC::IBKL,
        IFSC::URMX => IFSC::IBKL,
        IFSC::UROX => IFSC::YESB,
        IFSC::USFB => IFSC::UJVN,
        IFSC::USNX => IFSC::ICIC,
        IFSC::UTBX => IFSC::HDFC,
        IFSC::UTCX => IFSC::HDFC,
        IFSC::UTGX => IFSC::SBIN,
        IFSC::UTKX => IFSC::ICIC,
        IFSC::UTZX => IFSC::YESB,
        IFSC::UUCX => IFSC::YESB,
        IFSC::VADX => IFSC::GSCB,
        IFSC::VAIX => IFSC::IBKL,
        IFSC::VANX => IFSC::UTIB,
        IFSC::VASX => IFSC::YESB,
        IFSC::VAUX => IFSC::KKBK,
        IFSC::VCAX => IFSC::YESB,
        IFSC::VCBX => IFSC::HDFC,
        IFSC::VCCX => IFSC::HDFC,
        IFSC::VCNB => IFSC::YESB,
        IFSC::VCOX => IFSC::IBKL,
        IFSC::VDCX => IFSC::TNSC,
        IFSC::VDYX => IFSC::HDFC,
        IFSC::VEDX => IFSC::TNSC,
        IFSC::VERX => IFSC::HDFC,
        IFSC::VGBX => IFSC::SBIN,
        IFSC::VHDX => IFSC::IBKL,
        IFSC::VICX => IFSC::KKBK,
        IFSC::VIDX => IFSC::WBSC,
        IFSC::VIJX => IFSC::HDFC,
        IFSC::VIKX => IFSC::YESB,
        IFSC::VIMX => IFSC::GSCB,
        IFSC::VIRX => IFSC::TNSC,
        IFSC::VISX => IFSC::IBKL,
        IFSC::VJSX => IFSC::HDFC,
        IFSC::VKCX => IFSC::YESB,
        IFSC::VKSX => IFSC::HDFC,
        IFSC::VMCX => IFSC::HDFC,
        IFSC::VMMX => IFSC::YESB,
        IFSC::VMUX => IFSC::YESB,
        IFSC::VNSX => IFSC::ICIC,
        IFSC::VRDX => IFSC::ICIC,
        IFSC::VSBX => IFSC::SVCB,
        IFSC::VSCX => IFSC::ICIC,
        IFSC::VSSX => IFSC::HDFC,
        IFSC::VSVX => IFSC::YESB,
        IFSC::VUCX => IFSC::HDFC,
        IFSC::VVCX => IFSC::HDFC,
        IFSC::VYAX => IFSC::YESB,
        IFSC::VYPX => IFSC::UTIB,
        IFSC::WACX => IFSC::IBKL,
        IFSC::WAIX => IFSC::SVCB,
        IFSC::WARX => IFSC::APBL,
        IFSC::WAUX => IFSC::IBKL,
        IFSC::WCBX => IFSC::HDFC,
        IFSC::WDCX => IFSC::FDRL,
        IFSC::WKGX => IFSC::BKID,
        IFSC::WNBX => IFSC::YESB,
        IFSC::WRCX => IFSC::YESB,
        IFSC::WUCX => IFSC::HDFC,
        IFSC::WZUX => IFSC::UTIB,
        IFSC::XJKG => IFSC::JAKA,
        IFSC::YADX => IFSC::YESB,
        IFSC::YAVX => IFSC::YESB,
        IFSC::YCBX => IFSC::HDFC,
        IFSC::YDCX => IFSC::HDFC,
        IFSC::YLNX => IFSC::YESB,
        IFSC::YMSX => IFSC::HDFC,
        IFSC::YNCX => IFSC::UTIB,
        IFSC::YNSX => IFSC::HDFC,
        IFSC::ZBBX => IFSC::ICIC,
        IFSC::ZBSX => IFSC::ICIC,
        IFSC::ZIBX => IFSC::ICIC,
        IFSC::ZILX => IFSC::ICIC,
        IFSC::ZIMX => IFSC::ICIC,
        IFSC::ZISX => IFSC::YESB,
        IFSC::ZLLX => IFSC::ICIC,
        IFSC::ZMMX => IFSC::ICIC,
        IFSC::ZRNB => IFSC::IBKL,
        IFSC::ZSAX => IFSC::ICIC,
        IFSC::ZSBX => IFSC::ICIC,
        IFSC::ZSGX => IFSC::ICIC,
        IFSC::ZSHX => IFSC::ICIC,
        IFSC::ZSJX => IFSC::ICIC,
        IFSC::ZSKX => IFSC::ICIC,
        IFSC::ZSLX => IFSC::ICIC,
        IFSC::ZSMX => IFSC::ICIC
    ];

    // banks supported by enach_npci_netbanking gateway for auth type card
    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    const ENACH_NPCI_NB_AUTH_CARD_BANKS = [
        IFSC::ACUX,
        IFSC::AIRP,
        IFSC::APGB,
        IFSC::AUBL,
        Netbanking::BARB_R,
        IFSC::BARB,
        IFSC::BKID,
        IFSC::CBIN,
        IFSC::CGBX,
        IFSC::CITI,
        IFSC::CLBL,
        IFSC::CNRB,
        IFSC::CNSX,
        IFSC::CSBK,
        IFSC::DBSS,
        IFSC::DCBL,
        IFSC::DEUT,
        IFSC::DLXB,
        IFSC::EDBX,
        IFSC::ESFB,
        IFSC::FDRL,
        IFSC::FINF,
        IFSC::HDFC,
        IFSC::IBKL,
        IFSC::ICIC,
        IFSC::IDFB,
        IFSC::IDIB,
        IFSC::INDB,
        IFSC::JAKA,
        IFSC::JIOP,
        IFSC::JSFB,
        IFSC::JUCX,
        IFSC::KARB,
        IFSC::KCCB,
        IFSC::KKBK,
        IFSC::KNSB,
        IFSC::MAHB,
        IFSC::MHSX,
        IFSC::NCBL,
        IFSC::NSPB,
        IFSC::PSIB,
        IFSC::PUNB,
        Netbanking::PUNB_R,
        IFSC::PYTM,
        IFSC::RATN,
        IFSC::RSSX,
        IFSC::SBIN,
        IFSC::SCBL,
        IFSC::SHIX,
        IFSC::SIBL,
        IFSC::SPCB,
        IFSC::SRCB,
        IFSC::STCB,
        IFSC::SURY,
        IFSC::TMBL,
        IFSC::UBIN,
        IFSC::USFB,
        IFSC::UTIB,
        IFSC::UTKS,
        IFSC::YESB,
        IFSC::ZCBL,
        IFSC::CIUB
    ];

    // disabled for all auth types
    const EMANDATE_REGISTRATION_DISABLED_BANKS = [
    ];

    const NB_EMANDATE_REGISTRATION_DISABLED_BANKS = [
        IFSC::AACX,
        IFSC::ABDX,
        IFSC::ABHY,
        IFSC::ABSB,
        IFSC::ABUX,
        IFSC::ACBX,
        IFSC::ACKX,
        IFSC::ACUX,
        IFSC::ADBX,
        IFSC::ADCC,
        IFSC::ADCX,
        IFSC::AGCX,
        IFSC::AGSX,
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
        IFSC::ANSX,
        IFSC::APBL,
        IFSC::APGX,
        IFSC::APMX,
        IFSC::APSX,
        IFSC::ASBL,
        IFSC::ASHX,
        IFSC::ASKX,
        IFSC::ASOX,
        IFSC::AUCX,
        IFSC::AVDX,
        IFSC::BACB,
        IFSC::BACX,
        IFSC::BARX,
        IFSC::BASX,
        IFSC::BAVX,
        IFSC::BBLX,
        IFSC::BCBM,
        IFSC::BCBX,
        IFSC::BDUX,
        IFSC::BHAX,
        IFSC::BHCX,
        IFSC::BHDX,
        IFSC::BHEX,
        IFSC::BHJX,
        IFSC::BHMX,
        IFSC::BHOX,
        IFSC::BHSX,
        IFSC::BHUX,
        IFSC::BJUX,
        IFSC::BKCX,
        IFSC::BKDX,
        IFSC::BKID,
        IFSC::BMCB,
        IFSC::BMPX,
        IFSC::BNBX,
        IFSC::BNCX,
        IFSC::BNSX,
        IFSC::BORX,
        IFSC::BRDX,
        IFSC::BRMX,
        IFSC::BSBX,
        IFSC::BURX,
        IFSC::BUZX,
        IFSC::CALX,
        IFSC::CBHX,
        IFSC::CCCX,
        IFSC::CGBX,
        IFSC::CHBX,
        IFSC::CHTX,
        IFSC::CJAX,
        IFSC::CLBL,
        IFSC::CMCB,
        IFSC::CMDX,
        IFSC::CMLX,
        IFSC::CNSX,
        IFSC::COCX,
        IFSC::COMX,
        IFSC::CSBX,
        IFSC::CTBX,
        IFSC::CZCX,
        IFSC::CZUX,
        IFSC::DAHX,
        IFSC::DBAX,
        IFSC::DCBX,
        IFSC::DDBX,
        IFSC::DENS,
        IFSC::DHUX,
        IFSC::DNSB,
        IFSC::DOBX,
        IFSC::DSCB,
        IFSC::DSPX,
        IFSC::DSUX,
        IFSC::DTCX,
        IFSC::EDBX,
        IFSC::EUCX,
        IFSC::FINF,
        IFSC::FINO,
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
        IFSC::GUNX,
        IFSC::HAMX,
        IFSC::HGBX,
        IFSC::HMNX,
        IFSC::HPCX,
        IFSC::HPSX,
        IFSC::HSDX,
        IFSC::HUCH,
        IFSC::ICBL,
        IFSC::ICMX,
        IFSC::IMPX,
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
        IFSC::JKSX,
        IFSC::JMCX,
        IFSC::JMHX,
        IFSC::JMPX,
        IFSC::JODX,
        IFSC::JONX,
        IFSC::JPCB,
        IFSC::JSBL,
        IFSC::JSBP,
        IFSC::JSMX,
        IFSC::JUCX,
        IFSC::JVCX,
        IFSC::KALX,
        IFSC::KAMX,
        IFSC::KBCX,
        IFSC::KCCB,
        IFSC::KCUB,
        IFSC::KCUX,
        IFSC::KDIX,
        IFSC::KHDX,
        IFSC::KHUX,
        IFSC::KKMX,
        IFSC::KLGB,
        IFSC::KMCX,
        IFSC::KMSX,
        IFSC::KNBX,
        IFSC::KNSB,
        IFSC::KOYX,
        IFSC::KRNX,
        IFSC::KRTX,
        IFSC::KSCB,
        IFSC::KSUX,
        IFSC::KUCX,
        IFSC::KUKX,
        IFSC::KVCX,
        IFSC::LATX,
        IFSC::LBMX,
        IFSC::LDPX,
        IFSC::LKMX,
        IFSC::LOKX,
        IFSC::MABL,
        IFSC::MAKX,
        IFSC::MALX,
        IFSC::MCSX,
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
        IFSC::MUPX,
        IFSC::MUSX,
        IFSC::MVCX,
        IFSC::MYAX,
        IFSC::MZCX,
        IFSC::NAIX,
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
        IFSC::NTBL,
        IFSC::NVSX,
        IFSC::ODGB,
        IFSC::OIBA,
        IFSC::OMCX,
        IFSC::ONSX,
        IFSC::OSMX,
        IFSC::PALX,
        IFSC::PASX,
        IFSC::PBGX,
        IFSC::PCBL,
        IFSC::PCSX,
        IFSC::PCTX,
        IFSC::PCUX,
        IFSC::PDSX,
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
        IFSC::PTCX,
        IFSC::PTNX,
        IFSC::PTSX,
        IFSC::PUBX,
        IFSC::PVCX,
        IFSC::QUCX,
        IFSC::RACX,
        IFSC::RAKX,
        IFSC::RBBX,
        IFSC::RCCX,
        IFSC::RCUX,
        IFSC::RDNX,
        IFSC::REBX,
        IFSC::RECX,
        IFSC::RGSX,
        IFSC::RRSX,
        IFSC::RSSX,
        IFSC::RZSX,
        IFSC::SACX,
        IFSC::SADX,
        IFSC::SAHX,
        IFSC::SASA,
        IFSC::SATX,
        IFSC::SAVX,
        IFSC::SBMX,
        IFSC::SBNX,
        IFSC::SBPX,
        IFSC::SBUJ,
        IFSC::SBUX,
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
        IFSC::SHRX,
        IFSC::SHSX,
        IFSC::SIRX,
        IFSC::SISX,
        IFSC::SKCX,
        IFSC::SKNX,
        IFSC::SKUX,
        IFSC::SMNX,
        IFSC::SMUX,
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
        IFSC::SRCX,
        IFSC::SSBX,
        IFSC::SSKX,
        IFSC::SSLX,
        IFSC::SULX,
        IFSC::SUMX,
        IFSC::SUNB,
        IFSC::SUTB,
        IFSC::SUVX,
        IFSC::SVCB,
        IFSC::SVCX,
        IFSC::SVSX,
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
        IFSC::TGUX,
        IFSC::THOX,
        IFSC::TIRX,
        IFSC::TJNX,
        IFSC::TJSB,
        IFSC::TLPX,
        IFSC::TMSX,
        IFSC::TNKX,
        IFSC::TNMX,
        IFSC::TSIX,
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
        IFSC::UCCX,
        IFSC::UCDX,
        IFSC::UCUX,
        IFSC::UMAX,
        IFSC::UMCX,
        IFSC::UNIX,
        IFSC::UNSX,
        IFSC::UPCX,
        IFSC::UROX,
        IFSC::UTKS,
        IFSC::VAIX,
        IFSC::VCBX,
        IFSC::VCCX,
        IFSC::VCNB,
        IFSC::VDYX,
        IFSC::VERX,
        IFSC::VICX,
        IFSC::VIKX,
        IFSC::VJSX,
        IFSC::VSBX,
        IFSC::VSCX,
        IFSC::VUCX,
        IFSC::VVCX,
        IFSC::WAIX,
        IFSC::WKGX,
        IFSC::WRCX,
        IFSC::XJKG,
        IFSC::YADX,
        IFSC::YAVX,
        IFSC::YLNX,
        IFSC::ZCBL,
        IFSC::ZSBL,
        IFSC::ZSHX,
        IFSC::ZSMX,
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
        IFSC::ABUX,
        IFSC::ACBX,
        IFSC::ACKX,
        IFSC::ACUX,
        IFSC::ADBX,
        IFSC::ADCC,
        IFSC::ADCX,
        IFSC::AGCX,
        IFSC::AGSX,
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
        IFSC::ANSX,
        IFSC::APBL,
        IFSC::APGX,
        IFSC::APMX,
        IFSC::APSX,
        IFSC::ASBL,
        IFSC::ASHX,
        IFSC::ASKX,
        IFSC::ASOX,
        IFSC::AUBL,
        IFSC::AUCX,
        IFSC::AVDX,
        IFSC::BACB,
        IFSC::BACX,
        Netbanking::BARB_R,
        IFSC::BARX,
        IFSC::BASX,
        IFSC::BAVX,
        IFSC::BBLX,
        IFSC::BCBM,
        IFSC::BCBX,
        IFSC::BDUX,
        IFSC::BHAX,
        IFSC::BHCX,
        IFSC::BHDX,
        IFSC::BHEX,
        IFSC::BHJX,
        IFSC::BHMX,
        IFSC::BHOX,
        IFSC::BHSX,
        IFSC::BHUX,
        IFSC::BJUX,
        IFSC::BKCX,
        IFSC::BKDX,
        IFSC::BKID,
        IFSC::BMCB,
        IFSC::BMPX,
        IFSC::BNBX,
        IFSC::BNCX,
        IFSC::BNSX,
        IFSC::BORX,
        IFSC::BRDX,
        IFSC::BRMX,
        IFSC::BSBX,
        IFSC::BURX,
        IFSC::BUZX,
        IFSC::CALX,
        IFSC::CBHX,
        IFSC::CBIN,
        IFSC::CCCX,
        IFSC::CHBX,
        IFSC::CHTX,
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CJAX,
        IFSC::CLBL,
        IFSC::CMCB,
        IFSC::CMDX,
        IFSC::CMLX,
        IFSC::CNRB,
        IFSC::COCX,
        IFSC::COMX,
        IFSC::COSB,
        IFSC::CSBK,
        IFSC::CSBX,
        IFSC::CTBX,
        IFSC::CZCX,
        IFSC::CZUX,
        IFSC::DAHX,
        IFSC::DBAX,
        IFSC::DCBL,
        IFSC::DCBX,
        IFSC::DDBX,
        IFSC::DENS,
        IFSC::DEUT,
        IFSC::DHUX,
        IFSC::DLXB,
        IFSC::DNSB,
        IFSC::DOBX,
        IFSC::DSCB,
        IFSC::DSPX,
        IFSC::DSUX,
        IFSC::DTCX,
        IFSC::EUCX,
        IFSC::FDRL,
        IFSC::FINO,
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
        IFSC::GUNX,
        IFSC::HAMX,
        IFSC::HDFC,
        IFSC::HGBX,
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
        IFSC::JKSX,
        IFSC::JMCX,
        IFSC::JMHX,
        IFSC::JMPX,
        IFSC::JODX,
        IFSC::JONX,
        IFSC::JPCB,
        IFSC::JSBL,
        IFSC::JSBP,
        IFSC::JSMX,
        IFSC::JVCX,
        IFSC::KALX,
        IFSC::KAMX,
        IFSC::KARB,
        IFSC::KBCX,
        IFSC::KCCB,
        IFSC::KCUB,
        IFSC::KCUX,
        IFSC::KDIX,
        IFSC::KHDX,
        IFSC::KHUX,
        IFSC::KKBK,
        IFSC::KKMX,
        IFSC::KLGB,
        IFSC::KMCX,
        IFSC::KMSX,
        IFSC::KNBX,
        IFSC::KOYX,
        IFSC::KRNX,
        IFSC::KRTX,
        IFSC::KSCB,
        IFSC::KSUX,
        IFSC::KUCX,
        IFSC::KUKX,
        IFSC::KVBL,
        IFSC::KVCX,
        IFSC::LATX,
        IFSC::LBMX,
        IFSC::LDPX,
        IFSC::LKMX,
        IFSC::LOKX,
        IFSC::MABL,
        IFSC::MAHB,
        IFSC::MAKX,
        IFSC::MALX,
        IFSC::MCSX,
        IFSC::MDEX,
        IFSC::MGBX,
        IFSC::MGCX,
        IFSC::MHNX,
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
        IFSC::MUPX,
        IFSC::MUSX,
        IFSC::MVCX,
        IFSC::MYAX,
        IFSC::MZCX,
        IFSC::NAIX,
        IFSC::NASX,
        IFSC::NAVX,
        IFSC::NAWX,
        IFSC::NBBX,
        IFSC::NBMX,
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
        IFSC::NTBL,
        IFSC::NVSX,
        IFSC::ODGB,
        IFSC::OIBA,
        IFSC::OMCX,
        IFSC::ONSX,
        IFSC::OSMX,
        IFSC::PALX,
        IFSC::PASX,
        IFSC::PBGX,
        IFSC::PCBL,
        IFSC::PCSX,
        IFSC::PCTX,
        IFSC::PCUX,
        IFSC::PDSX,
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
        IFSC::PTCX,
        IFSC::PTNX,
        IFSC::PTSX,
        IFSC::PUBX,
        IFSC::PUNB,
        Netbanking::PUNB_R,
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
        IFSC::RGSX,
        IFSC::RRSX,
        IFSC::RZSX,
        IFSC::SACX,
        IFSC::SADX,
        IFSC::SAHX,
        IFSC::SASA,
        IFSC::SATX,
        IFSC::SAVX,
        IFSC::SBMX,
        IFSC::SBNX,
        IFSC::SBPX,
        IFSC::SBUJ,
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
        IFSC::SHRX,
        IFSC::SHSX,
        IFSC::SIBL,
        IFSC::SIRX,
        IFSC::SISX,
        IFSC::SKCX,
        IFSC::SKNX,
        IFSC::SKUX,
        IFSC::SMNX,
        IFSC::SMUX,
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
        IFSC::SRCX,
        IFSC::SSBX,
        IFSC::SSKX,
        IFSC::SSLX,
        IFSC::SULX,
        IFSC::SUMX,
        IFSC::SUNB,
        IFSC::SUTB,
        IFSC::SUVX,
        IFSC::SVCB,
        IFSC::SVCX,
        IFSC::SVSX,
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
        IFSC::TGUX,
        IFSC::THOX,
        IFSC::TIRX,
        IFSC::TJNX,
        IFSC::TJSB,
        IFSC::TLPX,
        IFSC::TMBL,
        IFSC::TMSX,
        IFSC::TNKX,
        IFSC::TNMX,
        IFSC::TSIX,
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
        IFSC::UCUX,
        IFSC::UMAX,
        IFSC::UMCX,
        IFSC::UNIX,
        IFSC::UNSX,
        IFSC::UPCX,
        IFSC::UROX,
        IFSC::UTIB,
        IFSC::VAIX,
        IFSC::VARA,
        IFSC::VCBX,
        IFSC::VCCX,
        IFSC::VCNB,
        IFSC::VDYX,
        IFSC::VERX,
        IFSC::VICX,
        IFSC::VIKX,
        IFSC::VJSX,
        IFSC::VSBX,
        IFSC::VSCX,
        IFSC::VUCX,
        IFSC::VVCX,
        IFSC::WAIX,
        IFSC::WKGX,
        IFSC::WRCX,
        IFSC::XJKG,
        IFSC::YADX,
        IFSC::YAVX,
        IFSC::YESB,
        IFSC::YLNX,
        IFSC::ZSBL,
        IFSC::ZSHX,
        IFSC::ZSMX,
        ];

    const AADHAAR_EMANDATE_REGISTRATION_DISABLED_BANKS = [
        IFSC::AIRP,
        IFSC::APGB,
        IFSC::BDBL,
        IFSC::CGBX,
        IFSC::CNSX,
        IFSC::DBSS,
        IFSC::EDBX,
        IFSC::ESAF,
        IFSC::ESFB,
        IFSC::FINF,
        IFSC::JAKA,
        IFSC::JIOP,
        IFSC::JSFB,
        IFSC::JUCX,
        IFSC::KNSB,
        IFSC::KVGB,
        IFSC::MHSX,
        IFSC::NCBL,
        IFSC::NSPB,
        IFSC::PSIB,
        IFSC::RSSX,
        IFSC::SBIN,
        IFSC::SHIX,
        IFSC::STCB,
        IFSC::SURY,
        IFSC::UJVN,
        IFSC::USFB,
        IFSC::UTKS,
        IFSC::ZCBL,
        //todo: After removing USFB from Disable banks list needs to add condition to route through UJVN.
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

    const GATEWAY_CONTROLLER_NOT_SCROOGE_FILE_BASED_REFUNDS = [
        self::NETBANKING_JSB,
        self::NETBANKING_AUSF
    ];

    const GATEWAY_TO_SETTLEMENT_CURRENCY_MAPPING = [
        self::CHECKOUT_DOT_COM => [Currency::USD],
        self::EMERCHANTPAY => [Currency::EUR,Currency::GBP,Currency::AUD],
        self::CURRENCY_CLOUD => [Currency::USD, Currency::EUR, Currency::GBP, Currency::AUD, Currency::CAD],
        self::PING_PONG => [Currency::USD],
    ];

    const WALLET_PAYMENT = 'walletPayments';
    const S2S_TOKEN = 's2s_token';

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
            self::PHONEPE,
            self::ZAAKPAY,
            self::CCAVENUE,
            self::PINELABS,
            self::LYRA,
            self::CHECKOUT_DOT_COM,
            self::FULCRUM,
            self::INGENICO,
            self::BILLDESK_OPTIMIZER,
            self::CHECKOUT_DOT_COM_OPTIMIZER,
            self::HDFC_EZETAP,
            self::OPTIMIZER_RAZORPAY,
            self::EASEBUZZ_OPTIMIZER
        ],

        Method::NETBANKING => [
            self::PAYTM,
            self::BILLDESK,
            self::EBS,
            self::ATOM,
            self::PAYU,
            self::CASHFREE,
            self::PHONEPE,
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

        Method::GIFT_CARDS => [
            self::WALLET_RAZORPAYWALLET,
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
            self::PHONEPE,
            self::PAYU,
            self::PAYTM,
            self::PINELABS,
            self::HDFC_EZETAP,
            self::BILLDESK_OPTIMIZER,
            self::CCAVENUE,
            self::OPTIMIZER_RAZORPAY,
            self::UPI_KOTAK,
            self::ATOM,
            self::EASEBUZZ_OPTIMIZER,
            self::UPI_RZPAPB,
            self::UPI_RZPAXIS
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
        self::CHECKOUT_DOT_COM_OPTIMIZER => [],
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
        Provider::AXIS      => self::BT_AXIS,
        Provider::AXIS_RTPL => self::BT_AXIS,
        Provider::IDFC       => self::BT_IDFC,
        Provider::INDUSIND  => self::BT_IBL,
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
        self::UPI_RZPAPB,
        self::UPI_RZPAXIS,
        self::ATOM,
        self::WALLET_PHONEPE,
        self::CRED,
        self::CASHFREE,
        self::PAYU,
        self::PAYTM,
        self::PINELABS,
        self::BILLDESK_OPTIMIZER,
        self::CCAVENUE,
        self::OPTIMIZER_RAZORPAY,
        self::EASEBUZZ_OPTIMIZER
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
        ],
        self::PHONEPE => [
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
        self:: PHONEPE => [
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
        self::LYRA => [
            Network::VISA,
            Network::MC,
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
        self:: CHECKOUT_DOT_COM_OPTIMIZER =>[
            Network::MC,
            Network::VISA,
            Network::AMEX,
            Network::DISC,
            Network::DICL,
            Network::JCB,
        ],
        self:: EASEBUZZ_OPTIMIZER => [
            Network::MC,
            Network::VISA,
            Network::AMEX,
            Network::RUPAY,
            Network::DICL,
            Network::DISC,
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

    public static $giftCardToGatewayMap = [
        Payment\Processor\GiftCard::RAZORPAYWALLET     => Gateway::WALLET_RAZORPAYWALLET,
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
        Gateway::UPI_AXIS,
        Gateway::UPI_RZPAPB,
    ];

    public static $cardMandateGateways = [
        Gateway::BILLDESK_SIHUB,
        Gateway::MANDATE_HQ,
    ];

    public static $upiRecurringValidateVPASupportedGateway = [
        Gateway::UPI_MINDGATE,
        Gateway::UPI_AXIS,
    ];

    public static $upiRecurringGateways = [
        Gateway::UPI_MINDGATE,
        Gateway::UPI_ICICI,
        Gateway::UPI_AXIS,
        Gateway::UPI_RZPAPB,
        Gateway::BILLDESK_OPTIMIZER,
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
        IFSC::DCBL,
        IFSC::ABHY,
        IFSC::TJSB,
        IFSC::PJSB,
        IFSC::APMC,
        IFSC::TBSB,
        IFSC::MCBL,
        IFSC::HCBL,
        IFSC::ORCB,
        IFSC::DNSB,
        IFSC::MSCI,
        IFSC::JANA,
        IFSC::JSFB,
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
        self::UPI_KOTAK,
        self::UPI_AIRTEL,
    ];

    public static $upiTransferGateway = [
        self::UPI_MINDGATE,
        self::UPI_ICICI,
        self::UPI_YESBANK,
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
        IFSC::ABUX,
        IFSC::ACBX,
        IFSC::ACKX,
        IFSC::ACUX,
        IFSC::ADBX,
        IFSC::ADCC,
        IFSC::ADCX,
        IFSC::AGCX,
        IFSC::AGSX,
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
        IFSC::ANSX,
        IFSC::APBL,
        IFSC::APGB,
        IFSC::APGX,
        IFSC::APMX,
        IFSC::APSX,
        IFSC::ASBL,
        IFSC::ASHX,
        IFSC::ASKX,
        IFSC::ASOX,
        IFSC::AUBL,
        IFSC::AUCX,
        IFSC::AVDX,
        IFSC::BACB,
        IFSC::BACX,
        NETBANKING::BARB_R,
        IFSC::BARX,
        IFSC::BASX,
        IFSC::BAVX,
        IFSC::BBLX,
        IFSC::BCBM,
        IFSC::BCBX,
        IFSC::BDBL,
        IFSC::BDUX,
        IFSC::BHAX,
        IFSC::BHCX,
        IFSC::BHDX,
        IFSC::BHEX,
        IFSC::BHJX,
        IFSC::BHMX,
        IFSC::BHOX,
        IFSC::BHUX,
        IFSC::BJUX,
        IFSC::BKCX,
        IFSC::BKDX,
        IFSC::BKID,
        IFSC::BMCB,
        IFSC::BMPX,
        IFSC::BNBX,
        IFSC::BNCX,
        IFSC::BNSX,
        IFSC::BORX,
        IFSC::BRDX,
        IFSC::BRMX,
        IFSC::BSBX,
        IFSC::BURX,
        IFSC::BUZX,
        IFSC::CALX,
        IFSC::CBHX,
        IFSC::CBIN,
        IFSC::CCCX,
        IFSC::CGBX,
        IFSC::CHBX,
        IFSC::CHTX,
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CJAX,
        IFSC::CLBL,
        IFSC::CMCB,
        IFSC::CMDX,
        IFSC::CMLX,
        IFSC::CNRB,
        IFSC::CNSX,
        IFSC::COCX,
        IFSC::COMX,
        IFSC::COSB,
        IFSC::CSBK,
        IFSC::CSBX,
        IFSC::CTBX,
        IFSC::CZCX,
        IFSC::CZUX,
        IFSC::DAHX,
        IFSC::DBAX,
        IFSC::DBSS,
        IFSC::DCBL,
        IFSC::DCBX,
        IFSC::DDBX,
        IFSC::DENS,
        IFSC::DEUT,
        IFSC::DHUX,
        IFSC::DLXB,
        IFSC::DNSB,
        IFSC::DOBX,
        IFSC::DSCB,
        IFSC::DSPX,
        IFSC::DSUX,
        IFSC::DTCX,
        IFSC::EDBX,
        IFSC::ESAF,
        IFSC::ESFB,
        IFSC::EUCX,
        IFSC::FDRL,
        IFSC::FINF,
        IFSC::FINO,
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
        IFSC::GUNX,
        IFSC::HAMX,
        IFSC::HDFC,
        IFSC::HGBX,
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
        IFSC::JKSX,
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
        IFSC::KALX,
        IFSC::KAMX,
        IFSC::KARB,
        IFSC::KBCX,
        IFSC::KCCB,
        IFSC::KCUB,
        IFSC::KCUX,
        IFSC::KDIX,
        IFSC::KHDX,
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
        IFSC::KRTX,
        IFSC::KSCB,
        IFSC::KSUX,
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
        IFSC::MCSX,
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
        IFSC::MUPX,
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
        IFSC::NSPB,
        IFSC::NTBL,
        IFSC::NVSX,
        IFSC::ODGB,
        IFSC::OIBA,
        IFSC::OMCX,
        IFSC::ONSX,
        IFSC::OSMX,
        IFSC::PALX,
        IFSC::PASX,
        IFSC::PBGX,
        IFSC::PCBL,
        IFSC::PCSX,
        IFSC::PCTX,
        IFSC::PCUX,
        IFSC::PDSX,
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
        IFSC::PTNX,
        IFSC::PTSX,
        IFSC::PUBX,
        NETBANKING::PUNB_R,
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
        IFSC::RGSX,
        IFSC::RRSX,
        IFSC::RSSX,
        IFSC::RZSX,
        IFSC::SACX,
        IFSC::SADX,
        IFSC::SAHX,
        IFSC::SASA,
        IFSC::SATX,
        IFSC::SAVX,
        IFSC::SBIN,
        IFSC::SBMX,
        IFSC::SBNX,
        IFSC::SBPX,
        IFSC::SBUJ,
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
        IFSC::SHRX,
        IFSC::SHSX,
        IFSC::SIBL,
        IFSC::SIRX,
        IFSC::SISX,
        IFSC::SKCX,
        IFSC::SKNX,
        IFSC::SKUX,
        IFSC::SMNX,
        IFSC::SMUX,
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
        IFSC::SRCX,
        IFSC::SSBX,
        IFSC::SSKX,
        IFSC::SSLX,
        IFSC::STCB,
        IFSC::SULX,
        IFSC::SUMX,
        IFSC::SUNB,
        IFSC::SURY,
        IFSC::SUTB,
        IFSC::SUVX,
        IFSC::SVCB,
        IFSC::SVCX,
        IFSC::SVSX,
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
        IFSC::TGUX,
        IFSC::THOX,
        IFSC::TIRX,
        IFSC::TJNX,
        IFSC::TJSB,
        IFSC::TLPX,
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
        IFSC::UMAX,
        IFSC::UMCX,
        IFSC::UNIX,
        IFSC::UNSX,
        IFSC::UPCX,
        IFSC::UROX,
        IFSC::UJVN,
        IFSC::USFB,
        IFSC::UTIB,
        IFSC::UTKS,
        IFSC::VAIX,
        IFSC::VARA,
        IFSC::VCBX,
        IFSC::VCCX,
        IFSC::VCNB,
        IFSC::VDYX,
        IFSC::VERX,
        IFSC::VICX,
        IFSC::VIKX,
        IFSC::VJSX,
        IFSC::VSBX,
        IFSC::VSCX,
        IFSC::VUCX,
        IFSC::VVCX,
        IFSC::WAIX,
        IFSC::WKGX,
        IFSC::WRCX,
        IFSC::XJKG,
        IFSC::YADX,
        IFSC::YAVX,
        IFSC::YESB,
        IFSC::YLNX,
        IFSC::ZCBL,
        IFSC::ZSBL,
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
        Gateway::UPI_RZPAPB,
        Gateway::UPI_RZPAXIS,
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
        Gateway::EASEBUZZ_OPTIMIZER
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
        Gateway::CHECKOUT_DOT_COM_OPTIMIZER,
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
        Gateway::PHONEPE,
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
        IFSC::IDFB,
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
        IFSC::IDFB,
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
            PayLater::HDFC => '100000',
            PayLater::RZPXPOSTPAID => '100000',
        ],
        Payment\Method::CARDLESS_EMI => [
            CardlessEmi::WALNUT369 => '90000',
            CardlessEmi::HCIN => '50000',
            CardlessEmi::LIQUILOANS=> '90000',
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
        Gateway::CHECKOUT_DOT_COM_OPTIMIZER => Gateway::CHECKOUT_DOT_COM_OPTIMIZER,
        Gateway::ICICI            => Gateway::ICICI,
        Gateway::OPTIMIZER_RAZORPAY => Gateway::OPTIMIZER_RAZORPAY,
        Gateway::EASEBUZZ_OPTIMIZER => Gateway::EASEBUZZ_OPTIMIZER
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
        Gateway::UPI_RZPAPB,
        Gateway::UPI_RZPAXIS,
        Gateway::ATOM,
        Gateway::CASHFREE,
        Gateway::PHONEPE,
        Gateway::PAYTM,
        Gateway::PAYU,
        Gateway::BILLDESK_OPTIMIZER,
        Gateway::CCAVENUE,
        Gateway::OPTIMIZER_RAZORPAY,
        Gateway::EASEBUZZ_OPTIMIZER
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
        CardlessEmi::LIQUILOANS,
        PayLater::RZPXPOSTPAID,
        CardlessEmi::INSTANT_EMI,
    ];

    public static $checkAccountSkipProvider = [
        CardlessEmi::WALNUT369,
        CardlessEmi::SEZZLE,
        CardlessEmi::LIQUILOANS,
        PayLater::AMAZONPAY,
        CardlessEmi::INSTANT_EMI,
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

    public static $upiEditTerminalBulkGateways = [
        self::UPI_ICICI,
        self::UPI_AXIS,
        self::UPI_YESBANK,
        self::GOOGLE_PAY,
        self::UPI_RZPAPB,
        self::UPI_RZPAXIS
    ];

    const CURRENCIES_SUPPORTED_BY_INTL_BANK_TRANSFER_BY_MODE = [
        IntlBankTransfer::SWIFT => [
            Currency::USD, Currency::AUD, Currency::CAD, Currency::HRK, Currency::DKK,
            Currency::CZK, Currency::EUR, Currency::HKD, Currency::HUF, Currency::ILS,
            Currency::KES, Currency::MXN, Currency::NZD, Currency::NOK, Currency::QAR,
            Currency::RUB, Currency::SAR, Currency::SGD, Currency::ZAR, Currency::SEK,
            Currency::CHF, Currency::THB, Currency::GBP, Currency::AED
        ],
        IntlBankTransfer::ACH => [Currency::USD],
        IntlBankTransfer::FPS => [Currency::GBP],
        IntlBankTransfer::SEPA => [Currency::EUR]
    ];

    const CURRENCY_TO_MODE_MAPPING_FOR_INTL_BANK_TRANSFER = [
        Currency::USD              => IntlBankTransfer::ACH,
        self::SWIFT                => IntlBankTransfer::SWIFT,
        Currency::GBP              => IntlBankTransfer::FPS,
        Currency::EUR              => IntlBankTransfer::SEPA
    ];

    const MODE_TO_VA_CURRENCY_ACCOUNT_MAPPING_FOR_INTL_BANK_TRANSFER = [
        IntlBankTransfer::SWIFT => self::SWIFT,
        IntlBankTransfer::ACH => Currency::USD,
        IntlBankTransfer::SEPA => Currency::EUR,
        IntlBankTransfer::FPS => Currency::GBP
    ];

    const OPGSP_SETTLEMENT_GATEWAYS = [
        self::EMERCHANTPAY,
        self::CURRENCY_CLOUD,
        self::CHECKOUT_DOT_COM,
        self::PING_PONG,
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

    public static function isPaylaterSkipCheckAccountProvider($provider)
    {
        return (in_array($provider, Payment\Gateway::$checkAccountSkipProvider, true) === true);
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

    public static function isSupportedEmandateBank($bank, $merchantId): bool
    {
        $app = App::getFacadeRoot();
        $experimentId = $app['config']->get('app.bank_data_via_npci_api_experiment');
        if(self::getSplitzResponse($merchantId, $experimentId) === 'enable'){
            $bankData = self::fetchBankDataFromApi();
            $banks = array_keys($bankData);
        }else{
            $banks = self::getAllEMandateBanks();
        }

        return (in_array($bank, $banks, true) === true);
    }

    public static function getSplitzResponse(string $id, string $experimentId)
    {
        $app = App::getFacadeRoot();
        $properties = [
            'id'            => $id,
            'experiment_id' => $experimentId,
        ];

        $response = $app['splitzService']->evaluateRequest($properties);

        $app['trace']->info(TraceCode::SPLITZ_RESPONSE, [
            'properties' => $properties,
            'response' => $response,
        ]);

        return $response['response']['variant']['name'] ?? '';
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

    public static function fetchBankDataFromApi() {
        $app = App::getFacadeRoot();
        // Check if data exists in the cache
        $cacheKey = 'npci_bank_details';
        $cachedData = $app['cache']->get($cacheKey);

        // If data is not in the cache, call the external API
        if ($cachedData === null) {
            $data = (new \RZP\Services\EmandateService)->fetchNpciData();
            $data = json_decode($data, true);
            $data = $data['bank_data'] ?? [];

            // Store the data in the cache
            $app['cache']->put($cacheKey, $data, 14400); // Cache for 4 hour
        } else {
            $data = $cachedData ?? [];
        }

        return $data;
    }

    public static function getFilteredEmandateBanks(array $authTypes): array
    {
        $data = self::fetchBankDataFromApi();

        foreach ($data as $bankCode => $bank) {
            // Check if the bank supports any of the specified auth types
            if (array_intersect($authTypes, $bank[self::AUTH_TYPES])) {
                $recurringData[self::EMANDATE][$bankCode] = $bank;
            }
        }
        return $recurringData[self::EMANDATE];

    }

    public static function getAvailableEmandateBanksForAuthType(string $authType): array
    {
        $banks = [];

        $app = App::getFacadeRoot();
        $experimentId = $app['config']->get('app.bank_data_via_npci_api_experiment');
        if (self::getSplitzResponse("enable_npci_banks", $experimentId) === 'enable') {
            $bankData = self::fetchBankDataFromApi();

            if ($authType === Payment\AuthType::AADHAAR) {
                foreach ($bankData as $bankCode => $bank) {
                    if (!in_array(Payment\AuthType::AADHAAR, $bank["auth_types"])) {
                        unset($bankData[$bankCode]);
                    }
                }
            }

            if ($authType === "netbanking") {
                foreach ($bankData as $bankCode => $bank) {
                    if (!in_array("netbanking", $bank["auth_types"])) {
                        unset($bankData[$bankCode]);
                    }
                }
            }

            $banks = array_keys($bankData);
        }else {
            $emandateBanks = self::getEmandateAuthTypeToBankMap();

            if (isset($emandateBanks[$authType]) === true) {
                $banks = $emandateBanks[$authType];
            }

            if ($authType === Payment\AuthType::AADHAAR) {
                $banks = Payment\Gateway::removeAadhaarEmandateRegistrationDisabledBanks($banks);
            }
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
            self::LYRA,
            self::CHECKOUT_DOT_COM,
            self::INGENICO,
            self::BILLDESK_OPTIMIZER,
            self::CHECKOUT_DOT_COM_OPTIMIZER,
            self::KOTAK_DEBIT_EMI,
            self::INDUSIND_DEBIT_EMI,
            self::AXIS_TOKENHQ,
            self::ICICI,
            self::OPTIMIZER_RAZORPAY,
            self::EASEBUZZ_OPTIMIZER
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

    public static function isUpiRecurringValidateVPASupportedGateway($gateway): bool
    {
        return in_array($gateway, self::$upiRecurringValidateVPASupportedGateway, true);
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
            self::CHECKOUT_DOT_COM_OPTIMIZER,
            self::KOTAK_DEBIT_EMI,
            self::INDUSIND_DEBIT_EMI,
            self::AXIS_TOKENHQ,
            self::ICICI,
            self::OPTIMIZER_RAZORPAY,
            self::EASEBUZZ_OPTIMIZER
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
            self::CHECKOUT_DOT_COM_OPTIMIZER,
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
            self::TNGD
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
                CardlessEmi::LIQUILOANS,
                CardlessEmi::INSTANT_EMI,
            ],
            self::PAYLATER     => [
                Paylater::LAZYPAY,
                PayLater::AMAZONPAY,
                Paylater::RZPXPOSTPAID,
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
                self::NETBANKING_EQUITAS,
                self::NETBANKING_AIRTEL,
                self::NETBANKING_JSB,
                self::NETBANKING_KOTAK,
                self::PAYTM,
            ],
            Method::WALLET => [
                self::WALLET_AMAZONPAY,
                self::WALLET_BAJAJ,
                self::WALLET_PAYZAPP,
                self::WALLET_PAYPAL,
                self::TNGD,
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
            self::WALLET_AIRTELMONEY,
            self::MOBIKWIK,
            self::TNGD,
        ];

        $acquirerGateways = [
            self::CARDLESS_EMI => [
                CardlessEmi::WALNUT369,
                CardlessEmi::SEZZLE,
                CardlessEmi::ZESTMONEY,
                CardlessEmi::LIQUILOANS,
                CardlessEmi::INSTANT_EMI,
            ],
            self::PAYLATER     => [
                Paylater::LAZYPAY,
                PayLater::AMAZONPAY,
                PayLater::RZPXPOSTPAID,
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
        if ($payment[Payment\Entity::GATEWAY] === WALLET::PAYTM &&
            $payment[Payment\Entity::METHOD] === Payment\Method::WALLET &&
            $payment[Payment\Entity::CPS_ROUTE] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return true;
        }

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

    public static function shouldSkipDebit($payment, $isOptimizerLinkAndPayWallet=false)
    {
        if($isOptimizerLinkAndPayWallet === true)
        {
            return true;
        }

        $gateways = [
            Gateway::WALLET_BAJAJ
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
            self::UPI_RZPAPB,
            self::UPI_RZPAXIS,
            self::ATOM,
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
            self::UPI_RZPAPB,
            self::UPI_RZPAXIS,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function isOnlyUpiPaymentServiceGateway($gateway): bool
    {
        $gateways = [
            self::UPI_RZPAXIS,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function isUpiPaymentServiceFullyRamped($gateway): bool
    {
        $gateways = [
            self::UPI_KOTAK,
            self::UPI_AXISOLIVE,
            self::UPI_RZPRBL,
            self::UPI_RZPAPB,
            self::UPI_RZPAXIS,
            self::UPI_YESBANK,
            self::UPI_ICICI,
            self::UPI_AXIS,
            self::ATOM,
            self::UPI_SBI,
            self::UPI_AIRTEL,
            self::UPI_MINDGATE,
            self::UPI_JUSPAY,
        ];

        return (in_array($gateway, $gateways, true));
    }

    public static function isUnexpectedPaymentOnCallbackDisabled($gateway): bool
    {
        $gateways = [
            self::UPI_KOTAK,
            self::UPI_RZPRBL,
            self::UPI_RZPAPB,
            self::UPI_RZPAXIS,
            self::ATOM,
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


    public static function isHdfcPosGateway($gateway) : bool
    {
        return $gateway == self::HDFC_POS;
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
