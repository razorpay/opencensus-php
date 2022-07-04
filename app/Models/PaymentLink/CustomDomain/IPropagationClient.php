<?php

namespace RZP\Models\PaymentLink\CustomDomain;

interface IPropagationClient extends ICDSClientAPI
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function checkPropagation(array $data): array;
}
