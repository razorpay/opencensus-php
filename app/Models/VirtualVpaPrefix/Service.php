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

        return [
            'prefix'    => $virtualVpaPrefix->getPrefix(),
        ];
    }

    protected function convertPrefixToLower(& $input) : void
    {
        $input[Entity::PREFIX] = strtolower($input[Entity::PREFIX]);
    }
}
