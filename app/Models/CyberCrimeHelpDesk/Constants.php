<?php

namespace RZP\Models\CyberCrimeHelpDesk;

class Constants
{
    const CYBER_CRIME_HELPDESK_WORKFLOW_CONTROLLER                      = 'RZP\Http\Controllers\CyberCrimeHelpDeskController@postCyberCrimeWorkflowApproval';

    const MAIL_TO_LEA_FROM_CYBRERSOURCE_HELPDESK_EMAIL_SUBJECT          = 'Razorpay Acknowledgement to LEA: %s';

    const MAIL_TO_LEA_FROM_CYBRERSOURCE_HELPDESK_EMAIL_TEMPLATE         = 'emails.merchant.cyber_helpdesk';

    const MAIL_TO_MERCHANT_ABOUT_CYBER_CRIME_EMAIL_SUBJECT              = 'Razorpay | Unauthorized transaction Alert - %s %s | %s';

    const MAIL_TO_MERCHANT_ABOUT_CYBER_CRIME_EMAIL_TEMPLATE             = 'emails.merchant.merchant_cyber_helpdesk';

    const REPLY_MAIL_TO_LEA_SUBJECT                                     = 'Razorpay Response to LEA: %s_%s';

    const REPLY_MAIL_TO_LEA_TEMPLATE                                    = 'emails.merchant.reply_to_lea_from_cyberdesk';

    const FRESHDESK_EMAIL_CYBER_CELL_SUB_CATEGORY                       = 'Cybercell report';

    const TICKET_DATA                                                   = 'ticket_data';

    const FD_TICKET_ID                                                  = 'fd_ticket_id';

    const SHARE_BENEFICARY_ACCOUNT_DETAILS                              = 'share_beneficary_account_details';

    const PUT_SETTLEMENT_ON_HOLD                                        =  'put_settlement_on_hold';

    const PAYMENT_REQUESTS                                              =  'payment_requests';

    const PREFIX_CYBER_CRIME_PAYMENT_DETAILS_COMMENT                    =   'agent_approved_payment_details_';

}
