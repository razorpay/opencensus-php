<?php

namespace RZP\Reconciliator\UpiSbi;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Payment;
use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /**
     * @see https://drive.google.com/drive/u/0/folders/0B1kf6HOmx7JBTmMzTXgwQVRrNm8
     */

    const ORDER_NUMBER = 'order_no';

    const TRANS_REF_NUMBER = 'trans_ref_no';

    const CUSTOMER_REF_NUM = 'customer_ref_no';

    const TRANSACTION_STATUS = 'transaction_status';

    const TRANSACTION_AMOUNT = 'transaction_amount';

    protected function getPaymentId(array $row)
    {
        return $row[self::ORDER_NUMBER];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::TRANS_REF_NUMBER];
    }

    protected function getCustomerDetails($row)
    {
        return [
            Base\Reconciliate::CUSTOMER_ID => $row[self::CUSTOMER_REF_NUM]
        ];
    }

    protected function getPaymentStatus(array $row)
    {
        $status = $row[self::TRANSACTION_STATUS];

        $map = [
            'success' => Payment\Status::AUTHORIZED,
            'failed' => Payment\Status::FAILED,
        ];

        return $map[strtolower($status)];
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        $reconAmount = (int) ($row[self::TRANSACTION_AMOUNT] * 100);

        $paymentAmount = $this->payment->getAmount();

        return ($reconAmount === $paymentAmount);
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->upi->findByPaymentId($paymentId)->first();
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setNpciReferenceId($referenceNumber);
    }

    protected function persistNbCustomerId(array $customerDetails, PublicEntity $gatewayPayment)
    {
        $customerId = $customerDetails[Base\Reconciliate::CUSTOMER_ID];

        $gatewayPayment->setGatewayPaymentId($customerId);
    }
}
