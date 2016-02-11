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

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
        Entity::CARD        => 'sometimes|in:0,1',
        Entity::AMEX        => 'sometimes|in:0,1',
        Entity::BANKS       => 'sometimes|in:0,1',
        Entity::PAYTM       => 'sometimes|in:0,1',
        Entity::MOBIKWIK    => 'sometimes|in:0,1',
        Entity::PAYZAPP     => 'sometimes|in:0,1',
        Entity::EMI         => 'sometimes|in:0,1',
    );

    public function getMerchantMethods($id)
    {
        $repo = $this->repo;

        return $repo::find($id);
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }
}