<?php

namespace App\RZP;

use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;

use Config;
use Razorpay\Api\Entity as ApiEntity;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;
use Razorpay\Api\Errors\ServerError as ServerError;

class Merchant extends Entity
{
    const CONFIG_URL = 'account/config';
    const CONFIG_LOGO_URL = 'account/config/logo';
    const SUBMERCHANT_CREATE_URL = 'submerchants';
    const BANK_ACCOUNT_URL = 'account/bank_account';

    public function create($params = null)
    {
        return parent::create($params);
    }

    /**
     * Creates a submerchant account
     * Uses Proxy Auth
    */
    public function createSubMerchant(array $params = [])
    {
        return $this->request('POST', self::SUBMERCHANT_CREATE_URL, $params);
    }

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all();
    }

    public function keys()
    {
        $entity = new Key;

        $entity->merchant_id = $this->id;

        return $entity;
    }

    public function activate()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/activate';

        return $this->request('POST', $relativeUrl);
    }

    /**
     * Enables live transactions for merchant
     */
    public function enable()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/live/enable';

        return $this->request('POST', $relativeUrl);
    }

    /**
     * disable live transactions for merchant
     */
    public function disable()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/live/disable';

        return $this->request('POST', $relativeUrl);
    }

    /**
     * Changes Payment methods for merchant
     */
    public function editMethods($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/methods';

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function edit($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id;

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function editEmail($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id . '/email';

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function fetchPricing()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/pricing';

        return $this->request('GET', $relativeUrl);
    }

    public function setPricing($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/pricing';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function fetchTerminals()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/terminals';

        return $this->request('GET', $relativeUrl);
    }

    public function setTerminal($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/terminals';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function fetchBanks()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/banks';

        return $this->request('GET', $relativeUrl);
    }

    public function setBanks($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/banks';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function fetchBankAccount()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/bank_account';

        return $this->request('GET', $relativeUrl);
    }

    public function setBankAccount($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/bank_account';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function getFeatures()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/features';

        return $this->request('GET', $relativeUrl);
    }

    public function setFeatures($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/features';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function fetchBalance()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/balance';

        return $this->request('GET', $relativeUrl);
    }

    public function generateBeneficiaryFile()
    {
        $relativeUrl = $this->getEntityUrl().'beneficiary/file';

        return $this->request('GET', $relativeUrl);
    }

    public function editCredits($params)
    {
        $relativeUrl = $this->getEntityUrl() . $this->id . '/credits';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function setId($id)
    {
        $this->attributes['id'] = $id;

        return $this;
    }

    public function fetchConfig()
    {
        return $this->request('GET', self::CONFIG_URL);
    }

    // This is on proxy auth, doesn't take merchant ID
    public function updateConfig($input)
    {
        return $this->request('PUT', self::CONFIG_URL, $input);
    }

    public function updateLogoConfig($input)
    {
        // Makes a guzzle file request
        $response = $this->makeGuzzleFileRequest($input);

        // Builds an entity from the response received
        $response = ApiEntity::buildEntity($response);

        return $response;
    }

    public function makeGuzzleFileRequest($input)
    {
        // Creates a new Guzzle client
        $client = new Guzzle(['base_url' => Config::get('api.url')]);

        // Sets the options for the request. Auth should be part of this.
        $options = array(
            'auth'      => $this->getApiCredentials($input['merchant_id']),
            'headers'   => ApiRequest::getHeaders()
        );

        // Creates a request instance
        $request = $client->createRequest("POST", self::CONFIG_LOGO_URL, $options);

        // Creates an object to insert post body data
        $postBody = $request->getBody();

        $filePath = $this->moveAndGetFilePath($input['logo']);

        $postFile = new PostFile('logo', fopen($filePath, 'r'));

        // Inserts file into the post body data
        $postBody->addFile($postFile);

        return $this->sendGuzzleFileRequest($client, $request, $filePath);
    }

    protected function sendGuzzleFileRequest($client, $request, $filePath)
    {
        try
        {
            // json() gets the response body
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
        $destinationPath = storage_path('files/logos');
        $fileName = $file->getFilename() . '.' . $file->getClientOriginalExtension();

        $file->move($destinationPath, $fileName);

        $filePath = $destinationPath . '/' . $fileName;

        return $filePath;
    }

    public function deleteFileLocally($filePath)
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

    protected function getApiCredentials($merchantId)
    {
        $mode = 'live';

        $id = 'rzp_' . $mode . '_' . $merchantId;

        $secret = Config::get('api.auth_pass');

        return [$id, $secret];
    }

    public function fetchProxyBankAccount()
    {
        return $this->request('GET', self::BANK_ACCOUNT_URL);
    }
}
