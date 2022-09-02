<?php

namespace RZP\Models\P2p\Mandate;

class Actions
{
    protected $event;

    protected $shouldUpdate = true;

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

    /**
     * @return bool
     */
    public function shouldUpdate(): bool
    {
        return $this->shouldUpdate;
    }

    /**
     * @param bool $shouldUpdate
     */
    public function setShouldUpdate(bool $shouldUpdate): self
    {
        $this->shouldUpdate = $shouldUpdate;

        return $this;
    }
}
