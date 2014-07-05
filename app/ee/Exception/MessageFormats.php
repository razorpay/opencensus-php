<?php

namespace EE\Exception;

use Illuminate\Support\MessageBag;

trait MessageFormats
{
    protected $messageBag = null;

    protected $messageArray = null;

    protected $first = null;

    protected $messageFormat = 'string';

    /**
     * If $message is either messageBag or array,
     * then it returns a string message.
     *
     * @param  mixed $message
     * @return string
     */
    protected function constructStringMessage($message)
    {
        if ($message instanceof messageBag)
        {
            $this->messageFormat = 'message_bag';

            $message = $this->handleMessageBagInstance($message);
        }
        else if (is_array($message))
        {
            $this->messageFormat = 'array';

            $message = $this->handleMessageArray();
        }

        return $message;
    }

    public function onlyCode($message, $code, $previous)
    {
        if (($message === null) and
            ($code !== 0))
        {
            $this->messageFormat = 'null';

            if (defined('\EE\Error\ErrorCode::'.$code) === false)
            {
                throw new \InvalidArgumentException($code . ' is not a valid code');
            }

            $message = constant('\EE\Error\PublicErrorDescription::'.$code);

            $error = new \EE\Error\Error($code, $message);

            $this->setError($error);

            parent::__construct($message, $code, $previous);

            return true;
        }

        return false;
    }


    protected function handleMessageBagInstance(MessageBag $bag)
    {
        $this->messageBag = $bag;

        $this->messageArray = $bag->getMessages();

        $message = implode('\n', $bag->all());

        $this->setFirstPair();

        return $message;
    }

    protected function handleMessageArray($message)
    {
        $this->messageArray = $message;

        $message = $this->implodeMessagesArray();

        $this->setFirstPair();

        return $message;
    }

    protected function generateError($code, $message)
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

    protected function getFirstPair()
    {
        $pair = each($this->first);

        return array($pair['key'], $pair['value']);
    }

    protected function constructError($message, $code)
    {
        if (($message !== null) and
            ($this->messageFormat !== 'string'))
        {
            list($field, $desc) = $this->getFirstPair();

            $code = $this->getErrorCode($field);

            $this->error = new \EE\Error\Error($code, $desc, $field);

            parent::__construct($desc, $code, null);
        }
        else if ($code !== 0)
        {
            $this->generateError($code, $message);

            parent::__construct($message, $code, null);
        }
    }

    protected function getErrorCode($field)
    {
        $className = __CLASS__;

        $pos = strrpos($className, '\\');

        $pos2 = strrpos($className, 'Exception');

        $category = substr($className, $pos+1, $pos2 - $pos - 1);

        $code = '';

        switch($category)
        {
            case 'BadRequest':
                $code = '\EE\Error\ErrorCode::BAD_REQUEST_ERROR';
                break;
            case 'FieldError':
                $code = '\EE\Error\ErrorCode::FIELD_ERROR_INVALID_'.strtoupper($field);
                break;
            case 'CardError':
                $code = '\EE\Error\ErrorCode::CARD_ERROR_INVALID_'.strtoupper($field);
                break;
        }

        if (defined($code) === false)
        {
            throw new \InvalidArgumentException($field . ' error not defined');
        }

        $code = constant($code);

        return $code;
    }
}
