<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Exception\BaseException;
use RZP\Models\Payment\UpiMetadata\Entity as Metadata;

abstract class UpiTransformer
{
    /**
     * @var Gateway
     */
    protected $context;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Entity
     */
    protected $upi;

    /**
     * @var BaseException
     */
    protected $exception;

    /**
     * @var Anomalies
     */
    protected $anomalies;

    /**
     * @var
     */
    protected $item;

    /**
     * @var
     */
    protected $input;

    abstract protected function getResponseArray(): array;

    abstract protected function updateMetadataFromResponse(): UpiTransformer;

    public function __construct(Gateway $context, Anomalies $anomalies)
    {
        $this->context = $context;

        $this->anomalies = $anomalies;
    }

    public function from(array $input, Response $response, Entity $upi = null, BaseException $exception = null)
    {
        $this->input        = $input;
        $this->response     = $response;
        $this->upi          = $upi;
        $this->exception    = $exception;

        return $this;
    }

    public function toArray()
    {
        if (empty($this->item) === false)
        {
            return $this->item->toArray();
        }

        $this->anomalies->logic('To array is called without a item', $this->response);

        return [];
    }

    protected function isSuccess(): bool
    {
        return is_null($this->exception);
    }

    protected function response(string $key, bool $strict = true)
    {
        $array = $this->getResponseArray();

        $value = array_get($array, $key);

        if ((empty($value) === true) and ($strict === true))
        {
            $this->anomalies->missing($key);
        }

        return $value;
    }
}
