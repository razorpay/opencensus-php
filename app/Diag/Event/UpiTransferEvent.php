<?php


namespace RZP\Diag\Event;


class UpiTransferEvent extends Event
{
    const EVENT_TYPE        = 'upi-transfer-events';

    const EVENT_VERSION     = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addUpiTransferDetails($properties);

        return $properties;
    }

    private function addUpiTransferDetails(array & $properties)
    {
        $upiTransfer = $this->entity;

        if ($upiTransfer !== null)
        {
            $properties['upi_transfer'] = [
                'id' => $upiTransfer->getId(),
            ];
        }
    }
}
