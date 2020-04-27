<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Hitachi;

use Config;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Type;
use RZP\Models\Terminal\Core;
use RZP\Models\Terminal\Entity;
use RZP\Constants\IndianStates;
use RZP\Constants\Entity as Constants;
use RZP\Models\Gateway\Terminal\Constants as TerminalConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailConstants;
use RZP\Models\Gateway\Terminal\GatewayProcessor\BaseGatewayProcessor;

class GatewayProcessor extends BaseGatewayProcessor
{

    const GATEWAY_INPUT         = 'gateway_input';
    const MERCHANT_DETAIL_INPUT = 'merchant_detail_input';
    const HITACHI_TID_OFFSET    = 10000;
    const HITACHI_INDEX_KEY     = 'hitachi_gateway_terminal_creation_index';
    const HITACHI_TERMINAL_TID_PREFIX = '38R';
    const HITACHI_TERMINAL_MID_PREFIX = '38RR000000';
    /*
     * Maintain a list of blacklisted MCCs (merchant categories) in the code(hard coded),
     * and skip Hitachi automatic onboarding for merchants belonging to these categories.
     * Use case is high-risk merchants, who should not be onboarded via Hitachi.
     */
    const HITACHI_BLACKLISTED_MCC = ['5962', '5966', '5967', '7995', '5912', '5122'];

    public function __construct()
    {
        parent::__construct();

        $this->gateway = Constants::HITACHI;
    }

    public function getInputValue($gateWayInput, $merchant)
    {
        $newIndex = $this->redis->incr(self::HITACHI_INDEX_KEY);

        $this->checkValue($newIndex, $merchant);

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

    public function addDefaultValueToMerchantDetailIfApplicable(array &$merchantDetail)
    {
        if ($this->shouldUpdateDefaultValuesInMerchantDetail($merchantDetail) === true) {

            $merchantDetail[MerchantDetailConstants::BUSINESS_OPERATION_STATE] = TerminalConstants::DEFAULT_BUSINESS_OPERATION_STATE;

            $merchantDetail[MerchantDetailConstants::BUSINESS_OPERATION_ADDRESS] = TerminalConstants::DEFAULT_BUSINESS_OPERATION_ADDRESS;

            $merchantDetail[MerchantDetailConstants::BUSINESS_OPERATION_PIN] = TerminalConstants::DEFAULT_BUSINESS_OPERATION_PIN;

            $merchantDetail[MerchantDetailConstants::BUSINESS_OPERATION_CITY] = TerminalConstants::DEFAULT_BUSINESS_OPERATION_CITY;
        }
    }

    protected function shouldUpdateDefaultValuesInMerchantDetail($merchantDetail)
    {
        $shouldUpdate = false;

        try
        {
            $gatewayProcessorValidator = new Validator();

            $gatewayProcessorValidator->setStrictFalse();

            $gatewayProcessorValidator->validateInput(self::MERCHANT_DETAIL_INPUT, $merchantDetail);
        }
        catch (Exception\BadRequestValidationFailureException $exception)
        {
            if ((empty($merchantDetail[MerchantDetailConstants::BUSINESS_NAME]) === true)
                or (empty($merchantDetail[MerchantDetailConstants::BUSINESS_DBA]) === true))
            {
                throw $exception;
            }

            $shouldUpdate = true;
        }

        return $shouldUpdate;
    }

    public function processTerminalData($terminalData, $merchant)
    {
        $this->setTerminalType($terminalData);

        return (new Core)->create($terminalData, $merchant);
    }

    public function validateGatewayInput($gatewayInput, $merchantDetail)
    {
        $gatewayProcessorValidator = new Validator();

        $gatewayProcessorValidator->validateInput(self::GATEWAY_INPUT, $gatewayInput);

        $gatewayProcessorValidator->setStrictFalse();

        $gatewayProcessorValidator->validateInput(self::MERCHANT_DETAIL_INPUT, $merchantDetail);
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

    private function checkValue($index, $merchant)
    {
        if ($index > 80000)
        {
            $this->trace->critical(TraceCode::MERCHANT_ONBOARD_INDEX_ABOVE_THRESHOLD, ['gateway' => Constants::HITACHI]);

            $data = [
                'index'                 => $index,
                'merchant_id'           => $merchant->getId(),
                'merchant_name'         => $merchant->getName(),
            ];

            $this->app['slack']->queue(
                TraceCode::MERCHANT_ONBOARD_INDEX_ABOVE_THRESHOLD, $data,
                [
                    'channel'               => Config::get('slack.channels.tech_alerts'),
                    'username'              => 'alerts',
                    'icon'                  => ':x:'
                ]
            );
        }
    }

    public function checkDbConstraints($input, $merchant)
    {
        $this->repo->beginTransactionAndRollback(
            function() use ($input, $merchant)
            {
                $mcc = $input['mcc'] ?? $merchant->getCategory();

                $terminalData = [
                    'category'            => $mcc,
                    'gateway'             => 'hitachi',
                    'gateway_merchant_id' => '38RRR1000000000',
                    'gateway_terminal_id' => '38RR1001',
                    'gateway_acquirer'    => 'ratn',
                    'currency'            => $input['currency_code'],
                ];

                $this->setTerminalType($terminalData);

                // this function is running a transaction block and is rolled back
                // however, within the transaction a terminal is created
                // the newly created terminal is getting synced to terminals service
                // but we are unable to rollback changes on terminals service whenever its rolled back on API
                // to solve this, we avoid syncing by calling create() with shouldSync=false
                (new Core)->create($terminalData, $merchant, false);
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
