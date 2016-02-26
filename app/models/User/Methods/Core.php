<?php

namespace Models\User\Methods;

use Models\Base;
use Models\User\Methods;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Methods\Repository;
    }
    
    public function create($uid, $input)
    {
        $input['user_id'] = $user->getKey();

        $method = (new Methods\Entity)->build($input);

        $this->validateExistingMethod($method);

        $this->repo->saveOrFail($method);

        return $method;
    }

    public function edit($method, $input)
    {
        $this->validateExistingMethod($method);

        $this->trace->info(
            TraceCode::TERMINAL_EDIT,
            [
                'method_id' => $method->getId(),
                'fields' => array_keys($input),
            ]);

        $method->edit($input);

        $this->repo->saveOrFail($method);
    
        return $method;
    }
}