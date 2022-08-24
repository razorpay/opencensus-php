<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;


use RZP\Models\P2p\Mandate\Flow;
use RZP\Models\P2p\Mandate\Type;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Models\P2p\Mandate\Status;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\ErrorMap;
use RZP\Models\P2p\Mandate\UpiMandate;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
use RZP\Gateway\P2p\Upi\Axis\Actions\MandateAction;

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
        $output = [];
        switch ($this->action)
        {
            case UpiAction::CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED:
                $output = [
                    Entity::TYPE                => Type::COLLECT,
                    Entity::FLOW                => Flow::DEBIT,
                    Entity::INTERNAL_STATUS     => Status::REQUESTED,
                    Entity::STATUS              => Status::REQUESTED,
                ];
                break;

            case UpiAction::CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED:
                $output = [
                    Entity::TYPE                => Type::COLLECT,
                    Entity::FLOW                => Flow::DEBIT,
                    Entity::INTERNAL_STATUS     => Status::UPDATED,
                    Entity::STATUS              => Status::REQUESTED,
                ];
                break;

            case UpiAction::MANDATE_STATUS_UPDATE:
                $output = [
                    Entity::TYPE                => Type::COLLECT,
                    Entity::FLOW                => Flow::DEBIT,
                ];
                break;

            case UpiAction::CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED:
                $output = [
                    Entity::TYPE                => Type::COLLECT,
                    Entity::FLOW                => Flow::DEBIT,
                    Entity::INTERNAL_STATUS     => Status::PAUSED,
                    Entity::STATUS              => Status::PAUSED,
                ];
                break;

            case MandateAction::UPDATE_OR_REVOKE_MANDATE:
                $output = [
                    Entity::TYPE                            => Type::COLLECT,
                    Entity::FLOW                            => Flow::DEBIT,
                    Entity::ACTION                          => MandateAction::UPDATE_OR_REVOKE_MANDATE,
                    Entity::STATUS                          => Status::REVOKED,
                    Entity::INTERNAL_STATUS                 => Status::REVOKED,
                ];
                break;
        }

        // switch in case of mandate status
        if(isset($this->input[Fields::STATUS]))
        {
            switch ($this->input[Fields::STATUS])
            {
                case Status::APPROVED:
                    $output = [
                        Entity::TYPE                             => Type::COLLECT,
                        Entity::FLOW                             => Flow::DEBIT,
                        Entity::ACTION                           => MandateAction::APPROVE_DECLINE_MANDATE,
                        Entity::STATUS                           => Status::APPROVED,
                        Entity::INTERNAL_STATUS                  => Status::APPROVED,
                    ];
                    break;

                case Status::REJECTED:
                    $output = [
                        Entity::TYPE                             => Type::COLLECT,
                        Entity::FLOW                             => Flow::DEBIT,
                        Entity::ACTION                           => MandateAction::APPROVE_DECLINE_MANDATE,
                        Entity::STATUS                           => Status::REJECTED,
                        Entity::INTERNAL_STATUS                  => Status::REJECTED,
                    ];
                    break;

                case Status::PAUSED:
                    $output = [
                        Entity::TYPE                            => Type::COLLECT,
                        Entity::FLOW                            => Flow::DEBIT,
                        Entity::ACTION                          => MandateAction::PAUSE_UNPAUSE_MANDATE,
                        Entity::STATUS                          => Status::PAUSED,
                        Entity::INTERNAL_STATUS                 => Status::PAUSED,
                    ];
                    break;

                case Status::UNPAUSED:
                    $output = [
                        Entity::TYPE                            => Type::COLLECT,
                        Entity::FLOW                            => Flow::DEBIT,
                        Entity::ACTION                          => MandateAction::PAUSE_UNPAUSE_MANDATE,
                        Entity::STATUS                          => Status::APPROVED,
                        Entity::INTERNAL_STATUS                 => Status::APPROVED,
                    ];
                    break;
            }
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
     * This is the method to transform sdk response
     * @return array
     */
    public function transformSdk(): array
    {
        $request = $this->input[Entity::MANDATE];

        $output = $this->transform();

        $this->checkForError($output);

        return array_merge($request, $output);
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
