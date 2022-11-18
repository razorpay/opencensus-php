<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Batch\Header;
use RZP\Models\Card\Entity as Card;
use RZP\Models\Customer;
use RZP\Models\FileStore;
use RZP\Models\Order;
use RZP\Models\Payment\AuthType;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Batch\Helpers\DirectDebit as Helper;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class DirectDebit extends Base
{
    /** @var PaymentProcessor  */
    protected $processor;

    /** @var Order\Core  */
    protected $orderCore;

    /** @var Customer\Core */
    protected $customerCore;

    const RESPONSE_PAYMENT_ID = 'razorpay_payment_id';

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->processor = new PaymentProcessor($this->merchant);

        $this->orderCore = new Order\Core();

        $this->customerCore = new Customer\Core();

        $this->isEncrypted = true;
    }

    protected function processEntry(array & $entry)
    {
        try
        {
            $order = $this->createOrder($entry);

            $customer = $this->createCustomer($entry);

            $this->processPayment($entry, $order, $customer);
        }
        // If an exception is thrown as a result of any of this processing,
        // we still need to mask the card number in the output file
        // and flush processor object for the next row.
        finally
        {
            $entry[Header::DIRECT_DEBIT_CARD_NUMBER] = $this->mask($entry[Header::DIRECT_DEBIT_CARD_NUMBER]);

            $this->processor->flushPaymentObjects();
        }
    }

    /**
     * @param array $row
     * @param Order\Entity $order
     * @param Customer\Entity $customer
     * @return array
     */
    protected function processPayment(array & $row, Order\Entity $order, Customer\Entity $customer)
    {
        $request = Helper::getPaymentInput($row, $order, $customer);

        $result = $this->processor->process($request);

        $payment = $this->processor->getPayment();

        $payment->batch()->associate($this->batch);

        $this->repo->saveOrFail($payment);

        $row[Header::DIRECT_DEBIT_PAYMENT_ID] = $result[self::RESPONSE_PAYMENT_ID];
        $row[Header::STATUS]                  = Batch\Status::SUCCESS;
    }

    private function createOrder(array & $row): Order\Entity
    {
        $orderInput = Helper::getOrderInput($row);

        $order = $this->orderCore->create($orderInput, $this->merchant);

        $row[Header::DIRECT_DEBIT_ORDER_ID] = $order->getPublicId();

        return $order;
    }

    protected function sendProcessedMail()
    {
        // Do not send email
        return ;
    }

    private function createCustomer(array $row): Customer\Entity
    {
        $customerInput = Helper::getCustomerInput($row);

        return $this->customerCore->createLocalCustomer($customerInput, $this->merchant, false);
    }

    /**
     * Child class must implement it
     *
     * @param string $errorDescription
     *
     * @return string
     */
    protected function getApiErrorCode(string $errorDescription): string
    {
        throw new \BadMethodCallException();
    }

    protected function postProcessEntries(array & $entries)
    {
        parent::postProcessEntries($entries);

        $processedAmount = 0;

        foreach ($entries as $entry)
        {
            if ($entry[Batch\Header::STATUS] === Batch\Status::SUCCESS)
            {
                $processedAmount += $entry[Batch\Header::DIRECT_DEBIT_AMOUNT];
            }
        }

        $this->batch->setProcessedAmount($processedAmount);

        $this->deleteBatchFile();
    }

    protected function deleteBatchFile()
    {
        $ufhFile = $this->repo->file_store->findByBatchId($this->batch->getId());

        $deleter = new FileStore\Deleter();

        // since file creation is happening on rzp-1415-prod-api-settlement we have to delete from the same bucket hence using same bucket config in type
        $deleter->type(Batch\Constants::NON_MIGRATED_BATCH)
                ->id($ufhFile->getId())
                ->merchantId($this->merchant->getId())
                ->file($ufhFile)
                ->delete();
    }

    protected function shouldEncrypt()
    {
        return $this->isEncrypted;
    }

    protected function shouldDecrypt()
    {
        return $this->isEncrypted;
    }

    protected function mask(string $card)
    {
       $entity = new Card();

       try
       {
           $entity->build([
               Card::NUMBER         =>  $card,
               Card::EXPIRY_MONTH   =>  Card::DUMMY_EXPIRY_MONTH,
               Card::EXPIRY_YEAR    =>  Card::DUMMY_EXPIRY_YEAR,
               Card::NAME           =>  'John Doe',
               Card::CVV            =>  Card::DUMMY_CVV,
           ]);
       }
       catch (\Exception $e)
       {
           return $card;
       }

        return  $entity->getMaskedCardNumber();
    }

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Header::DIRECT_DEBIT_CARD_NUMBER]);
        unset($payloadEntry[Header::DIRECT_DEBIT_EXPIRY_MONTH]);
        unset($payloadEntry[Header::DIRECT_DEBIT_EXPIRY_YEAR]);
    }
}
