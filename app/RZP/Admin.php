<?php

namespace App\RZP;

use Trace;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;

use Config;
use Razorpay\Api\Entity as ApiEntity;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;
use Razorpay\Api\Errors\ServerError as ServerError;

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
        // Makes a guzzle file request
        $response = $this->makeGuzzleFileRequest($input, $mode);

        // Builds an entity from the response received
        return ApiEntity::buildEntity($response);
    }

    public function makeGuzzleFileRequest($input, $mode = 'live')
    {
        // Creates a new Guzzle client
        $client = new Guzzle([
            'base_url' => ApiUrl::getApiBaseUrl(),
            'defaults' => [
                'timeout' => Config::get('api.request_timeout'),
            ]
        ]);

        // Sets the options for the request. Auth should be part of this.
        $options = array(
            // For reconciliation route, auth is not required.
            // But, sending it just for the sake of it.
            'auth'      => $this->getApiCredentials($mode),
            'headers'   => ApiRequest::getHeaders(),
            // TODO: Check if $postBody->setField() can be used, instead.
            'body'      => [
                $input
            ]
        );

        // Creates a request instance
        $request = $client->createRequest("POST", self::RECONCILIATION_URL, $options);

        // Creates an object to insert post body data
        $postBody = $request->getBody();

        $filePaths = $this->addFilesToRequest($postBody, $input);

        return $this->sendGuzzleFileRequest($client, $request, $filePaths);
    }

    protected function addFilesToRequest($postBody, $input)
    {
        $filePaths = [];

        foreach (range(1, $input['attachment-count']) as $attachmentNumber)
        {
            $inputFileName = 'attachment-' . $attachmentNumber;

            $filePath = $this->moveAndGetFilePath($input[$inputFileName]);

            $postFile = new PostFile($inputFileName, fopen($filePath, 'r'));

            $postBody->addFile($postFile);

            $filePaths[] = $filePath;
        }

        return $filePaths;
    }

    protected function sendGuzzleFileRequest($client, $request, $filePaths)
    {
        try
        {
            $start_time = microtime(true);

            $response = $client->send($request);

            $end_time = microtime(true);

            $time_taken = $end_time - $start_time;

            // log if response time is more then 180 seconds
            if ($time_taken > 180)
            {
                Trace::info(TraceCode::API_SLOW_RESPONSE_CALL, [
                    'api_response_time' => $time_taken,
                ]);
            }

            // json() gets the response body
            $jsonResponse = $response->json();

            return $jsonResponse;
        }
        catch (\Exception $ex)
        {

            Trace::error(
                TraceCode::API_REQUEST_FAILURE,
                [
                    'message'           => $ex->getMessage(),
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
        finally
        {
            // Delete the local file created after the request is made.
            $this->deleteFilesLocally($filePaths);
        }

        return null;
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
