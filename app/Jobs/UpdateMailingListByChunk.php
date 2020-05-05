<?php

namespace RZP\Jobs;

use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Jobs\MailingListUpdate;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Trace\Facades\Trace as TraceFacade;

class UpdateMailingListByChunk extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    public $timeout = 600;

    /**
     * @var array
     */
    protected $merchantChunk;

    protected $queueConfigKey = 'mailing_list_update';

    public function __construct(string $mode, array $merchantChunk)
    {
        parent::__construct($mode);

        $this->merchantChunk = $merchantChunk;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        try
        {    
            $merchantRepo = new Merchant\Repository;

            $featureRepo = new Feature\Repository;

            $onDemandEnabledMerchants = $featureRepo->getMerchantIdsHavingFeature(Feature\Constants::ES_ON_DEMAND, $this->merchantChunk);

            $onDemandNotEnabledMerchants = array_diff($this->merchantChunk, $onDemandEnabledMerchants);

            $this->trace->info(TraceCode::UPDATE_MAILING_LIST_CHUNK_INITIATED, [
                'on_demand_merchants' => count($onDemandEnabledMerchants),
                'default_merchants'   => count($onDemandNotEnabledMerchants)
            ]);

            $liveOnDemandMerchantContact = $merchantRepo->fetchLiveMerchantContacts($onDemandEnabledMerchants)->get()->toArray();

            $liveMerchantContacts = $merchantRepo->fetchLiveMerchantContacts($onDemandNotEnabledMerchants)->get()->toArray();

            $encodedOnDemandListMembers = [];

            $encodedListMembers = [];

            foreach ($liveOnDemandMerchantContact as $merchant) 
            {
                $this->encodeMerchantDetails($merchant, $encodedOnDemandListMembers);
            }

            foreach ($liveMerchantContacts as $merchant) 
            {
                $this->encodeMerchantDetails($merchant, $encodedListMembers);
            }

            $encodedOnDemandListMembers = array_map('json_decode', array_unique($encodedOnDemandListMembers));
           
            $encodedListMembers = array_map('json_decode', array_unique($encodedListMembers));

            $encodedOnDemandListMembersChunk = array_chunk($encodedOnDemandListMembers, 1000);

            $encodedListMembersChunk = array_chunk($encodedListMembers, 1000);

            $iterationNumber = 0;

            foreach($encodedOnDemandListMembersChunk as $encodedOnDemandListMember)
            {
                MailingListUpdate::dispatch(
                    $this->mode,
                    array_values($encodedOnDemandListMember),
                    true,
                    Merchant\Constants::LIVE_SETTLEMENT_ON_DEMAND)
                ->delay($iterationNumber % 901);

                $iterationNumber++;
            }

            $iterationNumber = 0;

            foreach($encodedListMembersChunk as $encodedListMember)
            {
                MailingListUpdate::dispatch(
                    $this->mode,
                    array_values($encodedListMember),
                    true,
                    Merchant\Constants::LIVE_SETTLEMENT_DEFAULT)
                ->delay($iterationNumber % 901);

                $iterationNumber++;
            }
        }
        catch (\Throwable $e)
        {
            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(1);
            }
            else
            {
                $this->delete();
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAILING_LIST_CHUNK_UPDATE_ERROR);
        }
    }

    protected function encodeMerchantDetails($merchant, & $response)
    {
        $response[] = json_encode([
                'address' => $merchant['email'],
                'name'    => $merchant['name']
            ]);

        // Attaching the Transaction Report Emails
        if (isset($merchant['transaction_report_email']))
        {
            foreach ($merchant['transaction_report_email'] as $email)
            {
                $response[] = json_encode([
                        'address' => $email,
                        'name'    => $merchant['name']
                    ]);
            }
        }
    }

}
