<?php
namespace RZP\Models\SimilarWeb;

class SimilarWebResponse
{
    protected $status;

    protected $visits;

    protected $errorMsg;

    public function __construct(array $response)
    {
        $this->status   = $response[Constants::STATUS] ?? '';
        $this->visits   = $response[Constants::VISITS] ?? 0;

        if (!$this->isSuccess())
        {
            $this->errorMsg = $response[Constants::ERROR_MSG] ?? 'Unknown Error';
        }
    }

    public function isSuccess()
    {
        return $this->status == Constants::SUCCESS;
    }

    public function getVisits()
    {
        return $this->visits;
    }
}
