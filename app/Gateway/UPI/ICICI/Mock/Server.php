<?php

namespace Gateway\UPI\ICICI\Mock;

use Carbon\Carbon;
use Gateway\UPI\ICICI;
use Gateway\Base;
use Gateway\Base\Action;
use Models\Payment;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            'code'          => '9',
            'bank_txn_id'   => '12345',
            'txn_id'        => '12345',
            'message'       => 'Processed'
        );

        // Now we encrypt it using the public key?

        return $url;
    }
}
