<?php

namespace Unit\Models\PaymentLink\CustomDomain;

trait BaseCDSClientTrait
{
    /**
     * @var \RZP\Models\PaymentLink\CustomDomain\ICDSClientAPI
     */
    protected $api;

    /**
     * @var \RZP\Models\PaymentLink\CustomDomain\ICDSClientAPI
     */
    protected $client;

    abstract protected function getResponse($method, $data = []);
    abstract protected function setClient();

    /**
     * @param string          $method
     * @param array           $data
     * @param \Throwable|null $e
     *
     * @return void
     */
    private function mockApi(string $method, array $data = [], \Throwable $e = null)
    {
        if ($e != null)
        {
            $this->api->expects($method)->withAnyArgs()->once()->andThrowExceptions([$e]);
        }
        else
        {
            $this->api->expects($method)->withAnyArgs()->once()->andReturn($this->getResponse($method, $data));
        }


        $this->setClient();
    }
}
