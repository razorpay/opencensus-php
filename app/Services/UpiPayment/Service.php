<?php

namespace RZP\Services\UpiPayment;

use App;
use RZP\Exception;
use RZP\Services\UpiPayment\Util;

/**
 * Service implements the UPI Payments service client
 */
class Service
{
    /**
     * App container
     *
     * @var mixed
     */
    protected $app;

    /**
     * Used for tracing
     *
     * @var mixed
     */
    protected $trace;

    /**
     * Application Config
     *
     * @var array
     */
    protected $config;

    /**
     * Initiates the app container, trace and UPS config
     */
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.upi_payment_service');
    }

    /**
     * Action handles all the action based payment requests
     *
     * @param  string $gateway
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    public function action(string $gateway, string $action, array $input) : array
    {
        $content = $this->buildRequestBody($action, $input);

        // Other processing to be added here.

        // Add authorize repsonse since other actions are not supported yet
        return ['data' => ['vpa' => 'razorpay@airtel']];
    }

    /**
     * buildRequestBody builds the request body for UPS
     *
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    protected function buildRequestBody(string $action, array $input): array
    {
        $data = [];

        switch ($action)
        {
        case Action::AUTHORIZE:
            $data = $this->getRequestBodyForAuthorize($action, $input);
            break;

        default:
            throw new Exception\LogicException(
                'No supported actions found for UPS',
                null,
                ['action' => $action]
            );
                break;
        }

        return $data;
    }

    /**
     * Builds Request Body for Authorize action
     *
     * @param  string $gateway
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    protected function getRequestBodyForAuthorize(string $action, array $input): array
    {
        Util::convertInputToArray($input);

        $content = [
            Request::PAYMENT    => $input['payment'] ?? null,
            Request::METADATA   => $input['upi'] ?? null,
            Request::TERMINAL   => $input['terminal'] ?? null,
            Request::MERCHANT   => $input['merchant'] ?? null,
            Request::ACTION     => $action,
        ];

        return $content;
    }
}
