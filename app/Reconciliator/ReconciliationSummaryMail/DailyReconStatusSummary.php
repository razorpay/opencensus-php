<?php

namespace RZP\Reconciliator\ReconciliationSummaryMail;

use Mail;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Timezone;
use RZP\Mail\Reconciliation\DailyReconStatusSummary as ReconSummaryMail;

class DailyReconStatusSummary extends Base\Core
{
    // Start date of summary
    protected $from;

    // End date of summary
    protected $to;

    // Count of entities to send in email
    protected $limit;

    // List of popular gateways
    const GATEWAYS = [
        'hdfc',
        'axis_migs',
        'cybersource',
        'kotak',
        'hitachi',
        'billdesk',
        'first_data',
        'upi_icici',
        'netbanking_icici',
        'netbanking_rbl',
        'netbanking_bob'
    ];

    const PARAMS = [
        'total_count',
        'total_amount',
        'recon_count',
        'unrecon_count',
        'recon_amount',
        'unrecon_amount'
    ];

    const ENTITIES = [
        'Refund',
        'Payment'
    ];

    /**
     *  Default time duration is 4 days.
     */
    const DURATION = 4;

    public function __construct()
    {
        parent::__construct();

        $this->from = Carbon::today(Timezone::IST)->subDays(self::DURATION)->getTimestamp();

        $this->to =  Carbon::today(Timezone::IST)->getTimestamp();
    }

    public function generateReconSummary(array $input)
    {
        $summary = $this->getFormattedSummary();

        $reconSummaryMail = new ReconSummarymail(self::GATEWAYS, self::PARAMS, $summary);

        Mail::queue($reconSummaryMail);

        return ['success' => true];
    }

    protected function getFormattedSummary()
    {
        $data = [];

        foreach (self::ENTITIES as $entity)
        {
            $entityClass = $this->getClassName($entity);

            $data[$entity] = (new $entityClass)->getReconStatusSummary();
        }

        return $data;
    }

    protected function getClassName(string $entity): string
    {
        return __NAMESPACE__ . '\\' . $entity . 'ReconStatusSummary';
    }

    protected function getFormattedDate($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('jS F, Y');
    }
}