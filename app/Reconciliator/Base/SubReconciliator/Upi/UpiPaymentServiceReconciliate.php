<?php

namespace RZP\Reconciliator\Base\SubReconciliator\Upi;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Metro\MetroHandler;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class UpiPaymentServiceReconciliate extends SubReconciliator\PaymentReconciliate
{
    /**
     * Returns null if the payment is routed through UPS.
     * In case of UPS payments, the entity is updated through different
     * flow
     *
     * @return void
     */
    protected function updateAndFetchGatewayPayment()
    {
        if ($this->payment->isRoutedThroughUpiPaymentService() === true)
        {
            return null;
        }

        parent::updateAndFetchGatewayPayment();
    }

    /**
     * runs pre-recon checks and updates gateway entity on UPS
     *
     * @return void
     */
    protected function runPreReconciledAtCheckRecon($rowDetails)
    {
        parent::runPreReconciledAtCheckRecon($rowDetails);

        if ($this->payment->isRoutedThroughUpiPaymentService() === false)
        {
            return;
        }

        $dataToUpdate = $this->getUpsGatewayDataToUpdate($rowDetails);

        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_RECON_UPDATE_DATA,
            $dataToUpdate);

        $this->publishToMetro($dataToUpdate);
    }

    /**
     * Persists UPS gateway entity with recon data
     *
     * @param  array  $rowDetails
     * @return array
     */
    protected function getUpsGatewayDataToUpdate(array $rowDetails)
    {
        $entity = $this->getUpsGatewayEntity();

        $dataToUpdate = [];

        if (empty($rowDetails[BaseReconciliate::REFERENCE_NUMBER]) === false)
        {
            $this->persistNpciReferenceNumber($entity, $rowDetails[BaseReconciliate::REFERENCE_NUMBER], $dataToUpdate);
        }

        if (empty($rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID]) === false)
        {
            $this->persistNpciTxnID($entity, $rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID], $dataToUpdate);
        }

        if (empty($rowDetails[BaseReconciliate::GATEWAY_PAYMENT_ID]) === false)
        {
            $this->persistGatewayReference($entity, $rowDetails[BaseReconciliate::GATEWAY_PAYMENT_ID], $dataToUpdate);
        }

        $this->persistReconAt($entity, $dataToUpdate);

        return $dataToUpdate;
    }
    /**
     * retrive UPS gateway entity
     *
     * @return array
     */
    protected function getUpsGatewayEntity(): array
    {
        $action = Constants::ENTITY_FETCH;

        $gateway = $this->payment->getGateway();

        $input = [
            Constants::MODEL            => Constants::AUTHORIZE,
            Constants::REQUIRED_FIELDS  => [
                Constants::CUSTOMER_REFERENCE,
                Constants::GATEWAY_REFERENCE,
                Constants::NPCI_TXN_ID,
                Constants::RECONCILED_AT,
            ],
            Constants::COLUMN_NAME      => Constants::PAYMENT_ID,
            Constants::VALUE            => $this->payment->getId(),
            Constants::GATEWAY          => $gateway
        ];

        $entity = $this->app['upi.payments']->action($action, $input, $gateway);

        $this->validateEntityFetchResponse($entity, $input);

        return $entity;
    }

    // validateEntityFetchResponse validates the entity fetch response from UPS
    protected function validateEntityFetchResponse(array $entity, array $input)
    {
        if ((empty($entity) === true) or
            (isset($entity[Constants::CUSTOMER_REFERENCE]) === false) or
            (isset($entity[Constants::GATEWAY_REFERENCE]) === false) or
            (isset($entity[Constants::NPCI_TXN_ID]) === false) or
            (isset($entity[Constants::RECONCILED_AT]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_UPI_PAYMENT_SERVICE_ENTITY_FETCH_ERROR,
                [
                    'input'     => $input,
                    'entity'    => $entity
                ],
                null,
                'received wrong entity from Upi Payment Service');
        }
    }

    /**
     * persist npci txn id
     *
     * @param array $entity
     * @param string $npciTxnId
     * @param array $dataToUpdate
     * @return void
     */
    protected function persistNpciTxnId(array $entity, string $npciTxnId, array &$dataToUpdate)
    {
        $dbNpciTxnId = $entity[Constants::NPCI_TXN_ID];

        if ((empty($dbNpciTxnId) === false) and
            ($dbNpciTxnId !== $npciTxnId))
        {
            $this->processReconAnomaly(Constants::NPCI_TXN_ID, $dbNpciTxnId, $npciTxnId);

            return;
        }

        if (empty($dbNpciTxnId) === true)
        {
            $dataToUpdate[Constants::NPCI_TXN_ID] = $npciTxnId;
        }
    }

    /**
     * persist customer reference (npci reference id)
     *
     * @param array $entity
     * @param string $referenceNumber
     * @param array $dataToUpdate
     * @return void
     */
    protected function persistNpciReferenceNumber(array $entity, string $referenceNumber, array &$dataToUpdate)
    {
        $dbNpciRefId = $entity[Constants::CUSTOMER_REFERENCE];

        if ((empty($npciRefId) === false) and
            ($dbNpciRefId !== $referenceNumber))
        {
            $this->processReconAnomaly(Constants::CUSTOMER_REFERENCE, $dbNpciRefId, $referenceNumber);

            return;
        }

        if (empty($npciRefId) === true)
        {
            $dataToUpdate[Constants::CUSTOMER_REFERENCE] = $referenceNumber;
        }
    }

    /**
     * persist reconciled at
     *
     * @param array $entity
     * @param array $dataToUpdate
     * @return void
     */
    protected function persistReconAt(array $entity, array &$dataToUpdate)
    {
        $reconciledAt = $entity[Constants::RECONCILED_AT];

        if (empty($reconciledAt) === false)
        {
            return;
        }

        $dataToUpdate[Constants::RECONCILED_AT] = Carbon::now()->getTimestamp();
    }

    /**
     * persist gateway reference (gateway payment id)
     *
     * @param array $entity
     * @param string $gatewayPaymentId
     * @param array $dataToUpdate
     * @return void
     */
    protected function persistGatewayReference(array $entity, string $gatewayPaymentId, array &$dataToUpdate)
    {
        $dbGatewayReference = trim($entity[Constants::GATEWAY_REFERENCE]);

        if ((empty($dbGatewayReference) === false) and
            ($dbGatewayReference !== $gatewayPaymentId))
        {
            $this->processReconAnomaly(Constants::GATEWAY_REFERENCE, $dbGatewayReference, $gatewayPaymentId);

            return;
        }

        if (empty($dbGatewayReference) === true)
        {
            $dataToUpdate[Constants::GATEWAY_REFERENCE] = $gatewayPaymentId;
        }
    }

    /**
     * publish message to metro topic
     *
     * @param array $dataToUpdate
     * @return void
     */
    protected function publishToMetro(array $dataToUpdate)
    {
        // publish to metro
        $metroHandler = (new MetroHandler());

        $topic = Constants::RECON_ENTITY_UPDATE . '-'. $this->mode;

        $data = [
            Constants::PAYMENT_ID   => $this->payment->getId(),
            Constants::GATEWAY_DATA => $dataToUpdate,
            Constants::GATEWAY      => $this->payment->getGateway(),
            Constants::BATCH_ID     => $this->batchId,
        ];

        $metroHandler->publish($topic, $data);
    }

    /**
     * processes recon gateway data anaomalies
     *
     * @param string $attribute
     * @param string $dbValue
     * @param string $reconValue
     * @return void
     */
    protected function processReconAnomaly(string $attribute, string $dbValue, string $reconValue)
    {
        $infoCode = ($this->reconciled === true) ? InfoCode::DUPLICATE_ROW : InfoCode::DATA_MISMATCH;

        $this->trace->info(
            TraceCode::RECON_MISMATCH,
            [
                'message'                   => sprintf('%s is not same as in recon', $attribute),
                'info_code'                 => $infoCode,
                'payment_id'                => $this->payment->getId(),
                'amount'                    => $this->payment->getBaseAmount(),
                'payment_status'            => $this->payment->getStatus(),
                'db_' . $attribute          => $dbValue,
                'recon_' . $attribute       => $reconValue,
                'gateway'                   => $this->gateway
            ]);
    }
}
