<?php

namespace RZP\Models\Base\Traits;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as EntityConstants;
use RZP\Error\ErrorCode;
use RZP\Constants\Metric;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\Merchant;

trait ExternalTransferPaymentRepo
{
    protected $entityName = EntityConstants::TRANSFER_PAYMENT;

    protected function validateIfExternalFetchIsEnabledForTransferPayment()
    {
        $keyName = EntityConstants::getExternalConfigKeyName($this->entityName);

        return (bool) ConfigKey::get($keyName, false);
    }

    protected function fetchExternalTransferPaymentByPaymentId($paymentId)
    {
        $class = EntityConstants::getExternalRepoSingleton($this->entityName);

        $startTime = millitime();

        $callerFunc = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];

        try
        {
            $entity = $class->fetchTransferPaymentByPaymentId($paymentId);

            if (empty($entity) === false)
            {

                $this->traceSuccessMetrics($callerFunc, $startTime);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            if ($e instanceof  BadRequestException
                && $e->getMessage() == "The requested payment was not found on the server")
            {
                return null;
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FETCH_TRANSFER_PAYMENT_VIA_ROUTE_SERVICE_FAILURE,
                [
                    'payment_id' => $paymentId,
                    'data'         => $e->getMessage(),
                    'from'         => $callerFunc,

                ]);

            $this->traceFailureMetrics($callerFunc, $startTime);
        }

        $data = [
            'model'      => $this->entityName,
            'operation'  => $callerFunc,
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function getUpdatableTransferPaymentFields(Transfer\Payment\Entity $transferPayment)
    {
        $params = [];

        $params[Transfer\Payment\Entity::AMOUNT_TRANSFERRED] = $transferPayment->getAmountTransferred();

        return $params;
    }

    protected function traceFailureMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_PAYMENT_REPO_FETCH_FAILURE, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_PAYMENT_REPO_FETCH_FAILURE_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function traceSuccessMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_PAYMENT_REPO_FETCH_SUCCESS, [
            'caller'      => $functionName,

        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_PAYMENT_REPO_FETCH_SUCCESS_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

}
