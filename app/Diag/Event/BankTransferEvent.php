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
                'id'        => $bankTransfer->getId(),
                'gateway'   => $bankTransfer->getGateway(),
            ];
        }
    }

    public function addCustomProperties()
    {
        $bankTransfer = $this->entity;

        if ($bankTransfer === null)
        {
            return;
        }

        $merchant = $bankTransfer->merchant;

        $customProperties = [
            'utr'               => $bankTransfer->getUtr(),
            'payee_account'     => $bankTransfer->getPayeeAccount(),
            'merchant_id'       => ($merchant !== null) ? $merchant->getId() : null,
            'merchant_name'     => ($merchant !== null) ? $merchant->getName() : null,
        ];

        $this->customProperties += $customProperties;
    }
}
