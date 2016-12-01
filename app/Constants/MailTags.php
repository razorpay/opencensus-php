<?php

namespace RZP\Constants;

class MailTags
{
    /**
     * Defines tags associated with emails
     */
    const KOTAK_BENEFICIARY_MAIL                = 'kotak_beneficiary_mail';

    /**
     * Email tags that should respond to the mailgun failure webhook
     * @var array Email tags
     * @static
     */
    public static $notifyTags = [
        self::KOTAK_BENEFICIARY_MAIL
    ];
}