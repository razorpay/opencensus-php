<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Jobs\AppsRiskCheck;

class Service extends Base\Service
{
    protected $validator;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator();

        $this->core = new Core();

        $this->mutex = $this->app['api.mutex'];
    }

    public function validate(array $input) : array
    {
        $this->trace->info(
            TraceCode::VIRTUAL_VPA_PREFIX_VALIDATE_REQUEST,
            $input
        );

        $isValid = false;

        try
        {
            $this->validator->validateInput('validate', $input);

            $this->convertPrefixToLower($input);

            $isValid = $this->core->validatePrefixAvailability($input[Entity::PREFIX]);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);
        }

        $this->app['diag']->trackVirtualVpaPrefixEvent(
            EventCode::VIRTUAL_VPA_PREFIX_VALIDATE,
            $this->merchant,
            null,
            [
                'prefix'    => $input[Entity::PREFIX],
                'is_valid'  => $isValid,
            ]
        );

        return [
            'is_valid'  => $isValid,
        ];
    }

    public function savePrefix(array $input) : array
    {
        $this->trace->info(
            TraceCode::VIRTUAL_VPA_PREFIX_CHANGE_REQUEST,
            $input
        );

        $this->validator->validateInput('validate', $input);

        $this->convertPrefixToLower($input);

        $virtualVpaPrefix = $this->mutex->acquireAndRelease(
            'virtual_vpa_prefix_' . $this->merchant->getId(),
            function () use ($input)
            {
                return $this->mutex->acquireAndRelease(
                    'virtual_vpa_prefix_' . $input[Entity::PREFIX],
                    function () use ($input)
                    {
                        return $this->core->savePrefix($input);
                    },
                    10,
                    ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
                );
            },
            10,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        $this->dispatchPrefixForRiskCheck($virtualVpaPrefix);

        return [
            'prefix'    => $virtualVpaPrefix->getPrefix(),
        ];
    }

    protected function convertPrefixToLower(& $input) : void
    {
        $input[Entity::PREFIX] = strtolower($input[Entity::PREFIX]);
    }

    protected function dispatchPrefixForRiskCheck($virtualVpaPrefix)
    {
        if (($this->isRazorxEnabledForRiskCheck() === false) or
            ($this->merchant->isFeatureEnabled(Feature\Constants::APPS_EXTEMPT_RISK_CHECK) === true))
        {
            return true;
        }

        $request = [
            'client_type' => 'smart_collect',
            'entity_id'   => $virtualVpaPrefix->getId(),
            'fields'      => [
                [
                    'key'        => Entity::PREFIX,
                    'value'      => $virtualVpaPrefix->getPrefix(),
                    'list'       => 'high_risk_list',
                    'config_key' => Entity::PREFIX,
                ],
            ]
        ];

        try
        {
            $this->trace->info(
                TraceCode::APPS_RISK_CHECK_SQS_PUSH_INIT,
                $request);

            AppsRiskCheck::dispatch($this->mode, $request);
        }
        catch (\Exception $e)
        {
            $this->trace->critical(
                TraceCode::APPS_RISK_CHECK_SQS_PUSH_FAILED,
                $request);
        }
    }

    public function isRazorxEnabledForRiskCheck()
    {
        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::APPS_RISK_CHECK,
            $this->mode);

        return ($variant === 'on');
    }
}
