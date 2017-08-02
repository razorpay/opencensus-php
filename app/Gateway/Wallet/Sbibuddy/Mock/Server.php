<?php

namespace RZP\Gateway\Wallet\Sbibuddy\Mock;

use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{

    public function authorize($input)
    {
        $redirectUrl = $input['callbackUrl'];

        return \Redirect::to($redirectUrl);
    }
}

