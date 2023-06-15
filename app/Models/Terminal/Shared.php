<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;
use RZP\Gateway\Base\Mock\GatewayTrait;

class Shared
{
    const NETBANKING_IDFC_TERMINAL           = '100NbIdfcTrmnl';
    const AMEX_RAZORPAY_TERMINAL             = '1000AmexShared';
    const ATOM_RAZORPAY_TERMINAL             = '1000AtomShared';
    const ATOM_RAZORPAY_TPV_TERMINAL         = '1000AtomTpvtml';
    const AXIS_GENIUS_RAZORPAY_TERMINAL      = '1000AxisGenius';
    const AXIS_MIGS_RAZORPAY_TERMINAL        = '1000AxisMigsTl';
    const BILLDESK_RAZORPAY_TERMINAL         = '1000BdeskTrmnl';
    const BLADE_RAZORPAY_TERMINAL            = '1000BladeTrmnl';
    const DIGIO_RAZORPAY_TERMINAL            = '1000DigioTrmnl';
    const LEGALDESK_RAZORPAY_TERMINAL        = '1000LegalDskTl';
    const ENACH_RBL_RAZORPAY_TERMINAL        = '1000EnachRblTl';
    const ENACH_NPCI_NETBANKING_TERMINAL     = '1EnachNbNpciTl';
    const ENACH_NPCI_NETBANKING_YESB_TERMINAL= '1EnhNbNpcYesTl';
    const ENACH_NPCI_NETBANKING_ICIC_TERMINAL= '1EnhNbNpIcicTl';
    const EBS_RAZORPAY_TERMINAL              = '100000EbsTrmnl';
    const HDFC_RAZORPAY_TERMINAL             = '1000HdfcShared';
    const MOBIKWIK_RAZORPAY_TERMINAL         = '1000MobiKwikTl';
    const NETBANKING_HDFC_TERMINAL           = '100NbHdfcTrmnl';
    const NETBANKING_HDFC_CRP_TERMINAL       = '100NbHdfcCrpTl';
    const NETBANKING_BOB_TERMINAL            = '100NbBbdaTrmnl';
    const NETBANKING_VIJAYA_TERMINAL         = '100NbVijbTrmnl';
    const NETBANKING_CORPORATION_TERMINAL    = '100NbCorpTrmnl';
    const NETBANKING_CUB_TERMINAL            = '1000NbCubTrmnl';
    const NETBANKING_IBK_TERMINAL            = '1000NbIbkTrmnl';
    const NETBANKING_IDBI_TERMINAL           = '100NbIdbiTrmnl';
    const NETBANKING_KOTAK_TERMINAL          = '100NbKotakTmnl';
    const NETBANKING_ICICI_TERMINAL          = '100NbIciciTmnl';
    const NETBANKING_ICICI_CRP_TERMINAL      = '100NbIcicCrpTl';
    const NETBANKING_ICICI_TPV_TERMINAL      = '100NbIcicTpvTl';
    const NETBANKING_ICICI_REC_TERMINAL      = '100NbIcicRecTl';
    const NETBANKING_HDFC_REC_TERMINAL       = '100NbHdfcRecTl';
    const NETBANKING_AIRTEL_TERMINAL         = '100NbAirtlTmnl';
    const NETBANKING_OBC_TERMINAL            = '100NbOrtelTmnl';
    const NETBANKING_AXIS_TERMINAL           = '100NbAxisTrmnl';
    const NETBANKING_AXIS_CRP_TERMINAL       = '100NbAxisCrpTl';
    const NETBANKING_AXIS_TPV_TERMINAL       = '100NbAxisTpvTl';
    const NETBANKING_AXIS_REC_TERMINAL       = '100NbAxisRecTl';
    const NETBANKING_UBI_TERMINAL            = '1000NbUbiTrmnl';
    const NETBANKING_SCB_TERMINAL            = '1000NbScbTrmnl';
    const NETBANKING_JKB_TERMINAL            = '1000NbJkbTrmnl';
    const NETBANKING_FEDERAL_TERMINAL        = '100NbFdrlTrmnl';
    const NETBANKING_FEDERAL_TPV_TERMINAL    = '100NbFdrlTpvTl';
    const NETBANKING_RBL_TERMINAL            = '100NbRblTermnl';
    const NETBANKING_ALLAHABAD_TERMINAL      = '100NbAllaTrmnl';
    const NETBANKING_CSB_TERMINAL            = '100NbCsbTermnl';
    const NETBANKING_CANARA_TERMINAL         = '100NbCnrbTrmnl';
    const NETBANKING_RBL_TPV_TERMINAL        = '100NbRblTpvTml';
    const NETBANKING_INDUSIND_TERMINAL       = '100NbIndnTrmnl';
    const NETBANKING_INDUSIND_TPV_TERMINAL   = '100NbIndnTpvTl';
    const NETBANKING_PNB_TERMINAL            = '100NbPunbTrmnl';
    const NETBANKING_PNB_TPV_TERMINAL        = '100NbPunbTpvTl';
    const NETBANKING_SIB_TERMINAL            = '1000NbSibTrmnl';
    const NETBANKING_CBI_TERMINAL            = '1000NbCbiTrmnl';
    const NETBANKING_SIB_TPV_TERMINAL        = '1000NbSibTpvTl';
    const NETBANKING_SBI_TERMINAL            = '100NbSbinTrmnl';
    const NETBANKING_SBI_TPV_TERMINAL        = '100NbSbinTpvTl';
    const NETBANKING_SBI_REC_TERMINAL        = '100NbSbinRecTl';
    const NETBANKING_YESB_TERMINAL           = '100NbYesbTrmnl';
    const NETBANKING_YESB_TPV_TERMINAL       = '100NbYesbTpvTl';
    const NETBANKING_PNB_CRP_TERMINAL        = '100NbPunbCrpTl';
    const NETBANKING_ESFB_TERMINAL           = '100NbEsfbTrmnl';
    const NETBANKING_ESFB_TPV_TERMINAL       = '100NbEsfbTpvTl';
    const NETBANKING_JSB_TERMINAL            = '1000NbJsbTrmnl';
    const NETBANKING_IOB_TERMINAL            = '1000NbIobTrmnl';
    const NACH_CITI_TERMINAL                 = '100NbcitiTrmnl';
    const NACH_ICICI_TERMINAL                = '100NbIcicTrmnl';
    const NETBANKING_KVB_TERMINAL            = '1000NbKvbTrmnl';
    const NETBANKING_KVB_TPV_TERMINAL        = '1000NbKvbTpvTl';
    const NETBANKING_SVC_TERMINAL            = '1000NbSvcTrmnl';
    const NETBANKING_DCB_TERMINAL            = '1000NbDcbTrmnl';
    const NETBANKING_UJVN_TERMINAL           = '100NbUjivTrmnl';
    const NETBANKING_UJVN_TPV_TERMINAL       = '100NbUjivTpvTl';
    const OLAMONEY_RAZORPAY_TERMINAL         = '1000OlamoneyTl';
    const PAYTM_RAZORPAY_TERMINAL            = '1000PaytmTrmnl';
    const PAYZAPP_RAZORPAY_TERMINAL          = '100PayzappTmnl';
    const PAYUMONEY_RAZORPAY_TERMINAL        = '100PayumnyTmnl';
    const FREECHARGE_RAZORPAY_TERMINAL       = '100FrchrgeTmnl';
    const BAJAJ_RAZORPAY_TERMINAL            = '100BajajTrminl';
    const EGHL_SHARED_TERMINAL               = 'LlTeg1IYN1MFwc';
    const SHARP_RAZORPAY_TERMINAL            = '1000SharpTrmnl';
    const CYBERSOURCE_HDFC_TERMINAL          = '1000CybrsTrmnl';
    const CYBERSOURCE_HDFC_TERMINAL_WITHOUT_SECRET2  = '1000CybesTrmnl';
    const CYBERSOURCE_AXIS_TERMINAL          = '1000CybAxTrmnl';
    const HITACHI_TERMINAL                   = '100HitachiTmnl';
    const HITACHI_DIRECT_TERMINAL            = '100HitaDirTmnl';
    const FULCRUM_DIRECT_TERMINAL            = '100FulcDirTmnl';
    const FIRST_DATA_RAZORPAY_TERMINAL       = '1000FrstDataTl';
    const PAYSECURE_RAZORPAY_TERMINAL        = '1000PaySecurTl';
    const UPI_MINDGATE_RAZORPAY_TERMINAL     = '100UPIMindgate';
    const UPI_MINDGATE_BQR_TERMINAL          = '100UPIMndgBqrT';
    const UPI_MINDGATE_INTENT_TERMINAL       = '1UpiIntMndgate';
    const UPI_AXIS_INTENT_TERMINAL           = 'UPIAXISIntTmnl';
    const UPI_MINDGATE_TPV_TERMINAL          = '100UPIMndgtTpv';
    const UPI_MINDGATE_SBI_RAZORPAY_TERMINAL = '100UPIMgateSbi';
    const UPI_MINDGATE_SBI_INTENT_TERMINAL   = '100UPIIntMgSbi';
    const UPI_MINDGATE_RECURRING_TERMINAL    = '100MgateRcrTml';
    const UPI_MINDGATE_RECURRING_TERMINAL_DEDICATED = '101MgateRcrTml';
    const UPI_ICICI_RAZORPAY_TERMINAL        = '100UPIICICITml';
    const UPI_ICICI_RECURRING_TERMINAL       = '100IciciRcrTml';
    const UPI_ICICI_RECURRING_TERMINAL_DEDICATED = '101IciciRcrTml';
    const UPI_ICICI_RECURRING_INTENT_TERMINAL_DEDICATED = '103IciciRcrTml';
    const UPI_ICICI_TERMINAL_DEDICATED       = '102IciciDedTml';
    const UPI_ICICI_RECURRING_INTENT_TERMINAL= '1IcicRcrIntTml';
    const UPI_ICICI_RECURRING_TPV_TERMINAL   = '101UPIICTpvTml';
    const UPI_ICICI_TPV_TERMINAL             = '100UPIICTpvTml';
    const UPI_AXIS_RAZORPAY_TERMINAL         = '100UPIAXISTmnl';
    const UPI_RBL_RAZORPAY_TERMINAL          = '100UPIRBLTrmnl';
    const UPI_AXIS_TPV_RAZORPAY_TERMINAL     = '100UPIAXISTpvl';
    const UPI_RBL_RAZORPAY_INTENT_TERMINAL   = '100UPIRBLITrnl';
    const UPI_HULK_RAZORPAY_TERMINAL         = '100UPIHulkTrml';
    const UPI_HULK_RAZORPAY_INTENT_TERMINAL  = '1UPIInHulkTrml';
    const UPI_HULK_RAZORPAY_TPV_TERMINAL     = '1UPITpvHulkTml';
    const UPI_ICICI_INTENT_TERMINAL          = '1UpiIntICICTml';
    const UPI_ICICI_VPA_TERMINAL             = '100UpiIciciVpa';
    const UPI_YESBANK_RAZORPAY_TERMINAL      = '100UpiYesbankT';
    const UPI_YESBANK_INTENT_TERMINAL        = '1UpiYesbankTml';
    const AEPS_ICICI_RAZORPAY_TERMINAL       = '1000AepsShared';
    const AIRTELMONEY_RAZORPAY_TERMINAL      = '100ArtlMnyTmnl';
    const AMAZONPAY_RAZORPAY_TERMINAL        = '100AmznpayTmnl';
    const JIOMONEY_RAZORPAY_TERMINAL         = '1000JioMnyTmnl';
    const SBIBUDDY_RAZORPAY_TERMINAL         = '1000SbibdyTmnl';
    const OPENWALLET_RAZORPAY_TERMINAL       = '100OpenwalltTl';
    const RAZORPAYWALLET_RAZORPAY_TERMINAL   = 'RzrpywlltTrmnl';
    const MPESA_RAZORPAY_TERMINAL            = '100VodaMpesaTl';
    const FSS_RAZORPAY_TERMINAL              = '100FssTerminal';
    const HITACHI_MOTO_TERMINAL              = '10hitachMotoTl';
    const ENSTAGE_TERMINAL                   = '100ensgageTrml';
    const CSB_TPV_TERMINAL                   = '1000csbtpvTrml';
    const CARDLESS_EMI_RAZORPAY_TERMINAL     = '1CrdlesEmiTrml';
    const CARDLESS_EMI_RAZORPAY_TERMINAL2    = '10CrdlesEmiTml';
    const CARDLESS_EMI_FLEXMONEY_TERMINAL    = '20CrdlesEmiTml';
    const CARDLESS_EMI_ZESTMONEY_TERMINAL    = '30CrdlesEmiTml';
    const CARDLESS_EMI_WALNUT369_TERMINAL    = '40CrdlesEmiTml';
    const CARDLESS_EMI_SEZZLE_TERMINAL       = '50CrdlesEmiTml';
    const PAYLATER_EPAYLATER_TERMINAL        = '10PayLaterTrml';
    const PAYLATER_ICICI_TERMINAL            = '10PLaterIciTml';
    const PAYLATER_FLEXMONEY_TERMINAL        = '10PLaterFlxTml';
    const PAYLATER_LAZYPAY_TERMINAL          = '10PLaterLPTmnl';
    const ALLA_TPV_TERMINAL                  = '1000alltpvTrml';
    const IDFB_TPV_TERMINAL                  = '100idfctpvTrml';
    const UPI_MINDGATE_INTENT_TPV_TERMINAL   = 'UPIMGTEIntTpvl';
    const UPI_AIRTEL_RAZORPAY_TERMINAL       = '100UPIArtlTmnl';
    const UPI_AIRTEL_INTENT_TERMINAL         = '10UpiIntAirtel';
    const UPI_KOTAK_RAZORPAY_TERMINAL        = '100UPIKotaTmnl';
    const UPI_AXISOLIVE_TPV_TERMINAL         = '100UPIAOTpvTml';
    const UPI_RZPRBL_TERMINAL                = '100UPIRpRlTmnl';
    const UPI_CITI_RAZORPAY_TERMINAL         = '100UPICitiTmnl';
    const WORLDLINE_TERMINAL                 = '1000WldlineTml';
    const UPI_JUSPAY_TERMINAL                = '100UpiJsPayTml';
    const CRED_TERMINAL                      = '100DiCreDTrmnl';
    const TWID_TERMINAL                      = '100ApTwiDTrmnl';
    const NETBANKING_FSB_TERMINAL            = '1000NbFsbTrmnl';
    const CARDLESS_EMI_FLEXMONEY_MULTILENDER_TERMINAL    = '20CrdlsEmiMlTl';
    const CARDLESS_EMI_FLEXMONEY_EMPTY_ENABLED_BANKS     = '20CrdlsEmiEmTl';
    const NETBANKING_AUSF_TERMINAL           = '100NbAusfTrmnl';
    const NETBANKING_DLB_TERMINAL            = '1000NbDlbTrmnl';
    const NETBANKING_TMB_TERMINAL            = '1000NbTmbTrmnl';
    const NETBANKING_KARNATAKA_TERMINAL      = '100NbKarbTrmnl';
    const NETBANKING_NSDL_TERMINAL           = '1000NbNsdlTrmnl';
    const BILLDESK_SIHUB_RAZORPAY_TERMINAL   = '100BdSihubTrml';
    const MANDATE_HQ_RAZORPAY_TERMINAL       = '1000ManhqTrmnl';
    const NETBANKING_DBS_TERMINAL            = '1000NbDbsTrmnl';
    const RUPAY_SIHUB_RAZORPAY_TERMINAL      = '1000RupSiTrmnl';
    const NETBANKING_SARASWAT_TERMINAL       = '1000NbSrcbTrmnl';
    const UPI_ICICI_DEDICATED_TERMINAL       = '102IciciDedTml';
    const UPI_YESBANK_DEDICATED_TERMINAL     = '100YesDedTrmnl';
    const UPI_LIVE_YESBANK_DEDICATED_TERMINAL= '100YesLivTrmnl';

    protected static $shared = array(
        self::ATOM_RAZORPAY_TERMINAL,
        self::AXIS_MIGS_RAZORPAY_TERMINAL,
        self::AXIS_GENIUS_RAZORPAY_TERMINAL,
        self::BILLDESK_RAZORPAY_TERMINAL,
        self::BLADE_RAZORPAY_TERMINAL,
        self::DIGIO_RAZORPAY_TERMINAL,
        self::LEGALDESK_RAZORPAY_TERMINAL,
        self::EBS_RAZORPAY_TERMINAL,
        self::ENACH_RBL_RAZORPAY_TERMINAL,
        self::HDFC_RAZORPAY_TERMINAL,
        self::MOBIKWIK_RAZORPAY_TERMINAL,
        self::OLAMONEY_RAZORPAY_TERMINAL,
        self::PAYTM_RAZORPAY_TERMINAL,
        self::NETBANKING_IDFC_TERMINAL,
        self::NETBANKING_HDFC_TERMINAL,
        self::NETBANKING_BOB_TERMINAL,
        self::NETBANKING_VIJAYA_TERMINAL,
        self::NETBANKING_KOTAK_TERMINAL,
        self::NETBANKING_ICICI_TERMINAL,
        self::NETBANKING_AIRTEL_TERMINAL,
        self::NETBANKING_ALLAHABAD_TERMINAL,
        self::NETBANKING_AXIS_TERMINAL,
        self::NETBANKING_FEDERAL_TERMINAL,
        self::NETBANKING_RBL_TERMINAL,
        self::NETBANKING_INDUSIND_TERMINAL,
        self::NETBANKING_CANARA_TERMINAL,
        self::NETBANKING_PNB_TERMINAL,
        self::NETBANKING_ESFB_TERMINAL,
        self::NETBANKING_SBI_TERMINAL,
        self::NETBANKING_KVB_TERMINAL,
        self::NETBANKING_SVC_TERMINAL,
        self::NETBANKING_IDBI_TERMINAL,
        self::NETBANKING_FSB_TERMINAL,
        self::NETBANKING_DCB_TERMINAL,
        self::NETBANKING_UJVN_TERMINAL,
        self::NETBANKING_AUSF_TERMINAL,
        self::NETBANKING_NSDL_TERMINAL,
        self::PAYZAPP_RAZORPAY_TERMINAL,
        self::PAYUMONEY_RAZORPAY_TERMINAL,
        self::FREECHARGE_RAZORPAY_TERMINAL,
        self::BAJAJ_RAZORPAY_TERMINAL,
        self::SHARP_RAZORPAY_TERMINAL,
        self::CYBERSOURCE_HDFC_TERMINAL,
        self::CYBERSOURCE_AXIS_TERMINAL,
        self::HITACHI_TERMINAL,
        self::FIRST_DATA_RAZORPAY_TERMINAL,
        self::PAYSECURE_RAZORPAY_TERMINAL,
        self::UPI_MINDGATE_RAZORPAY_TERMINAL,
        self::UPI_MINDGATE_TPV_TERMINAL,
        self::UPI_ICICI_RAZORPAY_TERMINAL,
        self::UPI_AXIS_RAZORPAY_TERMINAL,
        self::UPI_AXIS_TPV_RAZORPAY_TERMINAL,
        self::UPI_HULK_RAZORPAY_TERMINAL,
        self::UPI_MINDGATE_SBI_RAZORPAY_TERMINAL,
        self::UPI_MINDGATE_SBI_INTENT_TERMINAL,
        self::AEPS_ICICI_RAZORPAY_TERMINAL,
        self::AIRTELMONEY_RAZORPAY_TERMINAL,
        self::AMAZONPAY_RAZORPAY_TERMINAL,
        self::JIOMONEY_RAZORPAY_TERMINAL,
        self::SBIBUDDY_RAZORPAY_TERMINAL,
        self::OPENWALLET_RAZORPAY_TERMINAL,
        self::RAZORPAYWALLET_RAZORPAY_TERMINAL,
        self::MPESA_RAZORPAY_TERMINAL,
        self::HITACHI_MOTO_TERMINAL,
        self::ENSTAGE_TERMINAL,
        self::CARDLESS_EMI_RAZORPAY_TERMINAL,
        self::CARDLESS_EMI_FLEXMONEY_TERMINAL,
        self::CARDLESS_EMI_ZESTMONEY_TERMINAL,
        self::WORLDLINE_TERMINAL,
        self::NACH_CITI_TERMINAL,
        self::NACH_ICICI_TERMINAL,
        self::NETBANKING_DLB_TERMINAL,
        self::NETBANKING_TMB_TERMINAL,
        self::NETBANKING_KARNATAKA_TERMINAL,
        self::BILLDESK_SIHUB_RAZORPAY_TERMINAL,
        self::MANDATE_HQ_RAZORPAY_TERMINAL,
        self::NETBANKING_DBS_TERMINAL,
        self::RUPAY_SIHUB_RAZORPAY_TERMINAL,
        self::UPI_ICICI_ONLINE_TERMINAL,
        self::UPI_ICICI_OFFLINE_TERMINAL,
    );

    // NOTE: No two shared terminal should be present for same gateway
    // See getSharedTerminalForGateway() for the reason
    protected static $map = [
        self::AMEX_RAZORPAY_TERMINAL             => Gateway::AMEX,
        self::BLADE_RAZORPAY_TERMINAL            => Gateway::MPI_BLADE,
        self::DIGIO_RAZORPAY_TERMINAL            => Gateway::ESIGNER_DIGIO,
        self::LEGALDESK_RAZORPAY_TERMINAL        => Gateway::ESIGNER_LEGALDESK,
        self::ATOM_RAZORPAY_TERMINAL             => Gateway::ATOM,
        self::AXIS_GENIUS_RAZORPAY_TERMINAL      => Gateway::AXIS_GENIUS,
        self::AXIS_MIGS_RAZORPAY_TERMINAL        => Gateway::AXIS_MIGS,
        self::BILLDESK_RAZORPAY_TERMINAL         => Gateway::BILLDESK,
        self::EBS_RAZORPAY_TERMINAL              => Gateway::EBS,
        self::CYBERSOURCE_HDFC_TERMINAL          => Gateway::CYBERSOURCE,
        self::HDFC_RAZORPAY_TERMINAL             => Gateway::HDFC,
        self::HITACHI_TERMINAL                   => Gateway::HITACHI,
        self::MOBIKWIK_RAZORPAY_TERMINAL         => Gateway::MOBIKWIK,
        self::NETBANKING_IDFC_TERMINAL           => Gateway::NETBANKING_IDFC,
        self::NETBANKING_HDFC_TERMINAL           => Gateway::NETBANKING_HDFC,
        self::NETBANKING_ALLAHABAD_TERMINAL      => Gateway::NETBANKING_ALLAHABAD,
        self::NETBANKING_CANARA_TERMINAL         => Gateway::NETBANKING_CANARA,
        self::NETBANKING_BOB_TERMINAL            => Gateway::NETBANKING_BOB,
        self::NETBANKING_VIJAYA_TERMINAL         => Gateway::NETBANKING_VIJAYA,
        self::NETBANKING_CORPORATION_TERMINAL    => Gateway::NETBANKING_CORPORATION,
        self::NETBANKING_KOTAK_TERMINAL          => Gateway::NETBANKING_KOTAK,
        self::NETBANKING_ICICI_TERMINAL          => Gateway::NETBANKING_ICICI,
        self::NETBANKING_AIRTEL_TERMINAL         => Gateway::NETBANKING_AIRTEL,
        self::NETBANKING_AXIS_TERMINAL           => Gateway::NETBANKING_AXIS,
        self::NETBANKING_FEDERAL_TERMINAL        => Gateway::NETBANKING_FEDERAL,
        self::NETBANKING_RBL_TERMINAL            => Gateway::NETBANKING_RBL,
        self::NETBANKING_INDUSIND_TERMINAL       => Gateway::NETBANKING_INDUSIND,
        self::NETBANKING_PNB_TERMINAL            => Gateway::NETBANKING_PNB,
        self::NETBANKING_SBI_TERMINAL            => Gateway::NETBANKING_SBI,
        self::NETBANKING_ESFB_TERMINAL           => Gateway::NETBANKING_EQUITAS,
        self::NETBANKING_KVB_TERMINAL            => Gateway::NETBANKING_KVB,
        self::NETBANKING_SVC_TERMINAL            => Gateway::NETBANKING_SVC,
        self::NETBANKING_DCB_TERMINAL            => Gateway::NETBANKING_DCB,
        self::NETBANKING_UJVN_TERMINAL           => Gateway::NETBANKING_UJJIVAN,
        self::NETBANKING_JSB_TERMINAL            => Gateway::NETBANKING_JSB,
        self::NETBANKING_IDBI_TERMINAL           => Gateway::NETBANKING_IDBI,
        self::NETBANKING_IOB_TERMINAL            => Gateway::NETBANKING_IOB,
        self::NETBANKING_FSB_TERMINAL            => Gateway::NETBANKING_FSB,
        self::NETBANKING_AUSF_TERMINAL           => Gateway::NETBANKING_AUSF,
        self::NETBANKING_NSDL_TERMINAL           => Gateway::NETBANKING_NSDL,
        self::OLAMONEY_RAZORPAY_TERMINAL         => Gateway::WALLET_OLAMONEY,
        self::PAYTM_RAZORPAY_TERMINAL            => Gateway::PAYTM,
        self::PAYZAPP_RAZORPAY_TERMINAL          => Gateway::WALLET_PAYZAPP,
        self::PAYUMONEY_RAZORPAY_TERMINAL        => Gateway::WALLET_PAYUMONEY,
        self::AIRTELMONEY_RAZORPAY_TERMINAL      => Gateway::WALLET_AIRTELMONEY,
        self::AMAZONPAY_RAZORPAY_TERMINAL        => Gateway::WALLET_AMAZONPAY,
        self::FREECHARGE_RAZORPAY_TERMINAL       => Gateway::WALLET_FREECHARGE,
        self::BAJAJ_RAZORPAY_TERMINAL            => Gateway::WALLET_BAJAJ,
        self::JIOMONEY_RAZORPAY_TERMINAL         => Gateway::WALLET_JIOMONEY,
        self::SBIBUDDY_RAZORPAY_TERMINAL         => Gateway::WALLET_SBIBUDDY,
        self::SHARP_RAZORPAY_TERMINAL            => Gateway::SHARP,
        self::FIRST_DATA_RAZORPAY_TERMINAL       => Gateway::FIRST_DATA,
        self::PAYSECURE_RAZORPAY_TERMINAL        => Gateway::PAYSECURE,
        self::AEPS_ICICI_RAZORPAY_TERMINAL       => Gateway::AEPS_ICICI,
        self::UPI_MINDGATE_RAZORPAY_TERMINAL     => Gateway::UPI_MINDGATE,
        self::UPI_MINDGATE_SBI_RAZORPAY_TERMINAL => Gateway::UPI_SBI,
        self::UPI_MINDGATE_SBI_INTENT_TERMINAL   => Gateway::UPI_SBI,
        self::UPI_ICICI_RAZORPAY_TERMINAL        => Gateway::UPI_ICICI,
        self::UPI_AXIS_RAZORPAY_TERMINAL         => Gateway::UPI_AXIS,
        self::UPI_AXIS_TPV_RAZORPAY_TERMINAL     => Gateway::UPI_AXIS,
        self::UPI_HULK_RAZORPAY_TERMINAL         => Gateway::UPI_HULK,
        self::UPI_JUSPAY_TERMINAL                => Gateway::UPI_JUSPAY,
        self::OPENWALLET_RAZORPAY_TERMINAL       => Gateway::WALLET_OPENWALLET,
        self::RAZORPAYWALLET_RAZORPAY_TERMINAL   => Gateway::WALLET_RAZORPAYWALLET,
        self::MPESA_RAZORPAY_TERMINAL            => Gateway::WALLET_MPESA,
        self::HITACHI_MOTO_TERMINAL              => Gateway::HITACHI,
        self::ENSTAGE_TERMINAL                   => Gateway::MPI_ENSTAGE,
        self::CARDLESS_EMI_RAZORPAY_TERMINAL     => Gateway::CARDLESS_EMI,
        self::CARDLESS_EMI_FLEXMONEY_TERMINAL    => Gateway::CARDLESS_EMI,
        self::CARDLESS_EMI_ZESTMONEY_TERMINAL    => Gateway::CARDLESS_EMI,
        self::PAYLATER_EPAYLATER_TERMINAL        => Gateway::PAYLATER,
        self::PAYLATER_ICICI_TERMINAL            => Gateway::PAYLATER_ICICI,
        self::WORLDLINE_TERMINAL                 => Gateway::WORLDLINE,
        self::NACH_CITI_TERMINAL                 => Gateway::NACH_CITI,
        self::NACH_ICICI_TERMINAL                => Gateway::NACH_ICICI,
        self::NETBANKING_DLB_TERMINAL            => Gateway::NETBANKING_DLB,
        self::NETBANKING_TMB_TERMINAL            => Gateway::NETBANKING_TMB,
        self::NETBANKING_KARNATAKA_TERMINAL      => Gateway::NETBANKING_KARNATAKA,
        self::BILLDESK_SIHUB_RAZORPAY_TERMINAL   => Gateway::BILLDESK_SIHUB,
        self::MANDATE_HQ_RAZORPAY_TERMINAL       => Gateway::MANDATE_HQ,
        self::NETBANKING_DBS_TERMINAL            => Gateway::NETBANKING_DBS,
        self::RUPAY_SIHUB_RAZORPAY_TERMINAL      => Gateway::RUPAY_SIHUB,
        self::NETBANKING_SARASWAT_TERMINAL       => Gateway::NETBANKING_SARASWAT,
        self::UPI_ICICI_ONLINE_TERMINAL          => Gateway::UPI_ICICI,
        self::UPI_ICICI_OFFLINE_TERMINAL         => Gateway::UPI_ICICI,
    ];

    public static function getSharedTerminalMapping()
    {
        return self::$map;
    }

    public static function getGatewayForTerminal($terminal)
    {
        return self::$map[$terminal];
    }
}
