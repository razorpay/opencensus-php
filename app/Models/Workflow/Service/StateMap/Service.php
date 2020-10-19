<?php

namespace RZP\Models\Workflow\Service\StateMap;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Service\StateMap;
use RZP\Models\Workflow\Service\StateMap\Validator;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->core = new StateMap\Core;
    }

    public function create(array $input)
    {
        (new Validator)->setStrictFalse()
            ->validateInput(Validator::CREATE, $input);

        $stateMapResponse = $this->core->create($input);

        return $stateMapResponse;
    }

    public function update(string $id, array $input)
    {
        (new Validator)->setStrictFalse()
            ->validateInput(Validator::UPDATE, $input);

        $stateMapResponse = $this->core->update($id, $input);

        return $stateMapResponse;
    }
}
