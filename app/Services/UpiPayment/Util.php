<?php

namespace RZP\Services\UpiPayment;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\Base\PublicEntity;

class Util
{
    /**
     * converts the input object array to array
     *
     * @param  array $input
     * @return void
     */
    public static function convertInputToArray(array &$input)
    {
        if (empty($input[Entity::TERMINAL]) === false)
        {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();
        }

        foreach ($input as $key => $data)
        {
            if ((is_object($data) === true) and ($data instanceof PublicEntity))
            {
                $input[$key] = $data->toArray();
            }
        }
    }

    /**
     * Returns URI for the specified action
     *
     * @param  string $action
     * @return string
     */
    public static function getUri(string $action): string
    {
        switch ($action)
        {
            case Action::AUTHORIZE:
                return Request::AUTHORIZE_URI;

            default:
                throw new Exception\LogicException('No supported actions found for UPS');
                break;
        }
    }

    /**
     * returns the Auth details for UPS from config
     *
     * @return array
     */
    public static function getAuthenticationArray(string $username, string $password): array
    {
        $authentication = [
            $username,
            $password,
        ];

        return $authentication;
    }

    /**
     * Trace the authorize request send to UPS
     *
     * @param  array $request
     * @return void
     */
    public static function getAuthorizeTraceData(array $request)
    {
        $content = $request['content'];

        $data = [
            Request::URL        => $request[Request::URL],
            Request::METHOD     => $request[Request::METHOD],
            Constant::GATEWAY    => $content[Request::PAYMENT][Constant::GATEWAY] ?? null,
            Request::PAYMENT     => [
                Constant::ID        => $content[Request::PAYMENT][Constant::ID] ?? null,
                Constant::AMOUNT    => $content[Request::PAYMENT][Constant::AMOUNT] ?? null,
                Constant::CURRENCY  => $content[Request::PAYMENT][Constant::CURRENCY] ?? null,
                Constant::CPS_ROUTE => $content[Request::PAYMENT][Constant::CPS_ROUTE] ?? null,
            ],
            Request::METADATA   => [
                Constant::FLOW      => $content[Request::METADATA][Constant::FLOW] ?? null,
                Constant::TYPE      => $content[Request::METADATA][Constant::TYPE] ?? null,
            ],
            Request::MERCHANT   => [
                Constant::BILLING_LABEL  => $content[Request::MERCHANT][Constant::BILLING_LABEL] ?? null,
            ],
        ];

        return $data;
    }

    /**
     * Converts Json String To Array
     *
     * @param  string $body
     * @return array
     */
    public static function jsonToArray(string $body): array
    {
        $responseData = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE)
        {
            return $responseData;
        }

        throw new Exception\RuntimeException(
            'Error Decoding the JSON response from UPS',
            ['json' => $body],
            null,
            ErrorCode::SERVER_ERROR_FAILED_TO_CONVERT_JSON_TO_ARRAY);
    }

    /**
     * Converts Array to Json string
     *
     * @param  array $data
     * @return string
     */
    public static function arrayToJsonString(array $data): string
    {
        $jsonEncodedData = json_encode($data);

        if (json_last_error() === JSON_ERROR_NONE)
        {
            return $jsonEncodedData;
        }

        throw new Exception\RuntimeException(
            'Error encoding array to JSON string',
            ['array' => $data],
            null,
            ErrorCode::SERVER_ERROR_FAILED_TO_CONVERT_ARRAY_TO_JSON);
    }
}
