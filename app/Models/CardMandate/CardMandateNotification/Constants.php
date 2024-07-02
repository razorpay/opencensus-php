<?php

namespace RZP\Models\CardMandate\CardMandateNotification;

class Constants
{
    const PDN_DECOUPLING_ORDER_RETRY_ATTEMPTS = 10;
    const MINIMUM_TIME_DIFFERENCE_IN_PDN_AND_DEBIT_PAYMENT = 36; // In hours
    const WITHOUT_AFA_AMOUNT_LIMIT   = 1500000;
    const AFA_MHQ_APPROVAL_LIMIT = 60*60*60; //In seconds (60 hrs)
}
