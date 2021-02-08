<?php

namespace RZP\Models\UpiMandate;

use Carbon\Carbon;

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
     * For details, refer https://docs.google.com/document/d/1CVPl4tY7qsnlS6K7l0GM-jYTY4DmKHzR8ZBLdPdfLW8
     *
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

            //Currently Sequence Number Algorithm is implemented ONLY for frequencies Daily and Monthly.
            //For others : Current Implementation always returns diff as 0 and sequence number as One
            default:
                return 0;
        }
    }

    /************************* Diff functions for different frequencies ***********************************/

    protected function daily(): int
    {
        return ($this->toDate)->diffInDays($this->fromDate);
    }

    /*Carbon takes absolute day to day cycle difference for a month.
                        startDate:       CurrDate        Carbon::diffInMonths     Expected
         Happy case :  5-2-2019          6-3-2019            1                      1
         Edge case :   5-2-2019          4-3-2019            0                      1
    */
    protected function monthly(): int
    {
        if (($this->fromDate->day) > ($this->toDate->day))
        {
            return ($this->toDate)->diffInMonths($this->fromDate) + 1;
        }

        return ($this->toDate)->diffInMonths($this->fromDate);
    }

    private function validateInput(): bool
    {
        return (Frequency::isValid($this->frequency)) and
               (($this->fromDate)->lessThanOrEqualTo($this->toDate));
    }
}
