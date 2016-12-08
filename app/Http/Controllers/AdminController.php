<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use App;
use Redirect;
use Request;
use RZP\Models\Admin;

class AdminController extends Controller
{
    public function getEntityMultiple($type)
    {
        $input = Request::all();

        $data = (new Admin\Service)->fetchMultipleEntities($type, $input);

        return ApiResponse::json($data);
    }

    public function getEntityById($type, $id)
    {
        $data = (new Admin\Service)->fetchEntityById($type, $id);

        return ApiResponse::json($data);
    }

    public function postSendTestNewsletter()
    {
        $input = Request::all();

        $data = (new Admin\Service)->sendTestNewsletter($input);

        return ApiResponse::json($data);
    }

    public function postSendNewsletter()
    {
        $input = Request::all();

        $data = (new Admin\Service)->sendNewsletter($input);

        return ApiResponse::json($data);
    }

    public function getTransparentRedirect()
    {
        $input = Request::all();

        if (isset($input['url']))
        {
            $url = $input['url'];
            unset($input['url']);

            $query = http_build_query($input);
            $url .= '?'.$query;

            return Redirect::to($url);
        }
    }

    public function postTransparentRedirect()
    {
        $input = Request::all();
    }

    public function getScorecard()
    {
        $input = Request::all();

        $data = (new Admin\Scorecard)->generateScorecard($input);

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

    public function editAdmin(string $id, string $adminId)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->editAdmin($id, $adminId, $input);

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

     public function getPermission(string $permissionId)
     {
         $data = (new Admin\Permission\Service)->getPermission($permissionId);

         return ApiResponse::json($data);
     }

     public function putPermission(string $permissionId)
     {
         $input = Request::all();

         $data = (new Admin\Permission\Service)->editPermission($permissionId, $input);

         return ApiResponse::json($data);
     }

     public function getMultiplePermissions()
     {
         $input = Request::all();

         $data = (new Admin\Permission\Service)->getMultiplePermissions($input);

         return ApiResponse::json($data);
     }

// --------------------- END CRUD for Permissions ----------------------------------------

    /**
    * Admin related functons
    */
    public function postAuthenticate(string $orgId, Admin\Admin\Service $adminService)
    {
        $input = Request::all();

        $response = $adminService->authenticate($orgId, $input);

        return ApiResponse::json($response);
    }

    public function postPasswordReset(string $orgId, Admin\Admin\Service $adminService)
    {
        $input = Request::all();

        $response = $adminService->passwordReset($orgId, $input);

        return ApiResponse::json($response);
    }

    public function oAuthLogin(string $orgId)
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

    public function getMerchantIds($orgId, $adminId)
    {
        $merchantIds = (new Admin\Admin\Service)->getMerchantIds($orgId, $adminId);

        return ApiResponse::json($merchantIds);
    }

    public function postLockBulkAccounts(Admin\Admin\Service $adminService)
    {
        $response = $adminService->lockUnusedAccounts();

        return ApiResponse::json($response);
    }

    public function auditLogSearch($orgId)
    {
        try
        {
            // Indexes use lower case of orgid
            $orgId = strtolower($orgId);

            $input = Request::all();

            $response = (new Admin\Admin\Service)->searchAuditLogs($orgId, $input);

            return ApiResponse::json($response);
        }
        catch(\Exception $e)
        {
            throw $e;
        }
    }
}
