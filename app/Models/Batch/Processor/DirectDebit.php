<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Batch\Header;
use RZP\Models\Card\Entity as Card;
use RZP\Models\Customer;
use RZP\Models\FileStore;
use RZP\Models\Order\Entity as Order;
use RZP\Models\Payment\AuthType;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Processor;
use \RZP\Models\Order\Core as OrderCore;

class DirectDebit extends Base
{
    protected function processEntry(array & $entry)
    {
        $this->processPayment($entry);
    }

    /**
     * @param array $row
     * @return array
     */
    protected function processPayment(array & $row)
    {

        try
        {
            $amount = (int) $row[Header::AMOUNT];
            $currency = $row[Header::CURRENCY];
            $note1  = $row[Header::NOTES1];
            $note2  = $row[Header::NOTES2];
            $note3  = $row[Header::NOTES3];
            $email  = $row[Header::EMAIL];
            $phone  = $row[Header::PHONE];
            $name   = $row[Header::CARDHOLDER_NAME];

            $order = $this->createOrder($row);
            $customer = $this->createCustomer($row);


            $card = [
                Card::NUMBER        =>  $row[Header::CARD],
                Card::CVV           =>  Card::DUMMY_CVV,
                Card::EXPIRY_MONTH  =>  substr($row[Header::EXPIRY], 0, 2),
                Card::EXPIRY_YEAR   =>  substr($row[Header::EXPIRY], 2, 2),
                Card::NAME          => $name,
            ];

            $request = [
                Payment::METHOD         => Method::CARD,
                Payment::AMOUNT         => $amount,
                Payment::EMAIL          => $email,
                Payment::CONTACT        => $phone,
                Payment::CURRENCY       => $currency,
                Payment::CARD           => $card,
                Payment::NOTES          => array(
                    'note1' =>  $note1,
                    'note2' =>  $note2,
                    'note3' =>  $note3,
                ),
                Payment::AUTH_TYPE      => AuthType::SKIP,
                Payment::CUSTOMER_ID    => $customer['id'],
                Payment::ORDER_ID       => $order['id'],
            ];

            $processor = new Processor($this->merchant);
            $result = $processor->process($request);
            $row[Header::STATUS]                    =   "CAPTURED";
            $row[Header::DIRECT_DEBIT_PAYMENT_ID]   =   $result['payment_id'];
        } finally
        {
            $row[Header::CARD] = substr($row[Header::CARD], 0,4) . 'xxxxxxxx' . substr($row[Header::CARD], 12);
        }


        return $row;
    }


    private function createOrder($row)
    {
        $orderService = new OrderCore();
        $orderInput = [
            Order::AMOUNT           =>  (int) $row[Header::AMOUNT],
            Order::CURRENCY         =>  $row[Header::CURRENCY],
            Order::RECEIPT          =>  $row[Header::RECEIPT],
            Order::PAYMENT_CAPTURE  =>  true,
        ];

        return $orderService->create($orderInput, $this->merchant);
    }

    protected function sendProcessedMail()
    {
        // Do not send email
        return ;
    }

    private function createCustomer($row)
    {
        $customerInput = [
            Customer::NAME          =>  $row[Header::CARDHOLDER_NAME],
            Customer::EMAIL         =>  $row[Header::EMAIL],
            Customer::CONTACT       =>  $row[Header::PHONE],
            Customer::FAIL_EXISTING =>  "0",
        ];
        $customerService = new Customer\Core();
        return $customerService->createLocalCustomer($customerInput, $this->merchant);
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
}
