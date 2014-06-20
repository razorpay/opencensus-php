<?php

namespace Exceptions;

use Illuminate\Support\MessageBag;

class InvalidCardException extends InvalidArgumentException
{

    /**
     * Card field for which the public error will be shown
     * @var string
     */
    protected $cardField = null;

    public function __construct(
        $message,
        $code = 0,
        Exception $previous = null)
    {
        $array = null;

        if ($message instanceof MessageBag)
        {
            $array = $message->getMessages();
        }
        else if (is_array($message))
        {
            $array = $message;
        }

        if ($array !== null)
        {
            list($field, $desc) = $this->getFirstKeyAndValue($array);

            $errorCode = '\EE\Error\ErrorCode::API_CARD_INVALID'.$field;

            if (defined($errorCode) === false)
            {
                throw new \InvalidArgumentException($field . ' error not defined');
            }

            $errorCode = constant($errorCode);

            $this->error = new \EE\Error\Error($errorCode, array(), $desc);
        }
        else if ($code !== 0)
        {
            if (defined('\EE\Error\ErrorCode::'.$code))
            {
                $this->error = new \EE\Error\Error($code);
            }
        }
    }

    protected function getFirstKeyAndValue(array $array)
    {
        $keys = array_keys($messages);

        $field = $keys[0];

        $value = $messages[$field];

        return array($field, $value);
    }
}