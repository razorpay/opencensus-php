<?php


namespace RZP\Http\Middleware;


use Closure;
use ApiResponse;
use RZP\Diag\EventCode;
use RZP\Exception\BaseException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

/**
 * Class FailureEventsInterceptor
 *
 *  Handles an outgoing response/exception/errors and sets accordingly the data to push to data lake
 *  The routes with which this middleware is attached to are defined
 *  in FAILURE_EVENTS_INTERCEPTOR_ROUTES in route.php file
 *
 * @package RZP\Http\Middleware
 */
class FailureEventsInterceptor
{
    protected $app;

    protected $router;

    protected $trace;

    protected $config;

    protected $ba;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $app['basicauth'];

        $this->config = $app['config']->get('database');

        $this->router = $this->app['router'];

        $this->trace = $this->app['trace'];
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->getStatusCode() >= 400)
        {
            $routeName = $this->router->currentRouteName();

            $event = $this->getEventCodeNameFromRoute($routeName);

            if ($event !== null)
            {
                $exception = json_decode($response->getContent(), true);

                $errorMessage = $exception['description'] ?? "";

                $merchant = $this->ba->getMerchant();

                $this->app['diag']->trackOnboardingEvent($event, $merchant, new BaseException($errorMessage, $response->getStatusCode()), $exception);
            }
        }

        return $response;
    }

    private function getEventCodeNameFromRoute(string $routeName)
    {
        $eventCodeNameRouteMappings = [
            'user_register'                    =>
                [
                    'event_code' => EventCode::SIGNUP_CREATE_ACCOUNT_FAILED,
                ],
            'merchant_edit_pre_signup_details' =>
                [
                    'event_code' => EventCode::SIGNUP_FINISH_SIGNUP_FAILED,
                ],
            'user_confirm_by_data'             =>
                [
                    'event_code' => EventCode::SIGNUP_EMAIL_VERIFICATION_FAILED,
                ],
            'merchant_instant_activation_post' =>
                [
                    'event_code' => EventCode::ACT_SUBMIT_FORM_FAILED,
                ],
            'merchant_activation_save'         =>
                [
                    'event_code' => EventCode::KYC_SAVE_MODIFICATIONS_FAILED,
                ],
            'merchant_activation_upload_file'  =>
                [
                    'event_code' => EventCode::KYC_UPLOAD_DOCUMENT_FAILED,
                ],
            'merchant_document_upload'         =>
                [
                    'event_code' => EventCode::KYC_UPLOAD_DOCUMENT_FAILED,
                ],
            'user_login'                       =>
                [
                    'event_code' => EventCode::MERCHANT_ONBOARDING_LOGIN_FAILURE,
                ],
            'user_reset_password_create'       =>
                [
                    'event_code' => EventCode::MERCHANT_ONBOARDING_RESET_PASSWORD_FAILURE,
                ],
            'merchant_sub_create'              =>
                [
                    'event_code' => EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP_ERROR,
                ],
            'merchant_sub_create_batch'              =>
                [
                    'event_code' => EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP_ERROR,
                ],
        ];

        if (array_key_exists($routeName, $eventCodeNameRouteMappings) === true)
        {
            return $eventCodeNameRouteMappings[$routeName]['event_code'];
        }

        return null;
    }
}
