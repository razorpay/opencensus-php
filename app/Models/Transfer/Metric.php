<?php

namespace RZP\Models\Transfer;

use App;
use RZP\Models\Base;

class Metric extends Base\Core
{
    use Base\Traits\MetricTrait;

    //Metric Names
    const TRANSFER_CREATE_FAILED                   = 'transfer_create_failed';
    const TRANSFER_CREATE_SUCCESS                  = 'transfer_create_success';
    const TRANSFER_REVERSAL_SUCCESS                = 'transfer_reversal_success';
    const TRANSFER_REVERSAL_FAILED                 = 'transfer_reversal_failed';
    const TRANSFER_ROUTE                           = 'transfer_route';
    const TRANSFER_TO_TYPE                         = 'transfer_to_type';

    public function pushCreateSuccessMetrics(array $input = [])
    {
        $dimensions = $this->getCreateDefaultDimensions($input);

        $this->trace->count(self::TRANSFER_CREATE_SUCCESS, $dimensions);
    }

    public function pushCreateFailedMetrics(\Throwable $e)
    {
        $this->pushExceptionMetrics($e, self::TRANSFER_CREATE_FAILED, $this->getCreateDefaultDimensions());
    }

    public function pushReversalSuccessMetrics()
    {
        $dimensions = [
            self::TRANSFER_ROUTE => $this->getRouteName(),
        ];
        $this->trace->count(self::TRANSFER_REVERSAL_SUCCESS, $dimensions);
    }

    public function pushReversalFailedMetrics(\Throwable $e)
    {
        $dimensions = [
            self::TRANSFER_ROUTE => $this->getRouteName(),
        ];
        $this->pushExceptionMetrics($e, self::TRANSFER_REVERSAL_FAILED, $dimensions);
    }

    private function getCreateDefaultDimensions(array $input = [])
    {
        return $dimensions = [
            self::TRANSFER_ROUTE   => $this->getRouteName(),
            self::TRANSFER_TO_TYPE => isset($input[ToType::ACCOUNT]) ? ToType::ACCOUNT : ToType::CUSTOMER
        ];
    }

    private function getRouteName()
    {
        return $this->app['api.route']->getCurrentRouteName();
    }
}
