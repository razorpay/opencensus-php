<?php

namespace RZP\Gateway\P2p\Base;

use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    protected function getRepository()
    {
        //
    }

    public function __call($action, $input)
    {
    }
}
