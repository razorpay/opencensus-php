<?php

namespace EE\Exception;

class ExtraFieldsException
{
    protected $fields;

    protected $count;

    public function __construct($fields, $code = 0 , Exception $previous = null)
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

        $this->error(
            \EE\Error\ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
            $message);

        parent::__construct($message, $intcode, $previous);
    }

    public function getExtraFields()
    {
        return $this->fields;
    }
}