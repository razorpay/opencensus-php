<?php

namespace App\RZP;

use Razorpay\Api\Request as ApiRequest;

class User extends Entity
{
    public function create($params = null)
    {
        return parent::create($params);
    }

    public function edit($userId, array $params)
    {
        $relativeUrl = $this->getEntityUrl().$userId;

        // For some reason unknown the normal way was not working
        ApiRequest::addHeader('Content-Type', 'application/json');

        $body = json_encode($params);

        return $this->request('PUT', $relativeUrl, $body);
    }

    public function get($userId, array $params)
    {
        $relativeUrl = $this->getEntityUrl().$userId;

        return $this->request('GET', $relativeUrl, $params);
    }

    public function attach($userId, array $params)
    {
        $relativeUrl = $this->getEntityUrl().$userId.'/attach';

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function detach($userId, array $params)
    {
        $relativeUrl = $this->getEntityUrl().$userId.'/detach';

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function updateMapping($userId, array $params)
    {
        $relativeUrl = $this->getEntityUrl().$userId.'/update';

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function confirmByData($params)
    {
        $relativeUrl = $this->getEntityUrl().'confirm_user_by_data';

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function changePassword($userId, array $params)
    {
        $relativeUrl = $this->getEntityUrl().$userId.'/password';

        return $this->request('PUT', $relativeUrl, $params);
    }
}
