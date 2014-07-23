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
        $this->setAttributes(
            PublicErrorCode::GATEWAY_ERROR,
            PublicErrorDescription::GATEWAY_ERROR_REQUEST_TIMEOUT,
            504);
    }

    public function setGatewayError($httpStatusCode = 502)
    {
        $this->setAttributes(
            PublicErrorCode::GATEWAY_ERROR,
            PublicErrorDescription::GATEWAY_ERROR,
            $httpStatusCode);
    }

    public function setServerError()
    {
        $this->setAttributes(
            PublicErrorCode::SERVER_ERROR,
            PublicErrorDescription::SERVER_ERROR,
            500);
    }

    public function setCardError($code, $desc, $field = null)
    {
        $this->setAttributes(
            $code,
            $desc,
            400,
            $field);
    }

    public function setFieldError($code, $desc, $field = null)
    {
        $this->setAttributes(
            $code,
            $desc,
            400,
            $field);
    }

    protected function setAttributes($code, $description, $httpStatusCode, $field = null)
    {
        $this->setErrorCode($code);

        $this->setDescription($description);

        $this->setHttpStatusCode($httpStatusCode);

        if ($field !== null)
            $this->setField($field);
    }

    public function setErrorCode($code)
    {
        if (defined(__NAMESPACE__.'\PublicErrorCode::'.strtoupper($code)) === false)
        {
            throw new \EE\Exception\InvalidArgumentException(
                $code . ' not a valid public errorcode');
        }

        $this->error['code'] = $code;
    }

    public function getErrorCode()
    {
        return $this->error['code'];
    }

    public function setErrorDescription($description)
    {
        $this->error['description'] = $description;
    }

    public function setDescription($desc)
    {
        $this->error['description'] = $desc;
    }

    public function getErrorDescription()
    {
        return $this->error['description'];
    }

    public function setField($field)
    {
        if ($field !== null)
            $this->error['field'] = $field;
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