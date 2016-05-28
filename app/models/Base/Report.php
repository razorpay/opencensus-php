<?php

namespace Models\Base;

use Constants\Entity as E;
use Carbon\Carbon;
use EE\Exception;
use Trace\TraceCode;
use Models\Transaction;

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

    public function __construct()
    {
        parent::__construct();

        $this->SBCessMonth = Carbon::createFromDate(
            self::SB_COMPLEX_CASE['year'],
            self::SB_COMPLEX_CASE['month']);
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

        return $entities->toArrayReport();
    }

    public function getInvoice($input)
    {
        $merchantId = $this->merchant->getId();

        (new Validator)->validateInput('report', $input);

        list($from, $to) = $this->getTimestamps($input);

        if ($this->isComplexCessCase($input))
        {
            $before15Nov = (new Transaction\Repository)->fetchDataForInvoice(
                $merchantId,
                $from,
                self::SWACH_BHARAT_CUTOFF_TIMESTAMP);

            $after15Nov  = (new Transaction\Repository)->fetchDataForInvoice(
                $merchantId,
                self::SWACH_BHARAT_CUTOFF_TIMESTAMP,
                $to);

            // Now we calculate taxes on each individually
            $this->addTaxComponents($before15Nov, $input, false);
            $this->addTaxComponents($after15Nov, $input, true);


            $data = $this->sumInvoiceData($before15Nov, $after15Nov);
        }
        else
        {
            $data = (new Transaction\Repository)->fetchDataForInvoice($merchantId, $from, $to);
            $sbCessApplied = $this->isSwachBharatCessApplicable($input);
            $this->addTaxComponents($data, $input, $sbCessApplied);
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

    protected function addTaxComponents(&$data, $input, $sbCessApplied)
    {
        $taxes = [];

        $data[self::RAZORPAY_FEE] = $data[self::TOTAL_FEE] - $data[self::TAX];

        // This is all in Paise
        // so we can round to the nearest integer
        if ($sbCessApplied)
        {
            $taxes[self::SWACH_BHARAT_CESS] = round($data[self::RAZORPAY_FEE] * self::SWACH_BHARAT_CESS_RATE);

            // Back calculate just the service tax
            $taxes[self::SERVICE_TAX] = round($data[self::TAX] - $taxes[self::SWACH_BHARAT_CESS]);
        }
        // No SB CESS
        else
        {
            $taxes[self::SERVICE_TAX] = $data[self::TAX];
        }

        $data[self::TAXES] = $taxes;
    }

    /**
     * This only handles the easy cases of
     * December 2015 or beyond
     * @return boolean
     */
    protected function isSwachBharatCessApplicable($input)
    {
        // This will revert to first of the month
        $inputDate  = Carbon::createFromDate($input['year'], $input['month']);

        // input date is greater than or equal to SBCessMonth
        return $inputDate->gte($this->SBCessMonth);
    }

    protected function isComplexCessCase($input)
    {
        // We are only comparing the year and month
        $inputDate  = Carbon::createFromDate(
            $input['year'],
            $input['month']);

        return $inputDate->eq($this->SBCessMonth);
    }

    protected function getTimestamps($input)
    {
        $year = (int) $input['year'];

        if (isset($input['day']))
        {
            $day = (int) $input['day'];
            $month = (int) $input['month'];

            $date = Carbon::today('Asia/Kolkata')
                          ->day($day)
                          ->month($month)
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
        set_time_limit(300);
    }
}
