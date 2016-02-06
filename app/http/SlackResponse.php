<?php

namespace Http;

use Response;
use Trace;

class SlackResponse
{
    public static function jsonResponse($text, $data = [])
    {
        $response = [
            "response_type" => "in_channel",
            "text"          => $text,
            "attachments"   => [(count($data) > 0) ? static::makeAttachments($data) : []]
        ];

        Trace::debug('SLACK_QUERY_RESPONSE', $response);

        return Response::json($response);
    }

    protected static function makeAttachments($data)
    {
        // Drop all nested fields

        foreach ($data as $key => $value)
        {
            if (! is_scalar($value))
            {
                unset($data[$key]);
            }
        }

        $fields = [];

        foreach ($data as $key => $value)
        {
            $fields[] = [
                'title' => $key,
                'value' => (string) $value,
                'short' => true
            ];
        }

        return [
            'fallback'  =>  "{$data['entity']} - {$data['id']}",
            'pretext'   =>  "Link to entity here",
            'fields'    =>  $fields
        ];
    }
}
