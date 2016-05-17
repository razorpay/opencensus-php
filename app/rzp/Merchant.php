<?php

namespace RZP;

use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;

use Config;
use Input;

use Razorpay\Api\Request as ApiRequest;
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
        if (isset($input['logo']) === true)
        {

            // $file = $input['logo'];
            //
            // // Now that we have added all POST params, we add the file itself
            // // This contains the field name to be used for the file field
            // $fileFieldName = 'logo';
            // // This contains the original file name with extension
            // $fileName = $file->getClientOriginalName();
            //
            // // This is as per guzzle 5, will need to get changed for 6
            // $postFile = new PostFile($fileFieldName, fopen($file, 'r'), $fileName);
            //
            // $input[$fileFieldName] = $postFile;
            //
            // $client = new Guzzle([
            //     'base_url' => Config::get('api.url'),
            //     // We already have a few headers initialized for this class
            //     // including the X-Dashboard and Razorpay-API Header
            //     'headers'   =>  ApiRequest::getHeaders() + [
            //             'X-Dashboard' => 'true',
            //             'User-Agent'  => 'Razorpay-PHP/guzzle6'
            //         ]
            // ]);
            //
            // $response = $client->post(self::CONFIG_LOGO_URL, $input)->json();

            $path = 'account/config/logo';

            $input['file_name'] = 'logo';

            $input['auth'] = 'proxy';

            $input['method'] = 'post';

            $request = new Admin\RawApiRequest($input, $path);

            return $request->send();

            return $this->request('POST', self::CONFIG_LOGO_URL, $input);
        }

        return $this->request('PUT', self::CONFIG_URL, $input);
    }
}
