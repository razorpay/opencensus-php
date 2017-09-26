<?php

namespace RZP\Mail\Gateway\RefundFile;

use RZP\Constants\MailTags;
use RZP\Mail\Base;
use RZP\Models\Payment\Gateway;


class Constants extends Base\Constants
{
    const RECIPIENT_EMAILS_MAP = [
        Gateway::NETBANKING_HDFC     => ['Directpay.Refunds@hdfcbank.com', 'settlements@razorpay.com'],
        Gateway::NETBANKING_ICICI    => ['icici.netbanking.refunds@razorpay.com', 'settlements@razorpay.com'],
        Gateway::NETBANKING_FEDERAL  => ['settlements@razorpay.com'],
        Gateway::NETBANKING_BOB      => ['settlements@razorpay.com'],
        Gateway::NETBANKING_RBL      => ['settlements@razorpay.com'],
        Gateway::NETBANKING_INDUSIND => ['settlements@razorpay.com'],
        Gateway::UPI_ICICI           => ['settlements@razorpay.com'],
        Gateway::WALLET_AIRTELMONEY  => ['settlements@razorpay.com'],
        Gateway::WALLET_PAYUMONEY    => ['settlements@razorpay.com']
    ];

    const HEADER_MAP = [
        Gateway::NETBANKING_HDFC     => 'Hdfc Netbanking refunds',
        Gateway::NETBANKING_BOB      => 'Bank of Baroda Netbanking refunds',
        Gateway::NETBANKING_ICICI    => 'Icici Netbanking refunds',
        Gateway::NETBANKING_FEDERAL  => 'Federal Netbanking refunds',
        Gateway::NETBANKING_RBL      => 'RBL Netbanking refunds',
        Gateway::NETBANKING_INDUSIND => 'Indusind Netbanking refunds',
        Gateway::UPI_ICICI           => 'UPI Icici Refunds',
        Gateway::WALLET_AIRTELMONEY  => 'Wallet Airtelmoney refunds',
        Gateway::WALLET_PAYUMONEY    => 'Wallet Payumoney refunds'
    ];

    const SUBJECT_MAP = [
        Gateway::NETBANKING_HDFC     => 'HDFC Netbanking refunds file for ',
        Gateway::NETBANKING_BOB      => 'Bank of Baroda Netbanking refunds file for ',
        Gateway::NETBANKING_ICICI    => 'Icici Netbanking refunds file for ',
        Gateway::NETBANKING_FEDERAL  => 'Federal Netbanking refunds file for ',
        Gateway::NETBANKING_INDUSIND => 'Indusind Netbanking refunds file for ',
        Gateway::UPI_ICICI           => 'UPI Icici refunds file for ',
        Gateway::WALLET_AIRTELMONEY  => 'Airtelmoney refunds file for ',
        Gateway::WALLET_PAYUMONEY    => 'PayUMoney refunds file for ',
        Gateway::NETBANKING_RBL      => 'RBL Netbanking refunds file for ',
    ];

    const MAILTAG_MAP = [
        Gateway::NETBANKING_HDFC     => MailTags::HDFC_NETBANKING_REFUNDS_MAIL,
        Gateway::NETBANKING_BOB      => MailTags::BOB_NETBANKING_REFUNDS_MAIL,
        Gateway::NETBANKING_ICICI    => MailTags::ICICI_NETBANKING_REFUNDS_MAIL,
        Gateway::UPI_ICICI           => MailTags::ICICI_UPI_REFUNDS_MAIL,
        Gateway::WALLET_AIRTELMONEY  => MailTags::AIRTEL_MONEY_REFUNDS_MAIL,
        Gateway::WALLET_PAYUMONEY    => MailTags::PAYU_MONEY_REFUNDS_MAIL,
    ];

    const BODY_MAP = [
        Gateway::NETBANKING_HDFC     => 'Please forward the HDFC Netbanking refunds file to: Directpay.Refunds@hdfcbank.com',
        Gateway::NETBANKING_BOB      => 'Please find attached refunds information for Bank of Baroda',
        Gateway::NETBANKING_ICICI    => 'Please forward the ICICI Netbanking refunds file to UBPS operations team',
        Gateway::UPI_ICICI           => 'Please find attached refunds information for UPI',
        Gateway::WALLET_AIRTELMONEY  => 'Please find attached refunds information for AirtelMoney',
        Gateway::WALLET_PAYUMONEY    => 'Please find attached refunds information for PayUMoney',
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::NETBANKING_HDFC     => 'emails.message',
        Gateway::NETBANKING_BOB      => 'emails.message',
        Gateway::NETBANKING_ICICI    => 'emails.admin.icici_refunds',
        Gateway::UPI_ICICI           => 'emails.message',
        Gateway::WALLET_AIRTELMONEY  => 'emails.message',
        Gateway::WALLET_PAYUMONEY    => 'emails.message',
    ];
}
