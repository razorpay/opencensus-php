<?php

namespace RZP\Services;

use Request;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Illuminate\Support\Arr;
use RZP\Http\RequestHeader;
use RZP\Jobs\HubspotRequestJob;
use RZP\Models\Feature\Constants;

class HubspotClient
{
    protected $baseUrl;

    protected $secret;

    protected $config;

    protected $trace;

    protected $eventData = [];

    protected $app;

    const APPLICATION_JSON = 'application/json';

    const EPOS_APP = 'epos_app';

    const EPOS = 'Epos';

    const RAZORPAY_APP_HEADER = 'X-Razorpay-App';

    protected $relativeUrls = [
        'update_contact_properties_by_email' => 'contacts/v1/contact/createOrUpdate/email/',
    ];

    protected $prefix_events = [
        'signup' => 'signup_',
        'l1'     => 'l1_',
        'l2'     => 'l2_'
    ];

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.hubspot');

        $this->baseUrl = $this->config['url'];

        $this->secret = $this->config['secret'];

        $this->app = $app;
    }

    public function trackSignupEvent(array $input)
    {
        if (isset($input['email']) === false)
        {
            return;
        }

        $payloadData['email'] = $input['email'];

        $this->addCommonProperties($payloadData);

        $this->dispatchRequestJob($payloadData);
    }

    public function trackConfirmEmailEvent(array $input)
    {
        $payloadData = $input;

        $this->addCommonProperties($payloadData);

        $this->dispatchRequestJob($payloadData);
    }

    public function trackPreSignupEvent(array $input, Merchant\Entity $merchant)
    {
        $payloadData = $input;

        $this->mapSignupValues($payloadData);

        $this->appendPrefixToArray($payloadData, $this->prefix_events['signup']);

        $this->addMerchantContext($payloadData, $merchant);

        $this->addCommonProperties($payloadData);

        $this->dispatchRequestJob($payloadData);
    }

    /**
     * Calls appropriate Mapping function of
     * Business_Type, Transaction_Volume and Department
     *
     * @param array $input
     */
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

    /**
     * Adds request source based on  X-Razorpay-App header, if header value is Epos then source is epos-app
     *
     * @param array $payloadData
     *
     */
    protected function addRequestSource(array & $payloadData)
    {
        $appHeader = Request::header(self::RAZORPAY_APP_HEADER);

        if ((empty($appHeader) === false) and
            (strcasecmp($appHeader, self::EPOS) === 0))
        {
            $payloadData['source'] = self::EPOS_APP;
        }
    }

    public function trackL1ContactProperties(array $input, Merchant\Entity $merchant, string $activationFlow = null)
    {
        $payloadData = $input;

        $this->filterEvents($payloadData);

        $this->mapSignupValues($payloadData);

        // prefixing keys of the input array with l1_
        $this->appendPrefixToArray($payloadData, $this->prefix_events['l1']);

        $this->addMerchantContext($payloadData, $merchant);

        $payloadData['bucket'] = $activationFlow;

        $this->addCommonProperties($payloadData);

        $this->dispatchRequestJob($payloadData);
    }

    public function trackL2ContactProperties(array $input, Merchant\Entity $merchant)
    {
        $payloadData = $input;

        $this->filterEvents($payloadData);

        $this->mapSignupValues($payloadData);

        $this->appendPrefixToArray($payloadData, $this->prefix_events['l2']);

        $this->addMerchantContext($payloadData, $merchant);

        $this->addCommonProperties($payloadData);

        $this->dispatchRequestJob($payloadData);
    }

    public function trackHubspotEvent(string $merchantEmail,
                                      array $payload)
    {
        $payload['email'] = $merchantEmail;
        $this->dispatchRequestJob($payload);
    }

    public function trackLinkedAccountCreation($email)
    {
        if ($email === null)
        {
            return;
        }

        $payload = [
            'email'                 => $email,
            'linked_account_flag'   => true,
        ];

        $this->dispatchRequestJob($payload);
    }

    public function skipMerchantOnboardingComm(string $email)
    {
        $payloadData[Constants::SKIP_SUBM_ONBOARDING_COMM] = true;

        $this->trackHubspotEvent($email, $payloadData);
    }

    public function trackSubmerchantSignUp($partnerEmail)
    {
        //Incase of mobile signup, partner email can be empty, so adding a null check here
        if (empty($partnerEmail) === true)
        {
            return;
        }

        $partnerEventPayload = [];

        $partnerEventPayload['new_submerchant_added'] = true;

        $this->trackHubspotEvent($partnerEmail, $partnerEventPayload);

    }

    protected function appendPrefixToArray(array & $payloadData, $prefix)
    {
        $prefix_array = array_fill(0, count($payloadData), $prefix);

        $prefix_key_array = array_map(function($key, $prefix) {

            $prefixKey = $prefix;

            return $prefixKey . $key;

        }, array_keys($payloadData), $prefix_array);

        $payloadData = array_combine($prefix_key_array, $payloadData);
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
            Merchant\Detail\Entity::BANK_ACCOUNT_NUMBER,
            Merchant\Detail\Entity::COMPANY_CIN,
            Merchant\Detail\Entity::COMPANY_PAN
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
            'headers' => [
                RequestHeader::CONTENT_TYPE  => self::APPLICATION_JSON,
            ],
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

    private function addProductType(array & $payloadData)
    {
        if (empty($this->app['basicauth']) === false)
        {
            $payloadData['product_type'] = $this->app['basicauth']->getRequestOriginProduct();
        }
    }

    /**
     * @param $payloadData
     */
    private function addCommonProperties(&$payloadData): void
    {
        $this->addProductType($payloadData);

        $this->addRequestSource($payloadData);
    }
}
