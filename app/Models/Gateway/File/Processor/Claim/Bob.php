<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

//use RZP\Gateway\Netbanking\Base\Entity;

class Bob extends Base
{
    const GATEWAY = 'netbanking_bob';

    public function createFile($data)
    {
        return;
    }
}
