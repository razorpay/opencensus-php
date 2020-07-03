<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Admin;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;

class SFMerchantPocUpdate extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'poc_update';

    protected $claimedMerchantsGroupId;

    protected $smeGroupId;

    protected $unClaimedGroupId;

    protected $value;

    protected $currentAdminIds;

    protected $smeAdminIds = [];

    public $timeout = 4000;


    public function __construct(string $mode, array $value, array $currentAdminIds)
    {
        parent::__construct($mode);

        RuntimeManager::setMemoryLimit('2048M');

        $this->value = $value;

        $this->currentAdminIds = $currentAdminIds;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $group = (new Group\Entity())->getSalesForceGroupId();

            $this->claimedMerchantsGroupId = $group[Group\Constant::SALESFORCE_CLAIMED_MERCHANTS_GROUP_ID];

            $this->smeGroupId = $group[Group\Constant::SALESFORCE_CLAIMED_SME_GROUP_ID];

            $this->unClaimedGroupId = $group[Group\Constant::SALESFORCE_UNCLAIMED_GROUP_ID];

            $merchantId = $this->value['Merchant_ID__c'];

            $this->trace->info(TraceCode::MERCHANT_POC_UPDATE_REQUEST,
                               [
                                   'Merchant POC update request',
                                   'merchantId' => $merchantId,
                               ]
            );

            $merchant = $this->repoManager->merchant->findOrFailPublic($merchantId);

            // linked accounts assuming that merchant's is already marketplace account
            // either in test Mode or Live Mode

            // Skipping Merchants associated account who has more than 2000 associated accounts this is temporary
            $noOfMerchantIds = $this->repoManager->merchant->fetchLinkedAccountsCount($merchantId);

            if ($noOfMerchantIds > 2000)
            {
                $this->trace->info(TraceCode::SF_POC_MERCHANT_LINKED_SKIPPED,
                                   [
                                       'message'    => 'Merchant POC Linked account skipped for Below merchants inside SFMerchantPocUpdate job',
                                       'merchantId' => $merchantId
                                   ]
                );

                $associatedAccounts = [];
            }
            else
            {
                $associatedAccounts = $this->repoManager->merchant->fetchLinkedAccountMids($merchantId);
            }

            $merchantAndAssociatedAccounts =  array_unique(array_merge([$merchantId], $associatedAccounts));

            $this->linkedAccountAdminRemoval($merchantAndAssociatedAccounts);

            $tagNames = strtolower($this->value['Owner_Role__c']);

            if (strpos($tagNames, 'sme') !== false)
            {
                $smeAdminIds = array_merge($this->smeAdminIds, $this->currentAdminIds);

                foreach ($merchantAndAssociatedAccounts as $merchantId)
                {
                    $this->updateSmeMerchants($merchantId);
                }

                $this->updateGroupToAdmins($this->smeGroupId, $this->unClaimedGroupId, array_unique($smeAdminIds));
            }
            else
            {
                foreach ($merchantAndAssociatedAccounts as $merchantId)
                {
                    $this->updateNonSmeMerchants($merchantId, $this->currentAdminIds);
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SF_POC_UPDATE_ERROR,
                [
                    'mode' => $this->mode,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::SF_POC_UPDATE_QUEUE_DELETE, [
                'id'           => $this->value['Merchant_ID__c'],
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

    /**
     * @param array $merchantAndAssociatedAccounts
     *
     * @throws \Throwable
     */
    protected function linkedAccountAdminRemoval(array $merchantAndAssociatedAccounts)
    {
        foreach ($merchantAndAssociatedAccounts as $merchantId)
        {
            $merchant = $this->repoManager->merchant->findOrFailPublic($merchantId);

            $historicalAdminIds = $merchant->admins->getIds();

            if (count($historicalAdminIds) > 0)
            {
                (new Admin\Core)->removeMerchantFromAdmins($merchant, $historicalAdminIds);
            }
        }
    }

    /**
     * @param string $merchantId
     *
     * @return mixed
     * @throws \Throwable
     */
    protected function removeFromUnclaimedAndAddToClaimedGroup(string $merchantId)
    {
        $merchant = $this->repoManager->merchant->findOrFailPublic($merchantId);

        (new Group\Core)->removeMerchantFromGroups($merchant, [$this->unClaimedGroupId]);

        (new Group\Core)->addMerchantToGroups($merchant, [$this->claimedMerchantsGroupId]);

        return $merchant;
    }

    /**
     * @param string $smeGroupId
     * @param string $unclaimedGroupId
     * @param array  $smeAdmins
     */
    public function updateGroupToAdmins(string $smeGroupId, string $unclaimedGroupId, array $smeAdmins)
    {
        $smeGroup = $this->repoManager->group->findOrFailPublic($smeGroupId);

        $unclaimedGroup = $this->repoManager->group->findOrFailPublic($unclaimedGroupId);

        (new Admin\Core)->assigningGroupToAdmins($smeGroup, $smeAdmins);

        (new Admin\Core)->assigningGroupToAdmins($unclaimedGroup, $smeAdmins);
    }

    /**
     * @param string $merchantId
     *
     * @throws \Throwable
     */
    public function updateSmeMerchants(string $merchantId)
    {
        $merchant = $this->removeFromUnclaimedAndAddToClaimedGroup($merchantId);

        (new Group\Core)->addMerchantToGroups($merchant, [$this->smeGroupId]);;
    }

    /**
     * @param string $merchantId
     * @param array  $adminIds
     *
     * @throws \Throwable
     */
    public function updateNonSmeMerchants(string $merchantId, array $adminIds)
    {
        $merchant = $this->removeFromUnclaimedAndAddToClaimedGroup($merchantId);

        (new Admin\Core)->assignMerchantToAdmins($merchant, $adminIds);
    }
}
