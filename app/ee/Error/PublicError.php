<?php

namespace EE\Error;

class PublicError
{
    protected $fields = array(
        'description',
        'code',
        'field');

    protected $error = array();

    protected $httpStatusCode = 200;

    public function setHttpStatusCode($statusCode)
    {
        if (!is_integer($statusCode))
        {
            throw new \InvalidArgumentException('statusCode should be integer, statusCode: '. $statusCode);
        }

        $this->httpStatusCode = $statusCode;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    public function setGatewayTimeout()
    {
        $this->setErrorCode(PublicErrorCode::GATEWAY_ERROR);

        $this->setDescription('Request to gateway timed out.');

        $this->setHttpStatusCode(504);
    }

    public function setGatewayError($httpStatusCode = 502)
    {
        $this->error['class'] = ErrorClass::GATEWAY_ERROR;

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
        $this->setErrorCode($code);

        $this->setErrorDescription($desc);

        $this->setHttpStatusCode(400);
    }

    public function setErrorCode($code)
    {
        $this->error['code'] = $code;
    }

    public function setErrorDescription($description)
    {
        $this->error['description'] = $description;
    }

    public function setDescription($desc)
    {
        $this->error['description'] = $desc;
    }

    public function getErrorArray()
    {
        return $this->error;
    }

    public function toArray()
    {
        return array(
            'error' => $this->getErrorArray());
    }
}