<?php

namespace RZP\Models\Merchant\FreeCredits;

use RZP\Models\Base;
use RZP\Models\Merchant\FreeCredits\Entity;
use RZP\Exception;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use Base\RepositoryFetch;

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

    public function getCampaignsMerchantParticipated($merchantId)
    {
        $campaigns = array();
        $logs = Entity::where(Entity::CAMPAIGN, '=', $merchantId)
            ->orderBy(Entity::CREATED_AT)
            ->get();

        foreach($logs as $creditLog)
        {
            array_push($campaigns, $creditLog->campaign);
        }
        return $campaigns;
    }

    /**
     * Checks if a record exists by ID in free_credits table
     *
     * @return bool
     */
    public function recordExists($id)
    {
        return Entity::where(Entity::ID, '=', $id)->exists();
    }

}
