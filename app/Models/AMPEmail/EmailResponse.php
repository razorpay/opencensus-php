<?php
namespace RZP\Models\AMPEmail;

class EmailResponse
{
    protected $refId;

    protected $message;

    protected $success;

    protected $error;

    protected $status;

    public function __construct(array $response)
    {
        $this->refId   = $response[Constants::REF] ?? '';
        $this->message = $response[Constants::MESSAGE] ?? '';
        $this->status  = $response[Constants::STATUS] ?? '';
        $this->error   = $response[Constants::ERROR] ?? '';
        $this->success = $response[Constants::SUCCESS] ?? '';
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function getId()
    {
        return $this->refId;
    }
}
