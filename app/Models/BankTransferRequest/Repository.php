<?php


namespace RZP\Models\BankTransferRequest;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\VirtualAccount\Entity as VirtualAccount;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANK_TRANSFER_REQUEST;

    public function updateByUtr(string $utr, array $data)
    {
        return $this->newQuery()
                    ->where(Entity::UTR, $utr)
                    ->update($data);
    }

    public function findOrFailByPublicIdWithParams(
        string $id,
        array  $params,
        string $connectionType = null
    ) : Base\PublicEntity
    {
        $bankTransferRequest = parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);

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
            Entity::INTENDED_VIRTUAL_ACCOUNT_ID => null,
            Entity::ACTUAL_VIRTUAL_ACCOUNT_ID   => null,
            Entity::MERCHANT_ID                 => null,
            Entity::MERCHANT_NAME               => null,
            Entity::BANK_TRANSFER_ID            => null,
            Entity::PAYMENT_ID                  => null,
            Entity::ORDER_ID                    => null,
            Entity::PRODUCT_TYPE                => null,
            Entity::PRODUCT_ID                  => null,
        ];

        try
        {
            $bankTransfer = $this->repo
                                 ->bank_transfer
                                 ->findByUtrAndPayeeAccountAndAmount(
                                     $bankTransferRequest->getUtr(),
                                     $bankTransferRequest->getPayeeAccount(),
                                     $bankTransferRequest->getAmount());

            if (isset($bankTransfer) === true)
            {
                $data[Entity::BANK_TRANSFER_ID] = $bankTransfer->getPublicId();

                $data[Entity::ACTUAL_VIRTUAL_ACCOUNT_ID] = VirtualAccount::getSignedId($bankTransfer->getVirtualAccountId());

                $payment = $bankTransfer->payment;

                if (isset($payment) === true)
                {
                    $data[Entity::PAYMENT_ID] = $payment->getPublicId();

                    $this->setOrderDetailsIfApplicable($data, $payment);
                }
            }

            $intendedVirtualAccount = $this->getIntendedVirtualAccount($bankTransferRequest, $bankTransfer);

            if (isset($intendedVirtualAccount) === true)
            {
                $data[Entity::INTENDED_VIRTUAL_ACCOUNT_ID] = $intendedVirtualAccount->getPublicId();

                $merchant = $intendedVirtualAccount->merchant;

                $data[Entity::MERCHANT_ID]      = (isset($merchant)) ? $merchant->getId() : null;
                $data[Entity::MERCHANT_NAME]    = (isset($merchant)) ? $merchant->getName() : null;
            }
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

    private function getIntendedVirtualAccount(Entity $bankTransferRequest, $bankTransfer)
    {
        if ((isset($bankTransfer) === true) and
            ($bankTransfer->getVirtualAccountId() !== VirtualAccount::SHARED_ID))
        {
            return $bankTransfer->virtualAccount;
        }

        $bankAccount = $this->repo
                            ->bank_account
                            ->findVirtualBankAccountByAccountNumberAndBankCode($bankTransferRequest->getPayeeAccount(), null, true);

        return (isset($bankAccount)) ? $bankAccount->source : null;
    }

    private function setOrderDetailsIfApplicable(array & $data, Payment $payment)
    {
        $order = $payment->order;

        if (isset($order) === true)
        {
            $data[Entity::ORDER_ID]     = $order->getPublicId();
            $data[Entity::PRODUCT_TYPE] = $order->getProductType();
            $data[Entity::PRODUCT_ID]   = $order->getProductId();
        }
    }
}
