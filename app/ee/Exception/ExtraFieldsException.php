<?php

namespace EE\Exception;

use EE\Error\Error;
use EE\Error\ErrorCode;

class ExtraFieldsException extends BaseException
{
    protected $fields;

    protected $count;

    public function __construct(
        $fields,
        $code = ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        Exception $previous = null)
    {
        $this->fields = $fields;

        $extrafields = $fields;

        $count = 1;

        if (is_array($fields))
        {
            $this->count = count($fields);

            $extrafields = implode(', ', $fields);
        }

        $message = $extrafields . ' is/are not required and should not be sent';

        $intcode = 0;

        $this->error = new Error($code, $message);

        parent::__construct($message, $code, $previous);
    }

    public function getExtraFields()
    {
        return $this->fields;
    }
}