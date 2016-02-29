<?php

namespace Models\User\Methods;

use Models\Base;
use Models\User;
use Models\User\Methods;

class Service extends Base\Service
{
    protected $repo;
    protected $methodsRepo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new User\Repository;

        $this->methodsRepo = new Methods\Repository;
    }

    public function add($uid, $input)
    {
        $user = $this->repo->findOrFailPublic($uid);

        $method = (new Methods\Core)->create($uid, $input);

        return $method->toArrayPublic();

    }

    public function edit($uid, $mid, $input)
    {
        $user = $this->repo->findOrFailPublic($uid);

        $method = $this->methodsRepo->getByIdAndUserId($uid, $mid);

        $method = (new Methods\Core)->edit($method, $input);

        return $method->toArrayPublic();
    }

    public function fetch($uid, $mid)
    {
        $user = $this->repo->findOrFailPublic($uid);

        $method = $this->methodsRepo->getByIdAndUserId($uid, $mid);

        return $method->toArrayPublic();

    }

    public function fetchMultiple($uid)
    {
        $user = $this->repo->findOrFailPublic($uid);

        $methods = $this->methodsRepo->getByUserId($uid);

        return $methods->toArrayPublic();
    }

    public function delete($uid, $mid)
    {
        $method = $this->methodsRepo->findOrFailPublic($mid);

        $method = $this->methodsRepo->deleteOrFail($method);

        if ($method === null)
            return [];

        return $method->toArrayPublic();
    }
}
 