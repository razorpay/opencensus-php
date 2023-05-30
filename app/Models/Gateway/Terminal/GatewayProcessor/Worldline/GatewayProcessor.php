<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Worldline;

use App;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Models\Terminal\Core;
use RZP\Gateway\Mozart\Action;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\UniqueIdEntity;
use Illuminate\Support\Facades\Redis;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\Gateway\Terminal\GatewayProcessor\BaseGatewayProcessor;


class GatewayProcessor extends BaseGatewayProcessor
{
    const GATEWAY_INPUT                               = 'gateway_input';

    // Atos and Worldline refers to same gateway, key on redis is atos
    const WORLDLINE_MID_INDEX_KEY                     = 'atos_gateway_terminal_creation_mid_index';
    const WORLDLINE_MID_OFFSET                        = 999000000000000;
    const TERMINAL_ONBOARDING_MUTEX_LOCK              = 'TERMINAL_ONBOARDING_MUTEX_LOCK';

    // For worldline, if terminal status is one of below, then it means merchant is onboarded on gateway successfully
    const MERCHANT_ONBOARDED_ON_GATEWAY_STATUSES      =   [Terminal\Status::PENDING, Terminal\Status::ACTIVATED, Terminal\Status::DEACTIVATED];

    const REMINDER_NAMESPACE                          =   'terminal_created_webhook';

    protected $tidGenerator;

    protected $redisMidKey;

    protected $gateway;

    public function __construct()
    {
        parent::__construct();

        $this->app = App::getFacadeRoot();

        $this->redis = $this->app['redis']->Connection('mutex_redis');

        $this->trace = $this->app['trace'];

        $this->tidGenerator = new TidGenerator();

        $this->redisMidKey = $this->mode . '_' . self::WORLDLINE_MID_INDEX_KEY;

        $this->gateway = Gateway::WORLDLINE;
    }

    // get gateway request array
    public function getGatewayData($input, $subMerchant, $merchantDetail)
    {
        // If the submerchant is already onboarded on gateway, then send additional tid request, otherwise send merchant-onboarding request
        if ($this->isMerchantOnboardedOnGateway($subMerchant->getId()) === true)
        {
            return $this->getGatewayRequestArrayForAdditionalTerminalCreation($subMerchant, $input);
        }

        return $this->getGatewayRequestArrayForMerchantOnboarding($subMerchant, $input);
    }

    public function processTerminalData($terminalResponseData, $subMerchant, $gatewayInput)
    {
        if (isset($terminalResponseData[Constants::SUCCESS]) === true and $terminalResponseData[Constants::SUCCESS] === true)
        {
            $terminalParams = $this->getTerminalCreationParams($gatewayInput, $subMerchant);

            $terminal = (new Core)->create($terminalParams, $subMerchant);

            $this->assignRequisiteFeatures($subMerchant);

            $this->setupCreateReminder($terminal);

            return $terminal;
        }

        $errorCode = ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
        $errorMsg = '';

        if (isset($terminalResponseData[Constants::ERROR][Constants::INTERNAL_ERROR_CODE]) === true)
        {
            $errorCode = $terminalResponseData[Constants::ERROR][Constants::INTERNAL_ERROR_CODE];
        }

        if (isset($terminalResponseData[Constants::DATA][Constants::DESCRIPTION]) === true)
        {
            $errorMsg = $terminalResponseData[Constants::DATA][Constants::DESCRIPTION];
        }

        throw new Exception\BadRequestException(
            $errorCode,
            null, null, $errorMsg);
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
                    Terminal\Entity::GATEWAY               => $this->gateway,
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

                (new Core)->create($terminalData, $merchant, false);
            }
        );
    }

    public function getLockResource($merchant, $gateway, $gatewayInput)
    {
        return $merchant->getId() . '_' . self::TERMINAL_ONBOARDING_MUTEX_LOCK;
    }

    protected function getGatewayRequestArrayForMerchantOnboarding($subMerchant, $input)
    {
        $partnerMerchant = $this->repo->merchant->getPartnerMerchantFromSubMerchantId($subMerchant->getId());

        $merchantDetail = $subMerchant->merchantDetail;

        $partnerMerchantDetail = $partnerMerchant->merchantDetail;

        $this->formatDetailsForGatewayRequestArray($partnerMerchant, $partnerMerchantDetail, $merchantDetail);

        $gatewayRequestArray = [
            'method'                    => "POST",
            'request_type'              => 'N',
            'gateway'                   => $this->gateway,
            'terminal'                  => $this->getTerminalData($subMerchant, $input),
            'merchant'                  => $subMerchant->toArray(),
            'merchant_details'          => $merchantDetail,
            'category_details'          => $this->getCategoryDetails($subMerchant),
            'partner_merchant'          => $partnerMerchant,
            'partner_merchant_details'  => $partnerMerchantDetail,
            'request_details'           => $this->getRequestDetails(),
            'bank_details'              => $partnerMerchant->bankAccount->toArrayPublic(),
            'pricing_details'           => $this->getPricingDetails($subMerchant),
            'other_details'             => $this->getPartnerOtherDetails(),
        ];

        return $gatewayRequestArray;
    }

    protected function getGatewayRequestArrayForAdditionalTerminalCreation($subMerchant, $input)
    {
        $gatewayRequestArray = [
            'method'                    => 'POST',
            'request_type'              =>  'A',
            'gateway'                   => $this->gateway,
            'terminal'                  => $this->getTerminalData($subMerchant, $input),
            'request_details'           => $this->getRequestDetails(),
            'other_details'             => $this->getPartnerOtherDetails(),
        ];

        return $gatewayRequestArray;
    }

    protected function getTerminalData($subMerchant, $input)
    {
        return [
            Terminal\Entity::MC_MPAN             => $input[Constants::MPAN][Constants::MASTERCARD],
            Terminal\Entity::VISA_MPAN           => $input[Constants::MPAN][Constants::VISA],
            Terminal\Entity::RUPAY_MPAN          => $input[Constants::MPAN][Constants::RUPAY],
            Terminal\Entity::GATEWAY_MERCHANT_ID => $this->generateMid($subMerchant),
            Terminal\Entity::GATEWAY_TERMINAL_ID => $this->tidGenerator->generateTid(),
        ];
    }

    protected function getTerminalCreationParams($gatewayInput, $subMerchant)
    {
        list($accountNumber, $ifscCode) = $this->getPartnerBankDetails();

        $terminalData = [
            Terminal\Entity::STATUS              => Terminal\Status::PENDING,
            Terminal\Entity::ENABLED             => 1,
            Terminal\Entity::CATEGORY            => $subMerchant->getCategory(),
            Terminal\Entity::ACCOUNT_NUMBER      => $accountNumber,
            Terminal\Entity::IFSC_CODE           => $ifscCode,
            Terminal\Entity::GATEWAY             => Gateway::WORLDLINE,
            Terminal\Entity::GATEWAY_ACQUIRER    => Gateway::ACQUIRER_AXIS,
            Terminal\Entity::GATEWAY_MERCHANT_ID => $gatewayInput[Constants::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
            Terminal\Entity::CARD                => '1',
            Terminal\Entity::EXPECTED            => '1',
            Terminal\Entity::GATEWAY_TERMINAL_ID => $gatewayInput[Constants::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_ID],
            Terminal\Entity::TYPE                => [
                                                        Terminal\Type::NON_RECURRING                 => '1',
                                                        Terminal\Type::BHARAT_QR                     => '1',
                                                        Terminal\Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
                                                    ],

            Terminal\Entity::MC_MPAN             => $gatewayInput[Constants::TERMINAL][Terminal\Entity::MC_MPAN],
            Terminal\Entity::VISA_MPAN           => $gatewayInput[Constants::TERMINAL][Terminal\Entity::VISA_MPAN],
            Terminal\Entity::RUPAY_MPAN          => $gatewayInput[Constants::TERMINAL][Terminal\Entity::RUPAY_MPAN],
        ];

        return $terminalData;
    }

    public function getGatewayRequestArrayForEnableOrDisable($terminal)
    {
        $cardVaultApp = $this->app['mpan.cardVault'];

        $mcMpan =  $terminal->getMCMpan();
        $visaMpan =  $terminal->getVisaMpan();
        $rupayMpan =  $terminal->getRupayMpan();

        if ((empty($mcMpan) === false) and (strlen($mcMpan) !== 16))
        {
            $mcMpan = $cardVaultApp->detokenize($mcMpan);
        }
        if ((empty($visaMpan) === false) and (strlen($visaMpan) !== 16))
        {
            $visaMpan = $cardVaultApp->detokenize($visaMpan);
        }
        if ((empty($rupayMpan) === false) and (strlen($rupayMpan) !== 16))
        {
            $rupayMpan = $cardVaultApp->detokenize($rupayMpan);
        }

        $uniqueRrn = UniqueIdEntity::generateUniqueId();

        $gatewayRequestArray = [
            'req_rrn'               =>  $uniqueRrn,
            'gateway'               =>  $terminal->gateway,
            'mid'                   =>  $terminal->getGatewayMerchantId(),
            'tid'                   =>  $terminal->getGatewayTerminalId(),
            'mc_mpan'               =>  $mcMpan,
            'visa_mpan'             =>  $visaMpan,
            'rupay_mpan'            =>  $rupayMpan,
        ];

        return $gatewayRequestArray;
    }

    public function raiseExceptionIfEnableOrDisableFails($response, $action)
    {
        switch($action)
        {
            case Constants::ENABLE_TERMINAL:
                if ((isset($response[Constants::DATA][Constants::STATUS]) === false) or
                    ($response[Constants::DATA][Constants::STATUS] !== Constants::TERMINAL_REACTIVATION_SUCCESSFUL))
                    {
                        // If somehow, terminal is already enabled and we are again trying to reenable it, then return from here
                        if ((isset($response[Constants::DATA][Constants::DESCRIPTION]) === true) and
                        ($response[Constants::DATA][Constants::DESCRIPTION] === Constants::MERCHANT_IS_ALREADY_IN_ACTIVE_STATE))
                            {
                                return;
                            }

                        throw new Exception\BadRequestException(
                            ErrorCode::GATEWAY_ERROR_TERMINAL_ENABLE_FAILED, null, $response);
                    }
                break;
            case Constants::DISABLE_TERMINAL:
                if ((isset($response[Constants::DATA][Constants::STATUS]) === false) or
                    ($response[Constants::DATA][Constants::STATUS] !== Constants::TERMINAL_DEACTIVATION_SUCCESSFUL))
                    {
                        // If somehow, terminal is already deactive and we are again trying to disable it, then return from here
                        if ((isset($response[Constants::DATA][Constants::DESCRIPTION]) === true) and
                        ($response[Constants::DATA][Constants::DESCRIPTION] === Constants::MERCHANT_IS_ALREADY_IN_DEACTIVE_STATE))
                            {
                                return;
                            }

                        throw new Exception\BadRequestException(
                            ErrorCode::GATEWAY_ERROR_TERMINAL_DISABLE_FAILED, null, $response);
                    }
                break;
        }
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

    protected function getPartnerBankDetails()
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        $partner = $this->repo->merchant->findOrFail($partnerMerchantId);

        $partnerBankAccount = $partner->bankAccount;

        $partnerBankAccountNumber = $partnerBankAccount->getAccountNumber();

        $partnerBankIfscCode = $partnerBankAccount->getIfscCode();

        return [$partnerBankAccountNumber, $partnerBankIfscCode];
    }

    protected function getRequestDetails()
    {
        $reqDetails['req_rrn'] = UniqueIdEntity::generateUniqueId();

        return $reqDetails;
    }

    protected function getCategoryDetails($merchant)
    {
        $mcc = (int) $merchant->getCategory();

        $details = Merchant\Detail\MccTccMapping::getTccFromMcc($mcc);

        $details['mcc'] = $mcc;

        return $details;
    }

    protected function getPricingDetails($merchant)
    {
        $mcc = (int) $merchant->getCategory();

        return Merchant\Detail\FreechargeWorldlineOnboardingDetails::getMccPricing($mcc);
    }

    protected function generateMid($subMerchant)
    {
        $params = [ Terminal\Entity::MERCHANT_ID => $subMerchant->getId(),
                    Terminal\Entity::GATEWAY     => $this->gateway ];

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

    /**
     * Belopw method checks whether the submerchant is actually onboarded on worldline gateway or not
     * A sub-merchant is actually on gateway if any of its terminal's status is pending, activated or deactivated
     */
    protected function isMerchantOnboardedOnGateway($subMerchantId)
    {
        $terminals = $this->repo->terminal->fetchByMerchantIdGatewayAndStatus($subMerchantId, $this->gateway, self::MERCHANT_ONBOARDED_ON_GATEWAY_STATUSES);

        if (count($terminals) === 0)
        {
            return false;
        }

        return true;
    }

    protected function assignRequisiteFeatures($merchant)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::BHARAT_QR) === false)
        {
            $featureParam = [
                Feature\Entity::ENTITY_TYPE => $merchant->getEntityName(),
                Feature\Entity::ENTITY_ID   => $merchant->getId(),
                Feature\Entity::NAME        => Feature\Constants::BHARAT_QR,
            ];

            (new Feature\Core)->create($featureParam, true);
        }
    }

    public function getGatewayActionName()
    {
        return Action::CREATE_TERMINAL;
    }

    protected function setupCreateReminder($terminal)
    {
        $callbackUrl = $this->getCallbackUrlForReminder($terminal);

        $reminderData = [
            'issued_at' => $terminal->getCreatedAt(),
        ];

        $requestData = [
            'namespace'         => self::REMINDER_NAMESPACE,
            'entity_id'         => $terminal->getId(),
            'entity_type'       => $terminal->getEntityName(),
            'reminder_data'     => $reminderData,
            'callback_url'      => $callbackUrl,
        ];

        try {
            $this->app['reminders']->createReminder($requestData, Merchant\Account::SHARED_ACCOUNT);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e);
        }
    }

    protected function getCallbackUrlForReminder($terminal)
    {
        $baseUrl = 'reminders/send';

        $mode = $this->mode;

        $entity = $terminal->getEntityName();

        $namespace = self::REMINDER_NAMESPACE;

        $terminalId = $terminal->getId();

        $callbackURL = sprintf('%s/%s/%s/%s/%s', $baseUrl, $mode, $entity, $namespace, $terminalId);

        return $callbackURL;
    }

}
