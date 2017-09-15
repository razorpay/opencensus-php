<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Admin;
use RZP\Constants\Entity as E;

class OrganizationController extends Controller
{
    public function postOrganization()
    {
        $input = Request::all();

        $data = $this->service(E::ORG)->create($input);

        return ApiResponse::json($data);
    }

    public function getOrganization($id)
    {
        $data = $this->service(E::ORG)->fetch($id);

        return ApiResponse::json($data);
    }

    public function getOrganizationByHostname($hostname)
    {
        $data = $this->service(E::ORG)->fetchByHostname($hostname);

        return ApiResponse::json($data);
    }

    public function getOrganizations()
    {
        $input = Request::all();

        $data = $this->service(E::ORG)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function putOrganization(string $id)
    {
        $input = Request::all();

        $data = $this->service(E::ORG)->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function deleteOrganization(string $id)
    {
        $data = $this->service(E::ORG)->delete($id);

        return ApiResponse::json($data);
    }

// --------------------- CRUD for Admins   ---------------------------------------

    public function getAdmin($adminId)
    {
        $data = $this->service(E::ADMIN)->getAdmin($adminId);

        return ApiResponse::json($data);
    }

    public function getAdminByAppAuth()
    {
        $input = Request::all();

        $data = $this->service(E::ADMIN)->getAdminByAppAuth($input);

        return ApiResponse::json($data);
    }

    public function createAdmin()
    {
        $input = Request::all();

        $data = $this->service(E::ADMIN)->createAdmin($input);

        return ApiResponse::json($data);
    }

    public function deleteAdmin($adminId)
    {
        $data = $this->service(E::ADMIN)->deleteAdmin($adminId);

        return ApiResponse::json($data);
    }

    public function fetchAdminMultiple()
    {
        $input = Request::all();

        $data = $this->service(E::ADMIN)->fetchMultiple();

        return ApiResponse::json($data);
    }

    public function getAdminMultipleOnAppAuth()
    {
        $input = Request::all();

        $data = $this->service(E::ADMIN)->fetchMultipleOnAppAuth($input);

        return ApiResponse::json($data);
    }

    public function editAdmin(string $adminId)
    {
        $input = Request::all();

        $data = $this->service(E::ADMIN)->editAdmin($adminId, $input);

        return ApiResponse::json($data);
    }

    public function logoutAdmin()
    {
        $data = $this->service(E::ADMIN)->logout();

        return ApiResponse::json($data);
    }

    public function postAdminLead()
    {
        $orgId = $this->ba->getAdminOrgId();

        $input = Request::all();

        $data = $this->service(E::ADMIN_LEAD)->sendInvitation($orgId, $input);

        return ApiResponse::json($data);
    }

    public function putAdminLead(string $id)
    {
        //getting from orgId since merchant dash also use this route.
        $orgId = $this->ba->getOrgId();

        $input = Request::all();

        $data = $this->service(E::ADMIN_LEAD)->editInvitation(
            $orgId, $id, $input);

        return ApiResponse::json($data);
    }

    public function getAdminLeadMultiple()
    {
        $orgId = $this->ba->getAdminOrgId();

        $data = $this->service(E::ADMIN_LEAD)->getInvitations($orgId);

        return ApiResponse::json($data);
    }

    public function verifyAdminLead(string $token)
    {
        $data = $this->service(E::ADMIN_LEAD)->verify($token);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for Admins   ---------------------------------------

// --------------------- CRUD for roles  -----------------------------------------
    public function createRole()
    {
        $input = Request::all();

        $data = $this->service(E::ROLE)->create($input);

        return ApiResponse::json($data);
    }

    public function getRole(string $roleId)
    {
        $data = $this->service(E::ROLE)->getRole($roleId);

        return ApiResponse::json($data);
    }

    public function getMultipleRoles()
    {
        $data = $this->service(E::ROLE)->getMultipleRoles();

        return ApiResponse::json($data);
    }

    public function deleteRole(string $roleId)
    {
        $data = $this->service(E::ROLE)->deleteRole($roleId);

        return ApiResponse::json($data);
    }

    public function putRole(string $roleId)
    {
        $input = Request::all();

        $data = $this->service(E::ROLE)->putRole($roleId, $input);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for roles  --------------------------------------

// --------------------- CRUD for Groups  -----------------------------------------
    public function createGroup()
    {
        $input = Request::all();

        $data = $this->service(E::GROUP)->createGroup($input);

        return ApiResponse::json($data);
    }

    public function getGroup(string $groupId)
    {
        $data = $this->service(E::GROUP)->getGroup($groupId);

        return ApiResponse::json($data);
    }

    public function getGroupsMultiple()
    {
        $data = $this->service(E::GROUP)->fetchMultiple();

        return ApiResponse::json($data);
    }

    /*
        We'll fetch all the groups eligible to be the "parent"
        of the incoming groupID
    */
    public function getAllowedGroups(string $groupId)
    {
        $input = Request::all();

        $data = $this->service(E::GROUP)->fetchEligibleParents($groupId, $input);

        return ApiResponse::json($data);
    }

    public function putGroup(string $groupId)
    {
        $input = Request::all();

        $data = $this->service(E::GROUP)->editGroup($groupId, $input);

        return ApiResponse::json($data);
    }

    public function deleteGroup(string $groupId)
    {
        $data = $this->service(E::GROUP)->deleteGroup($groupId);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for Groups  -------------------------------------

// --------------------- CRUD for Org FieldMap ----------------------------------------

    public function postOrgFieldMap(string $orgId)
    {
        $input = Request::all();

        $data = $this->service(E::ORG_FIELD_MAP)->createFieldMapForEntity($orgId, $input);

        return ApiResponse::json($data);
    }

    public function putOrgFieldMap(string $orgId, string $id)
    {
        $input = Request::all();

        $data = $this->service(E::ORG_FIELD_MAP)->editFieldMapForEntity($orgId, $id, $input);

        return ApiResponse::json($data);
    }

    public function getOrgFieldMapMultiple(string $orgId)
    {
        $data = $this->service(E::ORG_FIELD_MAP)->fetchMultiple($orgId);

        return ApiResponse::json($data);
    }

    public function getOrgFieldMap(string $orgId, string $id)
    {
        $data = $this->service(E::ORG_FIELD_MAP)->getFieldsForEntity($orgId, $id);

        return ApiResponse::json($data);
    }

    public function getOrgFieldMapByEntity(string $orgId, string $entity)
    {
        $data = $this->service(E::ORG_FIELD_MAP)->getByEntity($orgId, $entity);

        return ApiResponse::json($data);
    }

    public function deleteOrgFieldMap(string $orgId, string $id)
    {
        $data = $this->service(E::ORG_FIELD_MAP)->deleteFieldMapForEntity($orgId, $id);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for Org FieldMap ----------------------------------------

// --------------------- CRUD for Permissions ----------------------------------------

    public function createPermission()
    {
        $input = Request::all();

        $data = $this->service(E::PERMISSION)->createPermission($input);

        return ApiResponse::json($data);
    }

    public function deletePermission(string $permId)
    {
        $data = $this->service(E::PERMISSION)->deletePermission($permId);

        return ApiResponse::json($data);
    }

    public function getPermission(string $id)
    {
        $data = $this->service(E::PERMISSION)->getPermission($id);

        return ApiResponse::json($data);
    }

    public function getRolesForPermission(string $id)
    {
        $data = $this->service(E::PERMISSION)->getRolesForPermission($id);

        return ApiResponse::json($data);
    }

    public function putPermission(string $id)
    {
        $input = Request::all();

        $data = $this->service(E::PERMISSION)->editPermission($id, $input);

        return ApiResponse::json($data);
    }

    public function getMultiplePermissions()
    {
        $orgId = $this->ba->getAdminOrgId();

        $input = Request::all();

        $data = $this->service(E::PERMISSION)->getMultiplePermissions($orgId, $input);

        return ApiResponse::json($data);
    }

    public function getPermissionsByType($type)
    {
        switch ($type)
        {
            case 'assignable':
                $data = $this->service(E::PERMISSION)->getAssignablePermissions();

                break;

            case 'all':
            default:
                $data = $this->service(E::PERMISSION)->getAllPermissions();

                break;
        }

         return ApiResponse::json($data);
    }

// --------------------- END CRUD for Permissions ----------------------------------------

    /**
    * Admin related functons
    */
    public function postAuthenticate()
    {
        $input = Request::all();

        $response = $this->service(E::ADMIN)->authenticate($input);

        return ApiResponse::json($response);
    }

    public function postForgotPassword()
    {
        $input = Request::all();

        $orgId = $this->ba->getOrgId();

        $response = $this->service(E::ADMIN)->forgotPassword($orgId, $input);

        return ApiResponse::json($response);
    }

    public function postResetPassword()
    {
        $input = Request::all();

        $orgId = $this->ba->getOrgId();

        $response = $this->service(E::ADMIN)->resetPassword($orgId, $input);

        return ApiResponse::json($response);
    }

    public function postChangePassword()
    {
        $input = Request::all();

        $response = $this->service(E::ADMIN)->changePassword($input);

        return ApiResponse::json($response);
    }

    public function oAuthLogin()
    {
        $input = Request::all();

        $data = $this->service(E::ADMIN)->loginWithOAuth($input);

        return ApiResponse::json($data);
    }

    public function postMailgunCallback($type)
    {
        $input = Request::all();

        $responseStatus = $this->service(E::ADMIN)->processMailgunCallback($type, $input);

        return ApiResponse::json([], $responseStatus);
    }

    /**
     * @deprecated Ref: #4216
     */
    public function getMerchantIds($id, $adminId)
    {
        $merchantIds = $this->service(E::ADMIN)->getMerchantIds($id, $adminId);

        return ApiResponse::json($merchantIds);
    }

    /**
     * @deprecated Ref: #4216
     */
    public function getMerchants($id, $adminId)
    {
        $input = Request::all();

        $response = $this->service(E::ADMIN)->getMerchants($id, $adminId, $input);

        return ApiResponse::json($response);
    }

    public function getMerchantIdsFromEs(Admin\Admin\Service $service)
    {
        $response = $service->getMerchantIdsFromEs();

        return ApiResponse::json($response);
    }

    public function getMerchantsFromEs(Admin\Admin\Service $service)
    {
        $input = Request::all();

        $response = $service->getMerchantsFromEs($input);

        return ApiResponse::json($response);
    }

    public function postLockBulkAccounts()
    {
        $response = $this->service(E::ADMIN)->lockUnusedAccounts();

        return ApiResponse::json($response);
    }

    public function auditLogSearch()
    {
        // Indexes use lower case of orgid
        $orgId = strtolower($this->ba->getAdminOrgId());

        $input = Request::all();

        $response = $this->service(E::ADMIN)->searchAuditLogs($orgId, $input);

        return ApiResponse::json($response);
    }
}
