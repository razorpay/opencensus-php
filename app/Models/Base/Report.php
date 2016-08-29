<?php

namespace RZP\Models\Base;

use RZP\Constants\Entity as E;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\Transaction;

class Report extends Service
{
    protected $allowed = array(
        E::ORDER,
        E::REFUND,
        E::PAYMENT,
        E::SETTLEMENT,
        E::TRANSACTION,
    );

    // Corresponds to 15th November 2015 00:00
    const SWACH_BHARAT_CUTOFF_TIMESTAMP = 1447525800;
    const SWACH_BHARAT_CESS = 'Swachh Bharat Cess';
    const SWACH_BHARAT_CESS_RATE = 0.005;

    //Corresponds to 1st June, 2016 00:00
    const KRISHI_KALYAN_CUTOFF_TIMESTAMP = 1464719400;
    const KRISHI_KALYAN_CESS = 'Krishi Kalyan Cess';
    const KRISHI_KALYAN_CESS_RATE = 0.005;

    const SERVICE_TAX  = 'Service Tax';
    const RAZORPAY_FEE = 'razorpay_fee';
    const TAXES = 'taxes';
    const TAX   = 'tax';
    const TOTAL_FEE = 'total_fee';

    /**
     * This is the case where we calculate the sum of
     * payments and calculate the swachh bharat cess
     * manually
     */
    const SB_COMPLEX_CASE = [
        'month'  =>  '11',
        'year'   =>  '2015'
    ];

    const KK_COMPLEX_CASE = [
        'month'  =>  '6',
        'year'   =>  '2016'
    ];

    protected $SBCessMonth;
    protected $KKCessMonth;


    public function __construct()
    {
        parent::__construct();

        $this->SBCessMonth = Carbon::createFromDate(
            self::SB_COMPLEX_CASE['year'],
            self::SB_COMPLEX_CASE['month']);

        $this->KKCessMonth = Carbon::createFromDate(
            self::KK_COMPLEX_CASE['year'],
            self::KK_COMPLEX_CASE['month']);
    }

    public function getReport($input, $entity)
    {
        $this->checkAllowedEntity($entity);

        $this->increaseAllowedSystemLimits();

        $begin = time();

        $merchantId = $this->merchant->getId();

        (new Validator)->validateInput('report', $input);

        date_default_timezone_set('Asia/Kolkata');

        list($from, $to) = $this->getTimestamps($input);

        $repo = E::getEntityRepository($entity);

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'entity'        => $entity,
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId,
                'time_started'  => $begin
            ]);

        $entities = (new $repo)->fetchEntitiesForReport($merchantId, $from, $to);

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

        $data = $entities->toArrayReport();

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

        return $data;
    }

    public function getInvoice($input)
    {
        $merchantId = $this->merchant->getId();

        (new Validator)->validateInput('report', $input);

        list($from, $to) = $this->getTimestamps($input);

        // If the invoice needs to be generated for November 2015 (SB Cess month), SB cess should not be
        // applied for transactions between November 1st to November 15th. For transactions between
        // November 15th to November 30th, SB cess should be applied.
        // For any other month, SB cess should be either applied (from Nov 2015) or not (before Nov 2015).
        if ($this->isComplexSBCessCase($input) === true)
        {
            // Gets the total fees and service tax of transactions of the merchants
            // before 15th november and after 15th november.
            $dataBefore15Nov = (new Transaction\Repository)->fetchDataForInvoice(
                $merchantId,
                $from,
                self::SWACH_BHARAT_CUTOFF_TIMESTAMP);

            $dataAfter15Nov  = (new Transaction\Repository)->fetchDataForInvoice(
                $merchantId,
                self::SWACH_BHARAT_CUTOFF_TIMESTAMP,
                $to);

            // Now we calculate taxes on each individually.
            // Since this block will be executed only if the input is November 2015,
            // KK cess should NOT be calculated in this flow. (KK cess should be
            // calculated for transactions from June 2016 only)
            $this->addTaxComponents($dataBefore15Nov, false, false);
            $this->addTaxComponents($dataAfter15Nov, true, false);

            $data = $this->sumInvoiceData($dataBefore15Nov, $dataAfter15Nov);
        }
        else
        {
            $data = (new Transaction\Repository)->fetchDataForInvoice($merchantId, $from, $to);

            $sbCessApplied = $this->isCessApplicable($input, $this->SBCessMonth);

            $kkCessApplied = $this->isCessApplicable($input, $this->KKCessMonth);

            $this->addTaxComponents($data, $sbCessApplied, $kkCessApplied);
        }

        return $data;
    }

    /**
     * Adds two invoice disjoint datasets together
     * This is used for the november 2015 breakdown
     * where we calculate separately for before and after
     * 15th of november
     */
    protected function sumInvoiceData($beforeSBCCutoff, $afterSBCCutoff)
    {
        return [
            self::TOTAL_FEE => $beforeSBCCutoff[self::TOTAL_FEE] + $afterSBCCutoff[self::TOTAL_FEE],
            self::RAZORPAY_FEE => $beforeSBCCutoff[self::RAZORPAY_FEE] + $afterSBCCutoff[self::RAZORPAY_FEE],
            self::TAX => $beforeSBCCutoff[self::TAX] + $afterSBCCutoff[self::TAX],
            self::TAXES => [
                self::SERVICE_TAX   =>  $beforeSBCCutoff[self::TAXES][self::SERVICE_TAX] +
                    $afterSBCCutoff[self::TAXES][self::SERVICE_TAX],
                // The first half doesn't have the swach bharat cess
                self::SWACH_BHARAT_CESS => $afterSBCCutoff[self::TAXES][self::SWACH_BHARAT_CESS]
            ]
        ];
    }

    protected function addTaxComponents(&$data, $sbCessApplied, $kkCessApplied)
    {
        $taxes = [];

        $data[self::RAZORPAY_FEE] = $data[self::TOTAL_FEE] - $data[self::TAX];

        // $data[self::TAX] is retrieved from the DB. It's the service tax amount, inclusive of
        // the various cess amounts.
        $totalTax = $data[self::TAX];

        $swCess = $kkCess = 0;

        // This is all in Paise
        // so we can round to the nearest integer
        if ($sbCessApplied === true)
        {
            $swCess = $taxes[self::SWACH_BHARAT_CESS] =
                round($data[self::RAZORPAY_FEE] * self::SWACH_BHARAT_CESS_RATE);
        }

        if ($kkCessApplied === true)
        {
            $kkCess = $taxes[self::KRISHI_KALYAN_CESS] =
                round($data[self::RAZORPAY_FEE] * self::KRISHI_KALYAN_CESS_RATE);
        }

        $taxes[self::SERVICE_TAX] = round($totalTax - $swCess - $kkCess);

        $data[self::TAXES] = $taxes;
    }

    /**
     * This only handles the easy cases of cess month
     * @return boolean
     */
    protected function isCessApplicable($input, $cessMonth)
    {
        // This will revert to first of the month
        $inputDate = Carbon::createFromDate($input['year'], $input['month']);

        // input date is greater than or equal to SBCessMonth
        return $inputDate->gte($cessMonth);
    }

    protected function isComplexSBCessCase($input)
    {
        // We are only comparing the year and month
        $inputDate  = Carbon::createFromDate(
            $input['year'],
            $input['month']
        );

        return $inputDate->eq($this->SBCessMonth);
    }

    protected function getTimestamps($input)
    {
        $year = (int) $input['year'];

        // If day is set, `from` and `to` are of that day start and end only.
        // If day is not set, month should be set. `from` and `to` will be
        // the first day and the last day of the month.
        if (isset($input['day']))
        {
            $day = (int) $input['day'];
            $month = (int) $input['month'];

            $date = Carbon::today('Asia/Kolkata')
                          ->month($month)
                          ->day($day)
                          ->year($year)
                          ->startOfDay();

            $from = $date->timestamp;
            $to = $date->addDay()->timestamp - 1;
        }
        else if (isset($input['month']))
        {
            $month = (int) $input['month'];

            assert($month > 0);
            assert($month <= 12);

            $from = Carbon::today('Asia/Kolkata')
                                  ->month($month)
                                  ->year($year)
                                  ->startOfMonth()
                                  ->timestamp;

            $to   = Carbon::today('Asia/Kolkata')
                                  ->month($month)
                                  ->year($year)
                                  ->endOfMonth()
                                  ->timestamp;
        }
        else
        {
            $from = $to = null;
        }

        return [$from, $to];
    }

    protected function checkAllowedEntity($entity)
    {
        if (in_array($entity, $this->allowed) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot get report for the given entity');
        }
    }

    protected function increaseAllowedSystemLimits()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(501);
    }
}
