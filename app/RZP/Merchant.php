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
    public function create($params = null)
    {
        return parent::create($params);
    }

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all($options);
    }

    public function activate()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/activate';

        return $this->request('POST', $relativeUrl, []);
    }

    public function edit($params)
    {
        // For empty arrays (groups [heimdall] in this case)
        ApiRequest::addHeader('Content-Type', 'application/json');

        // JSON encoding is also requried
        $body = json_encode($params);

        $relativeUrl = $this->getEntityUrl().$this->id;

        return $this->request('PUT', $relativeUrl, $body);
    }

    public function editEmail($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id . '/email';

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function setTerminal($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/terminals';

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

    public function getUsers($merchantId)
    {
        $relativeUrl = $this->getEntityUrl().$merchantId.'/users';

        return $this->request('GET', $relativeUrl);
    }
}
