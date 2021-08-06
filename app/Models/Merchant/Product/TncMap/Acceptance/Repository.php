<?php

namespace RZP\Models\Merchant\Product\TncMap\Acceptance;

use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\Base\Repository as BaseRepository;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends BaseRepository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_tnc_acceptance';

    public function acceptedTncExists(string $tncMapId, string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::TNC_MAP_ID, '=' , $tncMapId)
                    ->where(Entity::MERCHANT_ID, '=' , $merchantId)
                    ->exists();
    }

    public function fetchMerchantAcceptanceByTncMapId(string $tncMapId, string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::TNC_MAP_ID, '=' , $tncMapId)
                    ->where(Entity::MERCHANT_ID, '=' , $merchantId)
                    ->first();
    }
}
