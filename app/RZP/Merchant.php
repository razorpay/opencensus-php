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
    const SUBMERCHANT_CREATE_URL = 'submerchants';
    const BANK_ACCOUNT_URL = 'account/bank_account';
    const PROXY_BALANCE_URL = 'balance';

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
        return parent::all($options);
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

    public function fetchBalance()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/balance';

        return $this->request('GET', $relativeUrl);
    }

    public function fetchProxyBalance()
    {
        return $this->request('GET', self::PROXY_BALANCE_URL);
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

    protected function getGuzzleInstance()
    {
        return new Guzzle([
            'base_uri' => Config::get('api.url'),
            'timeout'  => 200,
        ]);
    }

    public function fetchProxyBankAccount()
    {
        return $this->request('GET', self::BANK_ACCOUNT_URL);
    }

    public function setSchedule($merchantId, $params)
    {
        // merchants/{id}/schedules
        $relativeUrl = $this->getEntityUrl().$merchantId.'/schedules';

        $res = $this->request('POST', $relativeUrl, $params);

        return $res;
    }

    public function actions($merchantId, $params)
    {
        $error = $response = null;

        try
        {
            $relativeUrl = "merchants/$merchantId/action";

            $response = $this->request('PUT', $relativeUrl, $params)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [ $e->getMessage() ];
        }

        return [ $error, $response ];
    }
}
