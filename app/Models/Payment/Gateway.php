<?php

namespace RZP\Models\Payment;

use App;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\Settlement;
use RZP\Models\Card\Network;
use RZP\Models\Terminal\TpvType;
use Razorpay\IFSC\IFSC as BaseIFSC;
use RZP\Models\Terminal\BankingType;
use RZP\Models\Payment\Processor\Upi;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\CardlessEmi;

class Gateway
{
    const AMEX                   = 'amex';
    const ATOM                   = 'atom';
    const ATOS                   = 'atos';
    const BHARAT_QR              = 'bharat_qr';
    const AXIS_GENIUS            = 'axis_genius';
    const AXIS_MIGS              = 'axis_migs';
    const BILLDESK               = 'billdesk';
    const MPI_BLADE              = 'mpi_blade';
    const MPI_ENSTAGE            = 'mpi_enstage';
    const CYBERSOURCE            = 'cybersource';
    const EBS                    = 'ebs';
    const ICICI                  = 'icici';
    const KOTAK                  = 'kotak';
    const RBL                    = 'rbl';
    const AXIS                   = 'axis';
    const ESIGNER_DIGIO          = 'esigner_digio';
    const ESIGNER_LEGALDESK      = 'esigner_legaldesk';
    const ENACH_RBL              = 'enach_rbl';
    const ENACH_NPCI_NETBANKING  = 'enach_npci_netbanking';
    const FIRST_DATA             = 'first_data';
    const HDFC                   = 'hdfc';
    const HITACHI                = 'hitachi';
    const MOBIKWIK               = 'mobikwik';
    const NETBANKING_SIB         = 'netbanking_sib';
    const NETBANKING_CBI         = 'netbanking_cbi';
    const NETBANKING_AIRTEL      = 'netbanking_airtel';
    const NETBANKING_AXIS        = 'netbanking_axis';
    const NETBANKING_IDFC        = 'netbanking_idfc';
    const NETBANKING_FEDERAL     = 'netbanking_federal';
    const NETBANKING_EQUITAS     = 'netbanking_equitas';
    const NETBANKING_BOB         = 'netbanking_bob';
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
    const PAYTM                  = 'paytm';
    const SHARP                  = 'sharp';
    const UPI_MINDGATE           = 'upi_mindgate';
    const UPI_SBI                = 'upi_sbi';
    const UPI_AXIS               = 'upi_axis';
    const UPI_ICICI              = 'upi_icici';
    const UPI_HULK               = 'upi_hulk';
    const UPI_RBL                = 'upi_rbl';
    const UPI_YESBANK            = 'upi_yesbank';
    const AEPS_ICICI             = 'aeps_icici';
    const ISG                    = 'isg';
    const PAYSECURE              = 'paysecure';
    const UPI_AIRTEL             = 'upi_airtel';
    const WORLDLINE              = 'worldline';
    const UPI_CITI               = 'upi_citi';

    const CARD_FSS               = 'card_fss';

    const WALLET_AIRTELMONEY = 'wallet_airtelmoney';
    const WALLET_AMAZONPAY   = 'wallet_amazonpay';
    const WALLET_FREECHARGE  = 'wallet_freecharge';
    const WALLET_JIOMONEY    = 'wallet_jiomoney';
    const WALLET_SBIBUDDY    = 'wallet_sbibuddy';
    const WALLET_MPESA       = 'wallet_mpesa';
    const WALLET_OLAMONEY    = 'wallet_olamoney';
    const WALLET_OPENWALLET  = 'wallet_openwallet';
    const WALLET_PAYUMONEY   = 'wallet_payumoney';
    const WALLET_PAYZAPP     = 'wallet_payzapp';
    const WALLET_PHONEPE     = 'wallet_phonepe';
    const WALLET_PAYPAL      = 'wallet_paypal';

    const CARDLESS_EMI       = 'cardless_emi';
    const PAYLATER           = 'paylater';

    const ACQUIRER_HDFC         = 'hdfc';
    const ACQUIRER_ICIC         = 'icic';
    const ACQUIRER_AXIS         = 'axis';
    const ACQUIRER_AMEX         = 'amex';
    const ACQUIRER_FSS          = 'fss';
    const ACQUIRER_RATN         = 'ratn';
    const ACQUIRER_YESB         = 'yesb';
    const ACQUIRER_BARB         = 'barb';
    const ACQUIRER_SBIN         = 'sbin';

    const NOT_SUPPORTED      = 'not_supported';
    const SUPPORTED          = 'supported';
    const NODAL_YESBANK      = 'nodal_yesbank';
    const NODAL_ICICI        = 'nodal_icici';

    const BT_YESBANK         = 'bt_yesbank';
    const BT_KOTAK           = 'bt_kotak';
    const BT_DASHBOARD       = 'bt_dashboard';

    // this is a dummy gateway. this is required to save MIDs & TIDs of a merchant.
    const EMI_SBI            = 'emi_sbi';
    const BAJAJFINSERV       = 'bajajfinserv';
    const GOOGLE_PAY         = 'google_pay';


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
    const DEFAULT_ESIGNER_GATEWAY = self::ESIGNER_DIGIO;

    const BAJAJ = 'bajajfinserv';

    const GATEWAY_ACQUIRERS = [
        self::AXIS_MIGS    => [self::ACQUIRER_AXIS, self::ACQUIRER_HDFC],
        self::HDFC         => [self::ACQUIRER_HDFC],
        self::CYBERSOURCE  => [self::ACQUIRER_AXIS, self::ACQUIRER_HDFC],
        self::FIRST_DATA   => [self::ACQUIRER_ICIC],
        self::AMEX         => [self::ACQUIRER_AMEX],
        self::AEPS_ICICI   => [self::ACQUIRER_ICIC],
        self::CARD_FSS     => [self::ACQUIRER_FSS, self::ACQUIRER_BARB, self::ACQUIRER_SBIN],
        self::HITACHI      => [self::ACQUIRER_RATN],
        self::ENACH_RBL    => [self::ACQUIRER_RATN],
        self::UPI_HULK     => [self::ACQUIRER_HDFC],
        self::CARDLESS_EMI => [CardlessEmi::ZESTMONEY, CardlessEmi::EARLYSALARY, CardlessEmi::FLEXMONEY],
        self::PAYLATER     => [PayLater::EPAYLATER],
    ];

    const POWER_WALLETS = [
        Wallet::MOBIKWIK,
        Wallet::PAYUMONEY,
        // Wallet::OLAMONEY,
        Wallet::FREECHARGE,
        // Wallet::MPESA,
    ];

    // Temporarily uses a different constant. Ideally POWER_WALLETS should be
    // used. Once auto debit functionality is implemented for all power wallets
    // TODO Deprecate it.
    const AUTO_DEBIT_GATEWAYS = array(
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
        self::SHARP,
    ];

    const REFUND_TIMEOUT_HANDLED_GATEWAYS = [
        self::WALLET_FREECHARGE,
        self::BILLDESK,
    ];

    // TODO: Add gateway and gateway_acquirer map to fix
    // this for other card gateways
    const DIRECT_SETTLEMENT_GATEWAYS = [
        self::AMEX              => self::AMEX,
        self::HDFC              => self::HDFC,
        self::ISG               => self::HDFC,
        self::BILLDESK          => self::BILLDESK,
        self::NETBANKING_AXIS   => self::AXIS,
        self::NETBANKING_HDFC   => self::HDFC,
        self::NETBANKING_ICICI  => self::ICICI,
        self::NETBANKING_KOTAK  => self::KOTAK,
        self::NETBANKING_RBL    => self::RBL,
        self::PAYTM             => self::PAYTM,
        self::UPI_AXIS          => self::AXIS,
        self::UPI_MINDGATE      => self::HDFC,
        self::WALLET_PAYPAL     => self::WALLET_PAYPAL,
    ];

    /**
    * Gateways for which we can validate the refunds
    * if they are successful after they are 'initiated'
    */
    const UNKNOWN_REFUNDS_VALIDATION_GATEWAYS = [
        self::WALLET_FREECHARGE
    ];

    const MCC_FILTER_GATEWAYS = [
        self::HDFC,
        self::HITACHI,
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
        self::CARDLESS_EMI,
        self::NETBANKING_CORPORATION,
        self::HITACHI,

        // UPI HULK is TEMPORARY, As payment are still failed on hulk and we can't do much there,
        //If you are seeing this after Sep'18, Please report to gateway payments team
        self::UPI_HULK,
        self::UPI_ICICI,
        self::UPI_MINDGATE,
        self::UPI_AXIS,
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
        Payment\Gateway::WALLET_PAYZAPP,
        Payment\Gateway::WALLET_MPESA,
        Payment\Gateway::CARD_FSS,
        Payment\Gateway::WALLET_PAYUMONEY,
        Payment\Gateway::WALLET_FREECHARGE,
        Payment\Gateway::WALLET_AMAZONPAY,
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
    ];

    // Bank such as Netbanking Canara enforces to send fee in request.
    const FEE_IN_AUTHORIZE_GATEWAYS = [
      Payment\Gateway::NETBANKING_CANARA
    ];

    // Please keep this list sorted. The list of Live Banks in API E-Mandate is available at https://www.npci.org.in/nach-e-mandates-new
    const ENACH_NPCI_NETBANKING_BANKS = [
        IFSC::CBIN,
        IFSC::CIUB,
        IFSC::DEUT,
        IFSC::ESFB,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::IBKL,
        IFSC::ICIC,
        IFSC::IDFB,
        IFSC::INDB,
        IFSC::IOBA,
        IFSC::KKBK,
        IFSC::MAHB,
        IFSC::PYTM,
        IFSC::RATN,
        IFSC::SIBL,
        IFSC::TMBL,
        IFSC::USFB,
        IFSC::UTIB,
        IFSC::YESB,
        Netbanking::PUNB_R,
        Netbanking::BARB_R,
    ];

    const EMANDATE_NB_DIRECT_BANKS = [
        IFSC::ICIC,
        IFSC::UTIB,
        IFSC::HDFC,
        IFSC::SBIN
    ];

    // The 2 commented banks are mentioned at the bottom
    // with their retail versions
    // Please keep this list sorted
    // You can find the latest PDF version
    // at https://www.npci.org.in/nach-e-mandates
    const EMANDATE_AADHAAR_BANKS = [
        IFSC::ABHY,
        IFSC::ACUX,
        IFSC::ADCC,
        IFSC::AGCX,
        IFSC::AJSX,
        IFSC::AMAX,
        IFSC::AMRX,
        IFSC::ANDB,
        IFSC::APBL,
        IFSC::APGB,
        IFSC::AUCX,
        IFSC::BACB,
        IFSC::BACX,
        // IFSC::BARB,
        IFSC::BCBM,
        IFSC::BGBX,
        IFSC::BHSX,
        IFSC::BHUX,
        IFSC::BKDN,
        IFSC::BKID,
        IFSC::BNPA,
        IFSC::BORX,
        IFSC::BURX,
        IFSC::CBIN,
        IFSC::CHAS,
        IFSC::CHAX,
        IFSC::CHDX,
        IFSC::CHSX,
        IFSC::CITI,
        IFSC::CIUB,
        IFSC::CMCX,
        IFSC::CNRB,
        IFSC::CORP,
        IFSC::COSB,
        IFSC::CSBK,
        IFSC::CSBX,
        IFSC::CURX,
        IFSC::CZCX,
        IFSC::DBSS,
        IFSC::DCBL,
        IFSC::DCDX,
        IFSC::DCKX,
        IFSC::DDBX,
        IFSC::DEUT,
        IFSC::DGBX,
        IFSC::DICX,
        IFSC::DSPX,
        IFSC::ESFB,
        IFSC::EUCX,
        IFSC::FDRL,
        IFSC::FGCB,
        IFSC::FSCX,
        IFSC::GCBX,
        IFSC::GCUX,
        IFSC::GDCX,
        IFSC::GSCB,
        IFSC::GSSX,
        IFSC::HDFC,
        IFSC::HSBC,
        IFSC::IBKL,
        IFSC::ICIC,
        IFSC::IDFB,
        IFSC::INDB,
        IFSC::ITDX,
        IFSC::IUCB,
        IFSC::JASB,
        IFSC::JHAX,
        IFSC::JONX,
        IFSC::JSBL,
        IFSC::JSBP,
        IFSC::JSCX,
        IFSC::JUCX,
        IFSC::KAAX,
        IFSC::KAIJ,
        IFSC::KALX,
        IFSC::KARB,
        IFSC::KARX,
        IFSC::KASX,
        IFSC::KBCX,
        IFSC::KCOB,
        IFSC::KCUB,
        IFSC::KDCX,
        IFSC::KDIX,
        IFSC::KHAX,
        IFSC::KKBK,
        IFSC::KNPX,
        IFSC::KOCX,
        IFSC::KRDX,
        IFSC::KSCB,
        IFSC::KTBX,
        IFSC::KUNS,
        IFSC::KVBL,
        IFSC::KVGB,
        IFSC::LBMX,
        IFSC::LCCX,
        IFSC::LKMX,
        IFSC::MAHB,
        IFSC::MBCX,
        IFSC::MERX,
        IFSC::MHSX,
        IFSC::MOGX,
        IFSC::MPRX,
        IFSC::MSAX,
        IFSC::MSNU,
        IFSC::MSOX,
        IFSC::NAIX,
        IFSC::NALX,
        // This is not in the IFSC package yet
        // Cleanup post the 1.2.4 release
        'NBMX',
        IFSC::NCBX,
        IFSC::NCCX,
        IFSC::NDCX,
        IFSC::NICB,
        IFSC::NOBX,
        IFSC::NOIX,
        IFSC::NSBX,
        IFSC::NSGX,
        IFSC::NVSX,
        IFSC::ORBC,
        IFSC::OSMX,
        IFSC::PABX,
        IFSC::PALX,
        IFSC::PATX,
        IFSC::PCUX,
        IFSC::PJSB,
        IFSC::PLUX,
        IFSC::PMCB,
        IFSC::PRTH,
        IFSC::PSRX,
        IFSC::PUGX,
        // IFSC::PUNB,
        IFSC::RAMX,
        IFSC::RATN,
        IFSC::RCUX,
        IFSC::REBX,
        IFSC::RGCX,
        IFSC::RNSX,
        IFSC::SAGX,
        IFSC::SCBL,
        IFSC::SCCX,
        IFSC::SDBX,
        IFSC::SDCB,
        IFSC::SDSX,
        IFSC::SHUX,
        IFSC::SIBL,
        IFSC::SJSX,
        IFSC::SRCB,
        IFSC::SSDX,
        IFSC::SSLX,
        IFSC::STRX,
        IFSC::SUTB,
        IFSC::SVCB,
        IFSC::SVNX,
        IFSC::SWMX,
        IFSC::SYNB,
        IFSC::TACX,
        IFSC::TADX,
        IFSC::TBCX,
        IFSC::TCUB,
        IFSC::TDIX,
        IFSC::TDMX,
        IFSC::TECX,
        IFSC::TEHX,
        IFSC::TGMB,
        IFSC::TJSB,
        IFSC::TKUX,
        IFSC::TMBL,
        IFSC::TPDX,
        IFSC::TSAB,
        IFSC::TSDX,
        IFSC::TSIX,
        IFSC::TUMX,
        IFSC::TUOX,
        IFSC::TVDX,
        IFSC::UBIN,
        IFSC::UCBA,
        IFSC::UCBS,
        IFSC::UCBX,
        IFSC::UCUX,
        IFSC::UKGX,
        IFSC::UMSX,
        IFSC::USFB,
        IFSC::UTIB,
        IFSC::UTZX,
        IFSC::UUCX,
        IFSC::VARA,
        IFSC::VCCX,
        IFSC::VEDX,
        IFSC::VIJX,
        IFSC::VJSX,
        IFSC::VUCX,
        IFSC::XJKG,
        IFSC::YESB,
        IFSC::ZSGX,
        IFSC::ZSHX,
        Netbanking::BARB_R,
        Netbanking::PUNB_R,
    ];

    // Esigner Digio is added here just for test cases
    const EMANDATE_AADHAAR_GATEWAYS = [
        Gateway::ESIGNER_DIGIO,
        Gateway::ESIGNER_LEGALDESK,
        Gateway::ENACH_RBL,
    ];

    /**
     * Need to ensure that only those gateways which have
     * verify implemented, are added in this array.
     * This is only until the flow is complete from Scrooge.
     * In the starting, we will only implement for APIs.
     *
     * TODO: reversal, emandate, bank transfer, netbanking etc type of gateways are not supported yet.
     *
     * @var array
     */
    public static $scroogeGateways = [
        Payment\Gateway::AMEX,
        Payment\Gateway::SHARP,
        Payment\Gateway::HDFC,
        Payment\Gateway::AXIS_MIGS,
        Payment\Gateway::FIRST_DATA,
        Payment\Gateway::CARD_FSS,
        Payment\Gateway::CYBERSOURCE,
        Payment\Gateway::UPI_ICICI,
        Payment\Gateway::UPI_MINDGATE,
        Payment\Gateway::HITACHI,
        Payment\Gateway::WALLET_OLAMONEY,
        Payment\Gateway::WALLET_JIOMONEY,
        Payment\Gateway::UPI_AXIS,
        Payment\Gateway::WALLET_PHONEPE,
        Payment\Gateway::ATOM,
        Payment\Gateway::UPI_AIRTEL,
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
        ],

        Method::NETBANKING => [
            self::PAYTM,
            self::BILLDESK,
            self::EBS,
            self::ATOM,
            self::NETBANKING_SIB,
            self::NETBANKING_CBI,
            self::NETBANKING_IDFC,
            self::NETBANKING_ICICI,
            self::NETBANKING_CUB,
            self::NETBANKING_IBK,
            self::NETBANKING_IDBI,
            self::NETBANKING_BOB,
            self::NETBANKING_HDFC,
            self::NETBANKING_CORPORATION,
            self::NETBANKING_KOTAK,
            self::NETBANKING_AIRTEL,
            self::NETBANKING_AXIS,
            self::NETBANKING_FEDERAL,
            self::NETBANKING_RBL,
            self::NETBANKING_INDUSIND,
            self::NETBANKING_PNB,
            self::NETBANKING_OBC,
            self::NETBANKING_CSB,
            self::NETBANKING_ALLAHABAD,
            self::NETBANKING_EQUITAS,
            self::NETBANKING_SBI,
            self::NETBANKING_CANARA,
            self::NETBANKING_VIJAYA,
            self::NETBANKING_YESB,
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
            self::WALLET_JIOMONEY,
            self::WALLET_SBIBUDDY,
            self::WALLET_OPENWALLET,
            self::WALLET_MPESA,
            self::WALLET_AMAZONPAY,
            self::WALLET_PHONEPE,
            self::WALLET_PAYPAL,
        ],

        Method::EMI => [
            self::HITACHI,
            self::AMEX,
            self::HDFC,
            self::FIRST_DATA,
        ],

        Method::UPI => [
            self::UPI_MINDGATE,
            self::UPI_ICICI,
            self::UPI_AXIS,
            self::UPI_SBI,
            self::UPI_HULK,
            self::UPI_YESBANK,
            self::UPI_AIRTEL,
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
        self::CYBERSOURCE           => [],
        self::PAYSECURE             => [],
        self::FIRST_DATA            => [
            self::NOT_SUPPORTED => [Network::MAES, Network::RUPAY]
        ],
        self::WALLET_OPENWALLET     => [],
        self::HITACHI               => [],
    ];

    /**
     * Card gateways which support purchase mechanism for at
     * least one card network.
     *
     * @var array
     */
    public static $gatewayNetworkPurchaseSupport = [
        self::HITACHI               => [
            self::NOT_SUPPORTED     => [Network::RUPAY]
        ],
    ];

    public static $bankTransferProviderGateway = [
        Provider::YESBANK   => self::BT_YESBANK,
        Provider::KOTAK     => self::BT_KOTAK,
        Provider::DASHBOARD => self::BT_DASHBOARD,
    ];

    //
    // Temporary, since bank transfers will be refactored to use terminals too
    // TODO: Remove when above refactor is done
    protected static $nonTerminalGateways = [
        self::BT_YESBANK,
        self::BT_KOTAK,
        self::BT_DASHBOARD,
    ];

    /**
     * Card gateways which support full auth reversal
     *
     * @var array
     */
    public static $reverse = [
        self::CYBERSOURCE,
        self::AXIS_MIGS,
        self::AMEX,
        self::WALLET_OPENWALLET,
        self::HITACHI,
        self::CARDLESS_EMI,
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
        self::UPI_RBL,
        self::UPI_YESBANK,
        self::UPI_AIRTEL,
        self::UPI_CITI,
    ];

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
            Network::DICL
        ],
        self::FIRST_DATA => [
            Network::MC,
            Network::VISA,
            Network::MAES,
        ],
    ];

    /**
     * Each card gateway only support specific card networks.
     * This maintains a map of gateway to card network which
     * is used in gateway and terminal selection logic
     *
     * @var array
     */
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
            Network::VISA
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
    ];

    public static $bharatQrCardNetwork = [
        // IMP: Order of networks matter!
        self::HITACHI => [
            Network::VISA,
            Network::MC,
            Network::RUPAY,
        ],
        self::ISG => [
            Network::VISA,
            Network::MC,
            Network::RUPAY,
        ],
        self::WORLDLINE => [
            Network::VISA,
            Network::MC,
            Network::RUPAY,
        ],
    ];

    public static $cardNetworkRecurringMap = [
        self::HITACHI => [
            Network::VISA,
            Network::MC,
        ],
    ];

    public static $walletToGatewayMap = [
        Wallet::OLAMONEY    => Gateway::WALLET_OLAMONEY,
        Wallet::PAYTM       => Gateway::PAYTM,
        Wallet::MOBIKWIK    => Gateway::MOBIKWIK,
        Wallet::PAYZAPP     => Gateway::WALLET_PAYZAPP,
        Wallet::PAYUMONEY   => Gateway::WALLET_PAYUMONEY,
        Wallet::AIRTELMONEY => Gateway::WALLET_AIRTELMONEY,
        Wallet::FREECHARGE  => Gateway::WALLET_FREECHARGE,
        Wallet::JIOMONEY    => Gateway::WALLET_JIOMONEY,
        Wallet::SBIBUDDY    => Gateway::WALLET_SBIBUDDY,
        Wallet::OPENWALLET  => Gateway::WALLET_OPENWALLET,
        Wallet::MPESA       => Gateway::WALLET_MPESA,
        Wallet::AMAZONPAY   => Gateway::WALLET_AMAZONPAY,
        Wallet::PHONEPE     => Gateway::WALLET_PHONEPE,
        Wallet::PAYPAL      => Gateway::WALLET_PAYPAL,
    ];

    public static $upiToGatewayMap = [
        Upi::HDFC  => Gateway::UPI_MINDGATE,
        Upi::ICIC  => Gateway::UPI_ICICI,
        Upi::SBIN  => Gateway::UPI_SBI,
        Upi::UTIB  => Gateway::UPI_AXIS,
    ];

    public static $acquirerToCodeMap = [
        self::ACQUIRER_HDFC => IFSC::HDFC,
        self::ACQUIRER_ICIC => IFSC::ICIC,
        self::ACQUIRER_AXIS => IFSC::UTIB,
        self::ACQUIRER_AMEX => Network::AMEX,
        self::ACQUIRER_RATN => IFSC::RATN,
        self::ACQUIRER_BARB => IFSC::BARB,
        self::ACQUIRER_SBIN => IFSC::SBIN,
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
    ];

    public static $verifyDisabled = [
        self::WALLET_OPENWALLET,
        self::NETBANKING_RBL,
        self::NETBANKING_ALLAHABAD,
        self::NETBANKING_CORPORATION,
        self::NETBANKING_IDFC,
        self::NETBANKING_VIJAYA,
        self::NETBANKING_EQUITAS,
        self::ENACH_NPCI_NETBANKING,
        self::NETBANKING_CBI,
        self::CARDLESS_EMI,
    ];

    public static $captureVerifyEnabled = [
        self::HITACHI,
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

    /**
     * List of gateways that support recurring payments
     *
     * @var array
     */
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
    ];

    public static $recurringCardNetworks = [
        Network::MC,
        Network::VISA,
    ];

    public static $recurringDebitCardBanks = [
        IFSC::ICIC,
        IFSC::CITI,
        IFSC::KKBK,
        IFSC::CNRB
    ];

    public static $directDebitCardNetworks = [
        Network::VISA,
        Network::MC,
        Network::MAES,
    ];

    public static $bharatQrGateways = [
        self::UPI_ICICI,
        self::HITACHI,
        self::SHARP,
        self::UPI_HULK,
        self::UPI_MINDGATE,
        self::ISG,
        self::WORLDLINE,
    ];

    public static $authTypeToEmandateGatewayMap = [
        AuthType::NETBANKING  => [
            Gateway::NETBANKING_AXIS,
            Gateway::NETBANKING_ICICI,
            Gateway::NETBANKING_HDFC,
            Gateway::NETBANKING_SBI,
            Gateway::ENACH_NPCI_NETBANKING,
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
        IFSC::ABHY,
        IFSC::ANDB,
        IFSC::UTIB,
        IFSC::BKID,
        IFSC::MAHB,
        IFSC::BCBM,
        IFSC::CNRB,
        IFSC::CBIN,
        IFSC::CITI,
        IFSC::DCBL,
        IFSC::FDRL,
        IFSC::HDFC,
        IFSC::ICIC,
        IFSC::IBKL,
        IFSC::IDFB,
        IFSC::INDB,
        IFSC::KKBK,
        IFSC::ORBC,
        Netbanking::PUNB_R,
        IFSC::RATN,
        IFSC::SRCB,
        IFSC::SCBL,
        IFSC::SVCB,
        IFSC::SYNB,
        IFSC::ADCC,
        IFSC::COSB,
        IFSC::HSBC,
        IFSC::SUTB,
        IFSC::UCBA,
        IFSC::UBIN,
        IFSC::YESB,
        IFSC::DBSS,
        IFSC::BGBX,
        IFSC::CORP,
        IFSC::VARA,
        IFSC::KVBL,
        Netbanking::BARB_R,
        IFSC::BKDN,
        IFSC::CSBX,
        IFSC::TMBL,
        IFSC::KAIJ,
        IFSC::TACX,
        IFSC::SIBL,
        IFSC::ESFB,
        IFSC::ACUX,
        IFSC::SBIN,
    ];

    /**
     * List of gateways and the banks that they support
     * for e-mandate. This list is required because some
     * gateways might support more than one bank for
     * e-mandate.
     *
     * @var array
     */
    public static $gatewaysEmandateBanksMap = [
        Gateway::NETBANKING_ICICI      => [IFSC::ICIC],
        Gateway::NETBANKING_AXIS       => [IFSC::UTIB],
        Gateway::NETBANKING_HDFC       => [IFSC::HDFC],
        Gateway::NETBANKING_SBI        => [IFSC::SBIN],
        Gateway::ENACH_NPCI_NETBANKING => self::ENACH_NPCI_NETBANKING_BANKS,
        Gateway::ENACH_RBL             => self::EMANDATE_AADHAAR_BANKS,
        // This is added here just for test cases
        // We are using UTIB in test cases
        Gateway::ESIGNER_DIGIO         => [
            IFSC::UTIB,
        ],
        Gateway::ESIGNER_LEGALDESK     => [
            IFSC::UTIB,
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

    /**
     * List of netbanking gateways that process emandate registration through file send
     *
     * @var array
     */
    public static $fileBasedEMandateRegistrationGateways = [
        Gateway::NETBANKING_HDFC,
        Gateway::ENACH_RBL,
        Gateway::ENACH_NPCI_NETBANKING,
        Gateway::NETBANKING_SBI,
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
        Gateway::UPI_RBL,
        Gateway::UPI_YESBANK,
        Gateway::UPI_AIRTEL,
        Gateway::WALLET_PHONEPE,
        Gateway::UPI_CITI,
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
     * For the banks we have direct tie-ups with,
     * here we list down the mapping from bank to netbanking gateway name.
     * There is no standardized bank gateway naming that we follow. IFSC
     * code option was discarded because it's not readable in general in code.
     *
     * @var array
     */
    public static $netbankingToGatewayMap = [
        //corp banks
        Netbanking::ICIC_C => Gateway::NETBANKING_ICICI,
        Netbanking::UTIB_C => Gateway::NETBANKING_AXIS,
        Netbanking::BARB_C => Gateway::NETBANKING_BOB,
        Netbanking::PUNB_C => Gateway::NETBANKING_PNB,

        // retail banks
        IFSC::IDFB         => Gateway::NETBANKING_IDFC,
        IFSC::ICIC         => Gateway::NETBANKING_ICICI,
        IFSC::HDFC         => Gateway::NETBANKING_HDFC,
        IFSC::CORP         => Gateway::NETBANKING_CORPORATION,
        IFSC::AIRP         => Gateway::NETBANKING_AIRTEL,
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
        IFSC::ALLA         => Gateway::NETBANKING_ALLAHABAD,
        IFSC::CNRB         => Gateway::NETBANKING_CANARA,
        IFSC::ESFB         => Gateway::NETBANKING_EQUITAS,
        IFSC::SBIN         => Gateway::NETBANKING_SBI,
        IFSC::VIJB         => Gateway::NETBANKING_VIJAYA,
        IFSC::YESB         => Gateway::NETBANKING_YESB,
        Netbanking::PUNB_R => Gateway::NETBANKING_PNB,
        Netbanking::BARB_R => Gateway::NETBANKING_BOB,
        IFSC::SBIN         => Gateway::NETBANKING_SBI,
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
        IFSC::CBIN          => Gateway::NETBANKING_CBI,
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
        IFSC::VIJB          => Gateway::NETBANKING_VIJAYA,
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
        IFSC::VIJB => Gateway::NETBANKING_VIJAYA,
        IFSC::ORBC => Gateway::NETBANKING_OBC,
        IFSC::CSBK => Gateway::NETBANKING_CSB,
        IFSC::CBIN => Gateway::NETBANKING_CBI,
        Netbanking::PUNB_R => Gateway::NETBANKING_PNB,
        Netbanking::BARB_R => Gateway::NETBANKING_BOB,
    ];


    /**
     * List of gateways which support netbanking, either in test or live mode.
     *
     * @var array
     */
    public static $netbankingGateways = [
        Gateway::BILLDESK,
        Gateway::EBS,
        Gateway::PAYTM,
        Gateway::ATOM
    ];

    public static $emiBanks = [
        IFSC::HDFC,
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
    ];

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
    ];

    public static $emiBankToGatewayMap = [
        IFSC::HDFC => Gateway::HDFC,
        IFSC::HSBC => Gateway::FIRST_DATA,
    ];

    /**
     * This variable defines the mapping of gateway acquirer and the
     * supported ifsc on that acquirer
     */
    public static $gatewayAcquirerIfscMapping = [
        Gateway::CARD_FSS => [
            self::ACQUIRER_FSS => [
                IFSC::UTIB,
                IFSC::IOBA,
                IFSC::ANDB,
                IFSC::SYNB,
                IFSC::SURY,
                IFSC::UCBA,
                IFSC::ICIC,
                IFSC::CBIN,
                IFSC::IDFB,
            ]
        ],

        Gateway::HDFC => [
            self::ACQUIRER_HDFC => [
                IFSC::HDFC,
            ]
        ],
    ];

    public static $onlyAuthorizationGateway = [
        Gateway::HITACHI,
        Gateway::ENACH_RBL,
    ];

    public static $authorizationAuthenticationGatewayMap = [
        Gateway::HITACHI     => Gateway::MPI_BLADE,
        Gateway::CYBERSOURCE => Gateway::CYBERSOURCE,
        Gateway::FIRST_DATA  => Gateway::FIRST_DATA,
        Gateway::AXIS_MIGS   => Gateway::AXIS_MIGS,
    ];

    public static $subscriptionOverOneYearGateways = [
        Gateway::AXIS_MIGS
    ];

    public static $upiIntentGateways = [
        Gateway::UPI_ICICI,
        Gateway::UPI_HULK,
        Gateway::UPI_MINDGATE,
        Gateway::UPI_AXIS,
        Gateway::UPI_RBL,
    ];

    public static $upiValidateVpaTerminals = [
        Mode::LIVE => [
            '9Q8w9weX9D1T27',
            'AK6NMmzbL6FPe4',
        ],
        Mode::TEST => [
            '1000SharpTrmnl',
        ],
    ];

    public static $cardlessEmiRedirectFlowProvider = [
        CardlessEmi::FLEXMONEY,
    ];

    public static $verifyClientOnS2s = [
        Gateway::UPI_CITI,
    ];

    public static function isNonTerminalGateway(string $gateway)
    {
        return in_array($gateway, self::$nonTerminalGateways, true);
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

    public static function getScroogeGateways(): array
    {
        return self::$scroogeGateways;
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

    /**
     * This function checks if given gateway is eligible for scrooge call.
     * Merchant id is not being used currently, but keeping the support of enabling specific merchants only.
     *
     * @param $gateway
     * @param $merchantId
     * @return bool
     *
     */
    public static function isScroogeGatewayAndMerchant(string $gateway = null, string $merchantId = null): bool
    {
        return (in_array($gateway, self::getScroogeGateways(), true) === true);
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

    /**
     * @param string $gateway
     *
     * @return bool
     */

    public static function isFileBasedEMandateDebitGateway(string $gateway): bool
    {
        return (in_array($gateway, self::$fileBasedEMandateDebitGateways) === true);
    }

    public static function isSupportedEmandateBank($bank): bool
    {
        $banks = self::getAllEMandateBanks();

        return (in_array($bank, $banks, true) === true);
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
    public static function supportsPurchase($gateway, $networkCode = null): bool
    {
        $supportsPurchase = isset(self::$gatewayNetworkPurchaseSupport[$gateway]);

        if ($supportsPurchase === true)
        {
            return self::isNetworkSupportedForPurchase($gateway, $networkCode);
        }

        return true;
    }

    public static function supportsReverse($gateway)
    {
        return in_array($gateway, self::$reverse, true);
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

    public static function isNetworkSupportedForPurchase($gateway, $networkCode)
    {
        // This means that all the networks are supported by the gateway for Purchase.
        if ((isset(self::$gatewayNetworkPurchaseSupport[$gateway][self::NOT_SUPPORTED]) === false) or
            ($networkCode === null))
        {
            return true;
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

    public static function canGatewayAutoDebit($gateway)
    {
        return (in_array($gateway, self::AUTO_DEBIT_GATEWAYS));
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
    public static function isCardNetworkSupported(string $network, string $gateway, bool $recurring = false)
    {
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

    public static function getGatewaysForNetbankingBank($bank, $isTPV = false)
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
                                             self::ENACH_NPCI_NETBANKING_BANKS
                                          )
                           );

        $netbankingBanks = array_values($netbankingBanks);

        return [
            AuthType::NETBANKING  => $netbankingBanks,
            AuthType::AADHAAR     => self::EMANDATE_AADHAAR_BANKS,
            AuthType::AADHAAR_FP  => self::EMANDATE_AADHAAR_BANKS,
        ];
    }

    public static function getTerminalsForValidateVpaForMode(string $mode)
    {
        // Currently we are only using MindGate and SBI for live and Sharp for test, later when
        // we have more gateways, we can introduce gateway selection logic here.
        return self::$upiValidateVpaTerminals[$mode];
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

    public static function isPowerWalletSupported($payment)
    {
        $gateway = $payment->getGateway();
        // We support power wallet flow if we can topup and autodebit.
        // Generally, power wallets allow topup of requests.
        if ((self::isPowerWallet($payment->getWallet()) === true) and
            (self::canGatewayAutoDebit($gateway) === true) and
            (self::canGatewayTopup($gateway) === true))
        {
            return true;
        }
        return false;
    }

}
