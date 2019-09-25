<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Atos;

use App;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Exception\ServerErrorException;
use RZP\Models\Terminal\Core;
use RZP\Models\Payment\Gateway;
use Illuminate\Support\Facades\Redis;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\TerminalOnboardingDetail;
use RZP\Models\Gateway\Terminal\GatewayProcessor\BaseGatewayProcessor;


class GatewayProcessor extends BaseGatewayProcessor
{
    const GATEWAY_INPUT                        = 'gateway_input';

    // MID
    const ATOS_MID_INDEX_KEY     = 'atos_gateway_terminal_creation_mid_index';
    const ATOS_MID_OFFSET        = 999000000000000;

    protected $tidGenerator;

    protected $redisMidKey;
    
    public function __construct()
    {
        parent::__construct();

        $this->app = App::getFacadeRoot();

        $this->redis = Redis::Connection();

        $this->trace = $this->app['trace'];

        $this->tidGenerator = new TidGenerator();

        $this->redisMidKey = $this->mode . '_' . self::ATOS_MID_INDEX_KEY;
    }

    // GetInput Value (params) for terminal creation
    public function getInputValue($gatewayInput, $subMerchant)
    {           
        list($accountNumber, $ifscCode) = $this->getBankDetails();

        $terminalData = [
            Terminal\Entity::STATUS              => Terminal\Status::CREATED, 
            Terminal\Entity::ENABLED             => 0,
            Terminal\Entity::CATEGORY            => $subMerchant->getCategory(),
            Terminal\Entity::TYPE                => $this->getTerminalType(),
            Terminal\Entity::ACCOUNT_NUMBER      => $accountNumber,
            Terminal\Entity::IFSC_CODE           => $ifscCode,
            Terminal\Entity::GATEWAY             => Gateway::ATOS,
            Terminal\Entity::GATEWAY_MERCHANT_ID => $this->generateMid($subMerchant),
            Terminal\Entity::GATEWAY_TERMINAL_ID => $this->tidGenerator->generateTid(),
        ];

        $terminalData[Terminal\Entity::MC_MPAN] = $gatewayInput[Constants::MPAN][Constants::MASTERCARD];
        
        $terminalData[Terminal\Entity::VISA_MPAN] = $gatewayInput[Constants::MPAN][Constants::VISA];

        $terminalData[Terminal\Entity::RUPAY_MPAN] = $gatewayInput[Constants::MPAN][Constants::RUPAY];

        return $terminalData;
    }

    public function processTerminalData($terminalData, $merchant)
    {
        $terminal = (new Core)->create($terminalData, $merchant);

        (new TerminalOnboardingDetail\Core)->create([], $terminal);

        return $terminal;
    }

    public function validateGatewayInput($gatewayInput, $merchant)
    {
        $gatewayProcessorValidator = new Validator();

        $gatewayProcessorValidator->validateInput(self::GATEWAY_INPUT, $gatewayInput);
    }

    public function checkDbConstraints($input, $merchant)
    {
        $this->repo->beginTransactionAndRollback(
            function() use ($input, $merchant)
            {
                $terminalData = [
                    'gateway'             => 'atos',
                    'gateway_merchant_id' => '999999999999',
                    'gateway_terminal_id' => '12345678',
                    'mc_mpan'             => $input[Constants::MPAN][Constants::MASTERCARD],
                    'visa_mpan'           => $input[Constants::MPAN][Constants::VISA],
                    'rupay_mpan'          => $input[Constants::MPAN][Constants::RUPAY],

                ];

                (new Core)->create($terminalData, $merchant);
            });
    }

    // Leaving empty because its an abstract method in BaseGatewayProcessor class
    public function getLockResource($merchant, $gateway, $gatewayInput)
    {

    }

    protected function getBankDetails()
    {        
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        $partner = $this->repo->merchant->findOrFail($partnerMerchantId);

        $partnerBankAccount = $partner->bankAccount;

        $partnerBankAccountNumber = $partnerBankAccount->getAccountNumber();

        $partnerBankIfscCode = $partnerBankAccount->getIfscCode();

        return [$partnerBankAccountNumber, $partnerBankIfscCode];
    }

    // This is a partner property, currently ATOS supporting only direct_settlements
    protected function getTerminalType($type = null)
    {
        if ($type === null)
        {
            $type = [
                Terminal\Type::DIRECT_SETTLEMENT_WITH_REFUND  => '1',
            ];
        }

        return $type;
    }

    protected function generateMid($subMerchant)
    {
        $params = [ Terminal\Entity::MERCHANT_ID => $subMerchant->getId(), 
                    Terminal\Entity::GATEWAY     =>  Gateway::ATOS ];

        // Existing terminals of this submerchant of this gateway
        $existingTerminals = $this->repo->terminal->getByParams($params);

        if (count($existingTerminals) > 0)
        {  
            return $existingTerminals->first()->getGatewayMerchantId();
        }

        $newMid = self::ATOS_MID_OFFSET + $this->redis->incr($this->redisMidKey);

        return $newMid;
    }
}
