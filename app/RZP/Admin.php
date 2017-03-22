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
    const UPLOAD_ACTIVATION_FILE_PREFIX_URL = 'merchant/activation/';

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

    public function fetchMerchantIds($orgId, $adminId)
    {
        $relativeUrl = "orgs/$orgId/admins/$adminId/merchant_ids";

        return $this->request('GET', $relativeUrl);
    }

    public function fetchMerchants($orgId, $adminId, array $input)
    {
        $relativeUrl = "orgs/$orgId/admins/$adminId/merchants";

        return $this->request('GET', $relativeUrl, $input);
    }

    public function sendNewsletter($params)
    {
        $relativeUrl = $this->getEntityUrl(). 'newsletter/mail';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function logout($orgId)
    {
        $relativeUrl = "orgs/$orgId/admin/logout";

        return $this->request('POST', $relativeUrl);
    }

    public function forgotPassword($orgId, $input)
    {
        $relativeUrl = "orgs/$orgId/admin/forgot_password";

        return $this->request('POST', $relativeUrl, $input);
    }

    public function resetPassword($orgId, $input)
    {
        $relativeUrl = "orgs/$orgId/admin/reset_password";

        return $this->request('POST', $relativeUrl, $input);
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
        // Makes a guzzle file request for reconciliation
        $response = $this->makeGuzzleFileRequest($input, self::RECONCILIATION_URL);

        // Builds an entity from the response received
        return ApiEntity::buildEntity($response);
    }

    public function uploadActivationFile(string $merchantId, array $input)
    {
        $url = self::UPLOAD_ACTIVATION_FILE_PREFIX_URL . $merchantId . '/files';

        $response = $this->makeGuzzleActivationFileRequest($input, $url);

        return ApiEntity::buildEntity($response);
    }

    protected function getGuzzleClient(array $input)
    {
        // Sets the options for the request. Auth should be part of this.
        $options = [
            'defaults'  =>  [
                'auth'      => $this->getApiCredentials(),
                'headers'   => ApiRequest::getHeaders(),
            ],
            'base_url' => Config::get('api.url')
        ];

         // Creates a new Guzzle client
        return new Guzzle($options);
    }

    public function makeGuzzleActivationFileRequest(array $input, string $url, string $method = 'POST')
    {
        $fileType = key($input);

        $client  = $this->getGuzzleClient($input);

        $request = $client->createRequest($method, $url);

        $postBody = $request->getBody();

        $filePath = $input[$fileType]->getRealPath();

        $postFile = new PostFile($fileType, fopen($filePath, 'r'));

        // Inserts file into the post body data
        $postBody->addFile($postFile);

        return $this->sendGuzzleFileRequest($client, $request, [$filePath]);
    }

    public function makeGuzzleFileRequest(array $input, string $url, string $method = 'POST')
    {
        $client = $this->getGuzzleClient($input);

        $request = $client->createRequest($method, $url);

        // Creates an object to insert post body data
        $postBody = $request->getBody();

        $filePaths = $this->addFilesToRequest($postBody, $input);

        return $this->sendGuzzleFileRequest($client, $request, $filePaths);
    }

    protected function addFilesToRequest($postBody, $input)
    {
        $filePaths = [];

        // This is used in case of Make API Call in actions tab
        if (!isset($input['attachment-count']))
        {
            return $filePaths;
        }

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

    public function passwordLogin($orgId, array $params)
    {
        $relativeUrl = "orgs/$orgId/admin/authenticate";

        return $this->request('POST', $relativeUrl, $params);
    }

    public function oAuthLogin(array $params, $orgId)
    {
        // $relativeUrl = $this->getEntityUrl().'oauth_login';
        $relativeUrl = "orgs/$orgId/admin/oauth_login";

        return $this->request('POST', $relativeUrl, $params);
    }

    public function getByEmail($orgId, $options)
    {
        // $relativeUrl = "orgs/$orgId/admins/get_by_attr";
        $relativeUrl = "admins/get-multiple-app-auth";

        return $this->request('GET', $relativeUrl, $options);
    }

    public function updateAdmin($orgId, $adminId, $params)
    {
        // $relativeUrl = "orgs/$orgId/admins/$adminId";
        $relativeUrl = "orgs/$orgId/admin-app-auth/$adminId";

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function getAdminData($orgId, $body)
    {
        $relativeUrl = "orgs/$orgId/current_admin";

        return $this->request('POST', $relativeUrl, $body);
    }

    public function getFileByAdmin($fileId)
    {
        $relativeUrl = "files/$fileId/signed-url";

        return $this->rawRequest('GET', $relativeUrl);
    }
}
