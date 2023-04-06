<?php

namespace RZP\Reconciliator\Base\SubReconciliator\Upi;

use Throwable;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Metro\MetroHandler;
use RZP\Reconciliator\Base;
use RZP\Gateway\Upi\Base\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Jobs\UpsRecon\UpsGatewayEntityUpdate;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;
use RZP\Reconciliator\Base\SubReconciliator\Upi\Constants as UpsConstants;

class UpiPaymentServiceReconciliate extends SubReconciliator\PaymentReconciliate
{
    use Base\UpiReconTrait;

    protected $syncUpdateGateways = [
        Payment\Gateway::UPI_ICICI,
        Payment\Gateway::UPI_YESBANK,
        Payment\Gateway::UPI_AIRTEL,
        Payment\Gateway::UPI_SBI,
        Payment\Gateway::UPI_MINDGATE,
        Payment\Gateway::UPI_AXIS,
        Payment\Gateway::UPI_JUSPAY
    ];

    /**
     * Returns null if the payment is routed through UPS.
     * In case of UPS payments, the entity is updated through different
     * flow
     *
     * @return void
     */
    protected function updateAndFetchGatewayPayment()
    {
        if (($this->payment->isRoutedThroughUpiPaymentService() === true) ||
            ($this->payment->isRoutedThroughPaymentsUpiPaymentService()) === true)
        {
            return null;
        }

        return parent::updateAndFetchGatewayPayment();
    }

    /**
     * runs pre-recon checks and updates gateway entity on UPS
     *
     * @return void
     */
    protected function runPreReconciledAtCheckRecon($rowDetails)
    {
        parent::runPreReconciledAtCheckRecon($rowDetails);

        if ($this->payment->isRoutedThroughUpiPaymentService() === false and
            $this->payment->isRoutedThroughPaymentsUpiPaymentService() === false)
        {
            return;
        }

        $dataToUpdate = $this->getUpsGatewayDataToUpdate($rowDetails);

        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_RECON_UPDATE_DATA,
            $dataToUpdate);

        if (empty($dataToUpdate) === true)
        {
            // do not push to metro if there is no data to update
            return;
        }

        $this->updateGatewayEntity($dataToUpdate);
    }

    protected function updateGatewayEntity($dataToUpdate)
    {
        $data = [
            Constants::PAYMENT_ID   => $this->payment->getId(),
            Constants::GATEWAY_DATA => $dataToUpdate,
            Constants::GATEWAY      => $this->payment->getGateway(),
            Constants::BATCH_ID     => $this->batchId,
            Constants::MODEL        => Constants::AUTHORIZE
        ];

        if ($this->shouldUpdateInSync() === true)
        {
            $this->updateEntityOnUps($data);
            return;
        }

        // update gateway entity asynchronously
        $this->dispatchToUpsReconQueue($data);
    }

    protected  function shouldUpdateInSync()
    {
        $gateway = $this->payment->getGateway();

        $isSyncGateway = in_array($gateway, $this->syncUpdateGateways, true);

        $feature = $gateway . '_recon_sync_update';

        $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(),
            $feature, $this->mode);

        return (($isSyncGateway) and
                ($variant === $gateway));
    }

    protected function updateEntityOnUps($data)
    {
        $action = Constants::RECON_ENTITY_SYNC_UPDATE;

        $this->app['upi.payments']->action($action, $data, $this->gateway);
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

        if (empty($dbNpciRefId) === true)
        {
            $dataToUpdate[Constants::CUSTOMER_REFERENCE] = $referenceNumber;
        }
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

    /** Dispatch the entity update message to sqs queue
     * @param array $data
     * @throws \Exception
     */
    protected function dispatchToUpsReconQueue(array $data)
    {
        try
        {
            UpsGatewayEntityUpdate::dispatch($this->mode, $data);
        }
        catch (\Exception $ex)
        {
            $this->trace->error(TraceCode::UPI_PAYMENT_JOB_DISPATCH_ERROR,
                [
                    UpsConstants::PAYMENT_ID   => $data[ Constants::PAYMENT_ID],
                    "error_message"            => $ex->getMessage()
                ]);

            throw $ex;
        }
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

    /**
     * returns UPS fiscal entity by customer reference
     *
     * @param string $referenceNumber
     * @param string $gateway
     * @return void
     */
    protected function fetchUpsGatewayEntityByRrn(string $referenceNumber, string $gateway)
    {
        try
        {
            $requiredFields = [
                    Constants::GATEWAY_REFERENCE,
                    Constants::NPCI_TXN_ID,
                    Constants::PAYMENT_ID,
                    Constants::GATEWAY,
                    Constants::RECONCILED_AT,
            ];

            return $this->getUpsGatewayEntityByColumn(
                Constants::CUSTOMER_REFERENCE,
                $referenceNumber,
                $gateway,
                $requiredFields);
        }
        catch (Exception\BadRequestException $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::INFO,
                TraceCode::UPI_PAYMENT_SERVICE_RECORD_NOT_FOUND,
                [
                    'gateway' => $gateway,
                ]
            );
            return [];
        }
    }

    /** Fetch ups gateway entity by column name
     * @param string $columnName
     * @param string $columnValue
     * @param string $gateway
     * @param array $requiredFields
     * @return mixed
     */
    protected function getUpsGatewayEntityByColumn(
        string $columnName,
        string $columnValue,
        string $gateway,
        array $requiredFields)
    {
        $action = Constants::ENTITY_FETCH;

        $input = [
            Constants::MODEL            => Constants::AUTHORIZE,
            Constants::REQUIRED_FIELDS  => $requiredFields,
            Constants::COLUMN_NAME      => $columnName,
            Constants::VALUE            => $columnValue,
            Constants::GATEWAY          => $gateway,
        ];

        return $this->app['upi.payments']->action($action, $input, $gateway);
    }

    /**
     * Method will return expected payment for given paymentId, rrn.
     *
     * @param string $paymentId
     * @param array $upsEntity
     * @param array $row
     * @return mixed|null
     */
    protected function getUpsExpectedPayment(string $paymentId, array $upsEntity, array $row)
    {
        if ($upsEntity['gateway'] !== $this->gatewayName)
        {
            // TODO: Raise a critical alert
            return null;
        }

        // If the existing payment is not already reconciled, we will return the same entity
        if (empty($upsEntity['reconciled_at']) === true)
        {
            return $paymentId;
        }

        // Formatting the npci reference id just to make sure any accidental trimming
        $existingRrn = $upsEntity['customer_reference'];

        // Even though the payment is already reconciled and if we do not have saved
        // a valid RRN, we will not go ahead with the reconciliation.
        if ($this->isUpiValidRrn($existingRrn) === false)
        {
            // TODO: Raise a critical alert
            return null;
        }

        $reconRrn = $this->getReferenceNumber($row);

        // If both the RRNs are same, we can return the payment to be reconciled
        if ($existingRrn === $reconRrn)
        {
            return $paymentId;
        }

        // Since we are receiving extra attempt but the status is failed, we do not need to create the payment
        // The validatePaymentStatus will function will take care of this if we send the existing entity
        if ($this->getReconPaymentStatus($row) !== Payment\Status::AUTHORIZED)
        {
            return $paymentId;
        }

        // Now we have multiple credit scenario, where one credit is already reconciled
        // And the RRN for reconciled one is not same as recon row rrn.
        $callbackData = $this->generateCallbackData($row);

        $response = (new Payment\Service)->unexpectedCallback($callbackData, $reconRrn, $this->gatewayName);

        if (empty($response[Entity::PAYMENT_ID]) === true)
        {
            // TODO: Trace critical
            return null;
        }

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'infoCode'      => Base\InfoCode::RECON_UNEXPECTED_PAYMENT_FOR_MULTIPLE_CREDIT,
                'payment_id'    => $response[Entity::PAYMENT_ID],
                'rrn'           => $reconRrn,
                'gateway'       => $this->gateway,
            ]);

        return $response[Entity::PAYMENT_ID];
    }

    protected function fetchUpsGatewayEntityByPaymentId(string $paymentId, string $gateway)
    {
        try
        {
            $requiredFields = [
                Constants::GATEWAY_REFERENCE,
                Constants::NPCI_TXN_ID,
                Constants::CUSTOMER_REFERENCE,
                Constants::GATEWAY,
                Constants::RECONCILED_AT,
            ];

            return $this->getUpsGatewayEntityByColumn(Constants::PAYMENT_ID ,$paymentId, $gateway, $requiredFields);
        }
        catch (Exception\BadRequestException $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::INFO,
                TraceCode::UPI_PAYMENT_SERVICE_RECORD_NOT_FOUND,
                [
                    'gateway' => $gateway,
                ]
            );
            return [];
        }
    }

    protected function generateCallbackData(array $row)
    {
        // to be implemented by gateway payment reconciliation file
        return;
    }
}
