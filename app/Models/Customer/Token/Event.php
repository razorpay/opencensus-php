<?php


namespace RZP\Models\Customer\Token;

use App;
use phpDocumentor\Reflection\Types\Boolean;
use RZP\Error\Error;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;

class Event extends Base\Core
{
    const NETWORK_TOKENISATION         = 'NETWORK_TOKENISATION';

    const FETCH_TOKEN                  = 'FETCH_TOKEN';

    const NETWORK_CRYPTOGRAM           = 'NETWORK_CRYPTOGRAM';

    const DELETE_TOKEN                 = 'DELETE_TOKEN';

    const PAR_API                      = 'PAR_API';

    const UPDATE_TOKEN                 = 'UPDATE_TOKEN';

    const MIGRATE_TOKEN                = 'MIGRATE_TOKEN';

    const FETCH_FINGERPRINT            = 'FETCH_FINGERPRINT';

    const EVENT_TYPE                   = 'token-events';

    const EVENT_VERSION                = 'v1';


    const ACTION_EVENT_MAPPING = [
        Action::CREATE                   => self::NETWORK_TOKENISATION,
        Action::FETCH                    => self::FETCH_TOKEN,
        Action::CRYPTOGRAM               => self::NETWORK_CRYPTOGRAM,
        Action::DELETE                   => self::DELETE_TOKEN,
        Action::PAR_API                  => self::PAR_API,
        Action::MIGRATE                  => self::MIGRATE_TOKEN,
        Action::UPDATE                   => self::UPDATE_TOKEN,
        Action::FETCH_FINGERPRINT        => self::FETCH_FINGERPRINT
    ];

    const EVENT_STRING_CODE_MAPPING = [
        "NETWORK_TOKENISATION_REQUEST_SENT"                  => EventCode::NETWORK_TOKENISATION_REQUEST_SENT,
        "NETWORK_TOKENISATION_RESPONSE_RECEIVED"             => EventCode::NETWORK_TOKENISATION_RESPONSE_RECEIVED,
        "FETCH_TOKEN_REQUEST_RECEIVED"                       => EventCode::FETCH_TOKEN_REQUEST_RECEIVED,
        "FETCH_TOKEN_REQUEST_PROCESSED"                      => EventCode::FETCH_TOKEN_REQUEST_PROCESSED,
        "FETCH_FINGERPRINT_REQUEST_RECEIVED"                 => EventCode::FETCH_FINGERPRINT_REQUEST_RECEIVED,
        "FETCH_FINGERPRINT_REQUEST_PROCESSED"                => EventCode::FETCH_FINGERPRINT_REQUEST_PROCESSED,
        "NETWORK_CRYPTOGRAM_REQUEST_RECEIVED"                => EventCode::NETWORK_CRYPTOGRAM_REQUEST_RECEIVED,
        "NETWORK_CRYPTOGRAM_REQUEST_SENT"                    => EventCode::NETWORK_CRYPTOGRAM_REQUEST_SENT,
        "NETWORK_CRYPTOGRAM_RESPONSE_RECEIVED"               => EventCode::NETWORK_CRYPTOGRAM_RESPONSE_RECEIVED,
        "NETWORK_CRYPTOGRAM_RESPONSE_SENT"                   => EventCode::NETWORK_CRYPTOGRAM_RESPONSE_SENT,
        "PAR_API_REQUEST_RECEIVED"                           => EventCode::PAR_API_REQUEST_RECEIVED,
        "PAR_API_REQUEST_SENT"                               => EventCode::PAR_API_REQUEST_SENT,
        "PAR_API_RESPONSE_RECEIVED"                          => EventCode::PAR_API_RESPONSE_RECEIVED,
        "PAR_API_RESPONSE_SENT"                              => EventCode::PAR_API_RESPONSE_SENT,
        "DELETE_TOKEN_REQUEST_SENT"                          => EventCode::DELETE_TOKEN_REQUEST_SENT,
        "DELETE_TOKEN_RESPONSE_RECEIVED"                     => EventCode::DELETE_TOKEN_RESPONSE_RECEIVED,
        "MIGRATE_TOKEN_REQUEST_SENT"                         => EventCode::MIGRATE_TOKEN_REQUEST_SENT,
        "MIGRATE_TOKEN_RESPONSE_RECEIVED"                    => EventCode::MIGRATE_TOKEN_RESPONSE_RECEIVED,
        "UPDATE_TOKEN_REQUEST_SENT"                          => EventCode::UPDATE_TOKEN_REQUEST_SENT,
        "UPDATE_TOKEN_RESPONSE_RECEIVED"                     => EventCode::UPDATE_TOKEN_RESPONSE_RECEIVED,
    ];

    /**
     * @param $input
     * @param $event
     * @param $type
     * @param array $response
     * @param null $exe
     * @param string $library
     */
    public function pushEvents($input, $event, $type, $response = [], $exe = null, $library = 's2s'): void
    {
        try
        {
            if (isset($event) === false)
            {
                return;
            }

            $event = $event.''.$type;

            if ((array_key_exists($event,self::EVENT_STRING_CODE_MAPPING) === false)
                || (isset(self::EVENT_STRING_CODE_MAPPING[$event]) === false))
            {
                return;
            }

            $eventCode = self::EVENT_STRING_CODE_MAPPING[$event];

            $eventData = $this->fetchRequestEventData($library, $input);

            $eventData = $this->fetchResponseEventData($exe, $eventData, $response);

            $traceEventData = $eventData;

            unset($traceEventData['card_iin']);

            $this->trace->info(TraceCode::TOKEN_HQ_RESPONSE_EVENT_DATA, [
                'response'       => $response,
                'eventData'      => $traceEventData,
                'execption'      => $exe,
                'eventCode'      => $eventCode,
                'event'          => $event,
                'type'           => $type
            ]);

            $context = [
                'task_id' => $this->app['request']->getTaskId(),
                'request_id' => $this->app['request']->getId(),
                'library' => $library,
            ];

            $this->trackTokenEvent(self::EVENT_TYPE, self::EVENT_VERSION, $eventCode, $eventData, $metaData = null, $readKey = [] , $writeKey = null, $context);

        }
        catch (\Throwable $exc)
        {
            $this->trace->traceException(
                $exc,
                Trace::ERROR,
                TraceCode::TOKEN_HQ_EVENT_PUSH_FAILED,
                [
                    'event'     => $event ?? 'none',
                ]);
        }
    }

    protected function getDefaultExceptionEventResponse(\Throwable $e , array $eventData): array
    {
        $errorAttributes = [];

        if ($e instanceof Exception\BaseException)
        {
            if (($e->getError() !== null) and ($e->getError() instanceof Error))
            {
                $errorAttributes = $e->getError()->getAttributes();
            }
        }
        else
        {
            $errorAttributes = [
                'error_code'         => $e->getCode(),
            ];
        }

        $eventData += [
            'status'                    => 'FAILED',
            'error_code'                => array_get($errorAttributes, Error::INTERNAL_ERROR_CODE),
            'network_error_reason_code' => array_get($errorAttributes, Error::REASON_CODE),
            'network_error_description' => array_get($errorAttributes, Error::GATEWAY_ERROR_DESC),
        ];

        return $eventData;
    }

    /**
     * @param $exe
     * @param array $eventData
     * @param $response
     * @return array
     */
    protected function fetchResponseEventData($exe, array $eventData, $response = []): array
    {
        if (empty($response))
        {
            return $eventData;
        }

        if ($response instanceof \WpOrg\Requests\Response)
        {
            $response = json_decode($response->body, true);
        }

        $eventData = $this->getCardDetails($response, $eventData);

        if (isset($exe))
        {
            return $this->getDefaultExceptionEventResponse($exe , $eventData);
        }

        if ((isset($response['service_provider_tokens']) === true) && (empty($response['service_provider_tokens']) === false))
        {
            $sptToken = $response['service_provider_tokens'][0];

            $eventData += [
                'status'        => 'SUCCESS',
                'spt_token'     => isset($sptToken['id']) ? $sptToken['id'] : null,
                'provider_type' => isset($sptToken['provider_type']) ? $sptToken['provider_type'] : null,
                'payment_account_reference' => ((isset($sptToken['provider_data'])) && isset($sptToken['provider_data']['payment_account_reference']))
                    ? $sptToken['provider_data']['payment_account_reference'] : null,
                'token_iin' => ((isset($sptToken['provider_data'])) && isset($sptToken['provider_data']['token_iin']))
                    ? $sptToken['provider_data']['token_iin'] : null,
                'pan_reference_id'  => ((isset($sptToken['provider_data'])) && isset($sptToken['provider_data']['network_reference_id']))
                    ? $sptToken['provider_data']['network_reference_id'] : null,
            ];
        }

        $eventData += [
                'status' => isset($response['success']) ? $response['success'] : 'SUCCESS',
                'spt_token'  => isset($response['id']) ? $response['id'] : null,
                'token_id'      => isset($response['token']) ? $response['token'] : null,
                'payment_account_reference' => isset($response['fingerprint']) ? $response['fingerprint'] : null,
            ];

        return $eventData;
    }

    /**
     * @param string $library
     * @param $input
     * @return array
     */
    protected function fetchRequestEventData(string $library, $input): array
    {
        $eventData = [
            'internal_service_request'  => isset($input['internal_service_request']) ? $input['internal_service_request'] : null,
            'async'                     => isset($input['async']) ? $input['async'] : null,
        ];

        if (isset($input['merchant']))
        {
            $eventData += ['merchant_id' => $input['merchant']['id'],];
        }

        if (isset($input['iin']))
        {
            $eventData += $this->addPrefix("card_", $input['iin']);
        }

        if (isset($input['card_data']))
        {
            $eventData = array_merge($eventData, $input['card_data']);
        }

        if ((empty($input['spt_token']) === false) or (empty($input['is_service_provider_token']) === false))
        {
            $eventData += [
              'spt_token'       => isset($input['id']) ? $input['id'] :
                  (isset($input['service_provider_token']) ? $input['service_provider_token'] : null),
            ];
        }
        else
        {
            $eventData += [
                'token'                     =>  isset($input['id']) ? $input['id'] :
                                        (isset($input['token_id']) ? $input['token_id'] :
                                            (isset($input['token'])? $input['token'] : null)),
                'card_number_sent'          =>  isset($input['tokenised']) === false ? null : !$input['tokenised'],
            ];
        }

        return $eventData;
    }

    /**
     * @param $response
     * @param array $eventData
     * @return array
     */
    public function getCardDetails($response, $eventData)
    {
        if((isset($eventData['card_iin']) === false)
            && ((isset($response['service_provider_tokens'][0]['provider_data']['token_number']) === true)
            || isset($response['token_number']) === true))
        {
            $tokenNumber = isset($response['token_number']) === true ? $response['token_number'] : $response['service_provider_tokens'][0]['provider_data']['token_number'];

            $tokenIIN = substr($tokenNumber, 0, 9);

            $eventData['token_iin'] = $tokenIIN;

            $bin = substr($tokenNumber, 0, 6);

            $cardIIN = Card\IIN\IIN::getTransactingIinforRange($tokenIIN) ?? $bin;

            $iin = $this->repo->card->retrieveIinDetails($cardIIN);

            $eventData += [
                'card_issuer' => $iin->getIssuer(),
                'card_network' => $iin->getNetwork(),
                'card_category'=> $iin->getCategory(),
                'card_type'    => $iin->getType(),
            ];
        }
        return $eventData;
    }


    public function trackTokenEvent(string $eventType,
                                    string $eventVersion,
                                    array $event,
                                    array $properties,
                                    array $metaData = null,
                                    array $readKey = [] ,
                                    string $writeKey = null,
                                    array  $context = null)
    {
        $topicName = 'events' . '.' .  $eventType . '.' . $eventVersion . '.' .  $this->mode;

        $event = [
            'event_name'          => $event['name'],
            'event_type'          => $eventType,
            'event_group'         => $event['group'],
            'version'             => $eventVersion,
            'event_timestamp'     => (int)(microtime(true)),
            'producer_timestamp'  => (int)(microtime(true)),
            'source'              => 'payments-card',
            'mode'                => 'live',
            'context'             => $context,
            'properties'          => $properties,
        ];


        if (($eventVersion === 'v2') === true)
        {
            $event['metadata']       = $metaData;
            $event['read_key']       = $readKey;
            $event['write_key']      = $writeKey;
        }

        (new KafkaProducer($topicName, stringify($event)))->Produce();
    }

    public function addPrefix(string $prefix, array $input)
    {
        $prefixInput = [];

        foreach($input as $key => $value){
            $prefixInput[$prefix . $key] = $value;
        }

        return $prefixInput;
    }
}

