<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class ExtraFieldsException extends RecoverableException
{
    protected $fields;

    protected $count;

    public function __construct(
        $fields,
        $code = ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        \Exception $previous = null,
        $data = null)
    {
        $this->fields = $fields;

        $extraFields = $fields;

        if (is_array($fields))
        {
            $this->count = count($fields);

            $extraFields = implode(', ', $fields);
        }

        $message = $extraFields . ' is/are not required and should not be sent';

        $this->error = new Error($code, $message, null, $data);

        $this->data = $data;

        parent::__construct($message, $code, $previous);
    }

    public function getExtraFields()
    {
        return $this->fields;
    }
}
