<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Admin;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Org\FieldMap;

class OrganizationController extends Controller
{
    public function postOrganization(Org\Service $orgService)
    {
        $input = Request::all();

        $data = $orgService->create($input);

        return ApiResponse::json($data);
    }

    public function getOrganization(Org\Service $orgService, $id)
    {
        $data = $orgService->fetch($id);

        return ApiResponse::json($data);
    }

    public function getOrganizationByHostname(Org\Service $orgService, $hostname)
    {
        $data = $orgService->fetchByHostname($hostname);

        return ApiResponse::json($data);
    }

    public function getOrganizations(Org\Service $orgService)
    {
        $input = Request::all();

        $data = $orgService->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function putOrganization(Org\Service $orgService, string $id)
    {
        $input = Request::all();

        $data = $orgService->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function deleteOrganization(Org\Service $orgService, string $id)
    {
        $data = $orgService->delete($id);

        return ApiResponse::json($data);
    }

// --------------------- CRUD for Admins   ---------------------------------------

    public function getAdmin($id, $adminId)
    {
        $data = (new Admin\Admin\Service)->getAdmin($id, $adminId);

        return ApiResponse::json($data);
    }

    public function getAdminByAppAuth(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->getAdminByAppAuth($id, $input);

        return ApiResponse::json($data);
    }

    public function createAdmin($id)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->createAdmin($id, $input);

        return ApiResponse::json($data);
    }

    public function deleteAdmin($id, $adminId)
    {
        $data = (new Admin\Admin\Service)->deleteAdmin($id, $adminId);

        return ApiResponse::json($data);
    }

    public function fetchAdminMultiple(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->fetchMultiple($id, $input);

        return ApiResponse::json($data);
    }

    public function getAdminMultipleOnAppAuth()
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->fetchMultipleOnAppAuth($input);

        return ApiResponse::json($data);
    }

    public function editAdmin(string $id, string $adminId)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->editAdmin($id, $adminId, $input);

        return ApiResponse::json($data);
    }

    public function logoutAdmin()
    {
        $data = (new Admin\Admin\Service)->logout();

        return ApiResponse::json($data);
    }

    public function postAdminLead(string $orgId)
    {
        $input = Request::all();

        $data = (new Admin\AdminLead\Service)->sendInvitation($orgId, $input);

        return ApiResponse::json($data);
    }

    public function putAdminLead(string $orgId, string $id)
    {
        $input = Request::all();

        $data = (new Admin\AdminLead\Service)->editInvitation(
            $orgId, $id, $input);

        return ApiResponse::json($data);
    }

    public function getAdminLeadMultiple(string $orgId)
    {
        $data = (new Admin\AdminLead\Service)->getInvitations($orgId);

        return ApiResponse::json($data);
    }

    public function verifyAdminLead(string $token)
    {
        $data = (new Admin\AdminLead\Service)->verify($token);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for Admins   ---------------------------------------

// --------------------- CRUD for roles  -----------------------------------------
    public function createRole(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Role\Service)->create($id, $input);

        return ApiResponse::json($data);
    }

    public function getRole(string $id, string $roleId)
    {
        $data = (new Admin\Role\Service)->getRole($id, $roleId);

        return ApiResponse::json($data);
    }

    public function getMultipleRoles(string $id)
    {
        $data = (new Admin\Role\Service)->getMultipleRoles($id);

        return ApiResponse::json($data);
    }

    public function deleteRole(string $id, string $roleId)
    {
        $data = (new Admin\Role\Service)->deleteRole($id, $roleId);

        return ApiResponse::json($data);
    }

    public function putRole(string $id, string $roleId)
    {
        $input = Request::all();

        $data = (new Admin\Role\Service)->putRole($id, $roleId, $input);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for roles  --------------------------------------

// --------------------- CRUD for Groups  -----------------------------------------
    public function createGroup(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->createGroup($id, $input);

        return ApiResponse::json($data);
    }

    public function getGroup(string $id, string $groupId)
    {
        $data = (new Admin\Group\Service)->getGroup($id, $groupId);

        return ApiResponse::json($data);
    }

    public function getGroupsMultiple(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->fetchMultiple($id, $input);

        return ApiResponse::json($data);
    }

    /*
        We'll fetch all the groups eligible to be the "parent"
        of the incoming groupID
    */
    public function getAllowedGroups(string $id, string $groupId)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->fetchEligibleParents($id, $groupId, $input);

        return ApiResponse::json($data);
    }

    public function putGroup(string $id, string $groupId)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->editGroup($id, $groupId, $input);

        return ApiResponse::json($data);
    }

    public function deleteGroup(string $id, string $groupId)
    {
        $data = (new Admin\Group\Service)->deleteGroup($id, $groupId);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for Groups  -------------------------------------

// --------------------- CRUD for Org FieldMap ----------------------------------------

    public function postOrgFieldMap(string $orgId)
    {
        $input = Request::all();

        $data = (new FieldMap\Service)->createFieldMapForEntity($orgId, $input);

        return ApiResponse::json($data);
    }

    public function putOrgFieldMap(string $orgId, string $id)
    {
        $input = Request::all();

        $data = (new FieldMap\Service)->editFieldMapForEntity($orgId, $id, $input);

        return ApiResponse::json($data);
    }

    public function getOrgFieldMapMultiple(string $orgId)
    {
        $data = (new FieldMap\Service)->fetchMultiple($orgId);

        return ApiResponse::json($data);
    }

    public function getOrgFieldMap(string $orgId, string $id)
    {
        $data = (new FieldMap\Service)->getFieldsForEntity($orgId, $id);

        return ApiResponse::json($data);
    }

    public function getOrgFieldMapByEntity(string $orgId, string $entity)
    {
        $data = (new FieldMap\Service)->getByEntity($orgId, $entity);

        return ApiResponse::json($data);
    }

    public function deleteOrgFieldMap(string $orgId, string $id)
    {
        $data = (new FieldMap\Service)->deleteFieldMapForEntity($orgId, $id);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for Org FieldMap ----------------------------------------

// --------------------- CRUD for Permissions ----------------------------------------

    public function createPermission()
    {
        $input = Request::all();

        $data = (new Admin\Permission\Service)->createPermission($input);

        return ApiResponse::json($data);
     }

    public function deletePermission(string $permId)
    {
        $data = (new Admin\Permission\Service)->deletePermission($permId);

        return ApiResponse::json($data);
     }

    public function getPermission(string $id)
    {
        $data = (new Admin\Permission\Service)->getPermission($id);

        return ApiResponse::json($data);
    }

    public function putPermission(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Permission\Service)->editPermission($id, $input);

        return ApiResponse::json($data);
    }

    public function getMultiplePermissions(string $orgId)
    {
        $data = (new Admin\Permission\Service)->getMultiplePermissions($orgId);

        return ApiResponse::json($data);
    }

    public function getPermissionsByType($type)
    {
        switch ($type)
        {
            case 'assignable':
                $data = (new Admin\Permission\Service)->getAssignablePermissions();

                break;

            case 'all':
            default:
                $data = (new Admin\Permission\Service)->getAllPermissions();

                break;
         }

         return ApiResponse::json($data);
     }

// --------------------- END CRUD for Permissions ----------------------------------------

    /**
    * Admin related functons
    */
    public function postAuthenticate(Admin\Admin\Service $adminService, string $id)
    {
        $input = Request::all();

        $response = $adminService->authenticate($id, $input);

        return ApiResponse::json($response);
    }

    public function postForgotPassword(Admin\Admin\Service $adminService, string $orgId)
    {
        $input = Request::all();

        $response = $adminService->forgotPassword($orgId, $input);

        return ApiResponse::json($response);
    }

    public function postResetPassword(Admin\Admin\Service $adminService, string $orgId)
    {
        $input = Request::all();

        $response = $adminService->resetPassword($orgId, $input);

        return ApiResponse::json($response);
    }

    public function oAuthLogin(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->loginWithOAuth($input);

        return ApiResponse::json($data);
    }

    public function postMailgunCallback($type)
    {
        $input = Request::all();

        $responseStatus = (new Admin\Service)->processMailgunCallback($type, $input);

        return ApiResponse::json([], $responseStatus);
    }

    public function getMerchantIds($id, $adminId)
    {
        $merchantIds = (new Admin\Admin\Service)->getMerchantIds($id, $adminId);

        return ApiResponse::json($merchantIds);
    }

    public function getMerchants($id, $adminId)
    {
        $input = Request::all();

        $response = (new Admin\Admin\Service)->getMerchants($id, $adminId, $input);

        return ApiResponse::json($response);
    }

    public function postLockBulkAccounts(Admin\Admin\Service $adminService)
    {
        $response = $adminService->lockUnusedAccounts();

        return ApiResponse::json($response);
    }

    public function auditLogSearch($id)
    {
        try
        {
            // Indexes use lower case of orgid
            $id = strtolower($id);

            $input = Request::all();

            $response = (new Admin\Admin\Service)->searchAuditLogs($id, $input);

            return ApiResponse::json($response);
        }
        catch(\Exception $e)
        {
            throw $e;
        }
    }
}
