<?php

namespace RZP\Models\Merchant;

class SlackActions
{
    const FUNDS_HELD            = 'Funds put on hold';
    const FUNDS_RELEASED        = 'Funds Released';
    const RISK_RATING_CHANGED   = 'Risk Rating Changed';
    const EMAIL_EDITED          = 'Email Edited';
    const BANK_DETAILS_EDITED   = 'Bank Details Edited';
    const BANK_LIST_EDITED      = 'Bank List Edited';
    const ADJUSTMENT_ADDED      = 'Adjustment Added';
    const FORM_LOCKED           = 'Form Locked';
    const FORM_UNLOCKED         = 'Form Unlocked';
    const PRICING_PLAN_SET      = 'Pricing Plan Set';
    const ACTIVATED             = 'Activated';
    const HDFC_EXCEL            = 'HDFC Excel generated';
    const LIVE_ENABLED          = 'Live transactions enabled';
    const LIVE_DISABLED         = 'Live transactions disabled';
    const ARCHIVED              = 'Archived';
    const UNARCHIVED            = 'Unarchived';
    const SUSPENDED             = 'Suspended';
    const UNSUSPENDED           = 'Unsuspended';
    const FREE_CREDITS_EDIT     = 'Free Credits Edited';
    const TAGGED                = 'Tagged';
    const CONFIRMED             = 'Confirmed';
    const ADMIN_EDIT            = 'Admin Edited';
    const ARCHIVE               = 'archive';
    const UNARCHIVE             = 'unarchive';
    const SUSPEND               = 'suspend';
    const UNSUSPEND             = 'unsuspend';
    const ENABLE                = 'enable';
    const DISABLE               = 'disable';

    public static $actionMsgMap = [
        self::ARCHIVE   => self::ARCHIVED,
        self::UNARCHIVE => self::UNARCHIVED,
        self::SUSPEND   => self::SUSPENDED,
        self::UNSUSPEND => self::UNSUSPENDED,
        self::ENABLE    => self::LIVE_ENABLED,
        self::DISABLE   => self::LIVE_DISABLED,
    ];
}
