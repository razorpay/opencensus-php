<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Exception;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'customer';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::EMAIL           => 'sometimes|email',
        Entity::ACTIVE          => 'sometimes|in:0,1',
        Entity::CONTACT         => 'sometimes'
    );

    public function getGlobalCustomerForPayment($payment)
    {
        if ($payment->getGlobalCustomerId() !== null)
        {
            $customer = $this->findOrFail($payment->getGlobalCustomerId());
            $payment->globalCustomer()->associate($customer);

            return $customer;
        }
    }

    public function fetchByAppToken($appToken)
    {
        if ($appToken->hasRelation('customer'))
        {
            return $appToken->customer;
        }

        $custId = $appToken->getCustomerId();

        $customer = $this->findOrFail($custId);

        $appToken->customer()->associate($customer);

        return $customer;
    }

    public function findByContactAndMerchant($contact, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->first();
    }

    public function fetchWithVpasBankAcnts($id, $columns = ['*'])
    {
        return $this->newQuery()
                    ->select($columns)
                    ->with(['vpas', 'bank_accounts'])
                    ->find($id);
    }

    public function findByContactEmailAndMerchant($contact, $email, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::EMAIL, '=', $email)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->first();
    }

    public function fetchByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }
}
