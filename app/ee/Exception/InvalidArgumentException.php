<?php

namespace EE\Exception;

use Illuminate\Support\messageBag;

class BadRequestException extends RazorpayException
{
    protected $messageBag = null;

    protected $messageArray = null;

    protected $first = null;

    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        if ($message instanceof messageBag)
        {
            $this->messageBag = $message;

            $this->messageArray = $message->getMessages();

            $message = implode('\n', $message->all());

            $this->setFirstPair();
        }
        else if (is_array($message))
        {
            $this->messageArray = $message;

            $message = $this->implodeMessagesArray();

            $this->setFirstPair();
        }

        parent::__construct($message, $code, $previous);
    }

    public function getMessageBag()
    {
        return $this->messageBag;
    }

    protected function setFirstPair()
    {
        $array = $this->messageArray;

        if ($array !== null)
        {
            $pair = each($array);

            $firstKey = $pair['key'];

            $firstValue = (is_array($pair['value'])) ? $pair['value'][0] : $pair['value'];

            $this->first = array($firstKey => $firstValue);
        }
    }

    protected function implodeMessagesArray()
    {
        $messages = array();

        foreach ($messageArray as $field => $values)
        {
            if (is_array($values))
                array_push($messages, implode('\n', $messages));
            else
                array_push($messages, $values);
        }

        $message = implode('\n', $messages);

        return $message;
    }

    public function getFirstPair()
    {
        $pair = each($this->first);

        return array($pair['key'], $pair['value']);
    }
}