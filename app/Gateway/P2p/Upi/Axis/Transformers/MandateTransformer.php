<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Models\P2p\Mandate\Flow;
use RZP\Models\P2p\Mandate\Type;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Models\P2p\Mandate\Status;
use RZP\Gateway\P2p\Upi\Axis\ErrorMap;
use RZP\Models\P2p\Mandate\UpiMandate;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;

/**
 * Class MandateTransformer
 *
 * @package RZP\Gateway\P2p\Upi\Axis\Transformers
 */
class MandateTransformer extends TransactionTransformer
{

    /**
     * Transform basic mandate fields
     * @return array
     */
    public function transform(): array
    {
        switch ($this->action)
        {
            case UpiAction::CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED:
                $output = [
                    Entity::TYPE                => Type::COLLECT,
                    Entity::FLOW                => Flow::DEBIT,
                    Entity::INTERNAL_STATUS     => Status::REQUESTED,
                ];

                break;
        }

        return $output;
    }

    /**
     * Transform incoming mandate data from gateway
     * This uses already transformed data by upiMandateTransformer
     *
     * @return array
     */
    public function transformIncoming(): array
    {
        $output = $this->transform();

        $this->checkForError($output);

        $mandate = $this->input[Entity::MANDATE];

        return array_merge($mandate, $output);
    }

    /**
     * Check the error and set the internal_status and internal_error_code based on gateway_error_code.
     *
     * @param $output
     */
    public function checkForError(& $output)
    {
        $gatewayCode = $this->input[UpiMandate\Entity::GATEWAY_ERROR_CODE];

        if ($gatewayCode === '00')
        {
            return;
        }

        $internalErrorCode = ErrorMap::gatewayMap($gatewayCode);

        if (in_array($gatewayCode, ErrorMap::$pendingErrors, true) === true)
        {
            $output[Entity::INTERNAL_STATUS]     = Status::PENDING;
        }
        else if (in_array($gatewayCode, ErrorMap::$rejectedErrors, true) === true)
        {
            $output[Entity::INTERNAL_STATUS]     = Status::REJECTED;
            $output[Entity::INTERNAL_ERROR_CODE] = $internalErrorCode;
        }
        else if (in_array($gatewayCode, ErrorMap::$expiredErrors, true) === true)
        {
            $output[Entity::INTERNAL_STATUS]     = Status::EXPIRED;
            $output[Entity::INTERNAL_ERROR_CODE] = $internalErrorCode;
        }
        else
        {
            $output[Entity::INTERNAL_STATUS]     = Status::FAILED;
            $output[Entity::INTERNAL_ERROR_CODE] = $internalErrorCode;
        }
    }
}
