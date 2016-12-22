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
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Transaction_Broker_Report';

    protected $allowed = [
        E::TRANSACTION
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

        return $this->generateReportFile($entities);
    }

    protected function generateReportFile($entities)
    {
         $data = $entities->map(function ($transaction)
        {
           $methodDetails = $transaction->getMethodDetails();

           $feesBreakupDetails = $transaction->getFeesBreakupDetails();

            return [
                'Merchant Name'      => $transaction->merchant->getName(),
                'Merchant ID'        => $transaction->merchant->getId(),
                'Txn Id'             => $transaction->source->getPublicId(),
                'Txn State'          => $transaction->getStateForReport(),
                'Client Code'        => 'NA',          // TBD
                'Merchant Txn Id'    => $transaction->getOrderId(),
                'Product'            => 'NSE',             // TBD
                'Discriminator'      => 'NB',        // TBD
                'Bank Name'          => $methodDetails[Payment\Method::NETBANKING] ?? null,
                'Card Type'          => $this->getCardType($methodDetails),
                'Card No'            => $this->getCardNumber($methodDetails),
                'Card Issuing Bank'  => $this->getCardIssuer($methodDetails),
                'Bank Ref No'        => $transaction->getBankReferenceNo(),
                'Gross Txn Amount'   => ($transaction->getAmount() / 100),
                'Txn Charges'        => ($feesBreakupDetails[FeeBreakup\Name::PAYMENT] ?? 0 / 100),
                'Service Tax'        => ($feesBreakupDetails[FeeBreakup\Name::SERVICE_TAX] ?? 0 / 100),
                'SB Cess'            => ($feesBreakupDetails[FeeBreakup\Name::SWACHH_BHARAT_CESS] ?? 0 / 100),
                'Krishi Kalyan Cess' => ($feesBreakupDetails[FeeBreakup\Name::KRISHI_KALYAN_CESS] ?? 0 / 100),
                'Total Chargeable'   => ($transaction->getFee() / 100),
                'Net Amount'         => $this->getNetAmountForReport($transaction),
                'Payment Status'     => $transaction->getPaymentStatus(),
                'Settlement Date'    => $transaction->getDateInFormatDMY(Transaction\Entity::SETTLED_AT),
                'Refund Reference'   => $transaction->source->getPublicId() ,      // TBD Need to decide whats to be shown here,
                'Refund Status'      => $transaction->getRefundStatus()
            ];
        });

        $urlExcel = $this->writeToExcelFile($data, $this->getFileNameWithoutExt());

        return ['url' => $urlExcel];
    }

    protected function getCardType($methodDetails)
    {
        $card = $methodDetails[Payment\Method::CARD] ?? null;

        if ($card !== null)
        {
            return $card->getType();
        }

        return null;
    }

    protected function getCardNumber($methodDetails)
    {
        $card = $methodDetails[Payment\Method::CARD] ?? null;

        if ($card !== null)
        {
            return $card->getFormatted();
        }

        return null;
    }

    protected function getCardIssuer($methodDetails)
    {
        $card = $methodDetails[Payment\Method::CARD] ?? null;

        if ($card !== null)
        {
            return $card->getIssuer();
        }

        return null;
    }

    protected function getNetAmountForReport($transaction)
    {
        if ($transaction->isTypeRefund())
        {
            return ($transaction->getDebit() / 100);
        }
        elseif ($transaction->isTypePayment())
        {
            return ($transaction->getCredit() / 100);
        }
    }
}
