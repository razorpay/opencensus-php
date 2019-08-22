<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Illuminate\Support\Arr;
use RZP\Jobs\HubspotRequestJob;

class HubspotClient
{
    protected $baseUrl;

    protected $secret;

    protected $config;

    protected $trace;

    protected $eventData = [];

    protected $relativeUrls = [
        'update_contact_properties_by_email' => 'contacts/v1/contact/createOrUpdate/email/',
    ];

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.hubspot');

        $this->baseUrl = $this->config['url'];

        $this->secret = $this->config['secret'];
    }

    public function trackSignupEvent(array $input)
    {
        if (isset($input['email']) === false)
        {
            return;
        }

        $payloadData['email'] = $input['email'];

        $this->dispatchRequestJob($payloadData);
    }

    public function trackPreSignupEvent(array $input, Merchant\Entity $merchant)
    {
        $payloadData = $input;

        $this->addMerchantContext($payloadData, $merchant);

        $this->mapSignupValues($payloadData);

        $this->dispatchRequestJob($payloadData);
    }

    protected function mapSignupValues(array & $input)
    {
        $mappingKeys = [
            Merchant\Detail\Entity::BUSINESS_TYPE,
            Merchant\Detail\Entity::TRANSACTION_VOLUME,
            Merchant\Detail\Entity::DEPARTMENT,
        ];

        foreach ($mappingKeys as $key)
        {
            if (array_key_exists($key, $input))
            {
                $functionName = camel_case('map_' . $key);

                if (method_exists($this, $functionName) === true)
                {
                    $input[$key] = $this->$functionName($input[$key]);
                }
            }
        }
    }

    public function trackL1ContactProperties(array $input, Merchant\Entity $merchant, string $activationFlow)
    {
        $payloadData = $input;

        $this->filterEvents($payloadData);

        $this->mapSignupValues($payloadData);

        // prefixing keys of the input array with level1
        $payloadData = array_combine(array_map(function($key) {

            $prefixKey = 'level1_';

            return $prefixKey . $key;

        }, array_keys($payloadData)), $payloadData);

        $this->addMerchantContext($payloadData, $merchant);

        $payloadData['bucket'] = $activationFlow;

        $this->dispatchRequestJob($payloadData);
    }

    public function trackL2ContactProperties(array $input, Merchant\Entity $merchant)
    {
        $payloadData = $input;

        $this->filterEvents($payloadData);

        $this->mapSignupValues($payloadData);

        $payloadData = array_combine(array_map(function($key) {

            $prefixKey = 'level2_';

            return $prefixKey . $key;

        }, array_keys($payloadData)), $payloadData);

        $this->addMerchantContext($payloadData, $merchant);

        $this->dispatchRequestJob($payloadData);
    }

    protected function addMerchantContext(array & $payloadData, Merchant\Entity $merchant)
    {
        $payloadData['email'] = $merchant['email'];

        $payloadData['mid'] = $merchant['id'];
    }

    /**
     *  mask merchant related sensitive information as true
     * @param array $input
     */
    protected function removeSensitiveInformationFromPayload(array & $input)
    {
        $keyForRemovingSensitiveInformation = [
            Merchant\Detail\Entity::GSTIN,
            Merchant\Detail\Entity::PROMOTER_PAN,
            Merchant\Detail\Entity::BUSINESS_PAN_URL,
            Merchant\Detail\Entity::ADDRESS_PROOF_URL,
            Merchant\Detail\Entity::PROMOTER_ADDRESS_URL,
            Merchant\Detail\Entity::BUSINESS_PROOF_URL,
        ];

        foreach($keyForRemovingSensitiveInformation as $key)
        {
            if (array_key_exists($key, $input))
            {
                $input[$key] = true;
            }
        }
    }

    protected function filterEvents(array & $input)
    {
        $this->removeSensitiveInformationFromPayload($input);

        $disallowedL1Events = [
            Merchant\Detail\Entity::BUSINESS_REGISTERED_COUNTRY,
            Merchant\Detail\Entity::BUSINESS_OPERATION_ADDRESS_L2,
            Merchant\Detail\Entity::BUSINESS_OPERATION_PROOF_URL,
            Merchant\Detail\Entity::BUSINESS_OPERATION_COUNTRY,
            Merchant\Detail\Entity::BUSINESS_OPERATION_ADDRESS_L2,
        ];

        Arr::except($input, $disallowedL1Events);
    }

    protected function mapBusinessType($businessType)
    {
        try
        {
            return Merchant\Detail\BusinessType::getKeyFromIndex($businessType);
        }
        catch (Exception\BadRequestValidationFailureException $exception)
        {
            return '';
        }
    }

    protected function mapTransactionVolume($volume)
    {
        return Merchant\Detail\TransactionVolume::mapTransactionVolume($volume);
    }

    protected function mapDepartment($dept)
    {
        return Merchant\Detail\Department::getType($dept);
    }

    public function dispatchRequestJob(array $payloadData)
    {
        if ($this->config['mock'] === true)
        {
            return;
        }

        $payload = $this->preparePayload($payloadData);

        $request = [
            'url'     => $this->getAbsoluteUrl($payloadData),
            'method'  => 'post',
            'headers' => [],
            'options' => [],
            'content' => json_encode($payload)
        ];

        $this->trace->info(
            TraceCode::HUBSPOT_EXTERNAL_SERVICE_REQUEST,
            [
                'email'   => $payloadData['email'],
                'method'  => $request['method'],
                'content' => $request['content'],
            ]);

        HubspotRequestJob::dispatch($request);
    }

    protected function getAbsoluteUrl(array $payloadData)
    {
        return $this->baseUrl . $this->relativeUrls['update_contact_properties_by_email'] . $payloadData['email'] . '?hapikey=' . $this->secret;
    }

    protected function preparePayload(array $input): array
    {
        $properties = [
            'properties' => []
        ];

        foreach ($input as $key => $value)
        {
            $jsonData = [
                'property' => $key,
                'value'    => $value,
            ];

            array_push($properties['properties'], $jsonData);
        }

        return $properties;
    }
}
