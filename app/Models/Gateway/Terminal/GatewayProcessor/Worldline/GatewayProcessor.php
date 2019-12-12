<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Worldline;

use App;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Models\Terminal\Core;
use RZP\Models\Payment\Gateway;
use Illuminate\Support\Facades\Redis;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\TerminalOnboardingDetail;
use RZP\Models\Gateway\Terminal\GatewayProcessor\BaseGatewayProcessor;


class GatewayProcessor extends BaseGatewayProcessor
{
    const GATEWAY_INPUT                               = 'gateway_input';

    // Atos and Worldline refers to same gateway, key on redis is atos
    const WORLDLINE_MID_INDEX_KEY                     = 'atos_gateway_terminal_creation_mid_index';
    const WORLDLINE_MID_OFFSET                        = 999000000000000;
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

        $this->redisMidKey = $this->mode . '_' . self::WORLDLINE_MID_INDEX_KEY;
    }

    // GetInput Value (params) for terminal creation
    public function getInputValue($gatewayInput, $subMerchant)
    {
        list($accountNumber, $ifscCode) = $this->getPartnerBankDetails();

        $terminalData = [
            Terminal\Entity::STATUS              => Terminal\Status::CREATED,
            Terminal\Entity::ENABLED             => 0,
            Terminal\Entity::CATEGORY            => $subMerchant->getCategory(),
            Terminal\Entity::ACCOUNT_NUMBER      => $accountNumber,
            Terminal\Entity::IFSC_CODE           => $ifscCode,
            Terminal\Entity::GATEWAY             => Gateway::WORLDLINE,
            Terminal\Entity::GATEWAY_ACQUIRER    => Gateway::ACQUIRER_AXIS,
            Terminal\Entity::GATEWAY_MERCHANT_ID => $this->generateMid($subMerchant),
            Terminal\Entity::CARD                => '1',
            Terminal\Entity::EXPECTED            => '1',
            Terminal\Entity::GATEWAY_TERMINAL_ID => $this->tidGenerator->generateTid(),
            Terminal\Entity::TYPE                => [
                                                        Terminal\Type::NON_RECURRING                 => '1',
                                                        Terminal\Type::BHARAT_QR                     => '1',
                                                        Terminal\Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
                                                    ],            
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
                    Terminal\Entity::GATEWAY               => Gateway::WORLDLINE,
                    Terminal\Entity::GATEWAY_ACQUIRER      => Gateway::ACQUIRER_AXIS,
                    Terminal\Entity::GATEWAY_MERCHANT_ID   => '999999999999',
                    Terminal\Entity::GATEWAY_TERMINAL_ID   => '12345678',
                    Terminal\Entity::TYPE                  => [
                                                                Terminal\Type::NON_RECURRING => '1',
                                                                Terminal\Type::BHARAT_QR     => '1',
                                                                Terminal\Type::DIRECT_SETTLEMENT_WITH_REFUND => '1'
                                                            ],
                    Terminal\Entity::MC_MPAN               => $input[Constants::MPAN][Constants::MASTERCARD],
                    Terminal\Entity::VISA_MPAN             => $input[Constants::MPAN][Constants::VISA],
                    Terminal\Entity::RUPAY_MPAN            => $input[Constants::MPAN][Constants::RUPAY],
                    Terminal\Entity::CARD                  => '1',
                    Terminal\Entity::EXPECTED              => '1',
                ];

                (new Core)->create($terminalData, $merchant);
            }
        );
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

        $this->formatDetailsForGatewayRequestArray($partnerMerchant, $partnerMerchantDetail, $merchantDetail);

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

    /**
     * There are some validations on Worldline, to avoid them, we need to format the request
     * 1. Partner Merchant name should be upper case without space
     * 2. State name should be full name of the state
     * 3. contact name should be present, we are sending default contact_name as Razorpay, if its not present
     * Note: Objects are by default pass by reference in php, in most programming languages for that matter
     */
    protected function formatDetailsForGatewayRequestArray($partnerMerchant, $partnerMerchantDetail, $merchantDetail)
    {
        $partnerMerchant[Merchant\Entity::NAME] = strtoupper(str_replace(' ', '', $partnerMerchant[Merchant\Entity::NAME]));

        $merchantDetail[Merchant\Detail\Entity::BUSINESS_REGISTERED_STATE] =
            $merchantDetail->getBusinessRegisteredStateName();
       
        $merchantDetail[Merchant\Detail\Entity::BUSINESS_OPERATION_STATE] =
            $merchantDetail->getBusinessRegisteredStateName();
       
        $partnerMerchantDetail[Merchant\Detail\Entity::BUSINESS_REGISTERED_STATE] =
            $partnerMerchantDetail->getBusinessRegisteredStateName();
       
        $partnerMerchantDetail[Merchant\Detail\Entity::BUSINESS_OPERATION_STATE] =
            $partnerMerchantDetail->getBusinessRegisteredStateName();
        
        if (empty($merchantDetail->getContactName()) === true)
        {
            $contactName = (is_null($partnerMerchantDetail) === false) ?
                $partnerMerchantDetail->getContactName() : Constants::DEFAULT_CONTACT_NAME;

            if (empty($contactName) === true)
            {
                $contactName = Constants::DEFAULT_CONTACT_NAME;
            }

            $merchantDetail->setContactName($contactName);
        }
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
        $timeStamp = (Carbon::now()->addMinutes(Constants::WORLDLINE_ACTIVATION_NEXT_RETRY_MINS))->getTimestamp();

        $terminalOnboardingDetail->setVerifyAt($timeStamp);

        if ($terminalOnboardingDetail->getVerifyBucket() >= Constants::WORLDLINE_ACTIVATION_RETRY_LIMIT)
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

        $details = Merchant\Detail\MccTccMapping::getTccFromMcc($mcc);

        $details['mcc'] = $mcc;

        return $details;
    }

    protected function getPricingDetails($terminal)
    {
        $merchant = $terminal->merchant;

        $mcc = (int) $merchant->getCategory();

        return Merchant\Detail\FreechargeWorldlineOnboardingDetails::getMccPricing($mcc);
    }

    protected function generateMid($subMerchant)
    {
        $params = [ Terminal\Entity::MERCHANT_ID => $subMerchant->getId(), 
                    Terminal\Entity::GATEWAY     =>  Gateway::WORLDLINE ];

        // Existing terminals of this submerchant of this gateway
        $existingTerminals = $this->repo->terminal->getByParams($params);

        if (count($existingTerminals) > 0)
        {  
            return $existingTerminals->first()->getGatewayMerchantId();
        }

        $newMid = self::WORLDLINE_MID_OFFSET + $this->redis->incr($this->redisMidKey);

        return strval($newMid);
    }

    protected function getPartnerOtherDetails()
    {
        // TODO: Currently other details are hardcoded for freecharge, 
        // need to make this generic
        return Merchant\Detail\FreechargeWorldlineOnboardingDetails::OTHER_DETAILS;
    }

}
