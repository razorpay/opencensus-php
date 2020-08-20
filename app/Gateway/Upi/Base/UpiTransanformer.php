<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Exception\BaseException;
use RZP\Models\Payment\UpiMetadata\Entity as Metadata;

class UpiTransanformer
{
    /**
     * @var Gateway
     */
    protected $context;

    /**
     * @var array
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

    public function __construct(Gateway $context, Anomalies $anomalies)
    {
        $this->context = $context;

        $this->anomalies = $anomalies;
    }

    public function from(array $input, array $response, Entity $upi = null, BaseException $exception = null)
    {
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
        $value = array_get($this->response, $key);

        if ((empty($value) === true) and ($strict === true))
        {
            $this->anomalies->missing($key);
        }

        return $value;
    }
}
