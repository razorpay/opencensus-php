<?php

namespace Models\Emi;

use Models\Base;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;
    }

    public function all()
    {
        $emiOptions = $this->repo->getAllEmiOptions();

        return $emiOptions->toArrayPublic();
    }

    public function fetch($id)
    {
        $emiOptions = $this->repo->findOrFail($id);

        return $emiOptions->toArrayPublic();
    }

    public function addEmiOption(array $input)
    {
        $emi = (new Core)->addEmiOption($input);

        return $emi->toArrayPublic();
    }
}