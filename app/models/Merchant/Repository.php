<?php

namespace Models\Merchant;

use Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Merchant';

    public function findBalanceLockForUpdate($id)
    {
        $oldRepo = $this->repo;

        $this->repo = '\Models\Merchant\Balance';
        $repo = $this->repo;

        $balance = $repo::lockForUpdate()->findOrFail($id);

        $this->repo = $oldRepo;

        return $balance;
    }
}