<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Models\Base;

class Metric extends Base\Core
{
    const VIRTUAL_ACCOUNT_CREATE_FAILED     = 'virtual_account_create_failed';
    const VIRTUAL_ACCOUNT_CREATE_SUCCESS    = 'virtual_account_create_success';

    const LABEL_TRACE_CODE                  = 'code';
    const LABEL_HAS_BANK_ACCOUNT            = 'has_bank_account';
    const LABEL_HAS_QR_CODE                 = 'has_qr_code';

    protected function getDefaultDimentions(array $input): array
    {
        $receivers = $input[Entity::RECEIVERS];

        if ((is_array($receivers) === true) and
            (isset($receivers[Entity::TYPES]) === true) and
            (is_array($receivers[Entity::TYPES]) === true))
        {
            $types = $receivers[Entity::TYPES];
        }
        else
        {
            $types = [];
        }

        $dimensions = [
            Metric::LABEL_HAS_BANK_ACCOUNT       => in_array(Receiver::BANK_ACCOUNT, $types),
            Metric::LABEL_HAS_QR_CODE            => in_array(Receiver::QR_CODE, $types)
        ];

        return $dimensions;
    }

    public function pushCreateMetrics(array $input)
    {
        $dimensions = $this->getDefaultDimentions($input);

        $this->trace->count(
            Metric::VIRTUAL_ACCOUNT_CREATE_SUCCESS,
            $dimensions
        );
    }

    public function pushFailedMetrics(array $input, \Throwable $e)
    {
        $dimensions = $this->getDefaultDimentions($input);

        $this->trace->count(
            Metric::VIRTUAL_ACCOUNT_CREATE_FAILED,
            array_merge([
                    Metric::LABEL_TRACE_CODE  => $e->getCode(),
                ],
                $dimensions
            )
        );
    }
}
