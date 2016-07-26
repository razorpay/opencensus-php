<?php

namespace RZP\Models\Merchant;

use RZP\Models\Base;
use RZP\Models\Merchant\FreeCredits;
use RZP\Exception;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use Base\RepositoryFetch;

    protected $entity = 'FreeCreditsLog';


    public function getFreeCreditsLogsOfCampaign($campaign)
    {
    }

    public function getFreeCreditsGrantedInCampaign($campaign)
    {
    }

    public function getCampaignsMerchantParticipated($merchant)
    {
    }

}
