<?php

namespace RZP\Mail\Gateway\RefundFile;

use RZP\Mail\Base;
use RZP\Constants\MailTags;
use RZP\Models\Payment\Gateway;

class Constants extends Base\Constants
{
    const RECIPIENT_EMAILS_MAP = [
        Gateway::ICICI_EMI              => ["icicicards.emi@razorpay.com","settlements@razorpay.com"],
        Gateway::NETBANKING_HDFC        => ['Directpay.Refunds@hdfcbank.com', 'settlements@razorpay.com'],
        Gateway::NETBANKING_ICICI       => ['icici.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
        Gateway::NETBANKING_IBK         => ['refunds@razorpay.com', 'settlements@razorpay.com'],
        Gateway::NETBANKING_CBI         => ['cbi.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
        Gateway::NETBANKING_CORPORATION => ['corporation.netbanking.refunds@razorpay.com'],
        Gateway::NETBANKING_FEDERAL     => ['settlements@razorpay.com'],
        Gateway::NETBANKING_BOB         => ['bob.netbanking.refunds@razorpay.com'],
        Gateway::NETBANKING_RBL         => ['settlements@razorpay.com'],
        Gateway::NETBANKING_INDUSIND    => ['settlements@razorpay.com'],
        Gateway::NETBANKING_ALLAHABAD   => ['settlements@razorpay.com'],
        Gateway::NETBANKING_CANARA      => ['hosettlement@canarabank.com', 'hodbspg@canarabank.com'],
        Gateway::NETBANKING_IDFC        => ['settlements@razorpay.com'],
        Gateway::UPI_ICICI              => ['settlements@razorpay.com'],
        Gateway::WALLET_AIRTELMONEY     => ['settlements@razorpay.com'],
        Gateway::WALLET_PAYUMONEY       => ['settlements@razorpay.com'],
        Gateway::PAYLATER_ICICI         => ['icici.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
        Gateway::NETBANKING_JSB         => ['jsb-netbanking.refunds@razorpay.com','settlements@razorpay.com'],
    ];

    const HEADER_MAP = [
        Gateway::ICICI_EMI              => 'Icici Emi Refunds File',
        Gateway::NETBANKING_HDFC        => 'Hdfc Netbanking refunds',
        Gateway::NETBANKING_CORPORATION => 'Corporation Netbanking refunds',
        Gateway::NETBANKING_ICICI       => 'Icici Netbanking refunds',
        Gateway::NETBANKING_IBK         => 'Indian Bank Netbanking refunds',
        Gateway::NETBANKING_CBI         => 'Cbi Netbanking refunds',
        Gateway::NETBANKING_FEDERAL     => 'Federal Netbanking refunds',
        Gateway::NETBANKING_BOB         => 'Bank of Baroda Netbanking refunds',
        Gateway::NETBANKING_IDFC        => 'Idfc Netbanking Refunds',
        Gateway::NETBANKING_RBL         => 'RBL Netbanking refunds',
        Gateway::NETBANKING_INDUSIND    => 'Indusind Netbanking refunds',
        Gateway::NETBANKING_ALLAHABAD   => 'Allahabad Netbanking refunds',
        Gateway::NETBANKING_CANARA      => 'Canara Netbanking refunds',
        Gateway::UPI_ICICI              => 'UPI Icici Refunds',
        Gateway::WALLET_AIRTELMONEY     => 'Wallet Airtelmoney refunds',
        Gateway::WALLET_PAYUMONEY       => 'Wallet Payumoney refunds',
        Gateway::ISG                    => 'Isg refunds',
        Gateway::PAYLATER_ICICI         => 'Icici Paylater refunds',
        Gateway::UPI_SBI                => 'UPI SBI Refunds',
        Gateway::UPI_AIRTEL             => 'UPI AIRTEL Refunds',
        Gateway::UPI_YESBANK            => 'UPI YESBANK Refunds',

    ];

    const SUBJECT_MAP = [
        Gateway::ICICI_EMI              => 'Icici Emi refund file for ',
        Gateway::NETBANKING_CORPORATION => 'Corporation Netbanking refunds file for ',
        Gateway::NETBANKING_ALLAHABAD   => 'Allahabad Netbanking refunds file for ',
        Gateway::NETBANKING_HDFC        => 'HDFC Netbanking refunds file for ',
        Gateway::NETBANKING_ICICI       => 'Icici Netbanking refunds file for ',
        Gateway::NETBANKING_IBK         => 'Indian Bank Netbanking refunds file for ',
        Gateway::NETBANKING_CBI         => 'Cbi Netbanking refunds file for ',
        Gateway::NETBANKING_FEDERAL     => 'Federal Netbanking refunds file for ',
        Gateway::NETBANKING_BOB         => 'Bank of Baroda Netbanking refunds file for ',
        Gateway::NETBANKING_IDFC        => 'Idfc Netbanking refunds file for ',
        Gateway::NETBANKING_INDUSIND    => 'Indusind Netbanking refunds file for ',
        Gateway::NETBANKING_CANARA      => 'Canara Netbanking refunds file for',
        Gateway::UPI_ICICI              => 'UPI Icici refunds file for ',
        Gateway::WALLET_AIRTELMONEY     => 'Airtelmoney refunds file for ',
        Gateway::WALLET_PAYUMONEY       => 'PayUMoney refunds file for ',
        Gateway::NETBANKING_RBL         => 'RBL Netbanking refunds file for ',
        Gateway::ISG                    => 'Isg refunds file for ',
        Gateway::PAYLATER_ICICI         => 'Icici Paylater refunds file for ',
        Gateway::UPI_SBI                => 'UPI SBI refunds file',
        Gateway::UPI_AIRTEL             => 'UPI AIRTEL refunds file',
        Gateway::UPI_YESBANK            => 'UPI YESBANK refunds file',
    ];

    const MAILTAG_MAP = [
        Gateway::ICICI_EMI              => MailTags::ICICI_EMI_REFUNDS_MAIL,
        Gateway::NETBANKING_CORPORATION => MailTags::CORPORATION_NETBANKING_REFUNDS_MAIL,
        Gateway::NETBANKING_ALLAHABAD   => MailTags::ALLAHABAD_NETBANKING_REFUNDS_MAIL,
        Gateway::NETBANKING_HDFC        => MailTags::HDFC_NETBANKING_REFUNDS_MAIL,
        Gateway::NETBANKING_BOB         => MailTags::BOB_NETBANKING_REFUNDS_MAIL,
        Gateway::NETBANKING_ICICI       => MailTags::ICICI_NETBANKING_REFUNDS_MAIL,
        Gateway::PAYLATER_ICICI         => MailTags::ICICI_PAYLATER_REFUNDS_MAIL,
        Gateway::NETBANKING_IBK         => MailTags::INDIAN_BANK_NETBANKING_REFUNDS_MAIL,
        Gateway::NETBANKING_CBI         => MailTags::CBI_NETBANKING_REFUNDS_MAIL,
        Gateway::NETBANKING_CANARA      => MailTags::CANARA_NETBANKING_REFUNDS_MAIL,
        Gateway::NETBANKING_IDFC        => MailTags::IDFC_NETBANKING_REFUNDS_MAIL,
        Gateway::UPI_ICICI              => MailTags::ICICI_UPI_REFUNDS_MAIL,
        Gateway::WALLET_AIRTELMONEY     => MailTags::AIRTEL_MONEY_REFUNDS_MAIL,
        Gateway::WALLET_PAYUMONEY       => MailTags::PAYU_MONEY_REFUNDS_MAIL,
        Gateway::ISG                    => MailTags::ISG_REFUNDS_MAIL,
        Gateway::UPI_SBI                => MailTags::UPI_SBI_REFUNDS_MAIL,
        Gateway::UPI_AIRTEL             => MailTags::UPI_AIRTEL_REFUNDS_MAIL,
        Gateway::UPI_YESBANK            => MailTags::UPI_YESBANK_REFUNDS_MAIL,
    ];

    const BODY_MAP = [
        Gateway::ICICI_EMI              => 'Please find the file details below',
        Gateway::NETBANKING_CORPORATION => 'Please find attached refunds information for Corporation Netbanking',
        Gateway::NETBANKING_ALLAHABAD   => 'Please find attached refunds information for Allahabad Netbanking',
      // @codingStandardsIgnoreLine
        Gateway::NETBANKING_HDFC        => 'Please forward the HDFC Netbanking refunds file to: Directpay.Refunds@hdfcbank.com',
        Gateway::NETBANKING_BOB         => 'Please find attached refunds information for Bank of Baroda',
        Gateway::NETBANKING_ICICI       => 'Please forward the ICICI Netbanking refunds file to UBPS operations team',
        Gateway::PAYLATER_ICICI         => 'Please forward the ICICI Paylater refunds file to UBPS operations team',
        Gateway::NETBANKING_CANARA      => 'Please find attached refunds information for Canara Bank',
        Gateway::UPI_ICICI              => 'Please find attached refunds information for UPI',
        Gateway::NETBANKING_IBK         => 'Please find attached refunds information for Indian Bank',
        Gateway::NETBANKING_IDFC        => 'Please find attached refunds information for Idfc',
        Gateway::WALLET_AIRTELMONEY     => 'Please find attached refunds information for AirtelMoney',
        Gateway::WALLET_PAYUMONEY       => 'Please find attached refunds information for PayUMoney',
        Gateway::ISG                    => 'Please find attached refunds file for Isg',
        Gateway::UPI_SBI                => 'Please find attached refunds file for UPI SBI',
        Gateway::UPI_AIRTEL             => 'Please find attached refunds file for UPI AIRTEL',
        Gateway::UPI_YESBANK            => 'Please find attached refunds file for UPI YESBANK',
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::ICICI_EMI              => 'emails.message',
        Gateway::NETBANKING_HDFC        => 'emails.message',
        Gateway::NETBANKING_IDFC        => 'emails.message',
        Gateway::NETBANKING_BOB         => 'emails.message',
        Gateway::NETBANKING_CBI         => 'emails.message',
        Gateway::NETBANKING_CORPORATION => 'emails.message',
        Gateway::NETBANKING_ALLAHABAD   => 'emails.message',
        Gateway::NETBANKING_ICICI       => 'emails.admin.icici_refunds',
        Gateway::PAYLATER_ICICI         => 'emails.admin.paylater_icici_refunds',
        Gateway::NETBANKING_IBK         => 'emails.admin.ibk_refunds',
        Gateway::UPI_ICICI              => 'emails.message',
        Gateway::WALLET_AIRTELMONEY     => 'emails.message',
        Gateway::WALLET_PAYUMONEY       => 'emails.message',
        GATEWAY::NETBANKING_CANARA      => 'emails.message',
        Gateway::ISG                    => 'emails.message',
        Gateway::UPI_SBI                => 'emails.message',
        Gateway::UPI_AIRTEL             => 'emails.message',
        Gateway::UPI_YESBANK            => 'emails.message',
    ];
}
