<?php

namespace RZP\Models\Workflow\Observer;

use App;
use Carbon\Carbon;

use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Models\Workflow\Observer\Constants as WorkflowObserverConstants;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Workflow\Service as WorkflowService;
use RZP\Trace\TraceCode;

class MerchantFohCmsObserver implements WorkflowObserverInterface
{
    protected $permissionName;

    protected $app;

    public function __construct($input)
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        if (key_exists(DifferEntity::PERMISSION, $input) === true) // permission at times might not be present
        {
            $this->permissionName = $input[DifferEntity::PERMISSION];
        }
    }

    public function onApprove(array $observerData)
    {

    }

    public function onClose(array $observerData)
    {

    }

    public function onReject(array $observerData)
    {
        if (isset($observerData[Constants::FROM_CMS]) === true)
        {
            //cms call to update case status to rejected by checker
            $cmsInput = [
                Constants::ACTION    => Constants::REJECTED_BY_CHECKER,
                Constants::MARKED_AS => $observerData[Constants::MARKED_AS],
                Constants::AGENT     => $this->app['basicauth']->getAdmin()->getName() ?? '',
                Constants::AGENT_ID  => $this->app['basicauth']->getAdmin()->getEmail() ?? '',
                Constants::STATUS    => $observerData[Constants::CASE_STATUS],
                Constants::PRIORITY  => $observerData[Constants::PRIORITY],
                Constants::COMMENT   => $observerData[Constants::COMMENT],
            ];

            $this->app['case-management-service']->requestAndGetParseBody("PUT",
                "v1/risk_cms/cases/" . $observerData[Constants::CASE_ID],
                $cmsInput
            );

        }
    }

    public function onCreate(array $observerData)
    {

    }

    public function onExecute(array $observerData)
    {
        if (isset($observerData[Constants::FROM_CMS]) === true)
        {
            //cms call to update case status to resolved
            $cmsInput = [
                Constants::ACTION       => Constants::RESOLVE,
                Constants::MARKED_AS    => $observerData[Constants::MARKED_AS],
                Constants::AGENT        => $this->app['basicauth']->getAdmin()->getName() ?? '',
                Constants::AGENT_ID     => $this->app['basicauth']->getAdmin()->getEmail() ?? '',
                Constants::ASSIGNEE     => $observerData[Constants::ASSIGNEE],
                Constants::ASSIGNEE_ID  => $observerData[Constants::ASSIGNEE_ID],
                Constants::STATUS       => $observerData[Constants::CASE_STATUS],
                Constants::PRIORITY     => $observerData[Constants::PRIORITY],
                Constants::COMMENT      => $observerData[Constants::COMMENT],
            ];

            $this->app['case-management-service']->requestAndGetParseBody("PUT",
                "v1/risk_cms/cases/" . $observerData[Constants::CASE_ID],
                $cmsInput
            );
        }
    }
}
