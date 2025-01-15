<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = 'customer';

    public function saveOrFail($entity, array $options = array())
    {
        parent::saveOrFail($entity);

        // check if the create flow has already been logged
        // the value of logged will not be set for create flows that we haven't identified yet
        if (!array_key_exists('logged', $options) || !$options['logged'])
            $this->trace->info(TraceCode::CUSTOMER_CREATE_UNKNOWN_FLOW,
                [
                    'customer_id' => $entity->getId(),
                    'merchant_id' => $entity->getMerchantId(),
                    'mode' => $this->app['rzp.mode'],
                    'internal_app_name' => $this->app['request.ctx']->getInternalAppName()
                ]);
    }

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
