<?php

namespace RZP\Models\Merchant\AutoKyc;

interface Processor
{
    public function process() : Response;
}
