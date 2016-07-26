<?php

namespace RZP\Models\Merchant\FreeCredits;

use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\FreeCredits;

class Core extends Base\Core
{
    public function checkFreeCreditLogExists($id)
    {
        return FreeCredits\Entity::find($id);
    }

    public function create($input)
    {
        $freeCreditLog = (new FreeCredits\Entity)->build($input);
        $this->repo->saveOrFail($freeCreditLog);
    }
}
