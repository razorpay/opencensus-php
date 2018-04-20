<?php

namespace RZP\Mail\Gateway\FailedRefund;

use RZP\Mail\Base;
use RZP\Constants\MailTags;
use RZP\Models\Gateway\File\Constants as Target;

class Constants extends Base\Constants
{
    const HEADER_MAP = [
        'All'                    => 'Failed Refunds',
        Target::UPI_ICICI        => 'UPI Icici Failed Refunds',
        Target::UPI_MINDGATE     => 'UPI Mindgate Failed Refunds',
        Target::AIRTEL_MONEY     => 'Airtel Money Failed Refunds',
        Target::AXIS_MIGS        => 'Axis Migs Failed Refunds',
        Target::ICIC_FIRST_DATA  => 'ICICI FirstData Failed Refunds',
        Target::HDFC_CYBERSOURCE => 'HDFC Cybersource Failed Refunds',
        Target::HDFC_FSS         => 'HDFC FSS Failed Refunds',
        Target::AXIS_CYBERSOURCE => 'Axis Cybersource Failed Refunds ',

    ];

    const SUBJECT_MAP = [
        'All'                    => 'Failed Refunds file for ',
        Target::UPI_ICICI        => 'UPI Icici failed refunds file for ',
        Target::UPI_MINDGATE     => 'UPI Mindgate failed refunds file for ',
        Target::AIRTEL_MONEY     => 'Airtel Money failed refunds file for ',
        Target::AXIS_MIGS        => 'Axis Migs failed refunds for ',
        Target::ICIC_FIRST_DATA  => 'FirstData failed refunds for ',
        Target::HDFC_CYBERSOURCE => 'HDFC Cybersource failed refunds for ',
        Target::HDFC_FSS         => 'HDFC FSS failed refunds for ',
        Target::AXIS_CYBERSOURCE => 'Axis Cybersource failed refunds for ',
    ];

    const BODY_MAP = [
        'All'                    => 'Please find attached failed refunds information.',
        Target::UPI_ICICI        => 'Please find attached failed refunds information for UPI ICICI',
        Target::UPI_MINDGATE     => 'Please find attached failed refunds information for UPI Mindgate',
        Target::AIRTEL_MONEY     => 'Please find attached failed refunds information for Airtel Money',
        Target::AXIS_MIGS        => 'Please process the attached refunds for Axis Migs',
        Target::ICIC_FIRST_DATA  => 'Please process the attached refunds for ICICI FirstData',
        Target::HDFC_CYBERSOURCE => 'Please process the attached refunds for HDFC Cybersource',
        Target::HDFC_FSS         => 'Please process the attached refunds for HDFC FSS',
        Target::AXIS_CYBERSOURCE => 'Please process the attached refunds for Axis Cybersource',
    ];

    const MAIL_TEMPLATE_MAP = [
        'All'                    => 'emails.message',
        Target::UPI_ICICI        => 'emails.message',
        Target::UPI_MINDGATE     => 'emails.message',
        Target::AIRTEL_MONEY     => 'emails.message',
        Target::AXIS_MIGS        => 'emails.message',
        Target::ICIC_FIRST_DATA  => 'emails.message',
        Target::HDFC_CYBERSOURCE => 'emails.message',
        Target::HDFC_FSS         => 'emails.message',
        Target::AXIS_CYBERSOURCE => 'emails.message',
    ];

    const MAILTAG_MAP = [
        'All'                    => MailTags::FAILED_REFUNDS_MAIL,
        Target::UPI_ICICI        => MailTags::ICICI_UPI_FAILED_REFUNDS_MAIL,
        Target::UPI_MINDGATE     => MailTags::MINDGATE_UPI_FAILED_REFUNDS_MAIL,
        Target::AIRTEL_MONEY     => MailTags::AIRTEL_MONEY_FAILED_REFUNDS_MAIL,
        Target::AXIS_MIGS        => MailTags::AXIS_MIGS_FAILED_REFUNDS_MAIL,
        Target::ICIC_FIRST_DATA  => MailTags::ICICI_FIRST_DATA_FAILED_REFUNDS_MAIL,
        Target::HDFC_CYBERSOURCE => MailTags::HDFC_CYBERSOURCE_FAILED_REFUNDS_MAIL,
        Target::HDFC_FSS         => MailTags::HDFC_FSS_FAILED_REFUNDS_MAIL,
        Target::AXIS_CYBERSOURCE => MailTags::AXIS_CYBERSOURCE_FAILED_REFUNDS_MAIL,
    ];
}
