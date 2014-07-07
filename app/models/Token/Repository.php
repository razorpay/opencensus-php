<?php

namespace Models\Token;

use Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Token';

    public static function findByTokenAndMerchantId($token, $merchant_id)
    {
        $repo = $this->repo;

        $token;

        try
        {
            $token = $repo::where('token', $token)
                        ->where(self::MERCHANT_ID, $merchant_id)
                        ->first();

        }
        catch(Exception $e)
        {
            throw $e;
        }

        return $token;
    }
}