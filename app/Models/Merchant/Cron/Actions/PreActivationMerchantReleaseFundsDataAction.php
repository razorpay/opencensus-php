<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant\Cron;
use RZP\Models\Merchant\Action;
use RZP\Models\Merchant\Detail;
use RZP\Models\RiskWorkflowAction\Core;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\RiskWorkflowAction\Constants;
use RZP\Models\RiskWorkflowAction\Validator;

class PreActivationMerchantReleaseFundsDataAction extends BaseAction
{
    const WORKFLOW_EMAIL_ID = 'wf-admin@razorpay.com';

    public function execute($data = []): ActionDto
    {
        $collectorData = $data['pre_activation_merchant_release_funds_data'];

        $data = $collectorData->getData();

        $merchantIds = $data[Cron\Constants::MERCHANT_IDS] ?? null;

        $workflowAdmin = $this->repo->admin->findByOrgIdAndEmail(Org\Entity::RAZORPAY_ORG_ID, self::WORKFLOW_EMAIL_ID);

        $this->app['trace']->info(TraceCode::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS, [
            'total_merchant_count' => ($merchantIds === null) ? 0 : count($merchantIds),
            'merchant_ids'         => $merchantIds,
            'workflow_admin_id'    => ($workflowAdmin === null) ? 0 : $workflowAdmin->getId()
        ]);

        if ((empty($merchantIds) === true) or count($merchantIds) === 0 or empty($workflowAdmin) === true)
        {
            return new ActionDto(Cron\Constants::SKIPPED);
        }

        $merchantIdChunks = array_chunk($merchantIds, 20);

        foreach ($merchantIdChunks as $merchantIdList)
        {
            $this->triggerCreateRiskActionForAdminIdSettlementClearance($merchantIdList, $workflowAdmin);
        }

        return new ActionDto(Cron\Constants::SUCCESS);
    }

    public function createRiskActionForAdminId($admin, $input)
    {
        $riskWorkFlowActionCore = (new Core());

        (new Validator())->validateInput('create_risk_action', $input);

        $riskWorkFlowActionCore->validateRiskAttributes($input);

        return $riskWorkFlowActionCore->createRiskWorkflowAction($input, $admin, null, true);

    }

    private function triggerCreateRiskActionForAdminIdSettlementClearance($merchantIdList, $workflowAdmin)
    {
        foreach ($merchantIdList as $merchantId)
        {
            $input = [
                'merchant_id'     => $merchantId,
                'action'          => Action::RELEASE_FUNDS,
                'risk_attributes' => array(Constants::CLEAR_RISK_TAGS => '0')
            ];

            $this->app['trace']->info(TraceCode::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS_INPUT, [
                'merchant_release_funds_input' => $input
            ]);

            try
            {
                $settlementClearanceExpEnabled = (new Detail\Core())
                    ->getSplitzResponse($merchantId, 'settlement_clearance_experiment_id');

                $this->app['trace']->info(TraceCode::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS_EXP_ENABLED, [
                    'merchant_id'                   => $merchantId,
                    'settlementClearanceExpEnabled' => $settlementClearanceExpEnabled
                ]);

                if ($settlementClearanceExpEnabled === 'true')
                {
                    $this->createRiskActionForAdminId($workflowAdmin, $input);
                }
            }
            catch (\Throwable $e)
            {
                $this->app['trace']->info(
                    TraceCode::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS_ERROR,
                    [
                        'merchant_id' => $merchantId,
                        'error'       => $e->getMessage(),
                    ]);
            }
        }
    }
}
