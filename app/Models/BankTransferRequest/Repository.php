<?php


namespace RZP\Models\BankTransferRequest;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\VirtualAccount;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANK_TRANSFER_REQUEST;

    public function updateByUtr(string $utr, array $data)
    {
        return $this->newQuery()
                    ->where(Entity::UTR, $utr)
                    ->update($data);
    }

    public function fetch(
        array $params,
        string $merchantId = null,
        bool $useSlave = false,
        bool $useMasterEsReplica = false
    ): Base\PublicCollection
    {
        $entities = parent::fetch($params, $merchantId, $useSlave, $useMasterEsReplica);

        if ($this->app['basicauth']->isAdminAuth() === false)
        {
            return $entities;
        }

        $bankTransferRequests = new Base\PublicCollection();

        foreach ($entities as $bankTransferRequest)
        {
            $this->addAttributesForAdminDashboard($bankTransferRequest);

            $bankTransferRequests->push($bankTransferRequest);
        }

        return $bankTransferRequests;
    }

    public function findOrFailByPublicIdWithParams(
        string $id,
        array $params,
        bool $useMasterEsReplica = false
    ) : Base\PublicEntity
    {
        $bankTransferRequest = parent::findOrFailByPublicIdWithParams($id, $params, $useMasterEsReplica);

        if ($this->app['basicauth']->isAdminAuth() === false)
        {
            return $bankTransferRequest;
        }

        $this->addAttributesForAdminDashboard($bankTransferRequest);

        return $bankTransferRequest;
    }

    protected function addAttributesForAdminDashboard($bankTransferRequest)
    {
        $data = [
            Entity::VIRTUAL_ACCOUNT_ID    => null,
            Entity::MERCHANT_ID           => null,
            Entity::MERCHANT_NAME         => null,
            Entity::BANK_TRANSFER_ID      => null,
            Entity::PAYMENT_ID            => null,
            Entity::ORDER_ID              => null,
            Entity::PRODUCT_TYPE          => null,
            Entity::PRODUCT_ID            => null,
        ];

        try
        {
            $bankTransfer = $this->repo
                                 ->bank_transfer
                                 ->findByUtrAndPayeeAccount($bankTransferRequest->getUtr(), $bankTransferRequest->getPayeeAccount());

            $data[Entity::BANK_TRANSFER_ID] = isset($bankTransfer) ? $bankTransfer->getPublicId() : null;

            $virtualAccount = $this->getVirtualAccount($bankTransferRequest, $bankTransfer);

            if (isset($virtualAccount) === true)
            {
                $data[Entity::VIRTUAL_ACCOUNT_ID] = $virtualAccount->getPublicId();

                $merchant = $virtualAccount->merchant;

                $data[Entity::MERCHANT_ID]      = (isset($merchant)) ? $merchant->getId() : null;
                $data[Entity::MERCHANT_NAME]    = (isset($merchant)) ? $merchant->getName() : null;

                $this->setOrderDetailsIfApplicable($data, $virtualAccount);
            }

            $payment = isset($bankTransfer) ? $bankTransfer->payment : null;

            $data[Entity::PAYMENT_ID] = (isset($payment)) ? $payment->getPublicId() : null;
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                null,
                ['bank_transfer_request_id' => $bankTransferRequest->getId()]
            );
        }

        $bankTransferRequest->fill($data);
    }

    /**
     * If bank_transfer entity is not created, we still have a virtual_account entity,
     * which can be fetched from the bank_account entity.
     *
     * @param Entity $bankTransferRequest
     * @param null $bankTransfer
     * @return VirtualAccount\Entity|null
     */
    private function getVirtualAccount(Entity $bankTransferRequest, $bankTransfer = null)
    {
        if ($bankTransfer !== null)
        {
            return $bankTransfer->virtualAccount;
        }

        $bankAccount = $this->repo
                            ->bank_account
                            ->findVirtualBankAccountByAccountNumberAndBankCode($bankTransferRequest->getPayeeAccount());

        return (isset($bankAccount)) ? $bankAccount->source : null;
    }

    private function setOrderDetailsIfApplicable(array & $data, VirtualAccount\Entity $virtualAccount)
    {
        if ($virtualAccount->hasOrder() === true)
        {
            $order = $virtualAccount->entity;

            $data[Entity::ORDER_ID]     = $order->getPublicId();
            $data[Entity::PRODUCT_TYPE] = $order->getProductType();
            $data[Entity::PRODUCT_ID]   = $order->getProductId();
        }
    }
}
