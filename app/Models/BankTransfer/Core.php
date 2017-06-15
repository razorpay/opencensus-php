<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $bankTransfer = (new Entity)->build($input);

        return $bankTransfer;
    }

    public function notify(Entity $bankTransfer)
    {
        if ($bankTransfer->isNotified() === false)
        {
            $bankTransfer->setNotified(true);

            $this->repo->saveOrFail($bankTransfer);
        }
    }
}
