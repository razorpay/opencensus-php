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

    const SERVICE_TAX = 'Service Tax';

    /**
     * This is the case where we calculate the sum of
     * payments and calculate the swachh bharat cess
     * manually
     */
    const SB_COMPLEX_CASE = [
        'month'  =>  '11',
        'year'   =>  '2015'
    ];

    public function getReport($input, $entity)
    {
        if (in_array($entity, $this->allowed) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot get report for the given entity');
        }

        $this->increaseAllowedSystemLimits();

        $merchantId = $this->merchant->getId();

        (new Validator)->validateInput('report', $input);

        list($from, $to) = $this->getTimestamps($input);

        $repo = E::getEntityRepository($entity);

        $entities = (new $repo)->fetchEntitiesForReport($merchantId, $from, $to);

        date_default_timezone_set('Asia/Kolkata');

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'entity'        => $entity,
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId
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
            $before15Nov['taxes'] = $this->calculateTaxComponents($before15Nov, $input, false);
            $after15Nov['taxes']  = $this->calculateTaxComponents($after15Nov, $input, true);


            $data = $this->sumInvoiceData($before15Nov, $after15Nov);
        }
        else
        {
            $data = (new Transaction\Repository)->fetchDataForInvoice($merchantId, $from, $to);
            $sbCessApplied = $this->isSwachBharatCessApplicable($input);
            $data['taxes'] = $this->calculateTaxComponents($data, $input, $sbCessApplied);
        }

        return $data;
    }

    /**
     * Adds two invoice disjoint datasets together
     * This is used for the november 2015 breakdown
     * where we calculate separately for before and after
     * 15th of november
     */
    protected function sumInvoiceData($beforeHalf, $afterHalf)
    {
        return [
            'total_fee' => $beforeHalf['total_fee'] + $afterHalf['total_fee'],
            'razorpay_fee' => $beforeHalf['razorpay_fee'] + $afterHalf['razorpay_fee'],
            'tax' => $beforeHalf['tax'] + $afterHalf['tax'],
            'taxes' => [
                self::SERVICE_TAX   =>  $beforeHalf['taxes'][self::SERVICE_TAX] +
                    $afterHalf['taxes'][self::SERVICE_TAX],
                // The first half doesn't have the swach bharat cess
                self::SWACH_BHARAT_CESS => $afterHalf['taxes'][self::SWACH_BHARAT_CESS]
            ]
        ];
    }

    protected function calculateTaxComponents(&$data, $input, $sbCessApplied)
    {
        $taxes = [];

        $data['razorpay_fee'] = $data['total_fee'] - $data['tax'];

        // This is all in Paise
        // so we can round to the nearest integer
        if ($sbCessApplied)
        {
            $taxes[self::SWACH_BHARAT_CESS] = round($data['razorpay_fee'] * self::SWACH_BHARAT_CESS_RATE);

            // Back calculate just the service tax
            $taxes[self::SERVICE_TAX] = round($data['tax'] - $taxes[self::SWACH_BHARAT_CESS]);
        }
        // No SB CESS
        else
        {
            $taxes[self::SERVICE_TAX] = $data['tax'];
        }

        return $taxes;
    }

    /**
     * This only handles the easy cases of
     * December 2015 or beyond
     * @return boolean
     */
    protected function isSwachBharatCessApplicable($input)
    {
        return (($input['year'] >= 2016) or
                (($input['year'] === 2015) and ($input['month'] === 12)));
    }

    protected function isComplexCessCase($input)
    {
        return (($input['month'] === self::SB_COMPLEX_CASE['month']) and
                ($input['year'] === self::SB_COMPLEX_CASE['year']));
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

            $startOfMonth = Carbon::today('Asia/Kolkata')
                                  ->month($month)
                                  ->startOfMonth()
                                  ->year($year)
                                  ->timestamp;

            $endMonth = $month + 1;

            if ($endMonth === 13)
            {
                $endMonth = 1;
                $year++;
            }

            $endOfMonth = Carbon::today('Asia/Kolkata')
                                ->month($endMonth)
                                ->startOfMonth()
                                ->year($year)
                                ->timestamp;

            $from = $startOfMonth;
            $to = $endOfMonth;
        }

        return [$from, $to];
    }

    protected function increaseAllowedSystemLimits()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
    }
}
