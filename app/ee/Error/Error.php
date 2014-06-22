<?php

namespace EE\Error;

class Error
{
    protected $attributes = array(
        'category' => null,
        'code' => null,
        'data' => null,
        'desc' => null);

    protected $publicError = null;

    public function __construct($code, $desc = null)
    {
        $data = null;
        $this->fill($code, $data, $desc);
    }

    public function fill($code, $data, $desc = null)
    {
        $this->setCode($code);

        $this->setCategory($code);

        $this->setData($data);

        $this->setDesc($desc);

        $this->constructPublicError();
    }

    public function setCodeAndCategory($code)
    {
        $this->setCode($code);

        $this->setCategory($code);
    }

    protected function setCode($code)
    {
        if (ErrorCode::errorCodeExists($code) === false)
        {
            throw new \InvalidArgumentException('Error code is not valid. Code: ' . $code);
        }

        $this->attributes['code'] = $code;
    }

    protected function getCode()
    {
        return $this->attributes['code'];
    }

    protected function setCategory($code)
    {
        $pos = strpos($code, '_');

        $category = substr($code, 0, $pos);

        $this->attributes['category'] = $category;
    }

    protected function setData($data)
    {
        $this->attributes['data'] = $data;
    }

    protected function setDesc(/* string */ $desc = null)
    {
        if ($desc === null)
            return;

        if (! is_string($desc))
        {
            throw new \InvalidArgumentException('desc should be string');
        }

        $this->attributes['desc'] = $desc;
    }

    protected function getAttribute($attr)
    {
        return $this->attributes[$attr];
    }

    protected function constructPublicError()
    {
        $this->publicError = new PublicError();

        switch ($this->getAttribute('category'))
        {
            case ErrorCategory::GATEWAY:
                $this->handleGatewayErrors();
                break;
            case ErrorCategory::CARD:
                $this->handleCardErrors();
                break;
            case ErrorCategory::DB:
                // @todo fill this case
                $this->handleDBErrors();
                break;
            case ErrorCategory::TRACE:
                // @todo
                break;
            default:
                throw new \InvalidArgumentException('Not a valid category');
        }

        $publicError = new PublicError($code, $description);
    }

    public function getPublicError()
    {
        return $this->publicError;
    }

    protected function handleGatewayErrors()
    {
        $code = $this->getAttribute('code');
        $data = $this->getAttribute('data');

        switch ($code)
        {
            case ErrorCode::GATEWAY_REQUEST_TIMEOUT:
                $this->publicError->setGatewayTimeout();
                return;

            default:
                $this->publicError->setGatewayError();
                return;
        }
    }

    protected function handleCardErrors()
    {
        $code = $this->getAttribute('code');
        $desc = $this->getAttribute('desc');

        $this->publicError->setCardError($code, $desc);
    }

    protected function handleAPIErrors()
    {
        $code = $this->getAttribute('code');
        $desc = $this->getAttribute('desc');

        switch ($code)
        {
            case Error::API_CARD_INVALID_NAME:
            case Error::API_CARD_INVALID_EXPIRY_MONTH:
            case Error::API_CARD_INVALID_EXPIRY_YEAR:
            case Error::API_CARD_INVALID_CVC:
            case Error::API_CARD_INVALID_BRAND:
            case Error::API_CARD_INVALID_AMOUNT:
            case Error::API_CARD_INVALID_UDF:
            case Error::API_CARD_INVALID_NUMBER:
            case Error::API_CARD_EXPIRED:
            case Error::API_TRANSACTION_INVALID_CURRENCY:
            case Error::API_TRANSACTION_INVALID_AMOUNT:
            default:
                throw new \InvalidArgumentException('This part is to be done.');
                break;
        }
    }

    protected function handleServerErrors()
    {
        $code = $this->getAttribute('code');
        $data = $this->getAttribute('data');

        switch ($code)
        {
            case Error::DB_RECORD_NOT_FOUND:
            case Error::DB_QUERY_FAILED:
            case Error::DB_QUERY_INVALID_SYNTAX:
                break;
        }
    }
}