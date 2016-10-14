<?php

namespace App\RZP;

use Config;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Entity as ApiEntity;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\ServerError as ServerError;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;

class Batch extends Entity
{
    const BATCH_FILE_URL = 'batches';

    public function fetchById($id)
    {
        return parent::fetch($id);
    }

    public function fetchMultiple($input)
    {
        return parent::all($input);
    }

    public function uploadBatchFile($mode, $merchantId, $input)
    {
        // Makes a guzzle file request
        $response = $this->makeGuzzleFileRequest($mode, $merchantId, $input);

        return $response;
    }

    public function downloadBatchFile($id)
    {
        $relativeUrl = "batches/$id/download";

        return $this->request('GET', $relativeUrl);
    }

    public function retryBatchFile($id)
    {
        $relativeUrl = "batches/$id/retry";

        return $this->request('POST', $relativeUrl);
    }

    protected function makeGuzzleFileRequest($mode, $merchantId, $input)
    {
        // Creates a new Guzzle client
        $client = new Guzzle([
                                'base_url' => Config::get('api.url')
                            ]);

        // Sets the options for the request. Auth should be part of this.
        $options = [
                'auth'      => $this->getApiCredentials($mode, $merchantId),
                'headers'   => ApiRequest::getHeaders(),
                'body'      => [
                                    'type' => $input['type']
                                ],
                ];

        // Creates a request instance
        $request = $client->createRequest('POST', self::BATCH_FILE_URL, $options);

        // Creates an object to insert post body data
        $postBody = $request->getBody();

        $filePath = $input['file']->getRealPath();

        $postFile = new PostFile('file', fopen($filePath, 'r'));

        // Inserts file into the post body data
        $postBody->addFile($postFile);

        return $this->sendGuzzleFileRequest($client, $request, $filePath);
    }

    protected function sendGuzzleFileRequest($client, $request, $filePath)
    {
        try
        {
            $response = $client->send($request);

            $jsonResponse = $response->json();

            return $jsonResponse;
        }
        catch (\Exception $ex)
        {
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
            $this->deleteFileLocally($filePath);
        }
    }

    protected function deleteFileLocally($filePath)
    {
        if (file_exists($filePath))
        {
            $success = unlink($filePath);

            if ($success === false)
            {
                // TODO: What do we do?
            }
        }
    }

    protected function getApiCredentials($mode, $merchantId)
    {
        $id = 'rzp_' . $mode . '_' . $merchantId;

        $secret = Config::get('api.auth_pass');

        return [$id, $secret];
    }

    protected function getEntityUrl()
    {
        return 'batches/';
    }
}
