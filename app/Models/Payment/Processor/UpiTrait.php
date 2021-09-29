<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Models\Payment\UpiMetadata\Type;
use RZP\Models\Payment\UpiMetadata\Entity;
use RZP\Models\Payment\UpiMetadata\Contants;

trait UpiTrait
{
    public function getUpiFlow($input)
    {
        return $input['upi']['flow'] ?? null;
    }

    public function isFlowCollect($input): bool
    {
        if ((isset($input['upi']['flow']) === false) or
            ($input['upi']['flow'] === Payment\Flow::COLLECT))
        {
            return true;
        }

        return false;
    }

    public function isFlowIntent($input): bool
    {
        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === Payment\Flow::INTENT))
        {
            return true;
        }

        return false;
    }

    public function isOtmPayment($input): bool
    {
        if ((isset($input[Method::UPI][Entity::TYPE]) === true) and
            ($input[Method::UPI][Entity::TYPE] === Type::OTM))
        {
            return true;
        }

        return false;
    }

    public function isUpiRecurringPayment($input): bool
    {
        return ((isset($input[Method::UPI]) === true) and
                (isset($input[Payment\Entity::RECURRING]) === true) and
                ((bool) $input[Payment\Entity::RECURRING] === true));
    }

    public function getUpiExpiryTime($input)
    {
        return $input['upi']['expiry_time'] ?? null;
    }

    public function getUpiStartTime($input)
    {
        return array_get($input, 'upi.start_time');
    }

    public function getUpiEndTime($input)
    {
        return array_get($input, 'upi.end_time');
    }

    public function getUpiType($input)
    {
        return array_get($input, 'upi.type');
    }

    public function getUpiVpa($input)
    {
        return array_get($input, 'upi.vpa');
    }

    /**
     * Sets defaults for OTM payments
     * @param array $input
     */
    protected function preProcessForUpiOtmIfApplicable(array &$input)
    {
        if ($this->isOtmPayment($input) === false)
        {
            return;
        }

        if (isset($input[Payment\Method::UPI][Entity::START_TIME]) === false)
        {
            $input[Payment\Method::UPI][Entity::START_TIME] = Carbon::now()->getTimestamp();
        }

        if (isset($input[Payment\Method::UPI][Entity::END_TIME]) === false)
        {
            $startDate = Carbon::createFromTimestamp($input[Payment\Method::UPI][Entity::START_TIME]);

            $endTime = $startDate->addSeconds(Entity::DEFAULT_OTM_EXECUTION_RANGE)->getTimestamp();

            $input[Payment\Method::UPI][Entity::END_TIME] = $endTime;
        }
    }

    /**
     * Pre Process the input for UPI and set the defaults.
     * @param array $input
     */
    protected function preProcessForUpiIfApplicable(array &$input)
    {
        if ($input['method'] !== Payment\Method::UPI)
        {
            return;
        }

        // New flow needs to use the UPI block, which was first utilising the `_`  meta block
        // For backward compatibility, we still pick the values from the `_` block and set it
        // on the `upi` block and Payments block has VPA which should be set in the `upi` block
        // Priority is always UPI block
        if (isset($input[Payment\Method::UPI][Payment\Entity::VPA]) === true)
        {
            $input[Payment\Method::UPI][Payment\Entity::VPA] = trim($input[Payment\Method::UPI][Payment\Entity::VPA]);

            $input[Payment\Entity::VPA] = $input[Payment\Method::UPI][Payment\Entity::VPA];
        }
        else if (isset($input[Payment\Entity::VPA]) === true)
        {
            $input[Payment\Entity::VPA] = trim($input[Payment\Entity::VPA]);

            $input[Payment\Method::UPI][Payment\Entity::VPA] =  $input[Payment\Entity::VPA];
        }

        if (isset($input[Payment\Method::UPI]['flow']) === true)
        {
            $input['_']['flow'] = $input[Payment\Method::UPI]['flow'];
        }
        else if ((isset($input['_']['flow']) === true) and (Entity::isValidFlow($input['_']['flow'])))
        {
            $input[Payment\Method::UPI]['flow'] = $input['_']['flow'];
        }

        if (isset($input[Payment\Method::UPI]['flow']) === false)
        {
            $input[Payment\Method::UPI]['flow'] = Payment\Flow::COLLECT;
        }

        if ($this->isOtmPayment($input) === true)
        {
            $this->preProcessForUpiOtmIfApplicable($input);
        }

        if (empty($input[Payment\Method::UPI][Entity::TYPE]) === true)
        {
            $input[Payment\Method::UPI][Entity::TYPE] = Type::DEFAULT;
        }

        // Add provider if it is available
        if (empty($input[Payment\Entity::UPI_PROVIDER]) === false)
        {
            $input[Payment\Method::UPI][Entity::PROVIDER] = $input[Payment\Entity::UPI_PROVIDER];
        }

        //Currently getting passed as _.app. Should be under upi.app.
        if ((isset($input['_'][Entity::APP] ) === true))
        {
            $input[Payment\Method::UPI][Entity::APP] = $input['_'][Entity::APP];
        }

        //Currently getting passed as _.upiqr . Should be under upi.mode.
        if ((isset($input[Payment\Method::UPI][Entity::MODE]) === true)
            and ($input[Payment\Method::UPI][Entity::MODE] === Payment\UpiMetadata\Mode::UPI_QR))
        {
            $input['_']['upiqr'] = true;
        }
        else if ((isset($input['_']['upiqr']) === true) and ($input['_']['upiqr']))
        {
            $input[Payment\Method::UPI][Entity::MODE] = Payment\UpiMetadata\Mode::UPI_QR;
        }
    }

    /**
     * Sets the metadata for upi in the payment entity.
     * @param Payment\Entity $payment
     * @param $input
     */
    protected function setUpiMetadataIfApplicable(Payment\Entity $payment, $input, $filtered = false)
    {
        if ($payment->isUpi() === true)
        {
            try
            {
                $upiMetadata = (new Payment\UpiMetadata\Core)->create($input[Payment\Method::UPI], $payment);

                /**
                 * We are setting the upi_metadata here. For persisting the upi metadata,
                 * we are using the Payment Observer where we listen to the created hook of
                 * the payment entity, and persist the upi metadata pulling it out from the payment
                 * entity's metadata.
                 */
                $payment->setMetadataKey(Payment\UpiMetadata\Entity::UPI_METADATA, $upiMetadata);
            }
            catch (Exception\ExtraFieldsException $e)
            {
                $this->trace->traceException(
                    $e,
                    $filtered === true ? Trace::CRITICAL : Trace::INFO,
                    TraceCode::PAYMENT_UPI_METADATA_SAVE_FAILED,
                    $input[Payment\Method::UPI] ?? null
                );

                if ($filtered === false)
                {
                    $input[Payment\Method::UPI] = array_only($input[Payment\Method::UPI], (new Entity)->getFillable());

                    $this->setUpiMetadataIfApplicable($payment, $input, true);
                }
            }
            catch (Exception\BaseException $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::PAYMENT_UPI_METADATA_SAVE_FAILED,
                    $input[Payment\Method::UPI] ?? null
                );
            }
        }
    }

    /**
     * @param Payment\Entity $payment
     * @param bool $strict
     * @return Entity
     * @throws Exception\LogicException
     */
    protected function getUpiMetadataForPayment(Payment\Entity $payment, bool $strict = true)
    {
        $metadata = $payment->getMetadata(Entity::UPI_METADATA);

        if (empty($metadata) === true)
        {
            // This will check in database
            $metadata = $payment->getUpiMetadata();
        }

        if ((empty($metadata) === true) and
            ($strict === true))
        {
            throw new Exception\LogicException('Upi metadata not found when needed');
        }

        return $metadata;
    }

    protected function getTracableAcquirerDataForUpi(Payment\Entity $payment, $data)
    {
        if (is_array($data) === false)
        {
            return [];
        }

        $tracable = $data;

        if ((isset($tracable[Entity::VPA]) === true) and
            (is_string($tracable[Entity::VPA]) === true))
        {
            $tracable[Entity::VPA] = mask_vpa($tracable[Entity::VPA]);
        }

        return $tracable;
    }

    /******************************** Upi Payment Service ******************************************/

    /**
     * Prepares the gatewayinput for Upi Service and calls action method of Upi service class
     *
     * @param Payment\Entity $payment
     * @param string $gateway
     * @param string $action
     * @param array $gatewayData
     * @return void
     */
    public function callUpiPaymentServiceAction(Payment\Entity $payment,
                                                string $gateway,
                                                string $action,
                                                array $gatewayData)
    {
        // set metadata in gatewayData array for authorize action.
        if ($action === Payment\Action::AUTHORIZE)
        {
            $this->setMetadataForUpsAuthorize($payment, $gatewayData);
        }

        return $this->app['upi.payments']->action($action, $gatewayData);
    }

    /**
     * Set UPS input metadata for authorize action
     *
     * @param Payment\Entity $payment
     * @param array $gatewayData
     *
     * @return void
     * */
    protected function setMetadataForUpsAuthorize(Payment\Entity $payment, array &$gatewayData)
    {
        $upiMetadata = $payment->getUpiMetadata()->toArray();

        // check Metadata details at
        // https://github.com/razorpay/proto/blob/master/paymentsupi/common/v1/fields/fields.proto
        $metadata = [
            Entity::FLOW        => $upiMetadata[Entity::FLOW],
            Entity::TYPE        => $upiMetadata[Entity::TYPE],
            Entity::MODE        => $upiMetadata[Entity::MODE],
            Entity::EXPIRY_TIME => $upiMetadata[Entity::EXPIRY_TIME] ?? null,
        ];

        $gatewayData['metadata'] = $metadata;
    }
}
