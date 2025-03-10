<?php

namespace RZP\Models\Transfer\Payment;

use Neves\Events\TransactionalClosureEvent;
use RZP\Constants;
use RZP\Constants\Entity as EntityConstants;
use RZP\Jobs\Transfers\TransferPaymentUpdate;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\Repository as BaseRepository;
use RZP\Models\Base\Traits\ExternalTransferPaymentRepo;

class Repository extends BaseRepository
{
    use ExternalTransferPaymentRepo;

    protected $entity = Constants\Entity::TRANSFER_PAYMENT;

    public function getTransferPaymentIncludingExternal($paymentID)
    {
        $transferPayments = $this->getTransferPayment($paymentID);

        if ($transferPayments->count() === 0)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForTransferPayment() &&
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    $transferPayments = $this->fetchExternalTransferPaymentByPaymentId($paymentID);

                    if (empty($transferPayments))
                    {
                        return new PublicCollection();
                    }

                    $transferPaymentsCollection = new PublicCollection();

                    $transferPaymentsCollection->add($transferPayments);

                    return $transferPaymentsCollection;
                }
            }
            catch(\Throwable $e) {
                throw $e;
            }
        }

        return $transferPayments;
    }

    public function getTransferPayment($paymentID)
    {
       return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, $paymentID)
                    ->get();
    }

    public function deleteTransferPayment($id)
    {
        return $this->newQuery()
            ->where(Entity::ID, $id)
            ->delete();
    }

    public function saveOrFail($transferPayment, array $options = array())
    {
        if ($transferPayment->isExternal())
        {
            \Event::dispatch(new TransactionalClosureEvent(function () use ($transferPayment)
            {
                TransferPaymentUpdate::dispatchNow($transferPayment);
            }));

        }
        else
        {
            parent::saveOrFail($transferPayment, $options);
        }
    }

}
