<?php


namespace RZP\Models\CardMandate\MandateHubs\OptimizerHubs;

// common constants for all Optimizer Hubs
class Constants
{

    const ID = "id";
    const CARD_ID = 'id';
    const CARD_LAST4 = 'last4';
    const CARD_NETWORK = 'network';
    const CARD_TYPE = 'type';
    const CARD_ISSUER = 'issuer';
    const CARD_INTERNATIONAL = 'international';
    const CARD_NAME = 'name';


    const FREQUENCY_AS_PRESENTED = 'as_presented';
    const MAX_AMOUNT_DEFAULT = 1500000;
    const DEBIT_TYPE_VARIABLE_AMOUNT = 'variable_amount';
    const TOKEN = 'token';

    const DATA = "data";
    const BIN_DATA = 'bin_data';
    const MANDATE_STATUS = 'mandate_status';
    const AFA_STATUS = 'afa_status';
    const INVOICE_STATUS = 'invoice_status';
    const INVOICE_ID = 'invoice_id';
    const PAYMENT = 'payment';
    const TERMINAL = 'terminal';
    const MERCHANT = 'merchant';
    const GATEWAY = 'gateway';
    const CARD = 'card';
    const IIN = 'iin';
    const CARD_MANDATE = 'card_mandate';
    const DEBIT_AT = 'debit_at';
    const IS_OPTIMIZER_CARD_MANDATE = 'is_optimizer_card_mandate';

    const IS_SI_SUPPORTED = 'is_si_supported';

    const MOZART = 'mozart';

    const NOTIFY_ACTION = 'notify_action';
    // Notify actions
    const NOTIFY_INIT = 'init';
    const NOTIFY_VERIFY = 'retrieve';
    const NOTIFY_DELETE = 'delete';


    const ACTIVE = 'active';
    const SUCCESS = 'success';
    const PENDING = 'pending';


}
