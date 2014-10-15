<?php

namespace Models\Base;

use DB;

class Repository extends \Razorpay\Spine\Repository
{
    protected $db;

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
