<?php


namespace RZP\Diag\Event;


class BankTransferEvent extends Event
{
    const EVENT_TYPE        = 'bank-transfer-events';

    const EVENT_VERSION     = 'v1';

    protected function getEventProperties()
    {
        $properties = [];

        $this->addBankTransferDetails($properties);

        return $properties;
    }

    private function addBankTransferDetails(array & $properties)
    {
        $bankTransfer = $this->entity;

        if ($bankTransfer !== null)
        {
            $properties['bank_transfer'] = [
                'id' => $bankTransfer->getId(),
            ];
        }
    }
}
