<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'methods';

    protected $appFetchParamRules = array(
        Entity::AMEX        => 'sometimes|in:0,1',
        Entity::BANKS       => 'sometimes|in:0,1',
        Entity::CARD        => 'sometimes|in:0,1',
        Entity::EMI         => 'sometimes|in:0,1',
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
        Entity::MOBIKWIK    => 'sometimes|in:0,1',
        Entity::PAYTM       => 'sometimes|in:0,1',
        Entity::PAYUMONEY   => 'sometimes|in:0,1',
        Entity::PAYZAPP     => 'sometimes|in:0,1',
        Entity::OLAMONEY    => 'sometimes|in:0,1',
        Entity::AIRTELMONEY => 'sometimes|in:0,1',
        Entity::FREECHARGE  => 'sometimes|in:0,1',
    );

    public function getMerchantMethods($id)
    {
        return $this->find($id);
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }
}
