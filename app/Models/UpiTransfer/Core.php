<?php

namespace RZP\Models\UpiTransfer;

use Config;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\VirtualAccount;
use RZP\Exception\LogicException;
use RZP\Models\UpiTransferRequest;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processPayment(array $gatewayResponse, $terminal, $upiTransferRequestId = null)
    {
        $upiTransferInput = $gatewayResponse['upi_transfer_data'];

        if (isset($upiTransferInput['payer_account_type']) === true)
        {
            // payer account type is not required in upi transfer entity
            unset($upiTransferInput['payer_account_type']);
        }

        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESS_REQUEST,
            [
                'upi_transfer_data' => $this->removePiiForLogging($upiTransferInput),
                'terminal'          => $terminal->getId(),
            ]
        );

        $this->convertPayeeVpaToLower($upiTransferInput);

        $upiTransfer = null;

        $paymentSuccess = false;

        $errorMessage = null;

        try
        {
            $upiTransfer = (new Entity)->build($upiTransferInput);

            $this->mutex->acquireAndRelease(
                $upiTransferInput[Entity::PROVIDER_REFERENCE_ID],
                function() use($gatewayResponse, $terminal, $upiTransfer)
                {
                    (new Processor($gatewayResponse, $terminal))->process($upiTransfer);
                },
                $ttl = 30,
                $errorCode = ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS
            );

            $paymentSuccess = true;

            return true;
        }
        catch (\Throwable $e)
        {
            $paymentSuccess = false;

            $errorMessage = $e->getMessage();

            $this->alertException($e, $upiTransferInput);

            switch ($errorMessage)
            {
                case TraceCode::UPI_TRANSFER_PAYMENT_DUPLICATE_NOTIFICATION:
                    return true;

                case TraceCode::REFUND_OR_CAPTURE_PAYMENT_FAILED:
                    $paymentSuccess = true;
                    return true;

                default:
                    return false;
            }
        }
        finally
        {
            $isExpected = null;

            if ($upiTransfer !== null)
            {
                $isExpected = $upiTransfer->isExpected();

                $errorMessage = $errorMessage ?? $upiTransfer->getUnexpectedReason();
            }

            (new UpiTransferRequest\Core())->updateUpiTransferRequest($upiTransferInput, $paymentSuccess, $errorMessage,
                                                                      $upiTransferRequestId);

            (new VirtualAccount\Metric())->pushPaymentMetrics(Constants\Entity::UPI_TRANSFER, $isExpected,
                                                              $paymentSuccess, $terminal->getGateway(), $errorMessage);

            $this->pushUpiTransferSourceToLake($upiTransfer);
        }
    }

    /**
     * Trace and send an alert to Slack.
     *
     * @param \Throwable $ex
     * @param array      $input
     */
    public function alertException(\Throwable $ex, array $input)
    {
        $input = $input['upi_transfer_data'] ?? $input;

        $this->trace->traceException(
            $ex,
            Trace::CRITICAL,
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESSING_FAILED,
            $this->removePiiForLogging($input, [Entity::PAYEE_VPA])
        );
    }

    protected function convertPayeeVpaToLower(array & $input)
    {
        $payeeVpa = $input['payee_vpa'];

        $input['payee_vpa'] = strtolower($payeeVpa);
    }

    protected function pushUpiTransferSourceToLake($upiTransfer)
    {
        if ($upiTransfer === null)
        {
            return;
        }

        $routeName = $this->app['api.route']->getCurrentRouteName();

        $properties = [];

        switch ($routeName)
        {
            case 'upi_transfer_process':
                $properties = [
                    'source'       => 'callback',
                    'request_from' => 'bank',
                ];

                break;

            case 'reconciliate':
            case 'reconciliate_via_batch_service':
                $properties = [
                    'source'       => 'recon',
                    'request_from' => 'admin',
                ];

                break;

            default:
                $this->trace->info(
                    TraceCode::UNTRACKED_ENDPOINT_UPI_TRANSFER,
                    [
                        'route_name'    => $routeName,
                        'npci_ref_id'   => $upiTransfer->getRrn(),
                    ]);

                break;

        }

        $this->app['diag']->trackUpiTransferRequestEvent(
            EventCode::UPI_TRANSFER_REQUEST,
            $upiTransfer,
            null,
            $properties
        );
    }

    /**
     * Pass fields to $fields that are not to be logged.
     * If nothing is passed, default PII fields will be
     * fetched from Entity class. If any field is not to
     * be completely removed, use the switch-case.
     *
     * @param array $array
     * @param array $fields
     * @return array
     */
    public function removePiiForLogging(array $array, array $fields = [])
    {
        if (empty($fields) === true)
        {
            $fields = (new Entity())->getPii();
        }

        foreach ($fields as $field)
        {
            if (isset($array[$field]) === false)
            {
                continue;
            }

            switch ($field)
            {
                case Entity::PAYEE_VPA:
                    $payeeVpa = $array[Entity::PAYEE_VPA];

                    $array[Entity::PAYEE_VPA . '_root']     = explode('.', $payeeVpa)[0];
                    $array[Entity::PAYEE_VPA . '_dynamic']  = explode('@', explode('.', $payeeVpa)[1])[0];
                    $array[Entity::PAYEE_VPA . '_handle']   = explode('@', $payeeVpa)[1];

                    break;

                default:
                    break;
            }

            unset($array[$field]);
        }

        return $array;
    }
}
