<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant;
use RZP\Models\Batch\Entity;
use RZP\Models\Merchant\Entity as ME;
use RZP\Models\Partner\SubMerchantBatchUtility as SubMerchantUtil;

class SubMerchant extends Base
{
    /**
     * @var Merchant\Entity
     */
    protected $partner;

    /**
     *  This variables contain all the config passed to the batch
     */
    protected $settings = [];

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);
    }

    protected function processEntry(array & $entry)
    {
        (new SubMerchantUtil())->processSubMerchantEntry($entry, $this->settings);
    }

    protected function performPreProcessingActions()
    {
        $config = $this->settingsAccessor->all()->toArray();

        $this->settings = array_merge($this->params, $config);

        $this->settings[ME::AUTO_ENABLE_INTERNATIONAL] = (bool) ($this->settings[ME::AUTO_ENABLE_INTERNATIONAL] ?? false);

        $this->settings[ME::SKIP_BA_REGISTRATION]      = (bool) ($this->settings[ME::SKIP_BA_REGISTRATION] ?? true);

        $this->partner = $this->repo->merchant->findOrFailPublic($this->settings[ME::PARTNER_ID]);

        $this->updateAuthDetails($this->partner);

        return parent::performPreProcessingActions();
    }

    /**
     * updates merchant information into auth,
     * this is being used to set org id and merchant info
     *
     * @param ME    $merchant
     * @param array $config
     */
    private function updateAuthDetails(ME $merchant)
    {
        $this->app['basicauth']->setMerchant($merchant);

        $this->app['basicauth']->setBatchContext($this->getBatchContext($this->settings));
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }

    protected function resetErrorOnSuccess(): bool
    {
        return false;
    }
}
