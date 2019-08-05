<?php

namespace RZP\Services;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Jobs\RequestJob;

class HubspotClient
{
    protected $baseUrl;

    protected $secret;

    protected $config;

    protected $trace;

    protected $eventData = [];

    protected $relativeUrls =
        [
            'update_contact_properties_by_email' => 'contacts/v1/contact/createOrUpdate/email/',
        ];

    public function __construct($app)
    {
        $this->trace   = $app['trace'];

        $this->config  = $app['config']->get('applications.hubspot');

        $this->baseUrl = $this->config['url'];

        $this->secret  = $this->config['secret'];
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

        $payloadData['email'] = $merchant['email'];

        $payloadData['mid'] = $merchant['id'];

        $this->dispatchRequestJob($payloadData);
    }

    public function dispatchRequestJob(array $payloadData)
    {
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

        RequestJob::dispatch($request);
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
