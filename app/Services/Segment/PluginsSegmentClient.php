<?php
namespace RZP\Services\Segment;

use RZP\Trace\TraceCode;
use Validator;

class PluginsSegmentClient extends SegmentAnalyticsClient
{
    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('services.plugins-segment');
    }

    public function sendEventToSegment($input)
    {
        try
        {
            $validator = Validator::make(
                $input,
                [
                    'event'      => 'required|string',
                    'properties' => 'required|array',
                ],
                [
                    'required' => 'The :attribute is required input.',
                    'string'   => 'The :attribute required in string format.',
                    'array'    => 'The :attribute required in array format.',
                ]
            );

            if ($validator->fails() === true)
            {
                return [
                    'status'  => 'error',
                    'message' => 'Input validation failed',
                    'errors'  => $validator->errors()->all()
                ];
            }

            $eventName  = $input['event'];
            $properties = $input['properties'];

            $merchant = $this->app['basicauth']->getMerchant();

            $properties['merchant_id'] = $merchant->getId();

            $this->trace->info(TraceCode::PLUGIN_SEGMENT_EVENT_DISPATCH,
                [
                    'merchant_id' => $properties['merchant_id'],
                    'event_name'  => $eventName
                ]);

            $eventData = [
                'events' => [
                    [
                        'type'   => 'identify',
                        'userId' => $properties['merchant_id'],
                        'traits' => $properties
                    ],
                    [
                        'type'       => 'track',
                        'userId'     => $properties['merchant_id'],
                        'event'      => $eventName,
                        'properties' => $properties,
                    ],
                ]
            ];

            $this->pushPluginsIdentifyandTrackEvent($eventData);

            $response = ['status' => 'success'];
        }
        catch(\Throwable $e)
        {
            $this->trace->error(TraceCode::USER_FETCH_FAILED_FOR_SEGMENT_EVENT,
                [
                    'merchant_id' => $properties['merchant_id'] ?? null,
                    'event_name'  => $eventName,
                    'exception'   => $e->getMessage(),
                ]);

            $response = ['status' => 'error'];
        }

        return $response;
    }

    public function pushPluginsIdentifyandTrackEvent($eventData)
    {
        try
        {
            if (empty($eventData) === true)
            {
                return false;
            }

            $writeKey = $this->config['auth']['write_key'];

            $headers = [
                'content-type'  => self::CONTENT_TYPE,
                'Authorization' => 'Basic '. base64_encode($writeKey . ':' . ''),
            ];

            $url = $this->config['url'] . $this->urlPattern;

            foreach ($eventData as $eventDataChunk)
            {
                $payload = [
                    'batch' => $eventDataChunk
                ];

                $this->sendEventRequest($headers, $url, $payload, true);
            }

            $this->flushEvents();
        }
        catch (\Exception $e)
        {
            $errorContext = [
                'class'   => get_class($this),
                'message' => $e->getMessage(),
                'type'    => 'plugins-segment'
            ];

            $this->trace->error(TraceCode::EVENT_PLUGINS_FAILED, $errorContext);
        }
    }
}
