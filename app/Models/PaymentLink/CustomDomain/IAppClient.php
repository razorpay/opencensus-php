<?php

namespace RZP\Models\PaymentLink\CustomDomain;

interface IAppClient extends ICDSClientAPI
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function createApp(array $data): array;
}
