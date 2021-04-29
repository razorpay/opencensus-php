<?php

namespace RZP\Models\BankingAccount;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;

class GoogleMapApi
{
    CONST BASEURL         = "https://maps.googleapis.com/maps/api/geocode/json?address=";

    CONST ENDURL          = "&components=country:IN&key=";

    const REQUEST_TIMEOUT = 5;

    protected $apiKey;

    protected $mock;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->apiKey = ($app['config']->get('applications.banking_account'))['apiKey'];

        $this->mock = ($app['config']->get('applications.banking_account'))['mock'];
    }

    public function getLocationFromPincode($pincode): array
    {
        try
        {
            if ($this->mock === true)
            {
                return [28.5388479, 77.2753728, null];
            }

            $response = $this->sendRequest($pincode, Requests::GET);

            $lat1 = $response['results'][0]['geometry']['location']['lat'];

            $lng1 = $response['results'][0]['geometry']['location']['lng'];

            return [$lat1, $lng1, null];
        }
        catch (Exception\RuntimeException | IntegrationException | BadRequestException $e)
        {
            if ($e->getMessage() === 'ZERO_RESULTS')
            {
                return [null, null, $e->getData()];
            }
            else
            {
                throw $e;
            }
        }
    }

    protected function sendRequest(string $pincode, string $method, string $data = null): array
    {
        $url = self::BASEURL . $pincode . self::ENDURL . $this->apiKey;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Accept'] = 'application/json';

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
        );

        $request = array(
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendRawRequest($request);

        $decodedResponse = json_decode($response->body, true);

        //check if $response is a valid json
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'External Operation Failed');
        }

        try
        {
            $this->checkErrors($decodedResponse);
        }
        catch (IntegrationException | BadRequestException $e)
        {
            throw $e;
        }

        return $decodedResponse;
    }

    protected function sendRawRequest(array $request)
    {
        $method = $request['method'];

        $response = null;

        switch($method)
        {
            case Requests::GET:
                $response = Requests::$method(
                    $request['url'],
                    $request['headers'],
                    $request['options']);
                break;
        }

        return $response;
    }

    protected function checkErrors(array $response)
    {
        if (isset($response['status']) === false)
        {
            throw new Exception\IntegrationException(
                'Third Party Error',
                null,
                $response
            );
        }

        if ($response['status'] === 'OK')
        {
            return;
        }

        if ($response['status'] === 'ZERO_RESULTS')
        {
            $errorMessage = 'Location does not exist';

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
                null,
                $errorMessage,
                $response['status']
            );
        }

        if ($response['status'] === 'Error')
        {
            $errorMessage = $response['message'] ?? 'Third Party Error';

            throw new Exception\IntegrationException(
                $errorMessage,
                null,
                $response
            );
        }

        throw new Exception\IntegrationException(
            'Something Went Wrong',
            null,
            $response);
    }
}
