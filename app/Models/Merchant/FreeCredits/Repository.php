<?php

namespace RZP\Models\Merchant\FreeCredits;

use RZP\Models\Base;
use RZP\Models\Merchant\FreeCredits;
use RZP\Exception;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use Base\RepositoryFetch;

    protected $entity = 'free_credits';

    public function getFreeCreditsLogsOfCampaign($campaign)
    {
        return Entity::where(Entity::CAMPAIGN, '=', $campaign)->get();
    }

    public function getFreeCreditsGrantedInCampaign($campaign)
    {
        $total_free_credits = 0;
        $logs = $this->getFreeCreditsLogsOfCampaign($campaign);
        foreach($logs as $creditLog)
        {
            $total_free_credits += $creditLog->credits;
        }
        return $total_free_credits;
    }

    public function getFreeCreditsLogsOfMerchant($merchantId)
    {
        return Entity::where(Entity::MERCHANT_ID, '=', $merchantId)
            ->orderBy(Entity::CREATED_AT)
            ->get();
    }

    public function getCampaignsMerchantParticipated($merchantId)
    {
        $campaigns = array();
        $logs = $this->getFreeCreditsLogsOfMerchant($merchantId);

        foreach($logs as $creditLog)
        {
            array_push($campaigns, $creditLog->campaign);
        }
        return $campaigns;
    }

    /**
     * Checks if a record exists by Merchant ID and Campaign Name
     * in free_credits table
     *
     * @return bool
     */
    public function recordExists($merchantId, $campaign)
    {
        $recordExists = Entity::where(Entity::CAMPAIGN, '=', $campaign)
            ->where(Entity::MERCHANT_ID, '=',  $merchantId)
            ->exists();
        return $recordExists;
    }

}
