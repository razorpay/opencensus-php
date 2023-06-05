<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use Illuminate\Support\Str;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Feature\Service as FeatureService;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Workflow\Constants as WorkflowConstants;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use RZP\Models\Merchant\Cron\Collectors\Core\DbDataCollector;

class FOHRemovalDataCollector extends DbDataCollector
{
    const DEFAULT_RISK_FOH_TEAM_EMAIL_IDS = array("adrian.browne@razorpay.com",
                                                  "arun.chandrasekaran@razorpay.com",
                                                  "chandrasekhar.thorati@razorpay.com",
                                                  "harshavardhana.rao@razorpay.com",
                                                  "jeya.m@razorpay.com",
                                                  "jyothi.sharma@razorpay.com",
                                                  "lapekshi.t@razorpay.com",
                                                  "manu.k@razorpay.com",
                                                  "mukesh.singh@razorpay.com",
                                                  "naveen.kk@razorpay.com",
                                                  "nikita.dsouza@razorpay.com",
                                                  "om.prakash@razorpay.com",
                                                  "priyadeep.dey@razorpay.com",
                                                  "pushkar.deshpande@razorpay.com",
                                                  "rahib.syed@razorpay.com",
                                                  "raja.gounder@razorpay.com",
                                                  "richa.choubey@razorpay.com",
                                                  "saravana.vuyyala@razorpay.com",
                                                  "shadrach.reddy@razorpay.com",
                                                  "shreya.das@razorpay.com",
                                                  "shwetha.p@razorpay.com",
                                                  "somesh.balani@razorpay.com",
                                                  "tanya.singh@razorpay.com",
                                                  "vinodh.k@razorpay.com",
                                                  "viral.turakhia@razorpay.com",
                                                  "yashwanth.v@razorpay.com");

    const RISK_LEA_TAGS = array("RISK_LEA_DEBIT-FREEZE_",
                                "RISK_LEA_FREEZING-ORDER_",
                                "RISK_LEA_PROVISIONAL-ATTACHMENT-ORDER_",
                                "RISK_LEA_CONFISICATED-FUNDS_",
                                "RISK_CHINESE_BLOCKED",
                                "CHINESE_MERCHANT",
                                "POWERBANK_FRAUD",
                                "BR_REFUND_RECOVERY",
                                "RISK_BOAT_CHECK");

    const CAPITAL_PRODUCTS_FEATURE_FLAGS = array(FeatureConstants::WITHDRAW_LOC,
                                                 FeatureConstants::WITHDRAWAL_ES_AMAZON,
                                                 FeatureConstants::CAPITAL_CARDS,
                                                 FeatureConstants::ES_ON_DEMAND_RESTRICTED,
                                                 FeatureConstants::ES_ON_DEMAND,
                                                 FeatureConstants::ONDEMAND_LINKED,
                                                 FeatureConstants::ONDEMAND_ROUTE,
                                                 FeatureConstants::CASH_ON_CARD,
                                                 FeatureConstants::AUTOMATED_LOC_ELIGIBLE,
                                                 FeatureConstants::ES_AUTOMATIC,
                                                 FeatureConstants::ES_AUTOMATIC_RESTRICTED);

    const WORKFLOW_CREATED_PAST_DAYS = 365;

    public function collectDataFromSource(): CollectorDto
    {
        // TODO: Implement collectDataFromSource() method.
        $this->app["trace"]->info(TraceCode::SELF_SERVE_CRON_FOH_REMOVAL);

        // email Ids from config
        $emailIds = $this->getRiskFohTeamEmailIds();

        //adminIds for above emails
        $adminIdList = [];

        foreach ($emailIds as $emailId)
        {
            $admin = $this->repo->admin->findByOrgIdAndEmail(Org\Entity::RAZORPAY_ORG_ID, $emailId);

            $adminIdList[] = $admin->getId();
        }


        $this->app["trace"]->info(TraceCode::FOH_REMOVAL_ADMIN_LIST, [
            'type'          => Constants::FOH_REMOVAL,
            'admin_ids'     => $adminIdList
        ]);

        //workflow action entityIds

        $workflowEntityIds = $this->getWorkflowEntityIds($adminIdList);

        $this->app["trace"]->info(TraceCode::FOH_REMOVAL_WORKFLOW_ENTITY_LIST, [
            'type'                        => Constants::FOH_REMOVAL,
            'entity_ids_count'            => ($workflowEntityIds === null) ? 0 : count($workflowEntityIds),
            'workflow_action_entity_ids'  => $workflowEntityIds,

        ]);

        $merchantIdChunks = array_chunk($workflowEntityIds, 20);

        //merchants on hold
        $onHoldMerchants = [];

        foreach ($merchantIdChunks as $merchantIdList)
        {
            $merchantIdList = $this->filterOnHoldMerchants($merchantIdList);

            $onHoldMerchants = array_merge($onHoldMerchants, $merchantIdList);

        }

        $this->app["trace"]->info(TraceCode::FOH_REMOVAL_ON_HOLD_MERCHANTS, [
            'on_hold_merchants'     => $onHoldMerchants,
        ]);

        $lastAuthorizedPaymentMerchants = $this->checkMerchantLastTransactionTimestamp($onHoldMerchants);

        $this->app["trace"]->info(TraceCode::FOH_REMOVAL_LAST_TRANSACTED_MERCHANTS, [
            'last_authorized_payment_merchants'     => $lastAuthorizedPaymentMerchants
        ]);

        // This function filters out the merchants who are non dao, lea tag merchants
        // capital product merchants

        $merchantIdsNotApplicableForFOH = $this->getMerchantsNotApplicableForFOH($lastAuthorizedPaymentMerchants);

        $negativeBalanceMerchants = $this->filterNegativePrimaryBalanceMerchants($merchantIdsNotApplicableForFOH);

        $finalMerchantIdList = $negativeBalanceMerchants;

        $this->app["trace"]->info(TraceCode::FOH_REMOVAL_DATA_COLLECTOR, [
            'final_merchant_id_list'    => $finalMerchantIdList
        ]);

        if (empty($finalMerchantIdList) === true) {

            $this->app['trace']->info(TraceCode::SELF_SERVE_CRON_FOH_REMOVAL, [
                'type'      => Constants::FOH_REMOVAL,
                'reason'    => 'no merchants to run the cron'
            ]);

            return CollectorDto::create([]);
        }

        $data[CronConstants::MERCHANT_IDS] = $finalMerchantIdList;

        return CollectorDto::create($data);

    }

    public function getMerchantsNotApplicableForFOH(array $lastAuthorizedPaymentMerchants)
    {
        $nonDaoMerchants = $this->getNonDaoLostAndWonDisputeMerchants($lastAuthorizedPaymentMerchants);

        $this->app["trace"]->info(TraceCode::FOH_REMOVAL_NON_DAO_MERCHANTS, [
            'non_dao_dispute_merchants'    => $nonDaoMerchants
        ]);

        $leaTagMerchants = $this->getMerchantTagsForDispute($nonDaoMerchants);

        $this->app["trace"]->info(TraceCode::FOH_REMOVAL_LEA_TAG_MERCHANTS, [
            'lea_document_tag_merchants'    => $leaTagMerchants
        ]);

        $capitalProductsMerchants = $this->checkFeatureFlagsForCapitalProducts($leaTagMerchants,self::CAPITAL_PRODUCTS_FEATURE_FLAGS);

        $this->app["trace"]->info(TraceCode::FOH_REMOVAL_CAPITAL_PRODUCTS_MERCHANTS, [
            'capital_products_merchants'    => $capitalProductsMerchants
        ]);

        $merchantIdList = $capitalProductsMerchants;

        return $merchantIdList;
    }

    protected function getRiskFohTeamEmailIds(): array
    {
        $emailIds = (array) (new AdminService)->getConfigKey(['key' => ConfigKey::RISK_FOH_TEAM_EMAIL_IDS]);

        if (empty($emailIds) === true)
        {
            $emailIds = self::DEFAULT_RISK_FOH_TEAM_EMAIL_IDS ;
        }

        return $emailIds;
    }

    private function getWorkflowIds() : array
    {
        return [
            env(WorkflowConstants::WORKFLOW_TOGGLE_SUSPEND),
            env(WorkflowConstants::WORKFLOW_TOGGLE_FUNDS),
            env(WorkflowConstants::WORKFLOW_TOGGLE_MERCHANT_LIVE),
            env(WorkflowConstants::WORKFLOW_MERCHANT_RISK_ALERT_FUNDS_ON_HOLD),
        ];
    }

    private function getPermissionIds() : array
    {
        return [
            $this->app['config']->get('app.permission_id_edit_merchant_hold_funds'),
            $this->app['config']->get('app.permission_id_edit_merchant_suspend'),
            $this->app['config']->get('app.permission_id_merchant_risk_alert_foh'),
            $this->app['config']->get('app.permission_id_edit_merchant_disable_live')
        ];
    }

    private function getWorkflowEntityIds(array $adminIdList) : array
    {
        $batch = 100;

        $skip = 0;

        $merchantIdList = [];

        do
        {
            $merchantIdsToEnqueue = $this->repo->workflow_action->fetchWorkFlowActionForWorkFlows(
                $batch,
                $skip,
                $adminIdList,
                self::getPermissionIds(),
                self::getWorkflowIds(),
                time(),
                Carbon::parse($this->lastCronTime)->subDays(self::WORKFLOW_CREATED_PAST_DAYS)->getTimestamp()
            );

            $count = count($merchantIdsToEnqueue);

            $skip += $count;

            $merchantIdList = array_unique(array_merge($merchantIdList, $merchantIdsToEnqueue));

        } while($batch === $count);

        return $merchantIdList;
    }

    Private function  filterOnHoldMerchants(array $merchantIds) : array
    {
        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIds);

        $includeIds  = [];

        foreach ($merchants as $merchant)
        {
            if ($merchant->getholdFunds() === true && empty($merchant->getParentId()) === true && $merchant->isBusinessBankingEnabled() === false)
            {
                $includeIds[] = $merchant->getId();
            }
        }

        return $includeIds;
    }

    private function checkMerchantLastTransactionTimestamp(array $merchantIds) : array
    {
        $includeIds = [];

        foreach ($merchantIds as $merchantId)
        {
            $hasTransacted = $this->repo->payment->hasMerchantTransacted($merchantId);

            if ($hasTransacted === true)
            {
                $count= $this->repo->payment->getMerchantTransactionCountBetweenTimestamps($merchantId, strtotime('-121 days'), time());

                if ($count === 0)
                {
                    $includeIds[] = $merchantId;
                }
            }
        }
        return $includeIds;
    }

    private function getNonDaoLostAndWonDisputeMerchants(array $merchantIdList) : array
    {
        $excludeIds = [];

        foreach ($merchantIdList as $merchantId)
        {
            $nonDaoDispute = $this->repo->dispute->getNonDaoLostAndWonDisputes($merchantId);

            if (empty($nonDaoDispute) === false)
            {
                $excludeIds[] = $merchantId;
            }
        }

        return array_diff($merchantIdList, $excludeIds);
    }

    private function getMerchantTagsForDispute(array $merchantIdList) : array
    {
        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIdList);

        $data = [];

        $excludeIds = [];

        foreach ($merchants as $merchant)
        {
            $data['tags'] = $merchant->tagNames();

            foreach ($data['tags'] as $tags) {

                if (Str::startsWith(strtoupper($tags), self::RISK_LEA_TAGS) === true)
                {
                    $excludeIds[] = $merchant->getId();
                }
            }
        }

        return array_diff($merchantIdList, $excludeIds);
    }

    private function checkFeatureFlagsForCapitalProducts(array $merchantIdList, array $featureFlags) : array
    {
        $excludeIds = [];

        foreach ($merchantIdList as $merchantId)
        {
            $features = (new FeatureService)->getFeatures('accounts', $merchantId);

            $assignedFeatures =  $features['assigned_features']->pluck('name')->toArray();

            if(empty(array_intersect($featureFlags, $assignedFeatures)) === false)
            {
                $excludeIds[] = $merchantId;
            }
        }

        return array_diff($merchantIdList, $excludeIds);
    }

    private function filterNegativePrimaryBalanceMerchants(array $merchantIdList) : array
    {
        $excludeIds = [];

        foreach ($merchantIdList as $merchantId)
        {
            $balance = $this->repo->balance->getMerchantBalanceByType($merchantId, Merchant\Balance\Type::PRIMARY) ?? null;

            if (empty($balance) === false)
            {
                $balance = $balance->toArrayPublic();

                $balanceAmount = $balance[Merchant\Balance\Entity::BALANCE];

                if ($balanceAmount < 0)
                {
                    $excludeIds[] = $merchantId;
                }
            }
        }

        return array_diff($merchantIdList, $excludeIds);
    }
}


