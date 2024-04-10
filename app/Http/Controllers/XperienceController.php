<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use Redirect;
use ApiResponse;
use RZP\Services\Xperience;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;

class XperienceController extends Controller
{

    protected $app;

    /** @var Xperience $xperience  */
    protected $xperience;

    public function __construct()
    {
        parent::__construct();

        $this->xperience = $this->app['xperience'];
    }

    public function getBulkPayoutById(string $bulkPayoutId)
    {
        $response = $this->xperience->getBulkPayoutById($bulkPayoutId);

        return ApiResponse::json($response);
    }

    public function getBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->getBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function ownerBulkRejectBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->ownerBulkRejectBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function getPendingBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->getPendingBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function getBulkPayoutsMetaSummary()
    {
        $input = Request::all();

        $response = $this->xperience->getBulkPayoutsMetaSummary($input);

        return ApiResponse::json($response);
    }

    public function approveBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->approveBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function createBulkPayout()
    {
        $input = Request::all();

        $response = $this->xperience->createBulkPayout($input);

        return ApiResponse::json($response);
    }

    public function getBulkPayoutRows(string $bulkPayoutId)
    {
        $input = Request::all();

        $response = $this->xperience->getBulkPayoutRows($bulkPayoutId, $input);

        return ApiResponse::json($response);
    }

    public function processBulkPayout(string $bulkPayoutId)
    {
        $input = Request::all();

        $response = $this->xperience->processBulkPayout($bulkPayoutId, $input);

        return ApiResponse::json($response);
    }

    public function rejectBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->rejectBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function workflowSummary()
    {
        $response = $this->xperience->workflowSummary();

        return ApiResponse::json($response);
    }

    public function migrateBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->migrateBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function createCostCenters()
    {
        $input = Request::all();

        $response = $this->xperience->createCostCenters($input);

        return ApiResponse::json($response);
    }

    public function getCostCenters()
    {
        $input = Request::all();

        $response = $this->xperience->getCostCenters($input);

        return ApiResponse::json($response);
    }

    public function getCostCenter(string $costCenterId)
    {

        $response = $this->xperience->getCostCenter($costCenterId);

        return ApiResponse::json($response);
    }

    public function updateCostCenter(string $costCenterId)
    {
        $input = Request::all();

        $response = $this->xperience->updateCostCenter($costCenterId, $input);

        return ApiResponse::json($response);
    }

    public function disableCostCenter(string $costCenterId)
    {

        $response = $this->xperience->disableCostCenter($costCenterId);

        return ApiResponse::json($response);
    }

    public function addUser()
    {
        $input = Request::all();

        $response = $this->xperience->addUser($input);

        return ApiResponse::json($response);
    }

    public function resendUserInvite($inviteId)
    {
        $response = $this->xperience->resendUserInvite($inviteId);

        return ApiResponse::json($response);
    }

    public function cancelUserInvite($inviteId)
    {
        $response = $this->xperience->cancelUserInvite($inviteId);

        return ApiResponse::json($response);
    }

    public function deleteUser(string $id)
    {
        $response = $this->xperience->deleteUser($id);

        return ApiResponse::json($response);
    }

    public function editUser(string $id)
    {
        $input = Request::all();

        $response = $this->xperience->editUser($id, $input);

        return ApiResponse::json($response);
    }

    public function getUser(string $id)
    {
        $input = Request::all();

        $response = $this->xperience->getUser($id, $input);

        return ApiResponse::json($response);
    }

    public function listUsers()
    {
        $input = Request::all();

        $response = $this->xperience->listUsers($input);

        return ApiResponse::json($response);
    }

    public function listGroupsOfUser(string $id)
    {
        $input = Request::all();

        $response = $this->xperience->listGroupsOfUser($id, $input);

        return ApiResponse::json($response);
    }

    public function listUsersOfGroup(string $id)
    {
        $input = Request::all();

        $response = $this->xperience->listUsersOfGroup($id, $input);

        return ApiResponse::json($response);
    }

    public function removeGroupOfUsers()
    {
        $input = Request::all();

        $response = $this->xperience->removeGroupOfUsers($input);

        return ApiResponse::json($response);
    }

    public function addGroupForUsers()
    {
        $input = Request::all();

        $response = $this->xperience->addGroupForUsers($input);

        return ApiResponse::json($response);
    }

    public function updateGroup(string $id)
    {
        $input = Request::all();

        $response = $this->xperience->updateGroup($id, $input);

        return ApiResponse::json($response);
    }

    public function listGroups()
    {
        $input = Request::all();

        $response = $this->xperience->listGroups($input);

        return ApiResponse::json($response);
    }

    public function getGroup(string $id)
    {
        $input = Request::all();

        $response = $this->xperience->getGroup($id, $input);

        return ApiResponse::json($response);
    }

    public function createGroup()
    {
        $input = Request::all();

        $response = $this->xperience->createGroup($input);

        return ApiResponse::json($response);
    }

    public function listGroupTypes()
    {
        $input = Request::all();

        $response = $this->xperience->listGroupTypes($input);

        return ApiResponse::json($response);
    }

    public function createGroupType()
    {
        $input = Request::all();

        $response = $this->xperience->createGroupType($input);

        return ApiResponse::json($response);
    }

    public function pendingEntitiesApprovalEmailCron()
    {
        $input = Request::all();

        $response = $this->xperience->sendPendingApprovalsEmail();

        return ApiResponse::json($response);
    }

    public function bulkCreateUserDetails()
    {
        $input = Request::all();

        $response = $this->xperience->bulkCreateUserDetails($input);

        return ApiResponse::json($response);
    }

    public function bulkCreateUserDetailsRaw()
    {
        $input = Request::all();

        $response = $this->xperience->bulkCreateUserDetailsRaw($input);

        return ApiResponse::json($response);
    }
}
