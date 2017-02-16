<?php

namespace RZP\Models\Terminal\Action;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Terminal\Action;

class Core extends Base\Core
{
    public function create($input, $id)
    {
        $input['terminal_id'] = $id;

        $action = (new Action\Entity)->build($input);

        $this->repo->terminal_action->saveOrFail($action);

        return $action;
    }

}
