<?php

namespace RZP\Models\Merchant;

class Action
{
    const ARCHIVE                                 = 'archive';
    const UNARCHIVE                               = 'unarchive';
    const SUSPEND                                 = 'suspend';
    const UNSUSPEND                               = 'unsuspend';
    const CREATED                                 = 'created';
    const ACTIVATED                               = 'activated';
    const LOCK                                    = 'lock';
    const UNLOCK                                  = 'unlock';
    const EDIT_COMMENT                            = 'edit_comment';
    const HOLD_FUNDS                              = 'hold_funds';
    const RELEASE_FUNDS                           = 'release_funds';
    const ENABLE_RECEIPT_EMAILS                   = 'enable_receipt_emails';
    const DISABLE_RECEIPT_EMAILS                  = 'disable_receipt_emails';
    const ENABLE_INTERNATIONAL                    = 'enable_international';
    const DISABLE_INTERNATIONAL                   = 'disable_international';
    const UPDATED                                 = 'updated';
    const SUBMITTED                               = 'submitted';
    const ACTIVATION_PROGRESS                     = 'activation_progress';
    const TEST_KEYS_CREATED                       = 'test_keys_created';
    const LIVE_KEYS_CREATED                       = 'live_keys_created';
    const FORCE_ACTIVATE                          = 'force_activate';
    const SET_RECEIPT_EMAIL_EVENT_AUTHORIZED      = 'set_receipt_email_event_authorized';
    const SET_RECEIPT_EMAIL_EVENT_CAPTURED        = 'set_receipt_email_event_captured';
    const LIVE_DISABLE                            = 'live_disable';
    const LIVE_ENABLE                             = 'live_enable';
    const UPDATE_MAX_PAYMENT_LIMIT                = 'update_max_payment_limit';
    const UPDATE_MAX_INTERNATIONAL_PAYMENT_LIMIT  = 'update_max_international_payment_limit';
    /*
        * in international disabling, there will be two types of disabling: Permanent and Temporary,
        * the only thing difference b/w both will be communication part.
    */
    const DISABLE_INTERNATIONAL_TEMPORARY = 'disable_international_temporary';
    const DISABLE_INTERNATIONAL_PERMANENT = 'disable_international_permanent';
    const DEBIT_NOTE_CREATE_EMAIL_SIGNUP  = 'debit_note_create_email_signup';
    const DEBIT_NOTE_CREATE_MOBILE_SIGNUP = 'debit_note_create_mobile_signup';

    const RISK_ACTIONS_LIST_FOR_SETTING_FRAUD_TYPE = [
        self::SUSPEND,
        self::UNSUSPEND,
        self::HOLD_FUNDS,
        self::RELEASE_FUNDS,
        self::LIVE_DISABLE,
        self::LIVE_ENABLE,
    ];


    public static function exists($action)
    {
        return defined(get_class() . '::' . strtoupper($action));
    }
}
