<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use GuzzleHttp\Client as HttpClient;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

/**
 * handles GupShup Whatsapp notification consent
 */
class GupShup extends Base\Core
{
    const GUPSHUP_OPT_IN_USER_URL = 'https://media.smsgupshup.com/GatewayAPI/rest';

    public function callGupShupConsent(string $phone)
    {
        $noiseGupShupCred = [
            [
                'userId'   => env('NOISE_GUPSHUP_USER_ID_1')?? null,
                'password' => env('NOISE_GUPSHUP_PASSWORD_1')?? null,
            ],
            [
                'userId'   => env('NOISE_GUPSHUP_USER_ID_2')?? null,
                'password' => env('NOISE_GUPSHUP_PASSWORD_2')?? null,
            ],
        ];

        // Need to send the customer consent to 2 gupshup account, need to run in loop.
        foreach ($noiseGupShupCred  as $value) {

            if (empty($value['userId']) || empty($value['password']))
            {
                continue;
            }

            $params = '?userid='.$value['userId'].'&password='. $value['password'].'&phone_number='.$phone.'&method=OPT_IN&auth_scheme=plain&v=1.1&channel=WHATSAPP&format=json';

            $response = $this->sendConsentRequest($params,'POST');

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_UPDATE_CUSTOMER,
                [
                    'type'         => 'customer_consent_gupshup_res',
                    'res'          => $response->getBody()->getContents(),
                    'status_code'  => $response->getStatusCode(),
                    'reason'       => $response->getReasonPhrase(),
                ]
            );
        }
    }

    public function sendConsentRequest($params, string $method)
    {
        $headers = [
            'Content-type' => 'application/x-www-form-urlencoded',
        ];
        
        try
        {
            $response = (new HttpClient)->request($method, self::GUPSHUP_OPT_IN_USER_URL.$params, [
                'headers' => $headers
            ]);

            return $response;
        }
        catch (GuzzleRequestException $e)
        {
            $errResponse = $e->getResponse();

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_UPDATE_CUSTOMER,
                [
                    'type'         => 'customer_consent_gupshup_error',
                    'error'        => $errResponse,
                ]
            );
        }
    }
}