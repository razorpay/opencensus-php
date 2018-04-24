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
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class DirectDebit extends Base
{
    /** @var PaymentProcessor  */
    protected $processor;

    /** @var Order\Core  */
    protected $orderCore;

    /** @var Customer\Core */
    protected $customerCore;

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->processor = new PaymentProcessor($this->merchant);

        $this->orderCore = new Order\Core();

        $this->customerCore = new Customer\Core();
    }

    protected function processEntry(array & $entry)
    {
        $order = $this->createOrder($entry);

        $customer = $this->createCustomer($entry);

        $this->processPayment($entry, $order, $customer);
    }

    /**
     * @param array $row
     * @param Order\Entity $order
     * @param Customer\Entity $customer
     * @return array
     */
    protected function processPayment(array & $row, Order\Entity $order, Customer\Entity $customer)
    {
        try
        {
            $amount     = (int) $row[Header::AMOUNT];
            $currency   = $row[Header::CURRENCY];
            $note1      = $row[Header::NOTES1];
            $note2      = $row[Header::NOTES2];
            $note3      = $row[Header::NOTES3];
            $email      = $row[Header::EMAIL];
            $phone      = $row[Header::PHONE];
            $name       = $row[Header::CARDHOLDER_NAME];

            $card = [
                Card::NUMBER        =>  $row[Header::CARD],
                Card::CVV           =>  Card::DUMMY_CVV,
                Card::EXPIRY_MONTH  =>  (int) $row[Header::EXPIRY_MONTH],
                Card::EXPIRY_YEAR   =>  (int) $row[Header::EXPIRY_YEAR],
                Card::NAME          =>  $name,
            ];

            $request = [
                Payment::METHOD         => Method::CARD,
                Payment::AMOUNT         => $amount,
                Payment::EMAIL          => $email,
                Payment::CONTACT        => $phone,
                Payment::CURRENCY       => $currency,
                Payment::CARD           => $card,
                Payment::NOTES          => [
                    'note1' =>  $note1,
                    'note2' =>  $note2,
                    'note3' =>  $note3,
                ],
                Payment::AUTH_TYPE      => AuthType::SKIP,
                Payment::CUSTOMER_ID    => $customer->getPublicId(),
                Payment::ORDER_ID       => $order->getPublicId(),
            ];

            $result = $this->processor->process($request);
            $row[Header::STATUS]                    =   Batch\Status::SUCCESS;
            $row[Header::DIRECT_DEBIT_PAYMENT_ID]   =   $result['payment_id'];
        }
        finally
        {
            $row[Header::CARD] = substr($row[Header::CARD], 0,4) . 'xxxxxxxx' . substr($row[Header::CARD], 12);
        }

        return $row;
    }

    private function createOrder(array $row): Order\Entity
    {
        $orderInput = [
            Order\Entity::AMOUNT           =>  (int) $row[Header::AMOUNT],
            Order\Entity::CURRENCY         =>  $row[Header::CURRENCY],
            Order\Entity::RECEIPT          =>  $row[Header::RECEIPT],
            Order\Entity::PAYMENT_CAPTURE  =>  true,
        ];

        return $this->orderCore->create($orderInput, $this->merchant);
    }

    protected function sendProcessedMail()
    {
        // Do not send email
        return ;
    }

    private function createCustomer(array $row): Customer\Entity
    {
        $customerInput = [
            Customer\Entity::NAME          =>  $row[Header::CARDHOLDER_NAME],
            Customer\Entity::EMAIL         =>  $row[Header::EMAIL],
            Customer\Entity::CONTACT       =>  $row[Header::PHONE],
        ];

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

        $ufhFile = $this->repo->file_store->findByBatchId($this->batch->getId());

        $deleter = new FileStore\Deleter();

        $deleter->type($ufhFile->getType())
            ->id($ufhFile->getId())
            ->merchantId($this->merchant->getId())
            ->file($ufhFile)
            ->delete();
    }

    protected function shouldEncrypt()
    {
        return true;
    }
}
