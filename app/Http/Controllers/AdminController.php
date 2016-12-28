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

    public function postInternationalRates($currency)
    {
        $data = (new Admin\Service)->postInternationalRates($currency);

        return ApiResponse::json($data);
    }

// --------------------- CRUD for Admins   ---------------------------------------

    public function getAdmin($orgId, $id)
    {
        $data = (new Admin\Admin\Service)->getAdmin($orgId, $id);

        return ApiResponse::json($data);
    }

    public function getAdminByAppAuth(string $orgId)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->getAdminByAppAuth($orgId, $input);

        return ApiResponse::json($data);
    }

    public function createAdmin($orgId)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->createAdmin($orgId, $input);

        return ApiResponse::json($data);
    }

    public function deleteAdmin($orgId, $adminId)
    {
        $data = (new Admin\Admin\Service)->deleteAdmin($orgId, $adminId);

        return ApiResponse::json($data);
    }

    public function fetchAdminMultiple(string $orgId)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->fetchMultiple($orgId, $input);

        return ApiResponse::json($data);
    }

    public function editAdmin(string $orgId, string $id)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->editAdmin($orgId, $id, $input);

        return ApiResponse::json($data);
    }

    public function logoutAdmin()
    {
        $data = (new Admin\Admin\Service)->logout();

        return ApiResponse::json($data);
    }

    public function getMerchantIds($orgId, $id)
    {
        $merchantIds = (new Admin\Admin\Service)->getMerchantIds($orgId, $id);

        return ApiResponse::json($merchantIds);
    }

    public function postAuthenticate(Admin\Admin\Service $adminService, string $orgId)
    {
        $input = Request::all();

        $response = $adminService->authenticate($orgId, $input);

        return ApiResponse::json($response);
    }

    public function postPasswordReset(Admin\Admin\Service $adminService, string $orgId)
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

// --------------------- END CRUD for Admins   ---------------------------------------

// --------------------- CRUD for roles  -----------------------------------------
    public function createRole(string $orgId)
    {
        $input = Request::all();

        $data = (new Admin\Role\Service)->create($orgId, $input);

        return ApiResponse::json($data);
    }

    public function getRole(string $orgId, string $id)
    {
        $data = (new Admin\Role\Service)->getRole($orgId, $id);

        return ApiResponse::json($data);
    }

    public function getMultipleRoles(string $orgId)
    {
        $data = (new Admin\Role\Service)->getMultipleRoles($orgId);

        return ApiResponse::json($data);
    }

    public function deleteRole(string $orgId, string $id)
    {
        $data = (new Admin\Role\Service)->deleteRole($orgId, $id);

        return ApiResponse::json($data);
    }

    public function putRole(string $orgId, string $id)
    {
        $input = Request::all();

        $data = (new Admin\Role\Service)->putRole($orgId, $id, $input);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for roles  --------------------------------------

// --------------------- CRUD for Groups  -----------------------------------------

    public function createGroup(string $orgId)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->createGroup($orgId, $input);

        return ApiResponse::json($data);
    }

    public function getGroup(string $orgId, string $id)
    {
        $data = (new Admin\Group\Service)->getGroup($orgId, $id);

        return ApiResponse::json($data);
    }

    public function getGroupsMultiple(string $orgId)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->fetchMultiple($orgId, $input);

        return ApiResponse::json($data);
    }

    /*
        We'll fetch all the groups eligible to be the "parent"
        of the incoming groupID
    */
    public function getAllowedGroups(string $orgId, string $id)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->fetchEligibleParents($orgId, $id, $input);

        return ApiResponse::json($data);
    }

    public function putGroup(string $orgId, string $id)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->editGroup($orgId, $id, $input);

        return ApiResponse::json($data);
    }

    public function deleteGroup(string $orgId, string $id)
    {
        $data = (new Admin\Group\Service)->deleteGroup($orgId, $id);

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

     public function getMultiplePermissions()
     {
         $input = Request::all();

         $data = (new Admin\Permission\Service)->getMultiplePermissions($input);

         return ApiResponse::json($data);
     }

// --------------------- END CRUD for Permissions ----------------------------------------


    public function postMailgunCallback($type)
    {
        $input = Request::all();

        $responseStatus = (new Admin\Service)->processMailgunCallback($type, $input);

        return ApiResponse::json([], $responseStatus);
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
