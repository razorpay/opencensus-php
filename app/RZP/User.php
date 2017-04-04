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

    public function attach($userId, $merchantId, $role)
    {
        $relativeUrl = $this->getEntityUrl().$userId.'/attach';

        return $this->request('PUT', $relativeUrl, ['merchant_id' => $merchantId, 'role' => $role]);
    }
}
