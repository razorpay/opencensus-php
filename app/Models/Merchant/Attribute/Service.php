<?php


namespace RZP\Models\Merchant\Attribute;

use RZP\Constants\Mode;
use Throwable;

use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Services\DiagClient;
use RZP\Services\SalesForceClient;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Attribute\Validator;
use RZP\Exception\BadRequestValidationFailureException;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    const TRACE_STEP_FEATURE_FLAG_FOR_INTENT_BEGIN = 'feature_flag_for_intent_begin';
    const TRACE_STEP_FEATURE_FLAG_FOR_INTENT_FLAG_ADDED = 'feature_flag_for_intent_flag_added';
    const TRACE_STEP_FEATURE_FLAG_FOR_INTENT_FLAG_EXISTS = 'feature_flag_for_intent_flag_exists';

    const MASTERCARD = 'mastercard';
    const VISA       = 'visa';
    const RAZORPAY   = 'Razorpay';

    const ADD = "ADD";
    const DELETE = "DELETE";

    protected $core;

    protected $mutex;

    /** @var $diag DiagClient */
    protected $diag;

    /** @var $salesforce SalesForceClient */
    protected $salesforce;
    /**
     * @var Repository
     */
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->merchant_attribute;

        $this->diag = $this->app['diag'];

        $this->salesforce = $this->app['salesforce'];

        $this->mutex = $this->app['api.mutex'];

    }

    public function upsertBulk(string $group, array $input): array
    {
        $mids = array_pull($input, 'merchant_ids');

        foreach ($mids as $mid) {
            $input[Common::MERCHANT_ID] = $mid;
            try {
                $this->upsert($group, $input);
            }
            catch (BadRequestValidationFailureException $e)
            {
                $this->trace->info(TraceCode::INSERT_MERCHANT_ATTRIBUTES_FAILED, [
                    'merchant' => $mid ,
                    'error' => $e->getMessage()
                ]);
            }
        }
        return ['success' => true];
    }

    /**
     * Group & Types - follows flat structure
     * e.g. {"type":"business_category","value": "School"},
     *   {"type":"monthly_payout_count","value": "1000"}
     * Validates Group & Types - Refer to entity to find allowed values map
     * Create or Update Group & Types at Merchant Product Level.
     * At Product, Group & Type level matching happens to identify update entries.
     * @param string $group
     * @param array $input
     * @return mixed
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function upsert(string $group, array $input, string $saveProduct = null, $merchant = null)
    {
        $merchant = $merchant ?? $this->merchant; // During signup, merchant wouldn't already be set in the service

        $product =  $saveProduct ?? $this->auth->getRequestOriginProduct();

        if (in_array($group, [Group::X_MERCHANT_CURRENT_ACCOUNTS], true) === true)
        {
            $product = Product::BANKING;
        }

        $attributeInputValidator = new Validator();

        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
            'merchant' => $merchant ,
            'received_input' => $input,
            'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
        ]);

        if(empty($merchant) === true and
           $this->app['basicauth']->isAdminAuth() === true)
        {
            $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
                'coming_inside_admin_auth' => true,
                'merchant' => $merchant ,
                'received_input' => $input,
                'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
            ]);
            $attributeInputValidator->validateInput('admin_upsert', $input);
            $merchant = $this->repo->merchant->findOrFail($input[Common::MERCHANT_ID]);
            $product = $input['product'] ?? Product::BANKING;
            $input = array_pull($input, Validator::PREFERENCES);
        }

        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
            'input_after_admin_auth_thing' => $input ,
            'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
        ]);

        foreach ($input as $item){
            $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
                'item_for_validation' => $item,
                'complete_input' => $input,
                'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
            ]);
            //Validate input for group & type
            $attributeInputValidator->validateInput('upsert_input_validation', $item);
            $item[Entity::GROUP] = $group;
            $attributeInputValidator->validateGroupAndType($item);
        }

        $types = array_column($input, 'type');

        $merchantAttributes =  $this->core->fetchKeyValues($merchant, $product, $group, $types);

        $internalAppName = app('request.ctx')->getInternalAppName();

        if ($internalAppName === 'master_onboarding' &&
            in_array(TYPE::CORPORATE_CARDS, $types, true) === true)
        {
            $this->ensureFeatureFlag($merchant, Feature\Constants::CAPITAL_CARDS_ELIGIBLE);

            return $merchantAttributes;
        }

        //find existing keys
        $merchantAttributesByKeys = $merchantAttributes->getDictionaryByAttribute(Entity::TYPE);
        $existingKeys = array_keys($merchantAttributesByKeys);

        // Do we need transaction? We don't need it. All entries can be saved independently
        foreach ($input as $item){
            $item[Entity::PRODUCT] = $product;
            $item[Entity::GROUP] = $group;

            if (in_array($item['type'], $existingKeys)){
                $this->core->update($merchantAttributesByKeys[$item['type']] , $item);
            } else {
                $this->core->create($item, $merchant);
            }
        }

        $updatedMerchantAttributes = $this->core->fetchKeyValues(
            $merchant, $product, $group, $types
        );

        $this->setFeatureFlagsIfApplicable($merchant, $updatedMerchantAttributes);

        return $updatedMerchantAttributes;
    }

    public function upsertPreferencesNitroHack(array $input){
        $merchant = $this->merchant;
        $product = Product::BANKING;

        $createdInput = array([
            'type' => Type::CA_ALLOCATED_BANK,
            'value' => $input['allocated_bank']
                              ], [
            'type' => Type::CA_ONBOARDING_FLOW,
            'value' => $input['onboarding_flow']
        ], [
            'type' => Type::CA_CAMPAIGN_ID,
            'value' => $input['campaign_id']
        ]);

        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
            'inside_upsert_nitro_hack' => 'true',
            'tracer' => '48ab2638-318e-11ec-908f-e60a7e5b4d6f',
            'merchant' => $merchant ,
            'request_origin' => $this->auth->getRequestOriginProduct(),
            'received_input' => $input,
            'created_input' => $createdInput,
        ]);

        return $this->upsert(Group::X_MERCHANT_CURRENT_ACCOUNTS, $createdInput, $product);
    }

    /**
     * Get merchant preferences by group and type
     * @param string $group
     * @param string|null $type
     * @return mixed
     */
    public function getPreferencesByGroupAndType(string $group, string $type = null)
    {
        $merchant = $this->merchant;

        $product = $this->auth->getRequestOriginProduct();

        $type = !empty($type) ? [$type] : [];

        $column = null;

        if (in_array($group, [Group::X_MERCHANT_SOURCE, Group::X_MERCHANT_INTENT], true) === true)
        {
            $column = Entity::CREATED_AT;
        }

        if (in_array($group, [Group::X_MERCHANT_CURRENT_ACCOUNTS], true) === true)
        {
            $product = Product::BANKING;
        }

        return $this->core->fetchKeyValues($merchant, $product, $group, $type, $column);
    }


    /**
     * Get merchant preferences by group and type
     * @param string $merchantId
     * @param string $group
     * @param string|null $type
     * @return mixed
     */
    public function getPreferencesByGroupAndTypeAdminForBanking(string $merchantId, string $group, string $type = null)
    {

        if ($this->auth->isAdminAuth() === true)
        {
            // In case of admin auth $merchantId will be part of the route
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        }
        else
        {
            $merchant = $this->merchant;
        }

        $product = Product::BANKING;

        $type = !empty($type) ? [$type] : [];

        $column = null;

        if (in_array($group, [Group::X_MERCHANT_SOURCE, Group::X_MERCHANT_INTENT], true) === true)
        {
            $column = Entity::CREATED_AT;
        }

        return $this->core->fetchKeyValues($merchant, $product, $group, $type, $column);
    }

    /**
     * Enable feature flags based on x_merchant_intent
     * @param Merchant\Entity $merchant
     * @param PublicCollection $merchantAttributes
     * @throws Throwable
     */
    protected function setFeatureFlagsIfApplicable(
        Merchant\Entity $merchant,
        PublicCollection $merchantAttributes)
    {
        $this->trace->info(TraceCode::MERCHANT_ATTRIBUTES, [
            'step' => self::TRACE_STEP_FEATURE_FLAG_FOR_INTENT_BEGIN,
            'merchant_id' => $merchant->getId(),
        ]);

        foreach ($merchantAttributes as $attribute)
        {
            $type = $attribute[Entity::TYPE];

            $featureConf = IntentFeature::INTENT_FEATURE_CONF[$type] ?? null;

            if (($attribute[Entity::GROUP] === Group::X_MERCHANT_INTENT) and
                ($featureConf !== null) and
                ($attribute[Entity::VALUE] === $featureConf[IntentFeature::EXPECTED_VALUE]))
            {
                $featureFlag = $featureConf[IntentFeature::FEATURE_FLAG];

                $this->ensureFeatureFlag($merchant, $featureFlag);
            }
        }
    }

    /**
     * To add feature flag to a merchant
     * @param Merchant\Entity $merchant
     * @param string $featureFlag
     * @throws Throwable
     */
    protected function ensureFeatureFlag(Merchant\Entity $merchant, string $featureFlag)
    {
        $merchantId = $merchant->getId();

        $context = [
            'merchant_id' => $merchantId,
            'feature_flag' => $featureFlag,
        ];

        if ($merchant->isFeatureEnabled($featureFlag))
        {
            $this->trace->info(
                TraceCode::MERCHANT_ATTRIBUTES,
                array_merge(['step' => self::TRACE_STEP_FEATURE_FLAG_FOR_INTENT_FLAG_EXISTS], $context));

            return;
        }

        $this->addFeatureFlag($merchantId, $featureFlag);

        $this->trace->info(
                TraceCode::MERCHANT_ATTRIBUTES,
                array_merge(['step' => self::TRACE_STEP_FEATURE_FLAG_FOR_INTENT_FLAG_ADDED], $context));
    }

    /**
     * To add feature flag to a merchant
     * @param string $merchantId
     * @param string $featureFlag
     * @throws Throwable
     */
    protected function addFeatureFlag(string $merchantId, string $featureFlag)
    {
        $featureParams = [
            Feature\Entity::ENTITY_ID   => $merchantId,
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAMES       => [$featureFlag],
            Feature\Entity::SHOULD_SYNC => false,
        ];

        try
        {
            (new Feature\Service)->addFeatures($featureParams);
        }
        catch (BadRequestException $exception)
        {
            if ($exception->getCode() === ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ALREADY_ASSIGNED)
            {
                $this->trace->info(TraceCode::FEATURE_STALE_READ_SUCCESS, $featureParams);
            }
            else
            {
                throw $exception;
            }
        }
    }

    public function onboardMerchantOnNetworks($input)
    {
        try
        {
            $this->trace->info(TraceCode::MERCHANT_ONBOARD_REQUEST_ON_NETWORK_DATA,[
                'input' => $input,
            ]);

            $merchantId = $input['merchant_id'];

            $mutex_key = "merchant_onboard_network_" . $merchantId;

            $this->mutex->acquireAndRelease($mutex_key,
                function () use ($merchantId,$input)
                {
                    $merchant = $this->repo->merchant->find($merchantId);
                    foreach ($input['networks'] as $network)
                    {
                        $networkRequesterAttributes = $this->entityRepo->getValueForProductGroupType($merchantId,Product::PRIMARY,$network,Type::REQUESTER_ID);

                        //To skip already onboarded merchants except Mastercard default merchants
                        if($networkRequesterAttributes && !($network === self::MASTERCARD && ($networkRequesterAttributes['value'] === $this->app['config']->get('gateway.mastercard.razorpay_requester_id'))))
                        {
                            continue;
                        }
                        $requesterAttribute =[];
                        $nameAttribute=[];

                        switch($network)
                        {
                            case self::VISA:
                                list($requesterAttribute, $nameAttribute) = $this->onboardOnVisa($merchant);
                                break;

                            case self::MASTERCARD:
                                list($requesterAttribute, $nameAttribute) = $this->onboardOnMasterCard($merchant);
                                break;

                            default:
                                $this->trace->info(TraceCode::ONBOARDING_NETWORK_NOT_SUPPORTED,[
                                    'network' => $network,
                                    'merchant_id' => $merchantId,
                                ]);
                                break;
                        }

                        if($requesterAttribute && $nameAttribute)
                        {
                            if($networkRequesterAttributes && ($network === self::MASTERCARD && ($networkRequesterAttributes['value'] === $this->app['config']->get('gateway.mastercard.razorpay_requester_id'))))
                            {
                                $this->core->update($networkRequesterAttributes,$requesterAttribute);
                                $networkNameAttributes = $this->entityRepo->getValueForProductGroupType($merchantId,Product::PRIMARY,$network,Type::MERCHANT_NAME);
                                $this->core->update($networkNameAttributes,$nameAttribute);

                            }
                            else{
                                $this->core->create($requesterAttribute,$merchant);
                                $this->core->create($nameAttribute,$merchant);
                            }
                        }
                    }

                },20,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);


        }
        catch(\Exception $e)
        {
            throw new \Exception(TraceCode::MERCHANT_ONBOARD_REQUEST_ON_NETWORK_FAILED);
        }

        return [
            "status" => "successful",
            "merchant_id" => $merchantId,
        ];
    }

    protected function onboardOnVisa($merchant)
    {
        list($visaIdentifier , $visaName) = $this->getDefaultValuesForMerchantOnboarding(Group::VISA,$merchant);

        $requesterAttribute = [
            Entity::PRODUCT     => Product::PRIMARY,
            Entity::GROUP       => Group::VISA,
            Entity::TYPE        => Type::REQUESTER_ID,
            Entity::VALUE       => $visaIdentifier,
        ];

        $nameAttribute = [
            Entity::PRODUCT     => Product::PRIMARY,
            Entity::GROUP       => Group::VISA,
            Entity::TYPE        => Type::MERCHANT_NAME,
            Entity::VALUE       => $visaName,
        ];

        return [$requesterAttribute, $nameAttribute];
    }

    protected function onboardOnMasterCard($merchant)
    {

        try {
            $mcIdentifierId = $this->app['config']->get('gateway.mastercard.identifier_id');
            $mcIdentifierName = $this->app['config']->get('gateway.mastercard.identifier_name');
            $input = $this->getRequestBodyForMCIdentifier($merchant,$mcIdentifierId,$mcIdentifierName);
            $response = $this->app->mozart->sendMozartRequest('onboarding',self::MASTERCARD,'merchant_enrollment',$input);

            if ($response['data']['merchantData'][0]['status'] === "Successful")
            {
                $mcRequesterId = $input['merchantData']['merchantID'];
                $mcNameId = $input['merchantData']['merchantName'];
            }
            else
            {
                throw new \Exception('Status was not successful');
            }
        }
        catch(\Exception $e)
        {
            list($mcRequesterId, $mcNameId) = $this->getDefaultValuesForMerchantOnboarding(Group::MASTERCARD,$merchant);
        }

        $requesterAttribute = [
            Entity::PRODUCT     => Product::PRIMARY,
            Entity::GROUP       => Group::MASTERCARD,
            Entity::TYPE        => Type::REQUESTER_ID,
            Entity::VALUE       => $mcRequesterId,
        ];

        $nameAttribute = [
            Entity::PRODUCT     => Product::PRIMARY,
            Entity::GROUP       => Group::MASTERCARD,
            Entity::TYPE        => Type::MERCHANT_NAME,
            Entity::VALUE       => $mcNameId,
        ];

        return [$requesterAttribute, $nameAttribute];

    }

    protected function getRequestBodyForMCIdentifier($merchant,$mcIdentifierId, $mcIdentifierName)
    {
        $merchantName = $merchant->getName();
        $merchantData = [
            'merchantID'            => $mcIdentifierId.'_'.$merchant->getId(),
            'merchantName'          => substr($mcIdentifierName.'_'.$merchantName,0,25),
        ];
        $input = [
            'merchantData'  => $merchantData,
            'action'         => self::ADD,
        ];
        return $input;

    }

    public function onboardMerchantOnNetworkBulk($input)
    {
        $limit = isset($input['limit']) ? $input['limit'] : 1000;
        $merchantIds = [];

        $this->app['rzp.mode']=Mode::LIVE;

        if(!isset($input['merchant_ids']))
        {
            $merchantIds = $this->repo->merchant->fetchMerchantsWithNotOnboardedOnNetworks(Product::PRIMARY,Merchant\Constants::listOfNetworksSupportedOn3ds2,$limit);
        }
        else{
            $merchantIds = $input['merchant_ids'];
        }

        foreach ($merchantIds as $id)
        {
            (new \RZP\Models\Merchant\Core)->checkAndPushMessageToMetroForNetworkOnboard($id);
        }

        return [
            "status" => "successful",
            "total_merchant_ids" => count($merchantIds),
        ];
    }

    public function getDefaultValuesForMerchantOnboarding($network,$merchant)
    {
        $merchantNameValue = substr($merchant->getName(),0,40);

        if($network === Group::VISA)
        {
            $requestorIdValue = $this->app['config']->get('gateway.visa.identifier_id').'*'.$merchant->getId();

        }elseif ($network === Group::MASTERCARD){
            $requestorIdValue = $this->app['config']->get('gateway.mastercard.razorpay_requester_id');
            $merchantNameValue = substr($this->app['config']->get('gateway.mastercard.razorpay_requester_name'),0,25);
        }

        return [$requestorIdValue, $merchantNameValue];
    }


}
