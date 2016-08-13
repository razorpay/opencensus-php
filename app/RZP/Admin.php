<?php

namespace App\RZP;

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

    public function sendTestNewsletter($params)
    {
        $relativeUrl = $this->getEntityUrl(). 'newsletter/test';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function sendNewsletter($params)
    {
        $relativeUrl = $this->getEntityUrl(). 'newsletter/mail';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function triggerError()
    {
        $relativeUrl = 'trigger/error';
        $response = null;
        try
        {
            $this->request('GET', $relativeUrl);
        }
        catch(\Razorpay\Api\Errors\ServerError $e)
        {
            return ['msg' => "Error triggered with msg {$e->getMessage()}"];
        }

        return false;
    }

    public function makeReconciliateRequest($input)
    {
        // Makes a guzzle file request
        $response = $this->makeGuzzleFileRequest($input);

        // Builds an entity from the response received
        return ApiEntity::buildEntity($response);
    }

    public function makeGuzzleFileRequest($input)
    {
        // Creates a new Guzzle client
        $client = new Guzzle(['base_url' => Config::get('api.url')]);

        // Sets the options for the request. Auth should be part of this.
        $options = array(
            // For reconciliation route, auth is not required.
            // But, sending it just for the sake of it.
            'auth'      => $this->getApiCredentials(),
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
            $response = $client->send($request);

            // json() gets the response body
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
                $success = unlink($filePath);

                if ($success === false)
                {
                    // TODO: What do we do?
                }
            }
        }
    }

    protected function getApiCredentials()
    {
        $mode = 'live';

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
