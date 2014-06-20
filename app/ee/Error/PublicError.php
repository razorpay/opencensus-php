<?php

namespace EE\Error;

class PublicError
{
    protected $fields = array(
        'class',
        'description',
        'code',
        'field');

    protected $attributes = array();

    protected $httpStatusCode = 200;

    public function setHttpStatusCode($statusCode)
    {
        if (!is_integer($statusCode))
        {
            throw new \InvalidArgumentException('statusCode should be integer, statusCode: '. $statusCode);
        }

        $this->httpStatusCode = $statusCode;
    }

    public function setGatewayTimeout()
    {
        $this->setErrorClass(ErrorClass::GATEWAY_ERROR);

        $this->setDescription('Request to gateway timed out.');

        $this->setHttpStatusCode(504);
    }

    public function setGatewayError($httpStatusCode = 502)
    {
        $this->attributes['class'] = ErrorClass::GATEWAY_ERROR;

        $this->setDescription('Some wizardry happened on gateway side causing the txn/request to fail');

        $this->setHttpStatusCode($httpStatusCode);
    }

    public function setServerError()
    {
        $this->setErrorClass(ErrorClass::GATEWAY_ERROR);

        $this->setDescription('Looks like nemo is again playing with our server. Please try your request again!');

        $this->httpStatusCode = 500;
    }

    public function setCardError($code, $desc)
    {
        $this->setErrorClass(ErrorClass::$code);

        $this->setErrorDesc($desc);
    }

    public function setErrorClass($class)
    {
        $this->attributes['class'] = $class;
    }

    public function setErrorDescription($description)
    {
        $this->attributes['description'] = $description;
    }

    public function setDescription($desc)
    {
        $this->attributes['description'] = $desc;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}