<?php


namespace RZP\Diag;


class ReqContext
{
    protected $trackId;

    public function setTrackId($trackId)
    {
        $this->trackId = $trackId;
    }

    public function getTrackId()
    {
        return $this->trackId;
    }

}
