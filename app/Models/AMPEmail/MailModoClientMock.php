<?php

namespace RZP\Models\AMPEmail;


use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class MailModoClientMock
{
    private $mockStatus;

    private $config;

    public function __construct(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;

        $this->config = config(Constants::APPLICATIONS_MAILMODO);
    }


    //trigger email
    public function triggerEmail(EmailRequest $request): EmailResponse
    {
        switch ($this->mockStatus)
        {
            case Constants::SUCCESS:

                return $this->getTriggerEmailSuccessResponse();

            default:

                return $this->getTriggerEmailFailureResponse();
        }

    }

    private function getTriggerEmailSuccessResponse(): EmailResponse
    {
        $response = [
            "success" => true,
            "message" => "Email scheduled successfully",
            "ref"     => "61d8190a-e1d0-4734-8851-012b21865e4d"
        ];

        return new EmailResponse($response);
    }

    private function getTriggerEmailFailureResponse(): EmailResponse
    {
        $response = [
            "error"   => "ValidationError",
            "success" => false,
            "status"  => "error",
            "message" => "The provided email is not a valid email id"
        ];

        return new EmailResponse($response);
    }
}
