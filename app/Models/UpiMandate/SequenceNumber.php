<?php

namespace RZP\Models\UpiMandate;

use Carbon\Carbon;

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
     * SequenceNumber constructor.
     * @param $fromDate
     * @param $toDate
     */
    public function __construct($fromDate, $toDate)
    {
        $this->fromDate = $this->formatDate($fromDate);

        $this->toDate =  $this->formatDate($toDate);
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

    protected function bimonthly(): int
    {
        $diff = $this->monthly();

        return floor($diff/2);
    }

    protected function quarterly(): int
    {
        $diff = $this->monthly();

        return floor($diff/3);
    }


    protected function halfYearly(): int
    {
        $diff = $this->monthly();

        return floor($diff/6);
    }

    protected function yearly(): int
    {
        $diff = $this->monthly();

        return floor($diff/12);
    }

    private function validateInput(): bool
    {
        return (Frequency::isValid($this->frequency)) and
               (($this->fromDate)->lessThanOrEqualTo($this->toDate));
    }
}
