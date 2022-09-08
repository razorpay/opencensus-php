<?php
namespace RZP\Models\SalesforceConverge;

class EventResponse
{
    protected $createdOn;

    protected $error;

    public function __construct(array $response)
    {
        if (($response['success'] ?? false) == false)
        {
            $this->error = new Error($response);
        }
    }

    public function isSuccess()
    {
        return empty($this->error);
    }
}
