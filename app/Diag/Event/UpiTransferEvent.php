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
                'id'        => $upiTransfer->getId(),
                'gateway'   => $upiTransfer->getGateway(),
            ];
        }
    }

    public function addCustomProperties()
    {
        $upiTransfer = $this->entity;

        if ($upiTransfer === null)
        {
            return;
        }

        $merchant = $upiTransfer->merchant;

        $customProperties = [
            'tr'                => $upiTransfer->getTr(),
            'payee_vpa'         => $upiTransfer->getPayeeVpa(),
            'merchant_id'       => ($merchant !== null) ? $merchant->getId() : null,
            'merchant_name'     => ($merchant !== null) ? $merchant->getName() : null,
        ];

        $this->customProperties += $customProperties;
    }
}
