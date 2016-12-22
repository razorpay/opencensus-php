<?php

namespace RZP\Models\Base;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Base\JitValidator;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Models\Transaction\FeeBreakup;
use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class BrokerTransactionReport extends Base\Report
{
    protected static $fileToWriteName = 'Transaction_Broker_Report';

    protected $allowed = [
        E::TRANSACTION
    ];

    protected $reportFormat = [
        'Merchant Name'      => null,
        'Merchant ID'        => null,
        'Txn Id'             => null,
        'Txn State'          => null,
        'Client Code'        => null,
        'Merchant Txn Id'    => null,
        'Product'            => 'NSE',
        'Discriminator'      => 'NB',
        'Bank Name'          => null,
        'Card Type'          => null,
        'Card No'            => null,
        'Card Issuing Bank'  => null,
        'Bank Ref No'        => null,
        'Gross Txn Amount'   => null,
        'Txn Charges'        => null,
        'Service Tax'        => null,
        'SB Cess'            => null,
        'Krishi Kalyan Cess' => null,
        'Total Chargeable'   => null,
        'Net Amount'         => null,
        'Payment Status'     => null,
        'Settlement Date'    => null,
        'Refund Reference'   => null,
        'Refund Status'      => null
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getReport($input, $entity)
    {
        $this->checkAllowedEntity($entity);

        $this->increaseAllowedSystemLimits();

        $begin = time();

        $merchantId = $this->merchant->getId();

        (new JitValidator)->rules(self::$rules)->input($input)->validate();

        date_default_timezone_set('Asia/Kolkata');

        list($from, $to) = $this->getTimestamps($input);

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'entity'        => $entity,
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId,
                'time_started'  => $begin
            ]);

        $repo = $this->repo->$entity;
        $entities = $repo->fetchEntitiesForBrokerReport($merchantId, $from, $to);
        $timeTaken = time() - $begin;

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'entity'        => $entity,
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId,
                'time_taken'    => $timeTaken
            ]);

        $timeTaken = time() - $begin;

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'entity'        => $entity,
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId,
                'time_taken'    => $timeTaken
            ]);

        return $this->getDataForReport($entities);
    }

    protected function getDataForReport($entities)
    {
        $data = $entities->map(function ($transaction)
        {
            $paymentMethodDetails = $transaction->getPaymentMethodDetails();

            $feesBreakupDetails = $transaction->getFeesBreakupDetails();

            $reportData = [];

            $reportData['Merchant Name'] = $transaction->merchant->getName();
            $reportData['Merchant ID'] = $transaction->merchant->getId();

            $reportData['Txn Id'] = $transaction->source->getPublicId();
            $reportData['Txn State'] = $transaction->getState();

            $reportData['Merchant Txn Id'] = $transaction->getOrderId();

            $reportData['Bank Name'] = $this->getBankNameForPayment($paymentMethodDetails);

            $reportData = array_merge($reportData, $this->getCardDetails($paymentMethodDetails));

            $reportData['Gross Txn Amount'] = $this->getFormattedAmount($transaction->getAmount());

            if (count($feesBreakupDetails) > 0)
            {
                $reportData = array_merge($reportData, $this->getFeeDetails($feesBreakupDetails));
            }

            $reportData['Total Chargeable'] = $this->getFormattedAmount($transaction->getFee());
            $reportData['Net Amount'] = $this->getNetAmountForReport($transaction);

            $reportData['Payment Status'] = $transaction->getPaymentStatus();

            $reportData['Settlement Date'] = $transaction->getDateInFormatDMY(Transaction\Entity::SETTLED_AT);

            $reportData = array_merge($this->reportFormat, $reportData);

            return $reportData;
        });

        return $data->toArray();
    }

    protected function getBankNameForPayment($paymentMethodDetails)
    {
        return $paymentMethodDetails[Payment\Method::NETBANKING] ?? null;
    }

    protected function getCardDetails($paymentMethodDetails)
    {
        $cardDetails = [];

        if (isset($paymentMethodDetails[Payment\Method::CARD]) === true)
        {
            $card = $paymentMethodDetails[Payment\Method::CARD];

            $cardDetails['Card Type'] = $card->getType();
            $cardDetails['Card No'] = $card->getFormatted();
            $cardDetails['Card Issuing Bank'] = $card->getIssuer();
        }

        return $cardDetails;
    }

    protected function getFeeDetails(array $feesBreakupDetails)
    {
        return [
            'Txn Charges'        => $this->getFormattedAmount($feesBreakupDetails[FeeBreakup\Name::PAYMENT]),
            'Service Tax'        => $this->getFormattedAmount($feesBreakupDetails[FeeBreakup\Name::SERVICE_TAX]),
            'SB Cess'            => $this->getFormattedAmount($feesBreakupDetails[FeeBreakup\Name::SWACHH_BHARAT_CESS]),
            'Krishi Kalyan Cess' => $this->getFormattedAmount($feesBreakupDetails[FeeBreakup\Name::KRISHI_KALYAN_CESS])
        ];
    }

    protected function getFormattedAmount(int $amount)
    {
        return ($amount / 100);
    }

    protected function getNetAmountForReport($transaction)
    {
        if ($transaction->isTypeRefund())
        {
            return $this->getFormattedAmount($transaction->getDebit());
        }
        elseif ($transaction->isTypePayment())
        {
            return $this->getFormattedAmount($transaction->getCredit());
        }
    }
}
