<?php

namespace App\RZP;

class Invitation extends Entity
{
    public function accept(string $id, array $params)
    {
        $relativeUrl = $this->getEntityUrl()."$id/accept";

        return $this->request('POST', $relativeUrl, $params);
    }
}
