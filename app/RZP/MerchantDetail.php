<?php

namespace App\RZP;

use Config;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Entity as ApiEntity;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\ServerError as ServerError;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;

class MerchantDetail extends Entity
{
    public function submitDetails($input)
    {
        $error = $response = null;

        try
        {
            $relativeUrl = 'merchant/activation';

            $response = $this->request('POST', $relativeUrl, $input)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [ $e->getMessage() ];
        }

        return [ $error, $response ];
    }

    public function uploadActivationFile($merchantId, $input)
    {
        // Makes a guzzle file request
        $response = $this->makeGuzzleFileRequest('live', $merchantId, $input);

        return $response;
    }

    protected function makeGuzzleFileRequest($mode, $merchantId, $input)
    {
        $fileType = key($input);

        // Creates a new Guzzle client
        $client = new Guzzle([
                                'base_url' => Config::get('api.url')
                            ]);

        // Sets the options for the request. Auth should be part of this.
        $options = [
                'auth'      => $this->getApiCredentials($mode, $merchantId),
                'headers'   => ApiRequest::getHeaders(),
                'body'      => [],
                ];

        // Creates a request instance
        $request = $client->createRequest('POST', 'merchant/activation/upload_file', $options);

        // Creates an object to insert post body data
        $postBody = $request->getBody();

        $filePath = $input[$fileType]->getRealPath();

        $postFile = new PostFile($fileType, fopen($filePath, 'r'));

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
}
