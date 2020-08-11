<?php


namespace RZP\Models\Mpan;

use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base;
use RZP\Constants;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::MPAN;

    // Default order defined in RepositoryFetch is created_at, id
    // Overriding here because  table does not have an id column.
    //
    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    protected $fetchParamRules = [
        Entity::NETWORK       => 'required|in:Visa,RuPay,MasterCard',
    ];

    public function findByMerchantIdMpans(string $merchantId, array $mpanValues)
    {        
        $mpan = $this->newQuery()
                      ->where(Entity::MERCHANT_ID, $merchantId)
                      ->whereIn(Entity::MPAN, $mpanValues)
                      ->get();

        return $mpan;
    }

    public function fetchUnassignedMpansForNetwork(string $network, int $count)
    {
       $mpans = $this->newQuery()
                     ->where(Entity::NETWORK, $network)
                     ->where(Entity::ASSIGNED, false)
                     ->take($count)
                     ->get();

       if ($mpans->count() < $count)
       {
           throw new ServerErrorException(null,
                                          ErrorCode::SERVER_ERROR_REQUESTED_MPANS_NOT_AVAILABLE,
                                          null,
                                          null);
       }

       return $mpans;
    }

    public function assignMpansToMerchant($mpansCollection)
    {
        $assignMpanToMerchantInput = [
            Entity::ASSIGNED    => true,
        ];

        foreach ($mpansCollection as $mpan)
        {
            $mpan->merchant()->associate($this->merchant);

            $mpan->edit($assignMpanToMerchantInput);
        }

        $this->repo->saveOrFailCollection($mpansCollection);

        return $mpansCollection;
    }

    public function fetchMpansForTokenization(int $count)
    {
        $mpans = $this->newQuery()
                      ->take($count)
                      ->whereRaw('LENGTH(mpan) = 16')
                      ->get();

        return $mpans;
    }
}
