<?php

namespace RZP\Models\Admin\Admin\Token;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function delete(Entity $token)
    {
        $this->repo->deleteOrFail($token);

        return $token->toArrayDeleted();
    }
}
