<?php

namespace Models\Customer;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer;
use Models\Merchant\Account;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'customer';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::EMAIL           => 'sometimes|email',
        Entity::ACTIVE          => 'sometimes|in:0,1',
        Entity::CONTACT         => 'sometimes'
    );

    public function findByContactForMerchant($contact, $merchantId)
    {
        $repo = $this->repo;

        return $repo::where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }

    public function findByContact($contact)
    {
        $repo = $this->repo;

        return $repo::where(Customer\Entity::CONTACT, '=', $contact)
                    ->first();
    }

    public function findByContactEmailForMerchant($contact, $email, $merchantId)
    {
        $repo = $this->repo;

        return $repo::where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::EMAIL, '=', $email)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }
}
