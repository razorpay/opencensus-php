<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;

use RZP\Base\JitValidator;
use RZP\Models\Transaction;
use RZP\Models\Pricing\Feature;
use RZP\Models\Pricing\FeeCalculator;
use RZP\Models\Transaction\FeeBreakup\Name as FeeName;

class InvoiceReport extends BaseReport
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
    const IGST = 'IGST';
    const CGST = 'CGST';
    const SGST = 'SGST';

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
        'month'  => '11',
        'year'   => '2015'
    ];

    const KK_COMPLEX_CASE = [
        'month'  => '6',
        'year'   => '2016'
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


        if ($this->isGstApplicable($from) === true)
        {
            $taxInfo = $this->getGstTaxes($fees);
        }
        else
        {
            $taxInfo = $this->getNonGstTaxes($fees);
        }

        $totalTax = $taxInfo['total_tax'];
        $taxes = $taxInfo['taxes'];

        return [
            self::TOTAL_FEE    => $totalRzpFee + $totalTax,
            self::RAZORPAY_FEE => $totalRzpFee,
            self::TAX          => $totalTax,
            self::TAXES        => $taxes,
        ];
    }

    /**
     * @return Array $arr
     * @return Array $arr['taxes']      List of tax componensts with respective values
     * @return Float $arr['total_tax']  Sum of all tax components
     */
    protected function getNonGstTaxes(array $f): array
    {
        $serviceTax = empty($f[FeeName::SERVICE_TAX]) ? 0 : intval($f[FeeName::SERVICE_TAX]['sum']);

        $swachBharatCess = empty($f[FeeName::SWACHH_BHARAT_CESS]) ? 0 : intval($f[FeeName::SWACHH_BHARAT_CESS]['sum']);

        $krishiKalyanCess = empty($f[FeeName::KRISHI_KALYAN_CESS]) ? 0 : intval($f[FeeName::KRISHI_KALYAN_CESS]['sum']);

        $nonGstTaxes = $serviceTax + $swachBharatCess + $krishiKalyanCess;

        $taxes = [];

        if ($nonGstTaxes > 0)
        {
            $taxes = [
                self::SERVICE_TAX        => $serviceTax,
                self::SWACH_BHARAT_CESS  => $swachBharatCess,
                self::KRISHI_KALYAN_CESS => $krishiKalyanCess,
            ];
        }

        return ['taxes' => $taxes, 'total_tax' => $nonGstTaxes];
    }

    /**
     * @return Array $arr
     * @return Array $arr['taxes']      List of tax componensts with respective values
     * @return Float $arr['total_tax']  Sum of all tax components
     */
    protected function getGstTaxes(array $fees): array
    {
        $igst = empty($fees[FeeName::IGST]) ? 0 : intval($fees[FeeName::IGST]['sum']);
        $cgst = empty($fees[FeeName::CGST]) ? 0 : intval($fees[FeeName::CGST]['sum']);
        $sgst = empty($fees[FeeName::SGST]) ? 0 : intval($fees[FeeName::SGST]['sum']);

        if (($cgst > 0) or ($sgst > 0))
        {
            $totalTax = $cgst + $sgst;

            $taxes = [
                self::CGST => $cgst,
                self::SGST => $sgst,
            ];
        }
        else
        {
            $totalTax = $igst;

            $taxes = [self::IGST => $igst];
        }

        return ['taxes' => $taxes, 'total_tax' => $totalTax];
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

    protected function isGstApplicable($fromTimestamp): bool
    {
        return ($fromTimestamp >= FeeCalculator::GST_TIMESTAMP);
    }

    protected function sumInvoiceData($beforeSBCCutoff, $afterSBCCutoff)
    {
        return [
            self::TOTAL_FEE => $beforeSBCCutoff[self::TOTAL_FEE] + $afterSBCCutoff[self::TOTAL_FEE],
            self::RAZORPAY_FEE => $beforeSBCCutoff[self::RAZORPAY_FEE] + $afterSBCCutoff[self::RAZORPAY_FEE],
            self::TAX => $beforeSBCCutoff[self::TAX] + $afterSBCCutoff[self::TAX],
            self::TAXES => [
                self::SERVICE_TAX   => $beforeSBCCutoff[self::TAXES][self::SERVICE_TAX] +
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
