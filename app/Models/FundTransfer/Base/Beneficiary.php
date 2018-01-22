<?php

namespace RZP\Models\FundTransfer\Base;

use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Base\PublicCollection;

abstract class Beneficiary extends BaseCore
{
    const SIGNED_URL_DURATION = '1440';

    abstract public function register(PublicCollection $bankAccounts, array $input = []): array;

    abstract protected function sendEmail(array $data);
}