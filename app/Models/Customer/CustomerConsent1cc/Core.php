<?php

namespace RZP\Models\Customer\CustomerConsent1cc;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($input): Entity
    {
        $consentEntity = (new Entity)->build($input);

        $consentEntity->generateId();

        $this->repo->customer_consent_1cc->saveOrFail($consentEntity);

        return $consentEntity;
    }
    public function update($consentEntity, $input){

        $consentEntity->edit($input);

        return  $this->repo->customer_consent_1cc->saveOrFail($consentEntity);
    }

    public function fetchCustomerConsent1cc($contact, $merchantId)
    {
        return (new Repository())->findByCustomerIdAndMerchantId($contact, $merchantId);
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function recordCustomerConsent1cc($input, $customer, $merchant)
    {
        Validator::validateRecordCustomerConsent1cc($input);

        $request['status'] = (int) $input['1cc_customer_consent'];

        return $this->transaction(
            function () use ($customer, $merchant, $request)
            {
                $customerConsent = (new Repository())->findByCustomerIdAndMerchantId($customer->getContact(), $merchant->getId(), false);

                $request[Entity::MERCHANT_ID] = $merchant->getId();

                $request[Entity::CONTACT] = $customer->getContact();

                //Fresh consent
                if (empty($customerConsent) === true)
                {
                    $response = $this->create($request);

                    return ['1cc_customer_consent' => $response['status']];
                }
                //consent status changed, We should not update if the existing consent status is 1
                if ($customerConsent->getStatus() !== 1 && $request['status'] !== $customerConsent->getStatus())
                {
                    $this->update($customerConsent, $request);

                    return ['1cc_customer_consent' => $request['status']];
                }
                return ['1cc_customer_consent' => $customerConsent->getStatus()];
            }
        );
    }
}
