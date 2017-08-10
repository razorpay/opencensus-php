<?php

namespace RZP\Models\Merchant;

class Action
{
    const ARCHIVE                = 'archive';
    const UNARCHIVE              = 'unarchive';
    const SUSPEND                = 'suspend';
    const UNSUSPEND              = 'unsuspend';
    const CREATED                = 'created';
    const ACTIVATED              = 'activated';
    const LOCK                   = 'lock';
    const UNLOCK                 = 'unlock';
    const EDIT_COMMENT           = 'edit_comment';
    const HOLD_FUNDS             = 'hold_funds';
    const RELEASE_FUNDS          = 'release_funds';
    const ENABLE_RECEIPT_EMAILS  = 'enable_receipt_emails';
    const DISABLE_RECEIPT_EMAILS = 'disable_receipt_emails';
    const ENABLE_INTERNATIONAL   = 'enable_international';
    const DISABLE_INTERNATIONAL  = 'disable_international';
    const UPDATED                = 'updated';
    const SUBMITTED              = 'submitted';
    const ACTIVATION_PROGRESS    = 'activation_progress';
    const TEST_KEYS_CREATED      = 'test_keys_created';
    const LIVE_KEYS_CREATED      = 'live_keys_created';

    public static function exists($action)
    {
        return defined(get_class() . '::' . strtoupper($action));
    }
}
