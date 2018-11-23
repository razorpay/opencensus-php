<?php

namespace RZP\Models\Admin\Admin\Token;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function deleteToken(Entity $token)
    {
        $data = $this->core()->delete($token);

        return $data;
    }
}
