<?php

namespace RZP\Http\Middleware;

use Closure;
use RZP\Trace\TraceCode;
use RZP\Models\User\Role;
use RZP\Models\User\BankingRole;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\RazorxTreatment;
use phpDocumentor\Reflection\Types\Self_;
use RZP\Http\Response\UserRoleBasedResponse;
use RZP\Http\Response\ActivationDetailsResponse;
use ApiResponse;

class UserRoleBasedResponseFilter
{

    const ROUTE_FILTER_MAPPING = [
        "merchant_activation_details" => ActivationDetailsResponse::class
    ];

    const ROUTE_FILTER_ROLES_MAPPING = ["merchant_activation_details" => [Role::SELLERAPP,
                                                                          Role::OPERATIONS,
                                                                          Role::FINANCE,
                                                                          Role::SUPPORT]
    ];

    /** @var BasicAuth */
    protected $ba;

    private   $razorx;

    protected $router;

    /**
     * Trace instance used for tracing
     *
     * @var Trace
     */
    protected $trace;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->router = $app['router'];

        $this->razorx = $app->razorx;

        $this->ba = $app['basicauth'];

        $this->trace = $app['trace'];
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response = json_decode(json_encode($response), true);

        $response = $response['original'];

        try
        {
            $routeName = $this->router->currentRouteName();

            $userRole = $this->ba->getUserRole() ?? Role::OWNER;

            if ($this->isFilterRequired($routeName, $userRole))
            {
                $className              = self::ROUTE_FILTER_MAPPING[$routeName];
                $fieldRoleMappingObject = new $className();
                $fieldRoleMapping       = $fieldRoleMappingObject->getFieldRoleMapping();

                $this->trace->info(TraceCode::FILTER_RESPONSE_BASED_ON_ROLE, ["merchant_id" => $this->ba->getMerchant()->getId()]);

                $response = $this->filterResponse($response, $fieldRoleMapping, $userRole);
            }

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::INFO,
                                         TraceCode::FILTER_RESPONSE_BASED_ON_ROLE,
                                         [
                                             'experiment' => RazorxTreatment::RESPONSE_FIELDS_FILTERING_FOR_ROLES,
                                             'user_role'  => $this->ba->getUserRole(),
                                         ]);
        }

        return ApiResponse::json($response);

    }

    protected function isFilterRequired($routeName, $userRole)
    {
        $variant = $this->razorx->getTreatment($this->ba->getMerchant()->getId(),
                                               RazorxTreatment::RESPONSE_FIELDS_FILTERING_FOR_ROLES,
                                               $this->ba->getMode(), 2);

        $this->trace->info(TraceCode::FILTER_RESPONSE_BASED_ON_ROLE, ['variant' => $variant, 'userRole' => $userRole]);

        if ((strtolower($variant) === 'on') === false)
        {
            return false;
        }
        if (array_key_exists($routeName, self::ROUTE_FILTER_MAPPING) === false)
        {
            return false;
        }
        if (array_key_exists($routeName, self::ROUTE_FILTER_ROLES_MAPPING) === false)
        {
            return false;
        }
        if (empty($userRole) === true or in_array($userRole, self::ROUTE_FILTER_ROLES_MAPPING[$routeName]) === false)
        {
            return false;
        }

        return true;
    }

    public function filterResponse(array $data, $roleMapping, $userRole, string $inputKey = ""): ?array
    {
        $response = null;

        foreach ($data as $key => $val)
        {

            $currentKey = $inputKey === "" ? $key : $inputKey . '.' . $key;

            if (array_key_exists($currentKey, $roleMapping))
            {
                if ($this->isRestrictedFieldForUserRole($currentKey, $userRole, $roleMapping) === false)
                {
                    $response[$key] = $val;
                }
                continue;
            }

            if (is_array($val))
            {
                if (count($val) > 0)
                {
                    $response[$key] = $this->filterResponse($val, $roleMapping, $userRole, $currentKey);
                }
            }
        }

        return $response;
    }

    protected function isRestrictedFieldForUserRole($field, $userRole, $roleMapping): bool
    {
        $restricted = false;

        if (in_array($userRole, $roleMapping[$field]) === false)
        {
            $restricted = true;
        }

        return $restricted;
    }
}
