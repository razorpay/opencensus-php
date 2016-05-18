<?php

namespace RZP;

use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;

use Config;
use Input;

use Razorpay\Api\Entity as ApiEntity;
use Razorpay\Api\Errors as RZPErrors;

// This is the default class we use for making requests
use RZP\Api as Api;

use Request;

use Models\Admin;

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
        // If logo needs to be updated, use this block.
        if (isset($input['logo']) === true)
        {
            // Makes a guzzle file request
            $response = $this->makeGuzzleFileRequest($input);

            // Builds an entity from the response received
            $response = ApiEntity::buildEntity($response);

            return $response;
        }

        return $this->request('PUT', self::CONFIG_URL, $input);
    }

    public function makeGuzzleFileRequest($input)
    {
        // Creates a new Guzzle client
        $client = new Guzzle(['base_url' => Config::get('api.url')]);

        // Sets the options for the request. Auth should be part of this.
        $options['auth'] = $this->setApiCredentials($input['merchant_id']);

        // Creates a request instance
        $request = $client->createRequest("POST", self::CONFIG_LOGO_URL, $options);

        // Creates an object to insert post body data
        $postBody = $request->getBody();

        $file = $input['logo'];

        $fileDetails = $this->moveAndGetFileDetails($file);

        $filePath = $fileDetails['file_path'];

        $postFile = new PostFile('logo', fopen($filePath, 'r'));

        // Inserts file into the post body data
        $postBody->addFile($postFile);

        // json() gets the response body
        $response = $client->send($request)->json();

        $this->deleteFileLocally($fileDetails);

        return $response;
    }

    protected function moveAndGetFileDetails($file)
    {
        $fileDetails = [
            'destination_path' => storage_path('files/logos'),
            'file_name' => $file->getFilename() . '.' . $file->getClientOriginalExtension(),
        ];

        $fileDetails['file_path'] = $fileDetails['destination_path'] . '/' . $fileDetails['file_name'];

        $file->move($fileDetails['destination_path'], $fileDetails['file_name']);

        return $fileDetails;
    }

    public function deleteFileLocally($fileDetails)
    {
        $filePath = $fileDetails['file_path'];

        if (file_exists($filePath))
        {
            $success = unlink($filePath);

            if ($success === false)
            {
                // TODO: What do we do?
            }
        }
    }

    protected function setApiCredentials($merchantId)
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
