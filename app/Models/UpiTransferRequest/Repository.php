<?php


namespace RZP\Models\UpiTransferRequest;

use RZP\Constants;
use RZP\Models\Base;
use Rzp\Models\Payment\Entity as Payment;
use RZP\Models\VirtualAccount\Entity as VirtualAccount;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::UPI_TRANSFER_REQUEST;

    public function updateByGatewayAndNpciRefId(string $gateway, string $npciReferenceId, array $data)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->where(Entity::NPCI_REFERENCE_ID, $npciReferenceId)
                    ->update($data);
    }

    public function findOrFailByPublicIdWithParams(
        string $id,
        array  $params,
        string $connectionType = null
    ) : Base\PublicEntity
    {
        $upiTransferRequest = parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);

        if ($this->app['basicauth']->isAdminAuth() === false)
        {
            return $upiTransferRequest;
        }

        $this->addAttributesForAdminDashboard($upiTransferRequest);

        return $upiTransferRequest;
    }

    protected function addAttributesForAdminDashboard($upiTransferRequest)
    {
        $data = [
            Entity::INTENDED_VIRTUAL_ACCOUNT_ID => null,
            Entity::ACTUAL_VIRTUAL_ACCOUNT_ID   => null,
            Entity::MERCHANT_ID                 => null,
            Entity::MERCHANT_NAME               => null,
            Entity::UPI_TRANSFER_ID             => null,
            Entity::PAYMENT_ID                  => null,
        ];

        try
        {
            $upiTransfer = $this->repo
                                ->upi_transfer
                                ->findByNpciReferenceIdAndGateway($upiTransferRequest->getNpciReferenceId(), $upiTransferRequest->getGateway());

            if (isset($upiTransfer) === true)
            {
                $data[Entity::UPI_TRANSFER_ID] = $upiTransfer->getPublicId();

                $data[Entity::ACTUAL_VIRTUAL_ACCOUNT_ID] = VirtualAccount::getSignedId($upiTransfer->getVirtualAccountId());

                $data[Entity::PAYMENT_ID] = Payment::getSignedId($upiTransfer->getPaymentId());
            }

            $intendedVirtualAccount = $this->getIntendedVirtualAccount($upiTransferRequest, $upiTransfer);

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
                ['upi_transfer_request_id' => $upiTransferRequest->getId()]
            );
        }

        $upiTransferRequest->fill($data);
    }

    private function getIntendedVirtualAccount(Entity $upiTransferRequest, $upiTransfer)
    {
        if ((isset($upiTransfer) === true) and
            ($upiTransfer->getVirtualAccountId() !== VirtualAccount::SHARED_ID))
        {
            return $upiTransfer->virtualAccount;
        }

        $vpa = $this->repo->vpa->findByAddress($upiTransferRequest->getPayeeVpa(), true);

        return ($vpa !== null) ? $vpa->virtualAccount : null;
    }
}
