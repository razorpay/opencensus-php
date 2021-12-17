<?php

namespace RZP\Exception\Batch;

use Illuminate\Support\MessageBag;

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Models\Batch\Validator;
use RZP\Exception\MessageFormats;
use RZP\Exception\RecoverableException;

class BadRequestValidationFailureException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $message = null,
        $field = null,
        $data = null)
    {
        $message = $this->constructStringMessage($message);

        $code = ErrorCode::BAD_REQUEST_VALIDATION_FAILURE;

        $this->constructError($code, $message, $field, $data);

        $this->data = $data;
    }

    protected function handleMessageBagInstance(MessageBag $bag)
    {
        $this->messageBag = $bag;

        $this->messageArray = $bag->getMessages();

        // Adding vertical tab between error messages to keep it inside same field
        // in ouput csv error file for batch payouts
        $message = implode("\v", $bag->all());

        $this->setFirstPair();

        return $message;
    }

    protected function constructError($code, $message, $field = null, $data = null)
    {
        $desc = $message;

        if (($message !== null) and
            ($this->messageFormat !== 'string'))
        {
            list($field, $desc) = $this->getFirstPair();
        }
        if ((new Validator)->checkIfOperationIsAllowed($data) === true)
        {
            // $data here is passed as null to be consistent with the
            // batch validation flow without the flag 'allow_complete_error_desc'
            $this->error = new Error($code, $message, $field, null);
        }
        else
        {
            $this->error = new Error($code, $desc, $field, $data);
        }

        parent::__construct($desc, $code, null);
    }
}
