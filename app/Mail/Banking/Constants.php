<?php

namespace RZP\Mail\Banking;

use RZP\Mail\Base;
use RZP\Constants\MailTags;
use RZP\Models\Settlement\Channel;

class Constants extends Base\Constants
{
    const RECIPIENT_EMAILS_MAP = [
        Channel::KOTAK   => 'kotak.beneficiary@razorpay.com',
        Channel::ICICI   => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
        Channel::AXIS    => 'axis.beneficiary@razorpay.com',
        Channel::HDFC    => 'hdfc.beneficiary@razorpay.com',
        Channel::YESBANK => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
        Channel::AXIS2   => 'axis.beneficiary@razorpay.com',
    ];

    const HEADER_MAP = [
        Channel::KOTAK   => 'Razorpay Kotak Beneficiary File',
        Channel::ICICI   => 'Razorpay ICICI Beneficiary File',
        Channel::AXIS    => 'Razorpay Axis Beneficiary File',
        Channel::YESBANK => 'Razorpay Yesbank Beneficiary',
        Channel::HDFC    => 'Razorpay hdfc Beneficiary',
        Channel::AXIS2   => 'Razorpay Axis2 Beneficiary File',
    ];

    const SUBJECT_MAP = [
        Channel::KOTAK   => 'Razorpay updated beneficiary file for Kotak',
        Channel::ICICI   => 'Razorpay updated beneficiary file for ICICI',
        Channel::AXIS    => 'Razorpay updated beneficiary file for Axis',
        Channel::HDFC    => 'Razorpay updated beneficiary file for HDFC',
        Channel::YESBANK => 'Razorpay updated beneficiary for Yesbank',
        Channel::AXIS2   => 'Razorpay updated beneficiary file for Axis2',
    ];

    const MAILTAG_MAP = [
        Channel::KOTAK   => MailTags::KOTAK_BENEFICIARY_MAIL,
        Channel::ICICI   => MailTags::ICICI_BENEFICIARY_MAIL,
        Channel::AXIS    => MailTags::AXIS_BENEFICIARY_MAIL,
        Channel::AXIS2   => MailTags::AXIS_BENEFICIARY_MAIL,
        Channel::HDFC    => MailTags::HDFC_BENEFICIARY_MAIL,
        Channel::YESBANK => MailTags::YESBANK_BENEFICIARY_MAIL,
    ];

    const FROM_EMAIL_MAP = [
        Channel::KOTAK   => 'kotak_beneficiary_file@razorpay.com',
        Channel::ICICI   => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
        Channel::AXIS    => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
        Channel::AXIS2   => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
        Channel::YESBANK => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
        Channel::HDFC    => Constants::MAIL_ADDRESSES[self::SETTLEMENTS],
    ];
}
