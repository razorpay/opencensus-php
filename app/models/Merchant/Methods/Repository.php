<?php

namespace Models\Merchant\Methods;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use Base\RepositoryFetch;

    protected $entity = 'Methods';

    public function getMerchantBanks($id)
    {
        $repo = $this->repo;

        return $repo::find($id);
    }

    public function saveMerchantBanks($banks)
    {
        $repo = $this->repo;

        $this->saveOrFail($banks);
    }
}