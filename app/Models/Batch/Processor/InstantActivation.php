<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\TLDExtract;

class InstantActivation extends Base
{
    protected $TLDExtract;

    protected $merchantCore;


    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->TLDExtract = new TLDExtract();

        $this->merchantCore = new Merchant\Core();
    }

    //protected function processEntry(array & $entry)
    //{
    //    if (empty($entry[Merchant\Entity::MERCHANT_ID]) === false)
    //    {
    //        $this->repo->transactionOnLiveAndTest(function() use (& $entry) {
    //
    //            $merchantDetails = $this->repo->merchant_detail->getByMerchantId(trim($entry[Merchant\Entity::MERCHANT_ID]));
    //
    //            $merchant = $this->repo->merchant->findOrFail(trim($entry[Merchant\Entity::MERCHANT_ID]));
    //
    //            $this->updateWhitelistedDomains($merchantDetails, $merchant);
    //
    //            $this->repo->merchant->saveOrFail($merchant);
    //        });
    //
    //        $entry[Header::STATUS] = Status::SUCCESS;
    //    }
    //}

    protected function processEntry(array & $entry)
    {
        if (empty($entry[Merchant\Entity::MERCHANT_ID]) === false)
        {
            $this->repo->transactionOnLiveAndTest(function() use (& $entry) {

                $merchant = $this->repo->merchant->findOrFail(trim($entry[Merchant\Entity::MERCHANT_ID]));

                $merchant->setProductInternational('1111000000');

                $this->repo->saveOrFail($merchant);
            });

            $entry[Header::STATUS] = Status::SUCCESS;
        }
    }

    protected function updateWhitelistedDomains($merchantDetails, $merchant)
    {
        $businessWebsite = $merchantDetails->getWebsite() ?? '';

        $additionalWebsites = $merchantDetails->getAdditionalWebsites() ?? [];

        $merchant->setWhitelistedDomains([]);

        $websites = array_merge([$businessWebsite],$additionalWebsites);

        foreach ($websites as $website)
        {
            $domain = $this->TLDExtract->getEffectiveTLDPlusOne($website);

            $this->merchantCore->addDomainInWhitelistedDomain($merchant, $domain);
        }
    }
}
