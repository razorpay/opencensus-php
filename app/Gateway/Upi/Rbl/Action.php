<?php

namespace RZP\Gateway\Upi\Rbl;

use RZP\Gateway\Base;

class Action extends Base\Action
{
    const SESSION_TOKEN  = 'session_token';

    const GENERATE_AUTH_TOKEN = 'generate_auth_token';

    const GET_TRANSACTION_ID = 'get_transaction_id';
}
