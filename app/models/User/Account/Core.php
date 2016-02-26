<?php

namespace Models\User;

use Models\Base;
use Models\User;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new User\Repository;
    }

    public function create($input)
    {
        $user = (new User\Entity)->build($input);

        $this->repo->saveOrFail($user);

        return $user;
    }

    public function edit($user, $input)
    {
        $user->edit($input);

        $this->repo->saveOrFail($user);

        $this->trace->info(
            TraceCode::User_EDIT,
            [$input]);

        return $user;
    }
}