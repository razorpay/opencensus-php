<?php

namespace RZP\Models\BankingAccount\BankLms;

class Constants
{
    const MIS_TYPE = 'mis_type';
    const PARTNER_MERCHANT_ID = 'partner_merchant_id';


    const LEAD_RECEIVED_TO_DATE         = 'lead_received_to_date';
    const LEAD_RECEIVED_FROM_DATE       = 'lead_received_from_date';
    const ACTIVATION_ACCOUNT_TYPE       = 'activation_account_type';

    const IS_GREEN_CHANNEL              = 'is_green_channel';
    const SORT_SENT_TO_BANK_DATE        = 'sort_sent_to_bank_date';

    const DUE_ON        = 'due_on'; // to filter leads due on a date 
    const IS_OVERDUE    = 'is_overdue'; // to filter leads that are overdue on a particular date
    const FEET_ON_STREET = 'feet_on_street';
}
