<?php

namespace RZP\Http\Middleware;

use App;
use RZP\Http\Route;
use RZP\Http\UserRolesScope;
use RZP\Http\RouteTeamMap;
use Illuminate\Http\Request;
use RZP\Services\AutoGenerateApiDocs;
use Symfony\Component\HttpFoundation\Response;
use RZP\Services\AutoGenerateApiDocs\Constants as ApiDocsConstants;

final class SaveApiDetailsForDocumentation
{
    protected $app;

    public function __construct()
    {
        $this->app = $app = App::getFacadeRoot();;
    }

    /**
     * Handles http request:
     * - Inits request context
     * - Attempts throttling
     * - Pushes http related metrics
     *
     * @param  Request  $request
     * @param  \Closure $next
     * @return Response
     * @throws \Throwable
     */
    public function handle(&$request, \Closure $next)
    {
        if (empty($request->header(ApiDocsConstants::API_DOCUMENTATION_DETAILS)) === true)
        {
            return $next($request);
        }


        $requestData             = $request->input();

        $apiDocumentationDetails = json_decode($request->header(ApiDocsConstants::API_DOCUMENTATION_DETAILS), true);

        $request->replace($requestData);

        $routeName              = $request->route()->getName();

        $apiSummary             = $apiDocumentationDetails[ApiDocsConstants::API_SUMMARY] ?? $this->getAPIDefaultDescription($routeName);
        $apiDescr               = $this->getAdditionalApiDescription($routeName);
        $apiRequestDescr        = $apiDocumentationDetails[ApiDocsConstants::API_REQUEST_DESCRIPTION];
        $apiResponseDescr       = $apiDocumentationDetails[ApiDocsConstants::API_RESPONSE_DESCRIPTION];

        $routeInfo = Route::getApiRoute($routeName);

        $utUrl = '/'. $routeInfo[1];

        $response = $next($request);

        $responseData = method_exists($response, 'getData') ? $response->getData() : [];

        if(is_object($responseData))
        {
            $responseData = $this->objectToArray($responseData);
        }

        $apiDetails = new AutoGenerateApiDocs\ApiDetails(
            $request->url(),
            $request->getMethod(),
            AutoGenerateApiDocs\ApiDetails::API_RESPONSE_CODE_200,
            $requestData,
            (array)$responseData,
            $apiDescr,
            $apiSummary,
            $utUrl,
            $request->header('Content-Type') ?? ApiDocsConstants::CONTENT_TYPE_APPLICATION_JSON,
            $apiRequestDescr,
            $response->headers->get('Content-Type') ?? ApiDocsConstants::CONTENT_TYPE_APPLICATION_JSON,
            $apiResponseDescr
        );

        $fileName = env('API_DOCS_FILE_NAME');

        $fileDir  = $request->header('auto-generate-api-docs-dir') ?? ApiDocsConstants::FILES_DIR;

        (new AutoGenerateApiDocs\SaveApiDetails($apiDetails, $fileDir, $fileName))->save();

        return $response;

    }

    protected function objectToArray($obj) {
        if(is_object($obj) || is_array($obj)) {
            $ret = (array) $obj;
            foreach($ret as &$item) {
                $item = $this->objectToArray($item);
            }
            return $ret;
        }else {
            return $obj;
        }
    }

    protected function getAPIDefaultDescription(string $routeName)
    {
        return ucwords(str_replace('_', ' ', $routeName));
    }

    protected function getAdditionalApiDescription(string $routeName)
    {
        $apiDescription = '';
        $userRoles = (new UserRolesScope())->getRouteUserRoles($routeName);
        if( empty( $userRoles ) === false )
        {
            $apiDescription .= '<br>**Permitted user roles:** ' . implode(',' , $userRoles);
        }

        $routePermission = !empty(Route::$routePermission[$routeName]) ? Route::$routePermission[$routeName] : null;
        if (empty($routePermission) == false)
        {
            $apiDescription .= '<br>**Permission Needed:** ' . $routePermission;
        }

        $authList = $this->getRouteAuthLists($routeName);
        if (empty($authList) == false)
        {
            $apiDescription .= '<br>**Permitted Auth :** ' . implode(',' , $authList);
        }

        $teamOwnerShip = RouteTeamMap::getTeamNamesForRoute($routeName);
        if (empty($teamOwnerShip) == false)
        {
            $apiDescription .= '<br>**Team Ownership:** ' . $teamOwnerShip;
        }

        return $apiDescription;
    }

    protected function getRouteAuthLists(string $routeName)
    {
        $authList = [];
        $availableAuthName = ['public', 'private', 'internal', 'proxy', 'admin', 'direct'];

        foreach ($availableAuthName as $authName)
        {
            if(in_array($routeName, Route::$$authName) === true)
            {
                $authList[] = $authName;
            }
        }
        return $authList;
    }

}
