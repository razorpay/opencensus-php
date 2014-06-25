<?php

namespace EE\Exception;

use Illuminate\Support\messageBag;

class ValidationFailureException extends BaseException
{
    protected $messageBag = null;

    public function __construct($messageBag)
    {
        $this->messageBag = $messageBag;

        // parent::__construct($message, 0, null);
    }

    public function getMessageBag()
    {
        return $this->messageBag;
    }
}