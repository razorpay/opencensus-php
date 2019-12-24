<?php

namespace RZP\Models\QrCode\Upi;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\QrCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;

class Processor extends VirtualAccount\Processor
{
    /**
     * Actual Callback Data
     * @var array
     */
    protected $input;
    /**
     * Payment Id as per the gateway callback
     * @var string
     */
    protected $referenceId;
    /**
     * Processed callback data from gateway
     * @var array
     */
    protected $data;
    /**
     * @var Terminal\Entity
     */
    protected $terminal;
    /**
     * Whether the authorized payment be captured or not
     * Default is true
     * @var bool
     */
    private $shouldCapturePayment = true;

    /**
     * In case of pending payment, the payment will be created
     * against shared virtual account thus to demo merchant
     * @var bool
     */
    private $isPaymentPendingAtGateway = false;

    public function __construct(array $input, string $referenceId, array $data, Terminal\Entity $terminal)
    {
        parent::__construct();

        $this->input = $input;

        $this->referenceId = $referenceId;

        $this->data = $data;

        $this->terminal = $terminal;
    }

    public function process(Base\PublicEntity $entity)
    {
        return $this->processPayment($entity);
    }

    protected function processPayment(Base\PublicEntity $entity)
    {
        // This will in return call self::getVirtualAccountFromEntity
        $this->setVirtualAccount($entity);

        // This will take the current virtual account's merchant
        $this->setMerchant();

        $paymentProcessor = $this->getPaymentProcessor();

        // This will check for duplicate payment and for pending payment as well
        $this->isDuplicate($entity);

        // In case we need use shared virtual account
        if ($this->useSharedVirtualAccount($entity) === true)
        {
            $this->virtualAccount = $this->createOrFetchSharedVirtualAccount();

            $this->setMerchant();

            // Force create a new payment processor
            $paymentProcessor = $this->getPaymentProcessor(true);

            // Since it is going to be on shared merchant
            $this->shouldCapturePayment = false;
        }

        $payment = $this->repo->transaction(
            function() use ($paymentProcessor)
            {
                $payment = $paymentProcessor->createPaymentFromS2SCallback(
                    $this->input,
                    $this->getPaymentArray(),
                    $this->terminal);

                $authorized = $this->authorizePushPayment($paymentProcessor, $payment);

                if ($authorized === true)
                {
                    $this->updateVirtualAccount($payment);
                }

                return $payment;
            });

        if ($this->shouldCapturePayment === true)
        {
            $paymentProcessor->autoCapturePayment($payment);
        }

        return $entity;
    }

    protected function isDuplicate(Base\PublicEntity $entity)
    {
        $paymentProcessor = $this->getPaymentProcessor();

        // Exception will be thrown in case of duplicate or failed payment
        try
        {
            $paymentProcessor->validatePushPayment($this->input, $this->terminal);

            return false;
        }
        catch (GatewayErrorException $exception)
        {
            if ($exception->getError()->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_PENDING)
            {
                // The payment now will be created on shared virtual account
                $this->isPaymentPendingAtGateway = true;

                return false;
            }

            // Else we can throw exception, It could be either be because payment is duplicate
            // or any other gateway specific error, in both cases exception is traces outside
            throw $exception;
        }
    }

    protected function getVirtualAccountFromEntity(Base\PublicEntity $entity)
    {
        // The very reason we are here is that merchant reference was found in qr_code
        // The unexpected part of story is already handled in GatewayController
        $qrCode = $this->repo->qr_code->findByMerchantReference($this->referenceId);

        if ($qrCode === null)
        {
            return null;
        }

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromQrCodeId($qrCode->getId());

        return $virtualAccount;
    }

    protected function useSharedVirtualAccount(Base\PublicEntity $entity): bool
    {
        // The this is a pending is pending at gateway, we will create on shared VA
        if ($this->isPaymentPendingAtGateway === true)
        {
            return true;
        }

        $amountExpected = $this->virtualAccount->getAmountExpected();

        $amountReceived = (int) $this->data['payment']['amount'];

        // If amount is not same as expected, we will refund the payment
        if ($amountExpected !== $amountReceived)
        {
            return true;
        }

        return parent::useSharedVirtualAccount($entity);
    }

    protected function getReceiver()
    {
        return $this->virtualAccount->qrCode;
    }

    /********************* PRIVATES ***************************/

    private function getPaymentArray()
    {
        $paymentInput = $this->data['payment'];

        $defaultArray = $this->getDefaultPaymentArray();

        $paymentArray = array_merge($paymentInput, $defaultArray);

        return $paymentArray;
    }

    private function createOrFetchSharedVirtualAccount()
    {
        $options = [
            'qr_code' => [
                'method' => [
                    'card'  => false,
                    'upi'   => true,
                ],
            ]
        ];

        return (new VirtualAccount\Core)->createOrFetchSharedVirtualAccount($options);
    }

    /**
     * While authorizing if Gateway finds non success status
     * We will simply mark the payment failed
     *
     * @param Payment\Processor\Processor $paymentProcessor
     * @param Payment\Entity $payment
     */
    private function authorizePushPayment(Payment\Processor\Processor $paymentProcessor, Payment\Entity $payment)
    {
        try
        {
            $paymentProcessor->authorizePushPayment($payment, $this->input);

            return true;
        }
        // TODO: We need to change the authorizePushPayment implementation
        catch (BadRequestException $exception)
        {
            return false;
        }
    }
}
