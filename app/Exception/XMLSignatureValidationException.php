<?php

namespace RZP\Exception;

use RZP\Error\ErrorCode;

class XMLSignatureValidationException extends RecoverableException
{
    const INVALID_XML_SIGNATURE = "XML Signature could not be verified";

    public function __construct($message = self::INVALID_XML_SIGNATURE)
    {
        $this->code = ErrorCode::GATEWAY_ERROR_XML_SIGNATURE_ERROR;
    }
}
