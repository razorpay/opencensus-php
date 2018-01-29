<?php

namespace RZP\Gateway\Netbanking\Csb;

use RZP\Gateway\Netbanking\Base;

class Gateway extends Base\Gateway
{
    public function authorize(array $input)
    {
        parent::authorize($input);

        sd('Reached authorize function');
    }

    public function callback(array $input)
    {
        parent::callback($input);

        sd('Reached callback function');
    }

    public function verify(array $input)
    {
        parent::verify($input);

        sd('Reached verify function');
    }
}
