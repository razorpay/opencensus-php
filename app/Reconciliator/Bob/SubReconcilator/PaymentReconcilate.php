<?php

namespace RZP\Reconciliator\Bob;

use Carbon\Carbon;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    public function getPaymentId(array $row)
    {
        return $row[ReconcilationFields::MERCHANT_TRACK_ID];
    }

    /**
     * Its is present as Retrieval Reference Number in recon file
     * It should be set as ref setReferenceNumberInGateway
     * in the gateway entity.
     *
     * @param $row
     * @return string
     */
    public function getReferenceNumber($row)
    {
        return $row[ReconcilationFields::RRN];
    }

    /**
     * Since we need to update rrn we would need gatewayPayment
     * Fetching this based on id because recon has failed payment also
     * @param $row
     * @return string
     */
    protected function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->card_fss
                    ->findByPaymentIdAndAction(
                        $paymentId,
                        Action::AUTHORIZE);
    }

    /**
     * The card_fss entity ref column should be updated with rrn
     * It should be set as ref setReferenceNumberInGateway
     * in the gateway entity.
     * @param string       $referenceNumber
     * @param PublicEntity $gatewayPayment CardFss Entity
     * */
    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setRef($referenceNumber);
    }
}
