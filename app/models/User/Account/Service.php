<?php 

namespace Models\User;

use Models\Base;
use Models\User;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new User\Repository;
    }

    public function create($input)
    {
        $user = (new User\Core)->create($input);

        return $user->toArrayPublic();
    }

    public function edit($id, $input)
    {
        $user = $this->repo->findOrFailPublic($id);

        $user= (new User\Core)->edit($user, $input);

        return $user->toArrayPublic();
    }

    public function fetch($id)
    {
        $user = $this->repo->findOrFailPublic($id);

        return $user->toArrayPublic();
    }

    public function delete($id)
    {

    }
}
