<?php

namespace App\RZP;

use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;

use Config;
use Razorpay\Api\Entity as ApiEntity;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;
use Razorpay\Api\Errors\ServerError as ServerError;

class Batch extends Entity
{
    const UPLOAD_BATCH_FILE = 'batches';

    public function fetchBatchById($id)
    {
        $relativeUrl = 'batches/' .$id;

        return $this->request('GET', $relativeUrl);
    }

    public function fetchMultipleBatches($input)
    {
        $relativeUrl = 'batches';

        unset($input['submit']);

        return $this->request('GET', $relativeUrl, $input);
    }

    public function uploadBatchFile($mode, $input)
    {
        // Makes a guzzle file request
        $response = $this->makeGuzzleFileRequest($mode, $input);

        return $response;
    }

    public function downloadBatchFile($id)
    {
        $relativeUrl = 'batches/' .$id .'/download';

        return $this->request('GET', $relativeUrl);
    }

    public function retryBatchFile($id)
    {
        $relativeUrl = 'batches/' .$id .'/retry';

        return $this->request('POST', $relativeUrl);
    }

    protected function makeGuzzleFileRequest($mode, $input)
    {
        // Creates a new Guzzle client
        $client = new Guzzle(['base_url' => Config::get('api.url')]);

        // Sets the options for the request. Auth should be part of this.
        $options = array(
            'auth'      => $this->getApiCredentials($mode, $input['merchant_id']),
            'headers'   => ApiRequest::getHeaders(),
            'body'      => ['type' => $input['type']],
        );

        // Creates a request instance
        $request = $client->createRequest("POST", self::UPLOAD_BATCH_FILE, $options);

        // Creates an object to insert post body data
        $postBody = $request->getBody();

        $filePath = $this->moveAndGetFilePath($input['file']);

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

    protected function getGuzzleInstance()
    {
        return new Guzzle([
            'base_uri' => Config::get('api.url'),
            'timeout'  => 200,
        ]);
    }

    protected function moveAndGetFilePath($file)
    {
        $destinationPath = storage_path('files/batches');
        $fileName = $file->getFilename() . '.' . $file->getClientOriginalExtension();

        $file->move($destinationPath, $fileName);

        $filePath = $destinationPath . '/' . $fileName;

        return $filePath;
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
