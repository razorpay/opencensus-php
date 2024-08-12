<?php

namespace RZP\Models\Invitation;

class Constants
{
    const INVITATION_DETAILS_INPUT_FIRST_NAME = 'first_name';
    const INVITATION_DETAILS_INPUT_LAST_NAME = 'last_name';

    const INVITATION_DETAILS_INPUT_FIRST_NAME_SHORT = 'fn';
    const INVITATION_DETAILS_INPUT_LAST_NAME_SHORT = 'ln';

    const ACCEPT_INVITE_SROUCE = 'accept_invite_source';
    const ACCEPT_INVITE_SROUCE_VENDOR_PORTAL_V2 = 'vendor_portal_v2';

    const PARTNER_AGENT_APP_INSTALL_URL = 'https://dashboard.razorpay.com/app/pos-sales/join?token=%s&flow=field-agent';
    const PARTNER_AGENT_APP_INVITE_SMS_TEMPLATE = 'sms.partnerships.invite_partner_agent';
}
