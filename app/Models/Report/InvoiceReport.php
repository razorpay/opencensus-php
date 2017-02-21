<?php

namespace RZP\Models\Report;

use Carbon\Carbon;

use RZP\Base\JitValidator;
use RZP\Models\Transaction;
use RZP\Models\Pricing\Feature;

class InvoiceReport extends Base
{
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

    public function getInvoiceV2($input)
    {
        $merchantId = $this->merchant->getId();

        (new JitValidator)->rules(self::$rules)->input($input)->validate();

        list($from, $to) = $this->getTimestamps($input);

        $feesBreakup = $this->repo->fee_breakup->fetchFeesBreakupForInvoice($merchantId, $from, $to);

        $fees = $feesBreakup->getStringAttributesByKey('name');

        $totalRzpFee = 0;

        foreach (Feature::FEATURE_LIST as $feature)
        {
            if (isset($fees[$feature]) === true)
            {
                $totalRzpFee += intval($fees[$feature]['sum']);
            }
        }

        $serviceTax = 0;
        $swachBharatCess = 0;
        $krishiKalyanCess = 0;

        if (empty($fees[Transaction\FeeBreakup\Name::SERVICE_TAX]) === false)
        {
            $serviceTax = intval($fees[Transaction\FeeBreakup\Name::SERVICE_TAX]['sum']);
        }

        if (empty($fees[Transaction\FeeBreakup\Name::SWACHH_BHARAT_CESS]) === false)
        {
            $swachBharatCess = intval($fees[Transaction\FeeBreakup\Name::SWACHH_BHARAT_CESS]['sum']);
        }

        if (empty($fees[Transaction\FeeBreakup\Name::KRISHI_KALYAN_CESS]) === false)
        {
            $krishiKalyanCess = intval($fees[Transaction\FeeBreakup\Name::KRISHI_KALYAN_CESS]['sum']);
        }

        $totalTax = $serviceTax + $swachBharatCess + $krishiKalyanCess;

        return [
            self::TOTAL_FEE    => $totalRzpFee + $totalTax,
            self::RAZORPAY_FEE => $totalRzpFee,
            self::TAX          => $totalTax,
            self::TAXES        => [
                self::SERVICE_TAX        => $serviceTax,
                self::SWACH_BHARAT_CESS  => $swachBharatCess,
                self::KRISHI_KALYAN_CESS => $krishiKalyanCess
            ],
        ];
    }

    public function getInvoice($input)
    {
        $merchantId = $this->merchant->getId();

        (new JitValidator)->rules(self::$rules)->input($input)->validate();

        list($from, $to) = $this->getTimestamps($input);

        // If the invoice needs to be generated for November 2015 (SB Cess month), SB cess should not be
        // applied for transactions between November 1st to November 15th. For transactions between
        // November 15th to November 30th, SB cess should be applied.
        // For any other month, SB cess should be either applied (from Nov 2015) or not (before Nov 2015).
        if ($this->isComplexSBCessCase($input) === true)
        {
            // Gets the total fees and service tax of transactions of the merchants
            // before 15th november and after 15th november.
            $dataBefore15Nov = $this->repo->transaction->fetchDataForInvoice(
                $merchantId,
                $from,
                self::SWACH_BHARAT_CUTOFF_TIMESTAMP);

            $dataAfter15Nov  = $this->repo->transaction->fetchDataForInvoice(
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
            $data = $this->repo->transaction->fetchDataForInvoice($merchantId, $from, $to);

            $sbCessApplied = $this->isCessApplicable($input, $this->SBCessMonth);

            $kkCessApplied = $this->isCessApplicable($input, $this->KKCessMonth);

            $this->addTaxComponents($data, $sbCessApplied, $kkCessApplied);
        }

        return $data;
    }

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
}