<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Atos;

use App;
use Carbon\Carbon;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Models\Terminal\Core;
use RZP\Models\Payment\Gateway;
use Illuminate\Support\Facades\Redis;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\TerminalOnboardingDetail;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Gateway\Terminal\GatewayProcessor\BaseGatewayProcessor;


class GatewayProcessor extends BaseGatewayProcessor
{
    const GATEWAY_INPUT          = 'gateway_input';

    // MID
    const ATOS_MID_INDEX_KEY     = 'atos_gateway_terminal_creation_mid_index';
    const ATOS_MID_OFFSET        = 999000000000000;

    const TERMINAL_ONBOARDING_VERIFICATION_MUTEX_LOCK = 'TERMINAL_ONBOARDING_VERIFICATION_MUTEX_LOCK';

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
        list($accountNumber, $ifscCode) = $this->getPartnerBankDetails();

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

    public function validateGatewayInput($gatewayInput, $merchantDetail)
    {
        $gatewayProcessorValidator = new Validator();

        $gatewayProcessorValidator->validateInput(self::GATEWAY_INPUT, $gatewayInput);
    }

    public function addDefaultValueToMerchantDetailIfApplicable(array &$merchantDetail)
    {

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

    public function getLockResource($terminal, $gateway, $gatewayInput)
    {
        return $terminal->getId() . '_' . self::TERMINAL_ONBOARDING_VERIFICATION_MUTEX_LOCK;
    }

    public function getGatewayRequestArrayForCreation($terminal)
    {
        $subMerchant = $terminal->merchant;

        $partnerMerchant = $this->repo->merchant->getPartnerMerchantFromSubMerchantId($subMerchant->getId());

        $merchantDetail = $subMerchant->merchantDetail;

        $partnerMerchantDetail = $partnerMerchant->merchantDetail;
        
        /*
        There is a validation in Mozart that merchant contact_name be present, as its required in ATOS onboarding
        If submerchant's contact_name is empty, we are sending partner's contact_name,
        if that is empty too, we are sending it as Razorpay.
        */
        if (empty($merchantDetail->getContactName()))
        {
            $contactName = is_null($partnerMerchantDetail) === false ? $partnerMerchantDetail->getContactName() : Constants::RAZORPAY;

            if (empty($contactName) === true)
            {
                $contactName = Constants::RAZORPAY;
            }

            $merchantDetail->setContactName($contactName);
        }

        $gatewayRequestArray = [
            'method'                    => "POST",
            'gateway'                   => $terminal->getGateway(),
            'terminal'                  => $terminal->toArrayWithPassword(),
            'merchant'                  => $subMerchant->toArray(),
            'merchant_details'          => $merchantDetail,
            'category_details'          => $this->getCategoryDetails($subMerchant),
            'partner_merchant'          => $partnerMerchant,
            'partner_merchant_details'  => $partnerMerchantDetail,
            'request_details'           => $this->getRequestDetails($terminal),
            'bank_details'              => $partnerMerchant->bankAccount->toArrayPublic(),
            'pricing_details'           => $this->getPricingDetails($terminal),
            'other_details'             => $this->getPartnerOtherDetails(),
        ];
        
        return $gatewayRequestArray;
    }

    public function getGatewayRequestArrayForVerification($terminal)
    {
        $gatewayRequestArray = [
            'method'            =>    "POST",
            'gateway'           =>    $terminal->getGateway(),
            'terminal'          =>    $terminal->toArrayWithPassword(),
            'request_details'   =>    $this->getRequestDetails($terminal),
        ];

        return $gatewayRequestArray;
    }

    // This function is actually being called from inside the queue job
    public function updateTerminalDetailsBasedOnCreationResponse($response, $terminal)
    {
        $terminalOnboardingDetail = $terminal->terminalOnboardingDetail;

        if (isset($response[Constants::SUCCESS]) === true and $response[Constants::SUCCESS] === true)
        {
            $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::PENDING);

            $terminalOnboardingDetail->setVerifyAt(Carbon::now()->getTimestamp());

            $terminal->setStatus(Terminal\Status::PENDING);

            $terminal->save();

            $terminalOnboardingDetail->save();
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, $response);
        }
    }

    public function updateTerminalDetailsBasedOnVerifyResponse($response, $terminal)
    {
        $terminalOnboardingDetail = $terminal->terminalOnboardingDetail;

        $terminalOnboardingDetail->incrementVerifyBucket();

        $status = $this->getTerminalVerificationStatusFromResponse($response);

        if ($status === Constants::CALLBACK_SUCCESSFUL)
        {
            $this->updateTerminalDetailsOnVerifyCallbackSuccesful($terminal, $terminalOnboardingDetail);
        }
        else
        {
            $this->updateTerminalDetailsOnVerifyCallbackFailure($terminal, $terminalOnboardingDetail);
        }

        $terminal->save();

        $terminalOnboardingDetail->save();
    }

    protected function getTerminalVerificationStatusFromResponse($response)
    {
        if (empty($response[Constants::DATA][Constants::STATUS]) === true)
        {
            $status = Constants::CALLBACK_FAILED;
        }
        else
        {
            $status = $response[Constants::DATA][Constants::STATUS];
        }

        return $status;
    }

    protected function updateTerminalDetailsOnVerifyCallbackSuccesful($terminal, $terminalOnboardingDetail)
    {
        $terminal->setStatus(Terminal\Status::ACTIVATED);

        $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::ACTIVATED);

        $terminalOnboardingDetail->setVerifyAt(null);

        $this->app['events']->fire('api.terminal.activated', ['main' => $terminal]);
    }

    protected function updateTerminalDetailsOnVerifyCallbackFailure($terminal, $terminalOnboardingDetail)
    {
        $timeStamp = (Carbon::now()->addMinutes(Constants::ATOS_ACTIVATION_NEXT_RETRY_MINS))->getTimestamp();

        $terminalOnboardingDetail->setVerifyAt($timeStamp);

        if($terminalOnboardingDetail->getVerifyBucket() >= Constants::ATOS_ACTIVATION_RETRY_LIMIT)
        {
            $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::ACTIVATION_FAILED);

            $terminal->setStatus(Terminal\Status::FAILED);

            $this->app['events']->fire('api.terminal.failed', ['main' => $terminal]);
        }
    }

    protected function getPartnerBankDetails()
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        $partner = $this->repo->merchant->findOrFail($partnerMerchantId);

        $partnerBankAccount = $partner->bankAccount;

        $partnerBankAccountNumber = $partnerBankAccount->getAccountNumber();

        $partnerBankIfscCode = $partnerBankAccount->getIfscCode();

        return [$partnerBankAccountNumber, $partnerBankIfscCode];
    }

    protected function getRequestDetails($terminal)
    {
        $reqDetails['req_rrn'] = $terminal->terminalOnboardingDetail->getReqRrn();

        return $reqDetails;
    }

    protected function getCategoryDetails($merchant)
    {
        $mcc = (int) $merchant->getCategory();

        $details = MerchantDetail\MccTccMapping::getTccFromMcc($mcc);

        $details['mcc'] = $mcc;

        return $details;
    }

    protected function getPricingDetails($terminal)
    {
        $merchant = $terminal->merchant;

        $mcc = (int) $merchant->getCategory();

        return MerchantDetail\FreechargeAtosOnboardingDetails::getMccPricing($mcc);
    }

    // TODO: This should be in partner processor, not gateway processor
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

    protected function getPartnerOtherDetails()
    {
        // TODO: Currently other details are hardcoded for freecharge, 
        // need to make this generic
        return MerchantDetail\FreechargeAtosOnboardingDetails::OTHER_DETAILS;
    }

}
