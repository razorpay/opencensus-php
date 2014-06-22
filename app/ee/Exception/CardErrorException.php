<?php

namespace EE\Exception;

use Illuminate\Support\MessageBag;

class CardErrorException extends BadRequestException
{

    /**
     * Card field for which the public error will be shown
     * @var string
     */
    protected $cardField = null;

    public function __construct(
        $message = '',
        $code = 0,
        \Exception $previous = null)
    {
        $intcode = 0;

        parent::__construct($message, $intcode, $previous);

        if (($message !== null) and
            (is_string($message) === false))
        {
            list($field, $desc) = $this->getFirstPair();

            $errorCode = '\EE\Error\ErrorCode::CARD_ERROR_INVALID_'.strtoupper($field);

            if (defined($errorCode) === false)
            {
                throw new \InvalidArgumentException($field . ' error not defined');
            }

            $errorCode = constant($errorCode);

            $this->error = new \EE\Error\Error($errorCode, $desc);
        }
        else if ($code !== 0)
        {
            if (defined('\EE\Error\ErrorCode::'.$code))
            {
                $error = new \EE\Error\Error($code, $message);

                $this->setError($error);
            }
            else
            {
                throw new \InvalidArgumentException($code . ' is not defined');
            }
        }
    }
}