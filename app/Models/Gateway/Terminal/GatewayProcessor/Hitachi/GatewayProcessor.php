<?php
/**
 * Created by PhpStorm.
 * User: shivamyuvraj
 * Date: 2019-01-28
 * Time: 19:29
 */

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Hitachi;

use RZP\Models\Terminal\Type;
use RZP\Models\Terminal\Core;
use RZP\Models\Terminal\Entity;
use RZP\Constants\Entity as Constants;
use RZP\Models\Gateway\Terminal\GatewayProcessor\BaseGatewayProcessor;

class GatewayProcessor extends BaseGatewayProcessor
{

    const GATEWAY_INPUT   = 'gateway_input';
    const HITACHI_TID_OFFSET = 10000;
    const HITACHI_INDEX_KEY = 'hitachi_gateway_terminal_creation_index';
    const HITACHI_TERMINAL_TID_PREFIX = '38R';
    const HITACHI_TERMINAL_MID_PREFIX = '38RR000000';

    public function __construct()
    {
        parent::__construct();

        $this->gateway = Constants::HITACHI;
    }

    public function getInputValue($gateWayInput, $merchant)
    {
        $newIndex = $this->redis->incr(self::HITACHI_INDEX_KEY);

        if ($newIndex > 99999)
        {
            $this->trace->info(
                TraceCode::MERCHANT_ONBOARD_INDEX_OUT_OF_BOUND,
                [
                    'gateway'   => Constants::HITACHI,
                ]);

            throw new Exception\RuntimeException('Index value out of bound for hitachi terminal creation');
        }

        if ($newIndex > 80000)
        {
            $this->trace->critical(TraceCode::MERCHANT_ONBOARD_INDEX_ABOVE_THRESHOLD, ['gateway' => Constants::HITACHI]);
        }

        $newId = self::HITACHI_TID_OFFSET + $newIndex;

        $newMid = self::HITACHI_TERMINAL_MID_PREFIX . $newId;

        $newTid = self::HITACHI_TERMINAL_TID_PREFIX . $newId;

        $mcc = $gateWayInput['mcc'] ?? $merchant->getCategory();

        $currencyCode = $gateWayInput['currency_code'];

        $transMode = $gateWayInput['trans_mode'];

        $gateWayInput = [
            'mid'           => $newMid,
            'tid'           => $newTid,
            'mcc'           => $mcc,
            'currency_code' => $currencyCode,
            'trans_mode'    => $transMode
        ];

        return $gateWayInput;
    }

    public function processTerminalData($terminalData, $merchant)
    {
        $this->setTerminalType($terminalData);

        return (new Core)->create($terminalData, $merchant);
    }

    public function validateGatewayInput($gatewayInput, $merchant)
    {
        (new Validator)->validateInput(self::GATEWAY_INPUT, $gatewayInput);

        // any check specific to merchants
    }

    private function setTerminalType(&$terminalData, $type = null)
    {
        if ($type === null)
        {
            $type = [
                Type::NON_RECURRING         => '1',
                Type::RECURRING_3DS         => '1',
                Type::RECURRING_NON_3DS     => '1',
                Type::DEBIT_RECURRING       => '1',
            ];
        }

        $terminalData[Entity::TYPE] = $type;
    }

    public function checkDbConstraints($input, $merchant)
    {
        $this->repo->beginTransactionAndRollback(
            function() use ($input, $merchant)
            {
                $terminalData = [
                    'category'            => $input['mcc'],
                    'gateway'             => 'hitachi',
                    'gateway_merchant_id' => $input['mid'],
                    'gateway_terminal_id' => $input['tid'],
                    'gateway_acquirer'    => 'ratn',
                    'currency'            => $input['currency_code'],
                ];

                $this->setTerminalType($terminalData);

                (new Core)->create($terminalData, $merchant);
            });
    }

    public function getLockResource($merchant, $gateway, $gatewayInput)
    {
        $currencyCode = $gatewayInput['currency_code'];

        $transMode = $gatewayInput['trans_mode'];

        $mcc = $gateWayInput['mcc'] ?? $merchant->getCategory();

        return 'merchantOnBoard_' . $merchant->getId() . '_' . $gateway . '_' . $currencyCode . '_' . $transMode . '_' . $mcc;
    }
}
