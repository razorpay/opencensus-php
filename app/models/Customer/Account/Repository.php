<?php

namespace Models\Customer;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'customer';

    public function findByEmailContactForMerchant($email, $contact, $merchantId)
    {
        $repo = $this->repo;

        return $repo::where(Customer\Entity::EMAIL, '=', $email)
                    ->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }

    public function findByEmailContact($email, $contact)
    {
        $repo = $this->repo;

        return $repo::where(Customer\Entity::EMAIL, '=', $email)
                    ->where(Customer\Entity::CONTACT, '=', $contact)
                    ->get();
    }
}
