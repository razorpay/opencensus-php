<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Exception\RecoverableException;
use RZP\Models\Admin;
use Request;
use Redirect;
use App;

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

    /**
     * Organization related functions
     */

// --------------------- CRUD for ORG  -----------------------------------------
    public function getOrg($id)
    {
        $data = (new Admin\Org\Service)->getOrg($id);

        return ApiResponse::json($data);
    }

    public function createOrg()
    {
        $input = Request::all();

        $data = (new Admin\Org\Service)->createOrg($input);

        return ApiResponse::json($data);
    }

    public function deleteOrg(string $id)
    {
        $data = (new Admin\Org\Service)->deleteOrg($id);

        return ApiResponse::json($data);
    }

    public function fetchOrgMultiple()
    {
        $input = Request::all();

        $data = (new Admin\Org\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function putOrg(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Org\Service)->editOrg($id, $input);

        return ApiResponse::json($data);
    }
// --------------------- END CRUD for ORG  ---------------------------------------

// --------------------- CRUD for Admins   ---------------------------------------

    public function getAdmin($id, $adminId)
    {
        $data = (new Admin\Admin\Service)->getAdmin($id, $adminId);

        return ApiResponse::json($data);
    }

    public function getMultipleAdmins($id)
    {
        $data = (new Admin\Admin\Service)->getMultipleAdmins($id, $adminId);

        return ApiResponse::json($data);
    }

    public function createAdmin($id)
    {
        $data = (new Admin\Admin\Service)->createAdmin($id);

        return ApiResponse::json($data);
    }

    public function deleteAdmin($id, $adminId)
    {
        $data = (new Admin\Admin\Service)->deleteAdmin($id, $adminId);

        return ApiResponse::json($data);
    }

    public function addMerchantToAdmin($id, $adminId)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->addMerchantToAdmin(
            $id, $adminId, $input);

        return ApiResponse::json($data);
    }

    public function addRoleToAdmin(
        string $id,
        string $adminId)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->addRoleToAdmin(
            $id, $adminId, $roleId);

        return ApiResponse::json($data);
    }

    public function revokeRoleFromAdmin(
        string $id,
        string $adminId)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->revokeRoleFromAdmin(
            $id, $adminId, $input);

        return ApiResponse::json($data);
    }

    public function fetchAdminMultiple(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->fetchMultiple($id, $input);

        return ApiResponse::json($data);
    }

// --------------------- END CRUD for Admins   ---------------------------------------

// --------------------- CRUD for roles  -----------------------------------------
    public function createRole(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Role\Service)->createRole($id, $input);

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

// --------------------- END CRUD for roles  --------------------------------------

// --------------------- CRUD for Groups  -----------------------------------------
    public function createGroup(string $id)
    {
        $input == Request::all();

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


    public function deleteGroup(string $id, string $groupId)
    {
        $data = (new Admin\Group\Service)->deleteGroup($id, $groupId);

        return ApiResponse::json($data);
    }

    public function addRolesToGroup(string $id, string $groupId)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->addRolesToGroup(
            $id, $groupId, $input);

        return ApiResponse::json($data);
    }

    public function addMerchantsToGroup(string $id, string $groupId)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->addMerchantsToGroup(
            $id, $groupId, $input);

        return ApiResponse::json($data);
    }

    public function addAdminsToGroup(string $id, string $groupId)
    {
        $input = Request::all();

        $data = (new Admin\Group\Service)->addAdminsToGroup(
            $id, $groupId, $input);

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

    public function createPermissionsFromJson()
    {
        $input = Request::all();

        $data = (new Admin\Permission\Service)->createPermissionsFromJson(
            $input);

        return ApiResponse::json($data);
    }

    public function getPermission(string $permissionId)
    {
        $data = (new Admin\Permission\Service)->getPermission($permissionId);

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
    public function passwordLogin(Admin\Service $service)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->login($input);

        return ApiResponse::json($data);
    }

    public function helloWorld()
    {
        return ApiResponse::json(['hello_world']);
    }
}
