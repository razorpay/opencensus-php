<?php

namespace App\RZP;

use Trace;
use Config;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Entity as ApiEntity;
use Razorpay\Api\Request as ApiRequest;
use GuzzleHttp\Exception\GuzzleException;
use Razorpay\Api\Errors\ServerError as ServerError;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;

class Admin extends Entity
{
    const RECONCILIATION_URL = 'reconciliate';

    public function fetchEntityById($entity, $id)
    {
        $relativeUrl = $this->getEntityUrl().$entity.'/'.$id;

        return $this->request('GET', $relativeUrl);
    }

    public function fetchMultipleEntities($entity, $options = [])
    {
        $relativeUrl = $this->getEntityUrl().$entity;

        return $this->request('GET', $relativeUrl, $options);
    }

    public function makeReconciliateRequest($input, $mode = 'live')
    {
        Trace::error(TraceCode::MAKE_RECONCILIATION_REQUEST, [
                         'mode'             => $mode,
                         'attachment-count' => $input['attachment-count'],
                     ]);

        // Makes a guzzle file request
        $response = $this->makeGuzzleFileRequest($input, $mode);

        // Builds an entity from the response received
        return ApiEntity::buildEntity($response);
    }

    public function makeGuzzleFileRequest($input, $mode = 'live')
    {
        // Creates a new Guzzle client
        $client = new Guzzle([
            'base_uri' => ApiUrl::getApiBaseUrl(),
            'defaults' => [
                'timeout' => Config::get('api.request_timeout'),
            ]
        ]);

        // Sets the options for the request. Auth should be part of this.
        $options = [
            // For reconciliation route, auth is not required.
            // But, sending it just for the sake of it.
            'auth'    => $this->getApiCredentials($mode),
            'headers' => ApiRequest::getHeaders(),
        ];

        $options['multipart'] = $this->getOutGoingMultipartData($input);

        return $this->sendGuzzleFileRequest($client, self::RECONCILIATION_URL, $options);
    }

    private function getOutGoingMultipartData($data): array
    {
        $outGoingData = [];

        foreach ($data as $key => $val)
        {
            if (is_array($val))
            {
                $data = $this->flatten($data, $val, $key);

                unset($data[$key]);
            }
        }

        foreach ($data as $key => $value)
        {
            if (is_int($key) === true)
            {
                $key = strval($key);
            }

            if ($value instanceof \SplFileInfo)
            {
                $fileName = $value->getClientOriginalName();

                $outGoingData[] =
                    [
                        'name'     => $key,
                        'contents' => Utils::tryFopen($value, 'r'),
                        'filename' => $fileName
                    ];
            }
            else
            {
                $outGoingData[] =
                    [
                        'name'     => $key,
                        'contents' => $value,
                    ];
            }
        }

        return $outGoingData;
    }

    protected function flatten($parent, $array, $prefix)
    {

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $parent = $this->flatten($parent, $value, $prefix . '[' . $key . ']');
            }
            else
            {
                $parent[$prefix . '[' . $key . ']'] = $value;
            }
        }

        return $parent;
    }

    /**
     * @throws BadRequestError
     * @throws ServerError
     */
    protected function sendGuzzleFileRequest($client, $path, $methodArgs)
    {
        try
        {
            $start_time = microtime(true);

            $response = $client->post($path, $methodArgs);

            $end_time = microtime(true);

            $time_taken = $end_time - $start_time;

            // log if response time is more than 180 seconds
            if ($time_taken > 180)
            {
                Trace::info(TraceCode::API_SLOW_RESPONSE_CALL, [
                    'api_response_time' => $time_taken,
                ]);
            }

            // gets the response body
            return json_decode($response->getBody(), true);
        }
        catch (GuzzleException $ex)
        {

            Trace::error(
                TraceCode::API_GUZZLE_EXCEPTION,
                [
                    'message'         => $ex->getMessage(),
                    'api_status_code' => $ex->getCode(),
                ]);

            throw new BadRequestError(
                $ex->getMessage(), $ex->getCode(),
                $ex->getCode(),
            );
        }
        catch (\Throwable $ex)
        {

            Trace::error(
                TraceCode::API_REQUEST_FAILURE,
                [
                    'message' => $ex->getMessage(),
                ]);

            $exceptionResponse = $ex->getResponse();

            if ($exceptionResponse->getReasonPhrase() === 'Bad Request')
            {
                // Bad request error is being handled in Merchant/Service
                throw new BadRequestError(
                    $exceptionResponse->json()['error']['description'], $ex->getCode(),
                    $exceptionResponse->getStatusCode()
                );
            }
            else
            {
                throw new ServerError(
                    $exceptionResponse->json()['error']['description'], $ex->getCode(),
                    $exceptionResponse->getStatusCode());
            }
        }
    }

    public function deleteFilesLocally($filePaths)
    {
        foreach ($filePaths as $filePath)
        {
            if (file_exists($filePath))
            {
                $success = unlink($filePath);  // nosemgrep : php.lang.security.unlink-use.unlink-use

                if ($success === false)
                {
                    // TODO: What do we do?
                }
            }
        }
    }

    protected function getApiCredentials($mode = 'live')
    {
        $id = 'rzp_' . $mode;

        $secret = Config::get('api.auth_pass');

        return [$id, $secret];
    }

    protected function moveAndGetFilePath($file)
    {
        $destinationPath = storage_path('files/reconciliation');
        //$fileName = $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
        $fileName = $file->getClientOriginalName();
        $file->move($destinationPath, $fileName);

        $filePath = $destinationPath . '/' . $fileName;

        return $filePath;
    }

    protected function getEntityUrl()
    {
        $fullClassName = get_class($this);
        $pos = strrpos($fullClassName, '\\');
        $className = substr($fullClassName, $pos + 1);
        $className = lcfirst($className);
        return $className.'/';
    }
}
