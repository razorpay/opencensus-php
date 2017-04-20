<?php

namespace RZP\Models\Aadhaar;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($input, $merchant)
    {
        $aadhaar = (new Entity)->build($input);

        $aadhaar->merchant()->associate($merchant);

        $this->repo->saveOrFail($aadhaar);

        return $aadhaar;
    }
}
