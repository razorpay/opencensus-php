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

        $this->setDescription(PublicErrorDescription::GATEWAY_REQUEST_TIMEOUT);

        $this->setHttpStatusCode(504);
    }

    public function setGatewayError($httpStatusCode = 502)
    {
        $this->error['code'] = PublicErrorCode::GATEWAY_ERROR;

        $this->setDescription(PublicErrorDescription::GATEWAY_ERROR);

        $this->setHttpStatusCode($httpStatusCode);
    }

    public function setServerError()
    {
        $this->setErrorCode(PublicErrorCode::SERVER_ERROR);

        $this->setDescription(PublicErrorDescription::SERVER_ERROR);

        $this->httpStatusCode = 500;
    }

    public function setBadRequestError($description, $field = null)
    {
        $this->setErrorCode(PublicErrorCode::BAD_REQUEST_ERROR);

        $this->setErrorDescription($description);

        $this->setField($field);

        $this->setHttpStatusCode(400);
    }

    public function setCardError($code, $desc, $field = null)
    {
        if (defined(__NAMESPACE__.'\PublicErrorCode::'.$code) === false)
        {
            throw new \EE\Exception\InvalidArgumentException(
                $code . ' not a valid public errorcode');
        }

        $this->setErrorCode($code);

        $this->setErrorDescription($desc);

        if ($field !== null)
            $this->setField($field);

        $this->setHttpStatusCode(400);
    }

    public function setErrorCode($code)
    {
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