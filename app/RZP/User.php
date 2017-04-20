<?php

namespace App\RZP;

class User extends Entity
{
    public function create($params = null)
    {
        return parent::create($params);
    }

    public function edit($params, $userId)
    {
        $relativeUrl = $this->getEntityUrl().$userId;

        return $this->request('PUT', $relativeUrl, $params);
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

    public function confirm($userId)
    {
        $relativeUrl = $this->getEntityUrl().$userId.'/confirm';

        return $this->request('PUT', $relativeUrl);
    }

    public function changePassword($userId, array $params)
    {
        $relativeUrl = $this->getEntityUrl().$userId.'/password';

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function login(array $params)
    {
        $relativeUrl = $this->getEntityUrl().'login';

        return $this->request('POST', $relativeUrl, $params);
    }
}
