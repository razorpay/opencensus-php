<?php

namespace RZP\Mail\Gateway\FailedRefund;

use RZP\Constants\MailTags;
use RZP\Mail\Base;
use RZP\Models\Payment\Gateway;

class Constants extends Base\Constants
{
    const HEADER_MAP = [
        Gateway::UPI_ICICI          => 'UPI Icici Failed Refunds',
        Gateway::WALLET_AIRTELMONEY => 'Airtel Money Failed Refunds',
        Gateway::AXIS_MIGS          => 'Axis Migs Failed Refunds',
        Gateway::FIRST_DATA         => 'FirstData Failed Refunds',
        Gateway::CYBERSOURCE        => 'Cybersource Failed Refunds',
        Gateway::HDFC               => 'HDFC Failed Refunds',
    ];

    const SUBJECT_MAP = [
        Gateway::UPI_ICICI          => 'UPI Icici Failed refunds file for ',
        Gateway::WALLET_AIRTELMONEY => 'Airtel Money Failed refunds file for ',
        Gateway::AXIS_MIGS          => 'Axis Migs Failed refunds for',
        Gateway::FIRST_DATA         => 'FirstData Failed Refunds for',
        Gateway::CYBERSOURCE        => 'Cybersource Failed Refunds for',
        Gateway::HDFC               => 'HDFC Failed Refunds for',
    ];

    const BODY_MAP = [
        Gateway::UPI_ICICI          => 'Please find attached failed refunds information for ICICI UPI',
        Gateway::WALLET_AIRTELMONEY => 'Please find attached failed refunds information for Airtel Money',
        Gateway::AXIS_MIGS          => 'Please find attached failed refunds information for Axis Migs',
        Gateway::FIRST_DATA         => 'Please find attached failed refunds information for FirstData',
        Gateway::CYBERSOURCE        => 'Please find attached failed refunds information for Cybersource',
        Gateway::HDFC               => 'Please find attached failed refunds information for Hdfc',
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::UPI_ICICI           => 'emails.message',
        Gateway::WALLET_AIRTELMONEY  => 'emails.message',
        Gateway::AXIS_MIGS           => 'emails.message',
        Gateway::FIRST_DATA          => 'emails.message',
        Gateway::CYBERSOURCE         => 'emails.message',
        Gateway::HDFC                => 'emails.message',
    ];

    const MAILTAG_MAP = [
        Gateway::UPI_ICICI           => MailTags::ICICI_UPI_FAILED_REFUNDS_MAIL,
        Gateway::WALLET_AIRTELMONEY  => MailTags::AIRTEL_MONEY_FAILED_REFUNDS_MAIL,
        Gateway::AXIS_MIGS           => MailTags::AXIS_MIGS_FAILED_REFUNDS_MAIL,
        Gateway::FIRST_DATA          => MailTags::FIRST_DATA_FAILED_REFUNDS_MAIL,
        Gateway::CYBERSOURCE         => MailTags::CYBERSOURCE_FAILED_REFUNDS_MAIL,
        Gateway::HDFC                => MailTags::HDFC_FAILED_REFUNDS_MAIL,
    ];
}
