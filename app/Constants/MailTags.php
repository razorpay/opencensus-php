<?php

namespace RZP\Constants;

class MailTags
{
    /**
     * Defines tags associated with emails
     */
    const KOTAK_BENEFICIARY_MAIL                = 'kotak_beneficiary_mail';
    const HDFC_NETBANKING_REFUNDS_MAIL          = 'hdfc_netbanking_refunds_mail';
    const AXIS_NETBANKING_REFUNDS_MAIL          = 'axis_netbanking_refunds_mail';
    const ICICI_NETBANKING_REFUNDS_MAIL         = 'icici_netbanking_refunds_mail';

    /**
     * Email tags that should respond to the mailgun failure webhook
     * @var array Email tags
     * @static
     */
    public static $notifyTags = [
        self::KOTAK_BENEFICIARY_MAIL,
        self::HDFC_NETBANKING_REFUNDS_MAIL,
        self::AXIS_NETBANKING_REFUNDS_MAIL,
        self::ICICI_NETBANKING_REFUNDS_MAIL
    ];
}
