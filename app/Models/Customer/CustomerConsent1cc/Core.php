<?php

namespace RZP\Models\Customer\CustomerConsent1cc;

use RZP\Models\Base;
use RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator;

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

        $isTripleConsentEnabled = (new SplitzExperimentEvaluator())->useTripleConsentForMerchant($merchant->getId());

        if($isTripleConsentEnabled) {
            $emailConsent = (int)$input['one_cc_email_customer_consent'];
            $whatsappConsent = (int)$input['one_cc_whatsapp_customer_consent'];
            $request['consent_json'] = [
                'one_cc_email_customer_consent' => $emailConsent,
                'one_cc_whatsapp_customer_consent' => $whatsappConsent,
            ];
        }
        return $this->transaction(
            function () use ($customer, $merchant, $request, $isTripleConsentEnabled)
            {
                $customerConsent = (new Repository())->findByCustomerIdAndMerchantId($customer->getContact(), $merchant->getId(), false);

                $request[Entity::MERCHANT_ID] = $merchant->getId();

                $request[Entity::CONTACT] = $customer->getContact();

                //Fresh consent
                if (empty($customerConsent) === true)
                {
                    $response = $this->create($request);

                    if ($isTripleConsentEnabled)
                    {
                        return [
                            'one_cc_email_customer_consent' => $response['consent_json']['one_cc_email_customer_consent'] ?? 0,
                            'one_cc_whatsapp_customer_consent' => $response['consent_json']['one_cc_whatsapp_customer_consent'] ?? 0,
                            '1cc_customer_consent' => $response['status'] ?? 0,
                        ];
                    }

                    return ['1cc_customer_consent' => $response['status']];
                }
                //consent status changed, We should not update if the existing consent status is 1
                if (!$isTripleConsentEnabled && $customerConsent->getStatus() !== 1 && $request['status'] !== $customerConsent->getStatus())
                {
                    $this->update($customerConsent, $request);

                    return ['1cc_customer_consent' => $request['status']];
                }
                if ($isTripleConsentEnabled)
                {
                    $consentJSON = $customerConsent->getConsentJSON();

                    if (empty($consentJSON)) {
                        $this->update($customerConsent, $request);
                    } elseif (
                        $consentJSON['one_cc_email_customer_consent'] !== $request['consent_json']['one_cc_email_customer_consent'] ||
                        $consentJSON['one_cc_whatsapp_customer_consent'] !== $request['consent_json']['one_cc_whatsapp_customer_consent']
                    ) {
                        $this->update($customerConsent, $request);
                    }

                    return [
                        'one_cc_email_customer_consent' => $request['consent_json']['one_cc_email_customer_consent'],
                        'one_cc_whatsapp_customer_consent' => $request['consent_json']['one_cc_whatsapp_customer_consent'],
                        '1cc_customer_consent' => $request['status'],
                    ];
                }
                return ['1cc_customer_consent' => $customerConsent->getStatus()];
            }
        );
    }
}
