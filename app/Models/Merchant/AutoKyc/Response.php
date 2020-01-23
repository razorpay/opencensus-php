<?php

namespace RZP\Models\Merchant\AutoKyc;

interface Response
{
    public function validateResponse();

    public function getResponseData();
}
