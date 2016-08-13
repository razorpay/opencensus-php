<?php

namespace App\Http;

use Response;
use Trace;

class SlackResponse
{

    protected static $filteredKeys = [
        'admin',
    ];

    public static function jsonResponse($text, $data = [])
    {
        $response = [
            "response_type" => "in_channel",
            "text"          => $text,

        ];

        if (count($data) >0)
        {
            $response['attachments'] = [static::makeAttachments($data)];
        }

        Trace::debug('SLACK_QUERY_RESPONSE', $response);

        return Response::json($response);
    }

    protected static function makeAttachments($data)
    {
        $data = static::cleanData($data);

        $fields = [];

        foreach ($data as $key => $value)
        {
            $fields[] = [
                'title' => $key,
                'value' => static::transformValue($value),
                'short' => true
            ];
        }

        return [
            'fallback'  =>  "Entity can't be displayed here",
            'fields'    =>  $fields
        ];
    }

    protected static function transformValue($value)
    {
        // We can't use switch here because
        // switch uses loose equality checks
        if ($value === true)
        {
            return "true";
        }
        elseif ($value === false)
        {
            return "false";
        }

        return $value;
    }

    // Drops filtered keys and drops null values
    protected static function cleanData($data)
    {
        return $data;

        // Move to the following once php 5.6 is live
        return array_filter($data, function ($value, $key) {

            if ((in_array($key, static::$filteredKeys))or
                ($value === null) or
                ($value === "") or
                (! is_scalar($value)))
            {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }
}
