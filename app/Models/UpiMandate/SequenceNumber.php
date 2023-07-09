<?php

namespace RZP\Models\UpiMandate;

use Carbon\Carbon;
use RZP\Constants\Timezone;

/**
 * Class SequenceNumber
 *
 * This class contains logic to calculate sequence number between any two dates for a given frequency.
 * It is currently used to calculate cycle number for recurring transactions.
 * For more details and test scenarios, refer https://docs.google.com/document/d/1CVPl4tY7qsnlS6K7l0GM-jYTY4DmKHzR8ZBLdPdfLW8
 */
class SequenceNumber
{
    /**
     * Default value of sequence number.
     * Default value is 1 in correspondence to payment for the first debit
     */
    const DEFAULT_SEQUENCE_NUMBER = 1;

    /**
     * The periodic interval which defines the repetition cycle for a recurring payment.
     */
    protected $frequency;

    /**
     * The start date from which the cycle started. In context of UPI Recurring, the start date is date
     * at which the mandate was confirmed.
     * @var  Carbon
     */
    protected $fromDate;

    /**
     * The date for which sequence number needs to be calculated
     * @var  Carbon
     */
    protected $toDate;

    /**
     * The map to store frequency and cycle count mapping.
     * This is handy while calculating sequence number per year.
     */
    protected $frequencyToCycleCount = [
        Frequency::HALF_YEARLY => 2,
        Frequency::QUARTERLY   => 4,
        Frequency::BIMONTHLY   => 6,
    ];

    /**
     * SequenceNumber constructor.
     * @param $fromDate
     * @param $toDate
     */
    public function __construct($fromDate, $toDate)
    {
        $this->fromDate = $this->formatDate($fromDate);

        $this->toDate =  $this->formatDate($toDate);
    }

    public function isValidCycle($recurType, $recurVal, $frequency)
    {
        $currentDay = Carbon::now(Timezone::IST)->day;
        $endOfCycle = Carbon::now(Timezone::IST)->endOfMonth()->day;

        if($frequency === Frequency::WEEKLY)
        {
            $currentDay = Carbon::now(Timezone::IST)->dayOfWeek;
            if($currentDay == 0)
            {
                $currentDay = 7;
            }
            $endOfCycle = 7;
        }

        $currentTime = Carbon::now()->getTimestamp();

        switch ($recurType)
        {
            case RecurringType::BEFORE:

                $diffInDays = abs($recurVal - $currentDay);
                $nextExecutionTime = Carbon::now(Timezone::IST)->addDays($diffInDays)->endOfDay()->getTimestamp();
                $diffInHours = floor(($nextExecutionTime-$currentTime)/3600);
                return (($currentDay <= $recurVal) and ($diffInHours >= 26));

            case RecurringType::ON:
                $diff = $recurVal-$currentDay;
                if(($recurVal == 1) and ($currentDay === $endOfCycle))
                {
                    $diff = 1;
                }
                $nextExecutionTime = Carbon::now(Timezone::IST)->addDays($diff)->endOfDay()->getTimestamp();
                $diffInHours = floor(($nextExecutionTime-$currentTime)/3600);

                return (($diff==1) and ($diffInHours >=26));

            case RecurringType::AFTER:

                $diffInDays = abs($endOfCycle - $currentDay);
                $nextExecutionDay = Carbon::now(Timezone::IST)->addDays($diffInDays)->endOfDay()->getTimestamp();
                $diffInHours = floor(($nextExecutionDay-$currentTime)/3600);

                return (($currentDay >= $recurVal) and ($diffInHours >= 26));

            default:
                return false;
        }
    }

    public function isValidExecutionDate($frequency)
    {
        $this->frequency = $frequency;

        if ($this->validateInput() === false)
        {
            return false;
        }

        $diff = $this->monthly();
        if($frequency === Frequency::WEEKLY)
        {
            $diff = $this->weekly();
        }
        switch ($frequency)
        {

            case Frequency::WEEKLY:
            case Frequency::MONTHLY:
                return ($diff >=1);

            case Frequency::QUARTERLY:
                return (($diff%3)==0);

            case Frequency::HALF_YEARLY:
                return (($diff%6)==0);

            case Frequency::YEARLY:
                return (($diff%12)==0);

            //For all other freq : Return diff as a false as we are not supporting other frequencies
            default:
                return false;
        }

    }

    /**
     * Calculates sequence number.
     * @param string $frequency frequency of the mandate
     * @return int|null
     */
    public function generate($frequency)
    {
        $this->frequency = $frequency;

        if ($this->validateInput() === false)
        {
            return null;
        }

        $diff = $this->findDifference();

        return $diff + self::DEFAULT_SEQUENCE_NUMBER;
    }

    protected function formatDate($date)
    {
        if ($date instanceof Carbon)
        {
            return $date;
        }
        if ($date === null)
        {
            return Carbon::now();
        }

        return Carbon::createFromTimestamp($date);
    }

    /**
     * Calculates difference between confirmed at and given at dates wrt frequency.
     * @return int
     */
    protected function findDifference(): int
    {
        switch ($this->frequency)
        {
            case Frequency::DAILY:
                return $this->daily();

            case Frequency::MONTHLY:
                return $this->monthly();

            case Frequency::WEEKLY:
                return $this->weekly();

            case Frequency::BIMONTHLY:
                return $this->bimonthly();

            case Frequency::QUARTERLY:
                return $this->quarterly();

            case Frequency::HALF_YEARLY:
                return $this->halfYearly();

            case Frequency::YEARLY:
                return $this->yearly();

             //For all other freq : Return diff as 0 and sequence number as 1
            default:
                return 0;
        }
    }

    /************************* Diff functions for different frequencies ***********************************/

    protected function daily(): int
    {
        return ($this->toDate->endOfDay())->diffInDays($this->fromDate->startOfDay());
    }

    protected function monthly(): int
    {
        return ($this->toDate->endOfMonth())->diffInMonths($this->fromDate->startOfMonth());
    }

    protected function weekly(): int
    {
        return ($this->toDate->endOfWeek())->diffInWeeks($this->fromDate->startOfWeek());
    }

    /**
     * Function to calculate sequence no. for bimonthly frequency
     * There are 6 cycles in an year :
     * Cycle 1 : Jan-Feb
     * Cycle 2 : Mar-Apr
     * Cycle 3 : May-June
     * Cycle 4 : Jul-Aug
     * Cycle 5 : Sep-Oct
     * Cycle 6 : Nov-Dec
     *
     * @return int
     */
    protected function bimonthly(): int
    {
        $seqNumber = 0;

        //sequence number calculation for the start and end months.
        $endDateMonth = $this->toDate->month;
        $startDateMonth = $this->fromDate->month;
        $cycleCount = $this->getFrequencyToCycleCount();

        $seqNumber += ($cycleCount - (int) ceil($startDateMonth/2));
        $seqNumber += (int) ceil($endDateMonth/2);

        /* sequence number calculation for years in between the start and end dates.
        There are 6 cycles in bimonthly-calculated year. Since 6 cycles implies 6 sequence numbers per year,
        we multiply the year diff by 6.
        */
        $seqDiffInYears = $this->getSeqNumberForYearDifference($cycleCount);

        $seqNumber += $seqDiffInYears;

        return $seqNumber;
    }

    /**
     * Function to calculate sequence no. for quarterly frequency
     * There are 4 cycles in an year :
     * Cycle 1 : Jan-Mar
     * Cycle 2 : Apr-Jun
     * Cycle 3 : Jul-Sept
     * Cycle 4 : Oct-Dec
     *
     * @return int
     */
    protected function quarterly(): int
    {
        $seqNumber = 0;

        //sequence number calculation for the start and end months.
        $endDateMonth = $this->toDate->month;
        $startDateMonth = $this->fromDate->month;
        $cycleCount = $this->getFrequencyToCycleCount();

        $seqNumber += ($cycleCount - (int) ceil($startDateMonth/3));
        $seqNumber += (int) ceil($endDateMonth/3);

        /* sequence number calculation for years in between the start and end dates.
        There are 4 cycles in quarterly-calculated year. Since 4 cycles implies 4 sequence numbers per year,
        we multiply the year diff by 4.
        */
        $seqDiffInYears = $this->getSeqNumberForYearDifference($cycleCount);

        $seqNumber += $seqDiffInYears;

        return $seqNumber;
    }

    /**
     * Function to calculate sequence no. for halfYearly frequency
     * There are 2 cycles in an year :
     * Cycle 1 : Jan-Jun
     * Cycle 2 : Jul-Dec
     *
     * @return int
     */
    protected function halfYearly(): int
    {
        $seqNumber = 0;

        //sequence number calculation for the start and end months.
        $endDateMonth = $this->toDate->month;
        $startDateMonth = $this->fromDate->month;
        $cycleCount = $this->getFrequencyToCycleCount();

        $startDateMonth <= 6 ? $seqNumber += 2 : $seqNumber += 1;
        $seqNumber += (int) ceil($endDateMonth/6);

        /* sequence number calculation for years in between the start and end dates.
        There are 2 cycles in bimonthly-calculated year. Since 2 cycles implies 2 sequence numbers per year,
        we multiply the year diff by 2.
        */
        $seqDiffInYears = $this->getSeqNumberForYearDifference($cycleCount);

        $seqNumber += $seqDiffInYears;

        return $seqNumber - 1;
    }

    /**
     * Function to calculate sequence no. for yearly frequency
     *
     * @return int
     */
    protected function yearly(): int
    {
        return ($this->toDate->endOfYear())->diffInYears($this->fromDate->startOfYear());
    }

    private function validateInput(): bool
    {
        return (Frequency::isValid($this->frequency)) and
               (($this->fromDate)->lessThanOrEqualTo($this->toDate));
    }

    /**
     * Returns the sequence number based on year difference and number of cycles per year
     * numberOfCycles is ->
     * 2 for half yearly : 2 cycles of 6 months in a year
     * 4 for quarterly : 4 cycles of 3 months in a year
     * 6 for bimonthly : 2 cycles of 5
     *
     * @param int $numberOfCycles
     *
     * @return float|int
     */
    private function getSeqNumberForYearDifference(int $numberOfCycles)
    {
        $endYear   = $this->toDate->endOfYear()->year;
        $startYear = $this->fromDate->startOfYear()->year;

        $diffInYears    = $endYear - $startYear - 1;

        return $diffInYears * $numberOfCycles;
    }

    /**
     * This function returns the cycle count for the selected frequency.
     * @return int
     */
    private function getFrequencyToCycleCount() : int
    {
        return $this->frequencyToCycleCount[$this->frequency];
    }
}
