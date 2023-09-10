<?php

namespace RZP\Models\Merchant\Acs\ParityChecker;


interface ParityInterface
{
    public function checkReadParity();
    public function checkWriteParity(): array;
}
