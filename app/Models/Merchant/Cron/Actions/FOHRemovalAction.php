<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant\Cron;
use RZP\Models\Merchant\Action;
use RZP\Models\RiskWorkflowAction\Core;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\RiskWorkflowAction\Validator;
use RZP\Models\RiskWorkflowAction\Constants;

class FOHRemovalAction extends BaseAction
{
    const WORKFLOW_EMAIL_ID = 'wf-admin@razorpay.com';

    public function execute($data = []): ActionDto
    {
        $collectorData = $data['FOH_removal_data'];

        $data = $collectorData->getData();

        $merchantIds = $data[Cron\Constants::MERCHANT_IDS] ?? null;

        $workflowAdmin = $this->repo->admin->findByOrgIdAndEmail(Org\Entity::RAZORPAY_ORG_ID, self::WORKFLOW_EMAIL_ID);

        $this->app['trace']->info(TraceCode::FOH_REMOVAL_ACTION,[
            'total_merchant_count' => ($merchantIds === null) ? 0 : count($merchantIds),
            'mids'                 => $merchantIds,
            'workflow_admin_id'    => ($workflowAdmin === null) ? 0 : $workflowAdmin->getId()
        ]);

        if ($merchantIds === null or count($merchantIds) === 0 or $workflowAdmin === null)
        {
            return new ActionDto(Cron\Constants::SKIPPED);
        }

        $merchantIdChunks = array_chunk($merchantIds, 20);

        foreach ($merchantIdChunks as $merchantIdList) {

            $this->triggerCreateRiskActionForAdminId($merchantIdList,$workflowAdmin);

        }

        return new ActionDto(Cron\Constants::SUCCESS);
    }

    public function createRiskActionForAdminId($admin, $input){

        (new Validator())->validateInput('create_risk_action', $input);

        (new Core())->validateRiskAttributes($input);

        return (new Core())->createRiskWorkflowAction($input, $admin);

    }

    private function triggerCreateRiskActionForAdminId($merchantIdList, $workflowAdmin)
    {
        foreach ($merchantIdList as $merchantId)
        {
            $input = array('action'          => Action::RELEASE_FUNDS,
                           'risk_attributes' => array(Constants::CLEAR_RISK_TAGS => '0'),
                           'merchant_id'     => $merchantId);

            $this->app['trace']->info(TraceCode::FOH_REMOVAL_ACTION_INPUT,[
                'input' => $input
            ]);

            try {
                $fohExperimentEnabled = self::isFOHRemovalExperimentEnabled($merchantId);

                $this->app['trace']->info(TraceCode::FOH_REMOVAL_EXPERIMENT_ENABLED,[
                    'merchant_id'           => $merchantId,
                    'fohExperimentEnabled'  => $fohExperimentEnabled
                ]);

                if ($fohExperimentEnabled === true)
                {
                    $this->createRiskActionForAdminId($workflowAdmin, $input);
                }
            }
            catch (\Throwable $e)
            {
                $this->app['trace']->info(
                    TraceCode::FOH_CREATE_RISK_ACTION_ERROR,
                    [
                        'merchant_id'   => $merchantId,
                        'error'         => $e->getMessage(),
                    ]);
            }
        }
    }

    protected function isFOHRemovalExperimentEnabled($merchantId): bool
    {
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.cmma_post_onboarding_foh_removal_splitz_experiment_id'),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        return $variant === \RZP\Models\Merchant\Escalations\Constants::ENABLE;
    }
}
