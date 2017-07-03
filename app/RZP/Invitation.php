<?php

namespace App\RZP;

class Invitation extends Entity
{
    public function fetchByToken(string $token)
    {
        $relativeUrl = $this->getEntityUrl()."token/$token";

        return $this->request('GET', $relativeUrl);
    }

    public function accept(string $id, array $params)
    {
        $relativeUrl = $this->getEntityUrl()."$id/accept";

        return $this->request('POST', $relativeUrl, $params);
    }
}
