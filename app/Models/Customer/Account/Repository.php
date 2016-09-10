<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant\Account;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;

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

    public function findByContactAndMerchant($contact, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->first();
    }

    public function findByContactEmailAndMerchant($contact, $email, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::EMAIL, '=', $email)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->first();
    }
}
