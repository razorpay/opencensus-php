<?php

namespace RZP\Models\Merchant\OwnerDetail;

use RZP\Http\Response\StatusCode;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function deleteOwnerDetail($id)
    {
        return (new Core())->deleteOwnerDetail($id);
    }
}
