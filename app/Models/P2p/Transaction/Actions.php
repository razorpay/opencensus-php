<?php

namespace RZP\Models\P2p\Transaction;

class Actions
{
    protected $event;

    /**
     * @return mixed
     */
    public function hasEvent(): bool
    {
        return (empty($this->event) === false);
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event): self
    {
        $this->event = $event;

        return $this;
    }
}
