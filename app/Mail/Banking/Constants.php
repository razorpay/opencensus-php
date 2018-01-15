<?php

namespace RZP\Mail\Banking;

use RZP\Mail\Base;
use RZP\Constants\MailTags;
use RZP\Models\Settlement\Channel;

class Constants extends Base\Constants
{
    const RECIPIENT_EMAILS_MAP = [
        Channel::KOTAK  => 'kotak.beneficiary@razorpay.com',
        Channel::ICICI  => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
        Channel::AXIS   => 'axis.beneficiary@razorpay.com',
    ];

    const HEADER_MAP = [
        Channel::KOTAK  => 'Razorpay Kotak Beneficiary File',
        Channel::ICICI  => 'Razorpay ICICI Beneficiary File',
        Channel::AXIS   => 'Razorpay Axis Beneficiary File',
    ];

    const SUBJECT_MAP = [
        Channel::KOTAK  => 'Razorpay updated beneficiary file for Kotak',
        Channel::ICICI  => 'Razorpay updated beneficiary file for ICICI',
        Channel::AXIS   => 'Razorpay updated beneficiary file for Axis',
    ];

    const MAILTAG_MAP = [
        Channel::KOTAK  => MailTags::KOTAK_BENEFICIARY_MAIL,
        Channel::ICICI  => MailTags::ICICI_BENEFICIARY_MAIL,
        Channel::AXIS   => MailTags::AXIS_BENEFICIARY_MAIL,
    ];

    const FROM_EMAIL_MAP = [
        Channel::KOTAK  => 'kotak_beneficiary_file@razorpay.com',
        Channel::ICICI  => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
        Channel::AXIS   => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
    ];
}