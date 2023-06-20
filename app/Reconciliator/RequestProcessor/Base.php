<?php

namespace RZP\Reconciliator\RequestProcessor;

use RZP\Exception;
use DirectoryIterator;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;
use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\Messenger;
use RZP\Reconciliator\Validator;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\FileProcessor;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\CardlessEmi;

class Base extends Core
{
    const GATEWAY                       = 'gateway';
    const ATTACHMENT_COUNT              = 'attachment_count';
    const ATTACHMENT_HYPHEN_COUNT       = 'attachment-count';
    const ATTACHMENT_HYPHEN_ONE         = 'attachment-1';
    const FORCE_UPDATE                  = 'force_update';
    const FORCE_AUTHORIZE               = 'force_authorize';
    const ATTACHMENT_HYPHEN_PREFIX      = 'attachment-';
    const SOURCE                        = 'source';
    const MANUAL_RECON_FILE             = 'manual_recon_file';
    const SUB_TYPE                      = 'sub_type';

    /**
     * Type of request processor
     */
    const LAMBDA                  = 'lambda';
    const MAILGUN                 = 'mailgun';
    const MANUAL                  = 'manual';
    const CRAWLER                 = 'crawler';

    const FILE_DETAILS            = 'file_details';
    const INPUT_DETAILS           = 'input_details';

    /**
     * These field can be force updated with passed with request
     */
    const REFUND_ARN            = 'refund_arn';
    const PAYMENT_ARN           = 'payment_arn';
    const PAYMENT_AUTH_CODE     = 'payment_auth_code';

    /******************
     * Gateway constants
     ******************/

    const HDFC                     = 'HDFC';
    const AXIS                     = 'Axis';
    const KOTAK                    = 'Kotak';
    const AIRTEL                   = 'Airtel';
    const BILLDESK                 = 'BillDesk';
    const PAYZAPP                  = 'PayZapp';
    const PAYZAPPV2                = 'PayZappV2';
    const MPESA                    = 'Mpesa';
    const MOBIKWIK                 = 'Mobikwik';
    const AMAZONPAY                = 'Amazonpay';
    const PAYTM                    = 'Paytm';
    const OLAMONEY                 = 'Olamoney';
    const FREECHARGE               = 'Freecharge';
    const EMANDATE_AXIS            = 'EmandateAxis';
    const NETBANKING_AXIS          = 'NetbankingAxis';
    const NETBANKING_ICICI         = 'NetbankingIcici';
    const NETBANKING_CANARA        = 'NetbankingCanara';
    const NETBANKING_FEDERAL       = 'NetbankingFederal';
    const NETBANKING_CORPORATION   = 'NetbankingCorporation';
    const NETBANKING_SIB           = 'NetbankingSib';
    const NETBANKING_SCB           = 'NetbankingScb';
    const NETBANKING_CBI           = 'NetbankingCbi';
    const NETBANKING_YESB          = 'NetbankingYesb';
    const NETBANKING_CUB           = 'NetbankingCub';
    const NETBANKING_IDBI          = 'NetbankingIdbi';
    const NETBANKING_IBK           = 'NetbankingIbk';
    const NETBANKING_SVC           = 'NetbankingSvc';
    const NETBANKING_RBL           = 'NetbankingRbl';
    const NETBANKING_CSB           = 'NetbankingCsb';
    const NETBANKING_IDFC          = 'NetbankingIdfc';
    const NETBANKING_INDUSIND      = 'NetbankingIndusind';
    const NETBANKING_PNB           = 'NetbankingPnb';
    const NETBANKING_BOB           = 'NetbankingBob';
    const NETBANKING_BOB_V2        = 'NetbankingBobV2';
    const NETBANKING_OBC           = 'NetbankingObc';
    const NETBANKING_VIJAYA        = 'NetbankingVijaya';
    const NETBANKING_EQUITAS       = 'NetbankingEquitas';
    const NETBANKING_HDFC          = 'NetbankingHdfc';
    const NETBANKING_HDFC_CORP     = 'NetbankingHdfcCorp';
    const NETBANKING_ALLAHABAD     = 'NetbankingAllahabad';
    const NETBANKING_JKB           = 'NetbankingJkb';
    const NETBANKING_SBI           = 'NetbankingSbi';
    const NETBANKING_KVB           = 'NetbankingKvb';
    const NETBANKING_JSB           = 'NetbankingJsb';
    const NETBANKING_FSB           = 'NetbankingFsb';
    const NETBANKING_IOB           = 'NetbankingIob';
    const NETBANKING_DCB           = 'NetbankingDcb';
    const NETBANKING_UJJIVAN       = 'NetbankingUjjivan';
    const NETBANKING_UBI           = 'NetbankingUbi';
    const NETBANKING_AUSF          = 'NetbankingAusf';
    const NETBANKING_AUSF_CORP     = 'NetbankingAusfCorp';
    const NETBANKING_DLB           = 'NetbankingDlb';
    const NETBANKING_TMB           = 'NetbankingTmb';
    const NETBANKING_KARB          = 'NetbankingKarb';
    const NETBANKING_KOTAK_V2      = 'NetbankingKotakV2';
    const NETBANKING_NSDL          = 'NetbankingNsdl';
    const NETBANKING_BDBL          = 'NetbankingBdbl';
    const NETBANKING_SARASWAT      = 'NetbankingSaraswat';
    const NETBANKING_UCO           = 'NetbankingUco';
    const VIRTUAL_ACC_KOTAK        = 'VirtualAccKotak';
    const VIRTUAL_ACC_YESBANK      = 'VirtualAccYesBank';
    const VIRTUAL_ACC_RBL          = 'VirtualAccRbl';
    const VIRTUAL_ACC_ICICI        = 'VirtualAccIcici';
    const JIOMONEY                 = 'Jiomoney';
    const UPI_SBI                  = 'UpiSbi';
    const PAYUMONEY                = 'PayuMoney';
    const EBS                      = 'Ebs';
    const FIRST_DATA               = 'FirstData';
    const PAYLATER_ICICI           = 'PaylaterIcici';
    const UPI_ICICI                = 'UpiIcici';
    const ADMIN                    = 'admin';
    const HITACHI                  = 'Hitachi';
    const FULCRUM                  = 'Fulcrum';
    const CARD_FSS_HDFC            = 'CardFssHdfc';
    const CARD_FSS_BOB             = 'CardFssBob';
    const CARD_FSS_SBI             = 'CardFssSbi';
    const ATOM                     = 'Atom';
    const ISG                      = 'Isg';
    const UPI_HDFC                 = 'UpiHdfc';
    const UPI_HULK                 = 'UpiHulk';
    const UPI_AXIS                 = 'UpiAxis';
    const UPI_YESBANK              = 'UpiYesBank';
    const AMEX                     = 'Amex';
    const CARDLESS_EMI_FLEXMONEY   = 'CardlessEmiFlexMoney';
    const PHONEPE                  = 'Phonepe';
    const PHONEPE_SWITCH           = 'Phonepeswitch';
    const PAYPAL                   = 'Paypal';
    const BAJAJFINSERV             = 'BajajFinserv';
    const GETSIMPL                 = 'Getsimpl';
    const VAS_AXIS                 = 'VasAxis';
    const YES_BANK                 = 'YesBank';
    const HDFC_DEBIT_EMI           = 'HdfcDebitEmi';
    const INDUSIND_DEBIT_EMI       = 'IndusindDebitEmi';
    const KOTAK_DEBIT_EMI          = 'KotakDebitEmi';
    const UPI_JUSPAY               = 'UpiJuspay';
    const UPI_AIRTEL               = 'UpiAirtel';
    const CRED                     = 'Cred';
    const WALNUT369                = 'Walnut369';
    const TWID                     = 'Twid';
    const CARDLESS_EMI_ZESTMONEY   = 'CardlessEmiZestMoney';
    const PAYLATER_LAZYPAY         = 'PaylaterLazypay';
    const BT_RBL                   = 'BtRbl';
    const CHECKOUT_DOT_COM         = 'checkout_dot_com';
    const CARDLESS_EMI_EARLYSALARY = 'CardlessEmiEarlySalary';
    const EMERCHANTPAY             = 'emerchantpay';
    const NETBANKING_DBS           = 'NetbankingDbs';
    const WALLET_BAJAJ             = 'WalletBajaj';


    /**
     * The gateway names should be the same name as the directories present under 'reconciliator'
     * The banks send their MIS files through this sender address.
     * List email addresses in lower case. Addresses are case insensitive, our checks are not.
     */
    const GATEWAY_SENDER_MAPPING = [
        self::HDFC                     => [],
        self::AXIS                     => ['pg.estatements@axisbank.com'],
        self::BILLDESK                 => [],
        self::PAYZAPP                  => ['donotreply@enstage.com'],
        self::PAYZAPPV2                => [],
        self::MOBIKWIK                 => [],
        self::AMAZONPAY                => [],
        self::MPESA                    => [],
        self::PAYTM                    => [],
        self::KOTAK                    => ['bankalerts@kotak.com'],
        self::OLAMONEY                 => [],
        self::FREECHARGE               => ['noreply@fcemail.in', 'noreply@freechargemail.in'],
        self::EMANDATE_AXIS            => ['cmsdirect.debit@axisbank.com'],
        self::NETBANKING_AXIS          => ['ibanking@axisbank.com'],
        self::NETBANKING_ICICI         => ['ubpshelp@icicibank.com', 'infinitydatacenter@icicibank.com'],
        self::NETBANKING_FEDERAL       => ['fednetrm@federalbank.co.in'],
        self::NETBANKING_SIB           => ['epayments@sib.co.in'],
        self::NETBANKING_SCB           => ['no-reply@northakross.com'],
        self::NETBANKING_CBI           => ['smcbipso@centralbankofindia.org.in'],
        self::NETBANKING_YESB          => [''],
        self::NETBANKING_CUB           => [''],
        self::NETBANKING_IDBI          => [''],
        self::NETBANKING_IBK           => [''],
        self::NETBANKING_RBL           => ['internetbanking@rblbank.com'],
        self::NETBANKING_EQUITAS       => [],
        self::NETBANKING_CANARA        => ['canarabank@canarabank.com'],
        self::AIRTEL                   => ['no-reply@airtelbank.com'],
        self::NETBANKING_INDUSIND      => [],
        self::NETBANKING_OBC           => [],
        self::NETBANKING_JKB           => ['netbanking@jkbmail.com'],
        self::NETBANKING_PNB           => [],
        self::NETBANKING_ALLAHABAD     => ['imps.recon@allahabadbank.in', 'cbspo.aeps@allahabadbank.in'],
        self::NETBANKING_IDFC          => [],
        self::NETBANKING_CSB           => ['noreply@csb.co.in', 'donotreply@csb.co.in'],
        self::NETBANKING_CORPORATION   => ['ncbsfeba@corpbank.co.in'],
        self::NETBANKING_VIJAYA        => [], //TODO: add this value when shared post UAT
        self::NETBANKING_BOB           => ['billpay@bankofbaroda.com'],
        self::NETBANKING_HDFC          => [],
        self::NETBANKING_SBI           => ['donotreply.inb@sbi.co.in', "donotreply.inb@alerts.sbi.co.in", "kalpit.jain@razorpay.com"],
        self::NETBANKING_KVB           => ['atmcashtally@kvbmail.com', 'lakshmim@kvbmail.com'],
        self::NETBANKING_SVC           => ['netbanking@svcbank.com', 'kavishwarrk@svcbank.com', 'nirmalvb@svcbank.com'],
        self::NETBANKING_JSB           => ['channel_payments@janabank.com'],
        self::NETBANKING_FSB           => ['prabhu.veluswamy@fincarebank.com','lijo.k@fincarebank.com','jayaprashanth.vk@fincarebank.com','febin.anto@fincarebank.com','ce035@fincarebank.com','joseph.arun@fincarebank.com'],
        self::NETBANKING_IOB           => ['54667@iobnet.co.in', '63015@iobnet.co.in', 'pg@iobnet.co.in', 'eseeadmin@iobnet.co.in'],
        self::NETBANKING_DCB           => ['sandesh.kadam@dcbbank.com', 'arif.shaikh@dcbbank.com'],
        self::NETBANKING_UBI           => [],
        self::NETBANKING_AUSF          => [],
        self::NETBANKING_AUSF_CORP     => [],
        self::NETBANKING_KOTAK_V2      => ['bankalerts@kotak.com'],
        self::NETBANKING_NSDL          => [],
        self::JIOMONEY                 => [],
        self::EBS                      => [],
        self::FIRST_DATA               => ['customer.care@icici.mailserv.in'],
        self::UPI_ICICI                => [],
        self::VIRTUAL_ACC_KOTAK        => ['kmb.reports@kotak.com'],
        self::VIRTUAL_ACC_YESBANK      => ['ereport@yesbank.in'],
        self::VIRTUAL_ACC_RBL          => [],
        self::UPI_SBI                  => [],
        self::PAYUMONEY                => [],
        self::HITACHI                  => [],
        self::FULCRUM                  => [],
        self::CARD_FSS_HDFC            => ['merchantops@fss.co.in'],
        self::ATOM                     => [],
        self::CARD_FSS_BOB             => [],
        self::CARD_FSS_SBI             => ['ipay.support@sbi.co.in'],
        self::UPI_AXIS                 => [],
        self::UPI_HDFC                 => [],
        self::UPI_HULK                 => [],
        self::UPI_YESBANK              => [],
        self::AMEX                     => [],
        self::ISG                      => ['kotak.acquirer@insolutionsglobal.com'],
        self::PHONEPE                  => [],
        self::PHONEPE_SWITCH           => [],
        self::PAYLATER_ICICI           => [],
        self::CARDLESS_EMI_FLEXMONEY   => ['tejal.gangadhar@flexmoney.in', 'prahalad.rao@flexmoney.in'],
        self::WALNUT369                => [],
        self::PAYPAL                   => [],
        self::VAS_AXIS                 => [],
        self::GETSIMPL                 => [],
        self::BAJAJFINSERV             => ['remiecftransactions@bizsupportc.com', 'ashwani.verma1@bajajfinserv.in'],
        self::YES_BANK                 => ['yesacquirer@insolutionsglobal.com'],
        self::HDFC_DEBIT_EMI           => ['emailintimation@hdfcbank.com'],
        self::INDUSIND_DEBIT_EMI       => [],
        self::KOTAK_DEBIT_EMI          => [],
        self::UPI_JUSPAY               => ['crs.upimerchantsettlement@axisbank.com'],
        self::UPI_AIRTEL               => [],
        self::UPI_YESBANK              => [],
        self::CRED                     => [],
        self::TWID                     => [],
        self::VIRTUAL_ACC_ICICI        => [],
        self::NETBANKING_DLB           => ['alerts@dhanbank.co.in'],
        self::NETBANKING_UJJIVAN           => [],
        self::NETBANKING_TMB           => ['recon@tmbank.in'],
        self::NETBANKING_KARB          => [],
        self::CHECKOUT_DOT_COM         => [],
        self::CARDLESS_EMI_ZESTMONEY   => ['finops.settlements@zestmoney.in'],
        self::PAYLATER_LAZYPAY         => [],
        self::NETBANKING_BDBL          => ['imps.dispute@bandhanbank.com'],
        self::NETBANKING_UCO           => ['hoe_banking.calcutta@ucobank.co.in'],
        self::CARDLESS_EMI_EARLYSALARY => [],
        self::NETBANKING_SARASWAT      => [],
        self::EMERCHANTPAY             => ['shruthi.krishna@emerchantpay.com'],
        self::NETBANKING_HDFC_CORP     => [],
        self::NETBANKING_DBS           => [],
        self::WALLET_BAJAJ             => ['art-recon@razorpay.com', 'kishor.kangune@bajajfinserv.in'],

        // Used when someone from the team needs to send the
        // reconciliation file via mail for reconciliation.
        self::ADMIN                  => ['kajol.nigam@razorpay.com'],
    ];

    const GATEWAY_CRAWLERS = [
        self::NETBANKING_BOB_V2     => Gateway::NETBANKING_BOB_V2,
        self::NETBANKING_CUB        => Gateway::NETBANKING_CUB,
        self::PAYPAL                => Gateway::WALLET_PAYPAL,
        self::GETSIMPL              => Gateway::GETSIMPL,
    ];

    /**
     * We need this mapping because same gateway is stored in two forms.
     * i.e. netbanking_hdfc and NetbankingHdfc refer to same gateway.
     * Few use cases : This conversion needed while making recon request
     * for individual MPR files in case of VAS and also for pushing 'source'
     * dimension in recon metric.
     */
    const GATEWAY_NAME_MAPPING = [
        Gateway::ATOM                   => self::ATOM,
        Gateway::BILLDESK               => self::BILLDESK,
        Gateway::KOTAK                  => self::KOTAK,
        Gateway::AXIS_MIGS              => self::AXIS,
        Gateway::AXIS_GENIUS            => self::AXIS,
        Gateway::FULCRUM                => self::FULCRUM,
        Gateway::HDFC                   => self::HDFC,
        Gateway::MOBIKWIK               => self::MOBIKWIK,
        Gateway::NETBANKING_AIRTEL      => self::AIRTEL,
        Gateway::NETBANKING_AXIS        => self::NETBANKING_AXIS,
        Gateway::NETBANKING_IDFC        => self::NETBANKING_IDFC,
        Gateway::NETBANKING_FEDERAL     => self::NETBANKING_FEDERAL,
        Gateway::NETBANKING_SIB         => self::NETBANKING_SIB,
        Gateway::NETBANKING_SCB         => self::NETBANKING_SCB,
        Gateway::NETBANKING_CBI         => self::NETBANKING_CBI,
        Gateway::NETBANKING_YESB        => self::NETBANKING_YESB,
        Gateway::NETBANKING_CUB         => self::NETBANKING_CUB,
        Gateway::NETBANKING_IDBI        => self::NETBANKING_IDBI,
        Gateway::NETBANKING_IBK         => self::NETBANKING_IBK,
        Gateway::NETBANKING_JKB         => self::NETBANKING_JKB,
        Gateway::NETBANKING_EQUITAS     => self::NETBANKING_EQUITAS,
        Gateway::NETBANKING_BOB         => self::NETBANKING_BOB,
        Gateway::NETBANKING_VIJAYA      => self::NETBANKING_VIJAYA,
        Gateway::NETBANKING_HDFC        => self::NETBANKING_HDFC,
        Gateway::NETBANKING_CORPORATION => self::NETBANKING_CORPORATION,
        Gateway::NETBANKING_ICICI       => self::NETBANKING_ICICI,
        Gateway::NETBANKING_INDUSIND    => self::NETBANKING_INDUSIND,
        Gateway::NETBANKING_KOTAK       => self::KOTAK,
        Gateway::NETBANKING_RBL         => self::NETBANKING_RBL,
        Gateway::NETBANKING_CSB         => self::NETBANKING_CSB,
        Gateway::NETBANKING_PNB         => self::NETBANKING_PNB,
        Gateway::NETBANKING_OBC         => self::NETBANKING_OBC,
        Gateway::NETBANKING_ALLAHABAD   => self::NETBANKING_ALLAHABAD,
        Gateway::NETBANKING_CANARA      => self::NETBANKING_CANARA,
        Gateway::NETBANKING_SBI         => self::NETBANKING_SBI,
        Gateway::NETBANKING_KVB         => self::NETBANKING_KVB,
        Gateway::NETBANKING_SVC         => self::NETBANKING_SVC,
        Gateway::NETBANKING_JSB         => self::NETBANKING_JSB,
        Gateway::NETBANKING_FSB         => self::NETBANKING_FSB,
        Gateway::NETBANKING_IOB         => self::NETBANKING_IOB,
        Gateway::NETBANKING_DCB         => self::NETBANKING_DCB,
        Gateway::NETBANKING_UJJIVAN     => self::NETBANKING_UJJIVAN,
        Gateway::NETBANKING_UBI         => self::NETBANKING_UBI,
        Gateway::NETBANKING_AUSF        => self::NETBANKING_AUSF,
        Gateway::NETBANKING_KOTAK       => self::NETBANKING_KOTAK_V2,
        Gateway::PAYTM                  => self::PAYTM,
        Gateway::UPI_MINDGATE           => self::UPI_HDFC,
        Gateway::UPI_SBI                => self::UPI_SBI,
        Gateway::UPI_AXIS               => self::UPI_AXIS,
        Gateway::UPI_AIRTEL             => self::UPI_AIRTEL,
        Gateway::UPI_ICICI              => self::UPI_ICICI,
        Gateway::UPI_JUSPAY             => self::UPI_JUSPAY,
        Gateway::UPI_HULK               => self::UPI_HULK,
        Gateway::ISG                    => self::ISG,
        Gateway::EBS                    => self::EBS,
        Gateway::BT_YESBANK             => self::VIRTUAL_ACC_YESBANK,
        Gateway::WORLDLINE              => self::VAS_AXIS,
        Gateway::NETBANKING_DLB         => self::NETBANKING_DLB,
        Gateway::NETBANKING_NSDL        => self::NETBANKING_NSDL,
        Gateway::NETBANKING_BDBL        => self::NETBANKING_BDBL,
        Gateway::NETBANKING_SARASWAT    => self::NETBANKING_SARASWAT,
        Gateway::NETBANKING_UCO         => self::NETBANKING_UCO,
        Gateway::NETBANKING_DBS         => self::NETBANKING_DBS,

        Gateway::AMEX                   => [
            Gateway::ACQUIRER_AMEX   => self::AMEX,
        ],

        Gateway::FIRST_DATA             => [
            Gateway::ACQUIRER_ICIC   => self::FIRST_DATA,
        ],

        Gateway::HITACHI                => [
            Gateway::ACQUIRER_RATN   => self::HITACHI,
        ],

        Gateway::CYBERSOURCE            => [
            Gateway::ACQUIRER_AXIS   => self::AXIS,
            Gateway::ACQUIRER_HDFC   => self::HDFC,
            Gateway::ACQUIRER_YESB   => self::YES_BANK,
        ],

        Gateway::CARD_FSS               => [
            Gateway::ACQUIRER_BARB   => self::CARD_FSS_BOB,
            Gateway::ACQUIRER_HDFC   => self::CARD_FSS_HDFC,
            Gateway::ACQUIRER_SBIN   => self::CARD_FSS_SBI,
        ],

        Gateway::WALLET_AIRTELMONEY     => self::AIRTEL,
        Gateway::WALLET_AMAZONPAY       => self::AMAZONPAY,
        Gateway::WALLET_FREECHARGE      => self::FREECHARGE,
        Gateway::WALLET_JIOMONEY        => self::JIOMONEY,
        Gateway::WALLET_MPESA           => self::MPESA,
        Gateway::WALLET_OLAMONEY        => self::OLAMONEY,
        Gateway::WALLET_PAYUMONEY       => self::PAYUMONEY,
        Gateway::WALLET_PAYZAPP         => self::PAYZAPP,
        Gateway::WALLET_PHONEPE         => self::PHONEPE,
        Gateway::WALLET_PHONEPESWITCH   => self::PHONEPE_SWITCH,
        Gateway::WALLET_PAYPAL          => self::PAYPAL,
        Gateway::GETSIMPL               => self::GETSIMPL,
        Gateway::CARDLESS_EMI           => [
            CardlessEmi::FLEXMONEY   => self::CARDLESS_EMI_FLEXMONEY,
            CardlessEmi::WALNUT369   => self::WALNUT369,
            CardlessEmi::ZESTMONEY   => self::CARDLESS_EMI_ZESTMONEY,
            CardlessEmi::EARLYSALARY => self::CARDLESS_EMI_EARLYSALARY,
        ],
        Gateway::PAYLATER               => [
            PayLater::LAZYPAY        => self::PAYLATER_LAZYPAY,
        ],
        Gateway::BAJAJFINSERV           => self::BAJAJFINSERV,
        Gateway::HDFC_DEBIT_EMI         => self::HDFC_DEBIT_EMI,
        Gateway::INDUSIND_DEBIT_EMI     => self::INDUSIND_DEBIT_EMI,
        Gateway::KOTAK_DEBIT_EMI        => self::KOTAK_DEBIT_EMI,
        Gateway::WORLDLINE              => self::VAS_AXIS,
        Gateway::TWID                   => self::TWID,
        Gateway::CHECKOUT_DOT_COM       => self::CHECKOUT_DOT_COM,
        Gateway::EMERCHANTPAY           => self::EMERCHANTPAY,
        Gateway::WALLET_BAJAJ           => self::WALLET_BAJAJ,
    ];

    // Gateways for which halt gateway mismatch recon flow is ready
    const HALT_RECON_ON_GATEWAY_MISMATCH = [
        Base::UPI_SBI       => Base::UPI_SBI
    ];

    /**
     * Set of attributes, which act as configuration for recon processing
     * and can be optionally passed in the request.
     */
    const CONFIG_PARAMS = [
        self::FORCE_UPDATE,
        self::SOURCE,
        self::FORCE_AUTHORIZE,
        self::MANUAL_RECON_FILE,
    ];

    protected $validator;

    protected $fileProcessor;

    protected $gateway;

    protected $messenger;

    protected $gatewayReconciliator;

    public function __construct()
    {
        parent::__construct();

        $this->validator     = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->messenger     = new Messenger;
    }

    public function getGateway()
    {
        return $this->gateway;
    }

    public function getGatewayReconciliator()
    {
        return $this->gatewayReconciliator;
    }

    protected function setGatewayReconciliatorObject()
    {
        $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' .
                                         $this->gateway . '\\' .
                                         'Reconciliate';

        $this->gatewayReconciliator = new $gatewayReconciliatorClassName($this->gateway);
    }

    /**
     * @param   array        $inputDetails
     * @param   array        $input
     * @param   string       $fileLocationType
     *
     * @return array
     */
    protected function getFileDetailsFromInput(
        array $inputDetails,
        array $input,
        string $fileLocationType = FileProcessor::UPLOADED)
    {
        $allFilesDetails = [];
        // Goes through each file and gets the file details.
        foreach (range(1, $inputDetails[self::ATTACHMENT_COUNT]) as $attachmentNumber)
        {
            // All the attachment files have to be named as 'attachment-{number}'
            // Validations should take care of this.
            $file = $input['attachment-' . $attachmentNumber];

            if ($this->fileProcessor->isZipFile($file, $fileLocationType) === true)
            {
                $zipFileDetails = [];

                try
                {
                    // Gets the actual zip file's details first.
                    $zipFileDetails = $this->fileProcessor->getFileDetails($file, $fileLocationType);

                    // Gets all files details present in the zip file.
                    $extractedFileDetails = $this->getFileDetailsFromZipFile($zipFileDetails);

                    // Throw an error if there's not even one file in the zip. Ideally, shouldn't happen.
                    if (empty($extractedFileDetails) === true)
                    {
                        // Exception instead of alert, to handle zip extraction exceptions also in the
                        // same alert in the catch block. (Cleaner code).
                        throw new Exception\ReconciliationException(
                            'No files present in the zip file attachment.',
                            ['file_name' => $file->getClientOriginalName()]
                        );
                    }

                    // Checks whether all the extracted files are zips too.
                    $multiLevelZip = $this->isTwoLevelZip($extractedFileDetails);

                    if ($multiLevelZip === true)
                    {
                        $extractedFileDetails = $this->getFileDetailsFromAllZipFiles($extractedFileDetails);
                    }

                    // Using array merge since $extractedFileDetails contains an
                    // array of file details of different files in the zip file.
                    $allFilesDetails = array_merge($allFilesDetails, $extractedFileDetails);
                }
                catch (\Exception $ex)
                {
                    $this->handleZipProcessingException($ex, $zipFileDetails);
                }
            }
            else
            {
                // Except zip, all other file types will return with a single element
                // and not an array. Hence using push here instead of merge.
                $allFilesDetails[] = $this->fileProcessor->getFileDetails($file, $fileLocationType);
            }
        }

        return $allFilesDetails;
    }

    /**
     * @param  \Exception $ex
     */
    protected function handleZipProcessingException(\Exception $ex, array $zipFileDetails)
    {
        $this->trace->traceException($ex);

        //
        // Axis sends hundreds of files daily with wrong password and one
        // file with the right password. We don't know which file has the
        // right password and which file has the wrong password.
        // Hence, we suppress all axis wrong password errors.
        //
        if (($this->gateway !== self::AXIS) and
            (str_contains($ex->getMessage(), 'Wrong password')))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'   => TraceCode::RECON_FILE_SKIP,
                    'message'      => 'Skipping file because unzip file caused an exception -> ' .
                        $ex->getMessage(),
                    'file_details' => !empty($extractedFileDetails) ? $extractedFileDetails : null,
                    'gateway'      => $this->gateway,
                ]);
        }

        $this->deleteFileLocallyIfPresent($zipFileDetails);
    }

    /**
     * Unzips the zip file. Iterates through each extracted file and collects
     * the file details.
     *
     * @param array $zipFileDetails Zip file that needs to be extracted.
     * @return array File details of all the files present in the zip file.
     */
    protected function getFileDetailsFromZipFile(array $zipFileDetails)
    {
        $allExtractedFilesDetails = [];

        $zipPassword = $this->gatewayReconciliator->getReconPassword($zipFileDetails);

        $use7z = $this->gatewayReconciliator->shouldUse7z($zipFileDetails);

        // unzipFile unzips the file and stores it in a location.
        $unzippedFolderPath = $this->fileProcessor->unzipFile($zipFileDetails, $zipPassword, $use7z);

        $unzippedFiles = new DirectoryIterator($unzippedFolderPath);

        // Iterates through each zip file and gets the file details for them.
        foreach ($unzippedFiles as $unzippedFile)
        {
            if ($unzippedFile->isFile() === true)
            {
                $allExtractedFilesDetails[] = $this->fileProcessor
                                                   ->getFileDetails($unzippedFile, FileProcessor::STORAGE);
            }

            //
            // If not a file, check for directory.
            // isDot() returns true for the hidden default directories '.' and '..' , so we
            // have put a 'false' condition here as we want to go into actual directories only.
            //
            else if (($unzippedFile->isDir() === true) and ($unzippedFile->isDot() === false))
            {
                $dirFiles = $this->getFilesFromDirectory($unzippedFile);

                $allExtractedFilesDetails = array_merge($allExtractedFilesDetails, $dirFiles);
            }
        }

        return $allExtractedFilesDetails;
    }

    /**
     * Get all the files from this directory.
     * This does not go inside nested sub-directories.
     *
     * @param \SplFileInfo $dir
     * @return array List of files
     */
    protected function getFilesFromDirectory(\SplFileInfo $dir)
    {
        $dirFiles = [];

        $unzippedFiles = new DirectoryIterator($dir->getPathname());

        foreach ($unzippedFiles as $unzippedFile)
        {
            if ($unzippedFile->isFile() === true)
            {
                $dirFiles[] = $this->fileProcessor
                    ->getFileDetails($unzippedFile, FileProcessor::STORAGE);
            }
        }

        return $dirFiles;
    }

    /**
     * Get all the files from the directory recursively.
     * This goes inside nested sub-directories.
     *
     * @param \SplFileInfo $dir
     * @return array List of files
     */
    protected function getFilesFromDirectoryRecursively(\SplFileInfo $dir)
    {
        $dirFiles = [];

        $unzippedFiles = new DirectoryIterator($dir->getPathname());

        foreach ($unzippedFiles as $unzippedFile)
        {
            if ($unzippedFile->isFile() === true)
            {
                $dirFiles[] = $this->fileProcessor
                    ->getFileDetails($unzippedFile, FileProcessor::STORAGE);
            }

            //
            // If not a file, check for directory.
            // isDot() returns true for the hidden default directories '.' and '..' , so we
            // have put a 'false' condition here as we want to go into actual directories only.
            //
            else if (($unzippedFile->isDir() === true) and ($unzippedFile->isDot() === false))
            {
                $dirFiles = array_merge($dirFiles, $this->getFilesFromDirectoryRecursively($unzippedFile));
            }
        }

        return $dirFiles;
    }

    protected function getFileDetailsFromAllZipFiles(array $zipFileDetails)
    {
        $allExtractedFileDetails = [];

        foreach ($zipFileDetails as $zf)
        {
            try
            {
                $extractedFileDetails = $this->getFileDetailsFromZipFile($zf);
            }
            catch (\Exception $ex)
            {
                $level = Trace::ERROR;

                if ($this->gateway === self::AXIS)
                {
                    $level = Trace::INFO;
                }

                $this->trace->traceException(
                    $ex,
                    $level,
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'message'           => 'Unable to extract zip file',
                        'zip_file_details'  => $zf,
                        'gateway'           => $this->gateway,
                    ]);

                continue;
            }

            $allExtractedFileDetails = array_merge($allExtractedFileDetails, $extractedFileDetails);
        }

        return $allExtractedFileDetails;
    }

    /**
     * Fetches the documents from the link, stores them in tmp
     * after extraction if necessary, deletes the zip file, keeping
     * the imp files
     *
     * @param  array $input
     * @return array
     */
    protected function fetchAndStoreLinkDocuments(array & $input)
    {
        if (empty($input[self::ATTACHMENT_HYPHEN_COUNT]) === true)
        {
            $input[self::ATTACHMENT_HYPHEN_COUNT] = 0;
        }

        $link = $this->gatewayReconciliator->getSettlementFileLink($input['body-html']);

        $this->trace->info(
            TraceCode::RECON_FILE_LINK,
            [
                'link'    => $link,
                'gateway' => $this->gateway,
            ]);

        if ($link === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'   => TraceCode::RECON_FILE_LINK_NOT_FOUND,
                    'message'      => 'Unable to get the link for the MIS file',
                    'gateway'      => $this->gateway,
                ]);

            throw new Exception\ReconciliationException(
                'Unable to get the link',
                [
                    'gateway' => $this->gateway
                ]);
        }

        $file = $this->fileProcessor->getAndStoreFileFromLink($link);

        $attachmentCount = (string) ((int) $input[self::ATTACHMENT_HYPHEN_COUNT] + 1);

        $input['attachment-' . $attachmentCount] = $file;
        $input[self::ATTACHMENT_HYPHEN_COUNT] = $attachmentCount;
    }

    /**
     * Returns true only if all the files are zip files.
     * Returns false otherwise.
     *
     * @param  array    $extractedFileDetails
     * @return bool     true if all the files are zip files
     *                  false, otherwise.
     */
    protected function isTwoLevelZip(array $extractedFileDetails): bool
    {
        foreach ($extractedFileDetails as $efd)
        {
            if ($efd[FileProcessor::EXTENSION] !== FileProcessor::ZIP_EXTENSION)
            {
                return false;
            }
        }

        return true;
    }

    protected function deleteFileLocallyIfPresent(array $fileDetails)
    {
        if (isset($fileDetails[FileProcessor::FILE_PATH]) === false)
        {
            return;
        }

        $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
    }
}
