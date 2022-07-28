<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Exception;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'customer';

    public function getGlobalCustomerForPayment($payment)
    {
        if ($payment->getGlobalCustomerId() !== null)
        {
            $customer = $this->findOrFail($payment->getGlobalCustomerId());
            $payment->globalCustomer()->associate($customer);

            return $customer;
        }
    }

    public function fetchByAppToken(AppToken\Entity $appToken): Entity
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

    public function findById($id, $columns = ['*'])
    {
        return $this->newQuery()
            ->select($columns)
            ->find($id);
    }

    public function findByContactAndMerchant($contact, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->first();
    }

    public function findByContactAndMerchantId($contact, $merchantId)
    {
        return $this->newQuery()
                    ->where(Customer\Entity::CONTACT, '=', $contact)
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchantId)
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

    public function findOrFailByPublicIdAndMerchant(string $id, Merchant\Entity $merchant)
    {
        Entity::verifyIdAndStripSign($id);

        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->find($id);
    }

    public function fetchByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Customer\Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }
}
