<?php

namespace App\Mailers;

class MailTags
{
    /**
     * Defines tags associated with emails
     */
    const CONFIRM_ACTIVATION_SUBMISSION = 'confirm_activation_submission';
    const NOTIFY_ACTIVATION_SUBMISSION  = 'notify_activation_submission';

    const CONTACT_FORM_SUBMISSION       = 'contact_form_submission';

    const MERCHANT_INVITATION_MAIL      = 'merchant_invitation_mail';
    const MEMBER_INVITATION_MAIL        = 'member_invitation_mail';

    const ACCOUNT_CONFIRMATION_MAIL     = 'account_confirmation_mail';
}
