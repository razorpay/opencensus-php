<?php

namespace Models\Base;

class Repository extends \Razorpay\Spine\Repository
{
    public function __construct()
    {
        parent::__construct();
    }

    public function findOrFailPublic($id, $columns = array('*'))
    {
        $repo = $this->repo;

        return $repo::findOrFailPublic($id, $columns);
    }
}
