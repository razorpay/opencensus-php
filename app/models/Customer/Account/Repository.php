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
}
