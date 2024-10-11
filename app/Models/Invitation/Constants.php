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

    const PARTNER_AGENT_APP_INSTALL_URL_PREFIX = 'https://accounts.razorpay.com/auth/?redirecturl=dashboard.razorpay.com%2Fapp%2Fpos-sales%2Fbasic-info%3Fflow%3Dpos-ekyc-agent&invitation=';
    const PARTNER_AGENT_APP_INVITE_SMS_TEMPLATE = 'sms.partnerships.invite_partner_agent';
}
