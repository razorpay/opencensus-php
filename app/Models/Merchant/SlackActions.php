<?php

namespace RZP\Models\Merchant;

class SlackActions
{
    // descriptions/messages
    const FUNDS_HELD                   = 'Funds put on hold';
    const FUNDS_RELEASED               = 'Funds Released';
    const RISK_RATING_CHANGED          = 'Risk Rating Changed';
    const EMAIL_EDITED                 = 'Email Edited';
    const BANK_DETAILS_EDITED          = 'Bank Details Edited';
    const BANK_LIST_EDITED             = 'Bank List Edited';
    const ADJUSTMENT_ADDED             = 'Adjustment Added';
    const FORM_LOCKED                  = 'Form Locked';
    const FORM_UNLOCKED                = 'Form Unlocked';
    const PRICING_PLAN_SET             = 'Pricing Plan Set';
    const ADD_TAGS                     = 'Tagged';
    const HDFC_EXCEL                   = 'HDFC Excel generated';
    const LIVE_ENABLED                 = 'Live transactions enabled';
    const LIVE_DISABLED                = 'Live transactions disabled';
    const RISK_ACTION_LIVE_ENABLED     = 'Risk Action - Live transactions enabled';
    const RISK_ACTION_LIVE_DISABLED    = 'Risk Action - Live transactions disabled';
    const ARCHIVED                     = 'Archived';
    const UNARCHIVED                   = 'Unarchived';
    const SUSPENDED                    = 'Suspended';
    const UNSUSPENDED                  = 'Unsuspended';
    const FREE_CREDITS_EDIT            = 'Free Credits Edited';
    const CONFIRMED                    = 'Confirmed';
    const ADMIN_EDIT                   = 'Admin Edited';
    const EMAIL_DISABLED               = 'Receipt email disabled';
    const EMAIL_ENABLED                = 'Receipt email enabled';
    const INTERNATIONAL_ENABLED        = 'Merchant international enabled';
    const INTERNATIONAL_DISABLED       = 'Merchant international disabled';

    // code
    const ARCHIVE                = 'archive';
    const UNARCHIVE              = 'unarchive';
    const SUSPEND                = 'suspend';
    const UNSUSPEND              = 'unsuspend';
    const ENABLE                 = 'enable';
    const DISABLE                = 'disable';
    const LIVE_ENABLE            = 'live_enable';
    const LIVE_DISABLE           = 'live_disable';
    const LOCK                   = 'lock';
    const UNLOCK                 = 'unlock';
    const ASSIGN_PRICING         = 'assign_pricing';
    const TAGGED                 = 'Tagged';
    const ASSIGN_BANKS           = 'assign_banks';
    const ADD_ADJUSTMENT         = 'add_adjustment';
    const EDIT_BANK_DETAILS      = 'edit_bank_details';
    const HOLD_FUNDS             = 'hold_funds';
    const RELEASE_FUNDS          = 'release_funds';
    const DISABLE_RECEIPT_EMAILS = 'disable_receipt_emails';
    const ENABLE_RECEIPT_EMAILS  = 'enable_receipt_emails';
    const ENABLE_INTERNATIONAL   = 'enable_international';
    const DISABLE_INTERNATIONAL  = 'disable_international';

    public static $actionMsgMap = [
        self::ARCHIVE                => self::ARCHIVED,
        self::UNARCHIVE              => self::UNARCHIVED,
        self::SUSPEND                => self::SUSPENDED,
        self::UNSUSPEND              => self::UNSUSPENDED,
        self::ENABLE                 => self::LIVE_ENABLED,
        self::DISABLE                => self::LIVE_DISABLED,
        self::LIVE_ENABLE            => self::RISK_ACTION_LIVE_ENABLED,
        self::LIVE_DISABLE           => self::RISK_ACTION_LIVE_DISABLED,
        self::LOCK                   => self::FORM_LOCKED,
        self::UNLOCK                 => self::FORM_UNLOCKED,
        self::ASSIGN_PRICING         => self::PRICING_PLAN_SET,
        self::TAGGED                 => self::ADD_TAGS,
        self::ASSIGN_BANKS           => self::BANK_LIST_EDITED,
        self::ADD_ADJUSTMENT         => self::ADJUSTMENT_ADDED,
        self::EDIT_BANK_DETAILS      => self::BANK_DETAILS_EDITED,
        self::HOLD_FUNDS             => self::FUNDS_HELD,
        self::RELEASE_FUNDS          => self::FUNDS_RELEASED,
        self::DISABLE_RECEIPT_EMAILS => self::EMAIL_DISABLED,
        self::ENABLE_RECEIPT_EMAILS  => self::EMAIL_ENABLED,
        self::ENABLE_INTERNATIONAL   => self::INTERNATIONAL_ENABLED,
        self::DISABLE_INTERNATIONAL  => self::INTERNATIONAL_DISABLED,
    ];
}
