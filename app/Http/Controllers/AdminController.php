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

    public function putOrg(string $id)
    {
        $input = Request::all();

        $data = (new Admin\Org\Service)->editOrg($id, $input);

        return ApiResponse::json($data);
    }

    public function createRole(string $orgId)
    {
        $input = Request::all();

        $data = (new Admin\Role\Service)->createRole($orgId, $input);

        return $data;
    }

    public function getRole(string $orgId, string $roleId)
    {
        $data = (new Admin\Role\Service)->getRole($orgId, $roleId);

        return $data;
    }

    public function getMultipleRoles(string $orgId)
    {
        $data = (new Admin\Role\Service)->getMultipleRoles($orgId);

        return $data;
    }

    public function createPermission(string $orgId)
    {
        $input = Request::all();

        $data = (new Admin\Permission\Service)->createPermission($orgId, $input);

        return $data;

    /**
    * Admin related functons
    */
    public function passwordLogin(Admin\Service $Service)
    {
        $input = Request::all();

        $data = (new Admin\Admin\Service)->login($input);

        return ApiResponse::json($data);
    }
}
