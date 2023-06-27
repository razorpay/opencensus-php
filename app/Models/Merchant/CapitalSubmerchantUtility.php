<?php

namespace RZP\Models\Merchant;

use RZP\Models\Merchant\Referral\Entity as ReferralEntity;
use Throwable;
use ApiResponse;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\User\Role;
use RZP\Constants\Product;
use RZP\Http\RequestHeader;
use Illuminate\Http\Response;
use RZP\Base\RepositoryManager;
use Illuminate\Http\JsonResponse;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\App;
use RZP\Jobs\SubMerchantTaggingJob;
use RZP\Error\PublicErrorDescription;
use Illuminate\Foundation\Application;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Models\Batch\Header as BatchHeader;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class CapitalSubmerchantUtility
{

    const PARTNER_CAN_ADD_SUBMERCHANT_FEATURE = [
        Feature\Constants::CAPITAL_CARDS_ELIGIBLE,
    ];

    /**
     * Test/Live mode
     *
     * @var string
     */
    protected string $mode;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected RepositoryManager $repo;

    /**
     * Trace instance used for tracing
     *
     * @var Trace
     */
    protected Trace $trace;

    /**
     * @var Core
     */
    protected Core $merchantCore;


    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->trace = $this->app['trace'];
    }

    /**
     * @return Core
     */
    protected function merchantCore(): Core
    {
        if (empty($this->merchantCore) === true)
        {
            $this->merchantCore = (new Merchant\Core());
        }

        return $this->merchantCore;
    }

    /**
     * Fetch capital applications in bulk for $product from LOS Service for list of $merchantIds
     *
     * @param array  $merchantIds
     * @param string $productId
     *
     * @return JsonResponse|Response
     * @throws IntegrationException
     * @throws Throwable
     */
    public function fetchApplicationsForSubmerchantsForProduct(array $merchantIds, string $productId): JsonResponse|Response
    {
        try
        {
            $response = $this->app['losService']->sendRequest(
                Constants::GET_CAPITAL_APPLICATIONS_BULK_URL,
                [
                    "merchant_ids"        => $merchantIds,
                    Constants::PRODUCT_ID => $productId,
                ],
                [
                    'X-Service-Name' => $this->app['basicauth']->getInternalApp() ?? '',
                    'X-Auth-Type'    => 'internal',
                ]
            );

            $response = $this->app['losService']->parseResponse($response);

            $this->trace->info(
                TraceCode::FETCH_CAPITAL_APPLICATIONS_FOR_SUBMERCHANTS_RESPONSE,
                [
                    'response' => $response,
                ]
            );

            return $response;
        }
        catch (Throwable $ex)
        {
            $this->trace->error(
                TraceCode::BAD_REQUEST_COULD_NOT_FETCH_SUBMERCHANT_CAPITAL_APPLICATIONS,
                [
                    'exception'   => $ex,
                    'description' => 'Could not fetch capital submerchant applications.',
                ]
            );

            throw $ex;
        }
    }

    /**
     * Fetch capital applications based on $product and $merchantId from LOS Service
     *
     * @param string $merchantId
     * @param string $productId
     *
     * @return array
     * @throws Throwable
     */
    public function fetchApplicationsForMerchantAndProduct(string $merchantId, string $productId)
    {
        $this->trace->info(
            TraceCode::FETCH_CAPITAL_APPLICATIONS_FOR_MERCHANT_REQUEST,
            [
                'merchant_id' => $merchantId,
                'product_id'  => $productId
            ]
        );

        try
        {
            $response = $this->app['losService']->sendRequest(
                Constants::GET_CAPITAL_APPLICATIONS_URL,
                [
                    "merchant_id"         => $merchantId,
                    Constants::PRODUCT_ID => $productId,
                ],
                [
                    'X-Service-Name' => $this->app['basicauth']->getInternalApp() ?? '',
                    'X-Auth-Type'    => 'internal',
                ]
            );

            $statusCode = $response->status_code;
            $body = json_decode($response->body, true);

            $this->trace->info(
                TraceCode::FETCH_CAPITAL_APPLICATIONS_FOR_MERCHANT_RESPONSE,
                [
                    'status_code' => $statusCode,
                    'body'        => $body
                ]
            );

            return ['body' => $body, 'status' => $statusCode];
        }
        catch (Throwable $ex)
        {
            $this->trace->error(
                TraceCode::BAD_REQUEST_COULD_NOT_FETCH_MERCHANT_CAPITAL_APPLICATIONS,
                [
                    "merchant_id"         => $merchantId,
                    Constants::PRODUCT_ID => $productId,
                    'exception'           => $ex,
                    'description'         => 'Could not fetch capital merchant applications.',
                ]
            );

            throw $ex;
        }
    }

    public function isCapitalReferralCodeApplicable(Merchant\Entity $merchant, ReferralEntity $referral)
    {
        // If merchant's contact name is null, user will fall into pre-signup flow. The referral code will be consumed at that stage.
        if($merchant->merchantDetail->getContactName() === null)
        {
            return false;
        }

        // If the merchant has the capital loc tag attached or it has an existing LOC application, we will not consume referral code.
        if($merchant->isTagAddedBasedOnPrefix(Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX) === true)
        {
            return false;
        }

        $isCapitalPartnershipExpEnabled = $this->isCapitalPartnershipEnabledForPartner($referral->getMerchantId());

        if ($isCapitalPartnershipExpEnabled === false)
        {
            return false;
        }

        $productIds = CapitalSubmerchantUtility::getLOSProductIds();

        $locProductId = $productIds[Constants::CAPITAL_LOC_EMI_PRODUCT_NAME];

        $response = $this->fetchApplicationsForMerchantAndProduct($merchant->getId(), $locProductId);

        if(empty($response) === false and empty($response['body']) === false and empty($response['body']['applications']) === false)
        {
            return false;
        }

        return true;
    }

    /**
     * Checks if the partner is whitelisted under capital_partnership experiment
     *
     * @param string $partnerId
     *
     * @return bool
     */
    public function isCapitalPartnershipEnabledForPartner(string $partnerId): bool
    {
        $properties = [
            'id'            => $partnerId,
            //'experiment_id' => 'L01gPBm1R1OpJG',
            'experiment_id' => $this->app['config']->get('app.capital_partnership_experiment_id'),
        ];

        $isExpEnabled = $this->merchantCore()->isSplitzExperimentEnable($properties, 'enable');

        $this->trace->info(
            TraceCode::CAPITAL_PARTNERSHIP_EXPERIMENT,
            [
                "properties" => $properties,
                "enabled"    => $isExpEnabled,
            ]
        );

        return $isExpEnabled;
    }

    /**
     * New referral link flow is kept behind this experiment
     *
     * @param string $partnerId
     *
     * @return bool
     */
    public function isGenerateNewCapitalReferralLinkExpEnabled(string $partnerId): bool
    {
        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $this->app['config']->get('app.capital_partner_new_referral_link_experiment_id'),
        ];

        $isExpEnabled = $this->merchantCore()->isSplitzExperimentEnable($properties, 'enable');

        $this->trace->info(
            TraceCode::CAPITAL_PARTNER_NEW_REFERRAL_EXPERIMENT,
            [
                "properties" => $properties,
                "enabled"    => $isExpEnabled,
            ]
        );

        return $isExpEnabled;
    }

    public function canPartnerAddFeatureForSubmerchant(array $featureNames, string $partnerId): bool
    {
        if (empty(array_diff($featureNames, self::PARTNER_CAN_ADD_SUBMERCHANT_FEATURE)) === false)
        {
            return false;
        }

        return $this->isCapitalPartnershipEnabledForPartner($partnerId);
    }

    /**
     * @param array  $input
     * @param string $partnerId
     *
     * @return array
     */
    public function extractInputFromCapitalBatchInvite(array $input, string $partnerId): array
    {
        $product = $input[Entity::PRODUCT] ?? Product::PRIMARY;

        $isCapitalSubMerchant = false;

        if ($product === Product::CAPITAL)
        {
            $isCapitalPartnershipEnabled = $this->isCapitalPartnershipEnabledForPartner($partnerId);

            if ($isCapitalPartnershipEnabled === true)
            {
                (new Validator)->validateInput('partner_submerchant_invite_capital', $input);

                $isCapitalSubMerchant = true;

                $input = [
                    Entity::NAME                   => $input[Entity::NAME],
                    Entity::EMAIL                  => $input[Entity::EMAIL],
                    MerchantDetail::CONTACT_MOBILE => $input[MerchantDetail::CONTACT_MOBILE],
                    Entity::PRODUCT                => Product::BANKING,
                    "actual_product"               => Product::CAPITAL
                ];
            }
        }

        return [$input, $isCapitalSubMerchant];
    }

    /**
     * @param array  $input
     * @param Entity $partner
     *
     * @return array
     */
    static function extractCapitalApplicationInput(array $input, Entity $partner): array
    {
        $productIds = self::getLOSProductIds();

        $locProductId = $productIds[Constants::CAPITAL_LOC_EMI_PRODUCT_NAME];

        return [
            BatchHeader::ANNUAL_TURNOVER_MIN => $input[BatchHeader::ANNUAL_TURNOVER_MIN],
            BatchHeader::ANNUAL_TURNOVER_MAX => $input[BatchHeader::ANNUAL_TURNOVER_MAX],
            Constants::LEAD_SOURCE           => "Partner",
            Constants::LEAD_SOURCE_ID        => $partner->getId(),
            Constants::SOURCE_DETAILS        => $partner->getName(),
            Constants::PRODUCT_ID            => $locProductId
        ];
    }

    /**
     * @return array
     */
    static function getLOSProductIds(): array
    {
        $headers = [
            'X-Service-Name' => app('basicauth')->getInternalApp() ?? '',
            'X-Auth-Type'    => 'internal',
        ];

        $response = app('losService')->sendRequest(Constants::GET_PRODUCTS_LOS_URL, [], $headers);

        $products = json_decode($response->body, true);

        $productIds = [];

        foreach ($products['products'] as $product)
        {
            $productIds[$product['name']] = $product['id'];
        }

        return $productIds;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws BadRequestValidationFailureException
     */
    static function extractMerchantDetailsInput(array $input): array
    {
        return array_filter(
            [
                MerchantDetail::CONTACT_EMAIL                  => $input[BatchHeader::EMAIL],
                MerchantDetail::CONTACT_MOBILE                 => $input[BatchHeader::CONTACT_MOBILE],
                MerchantDetail::BUSINESS_NAME                  => $input[BatchHeader::BUSINESS_NAME],
                MerchantDetail::BUSINESS_TYPE                  => BusinessType::getIndexFromKey(
                    mb_strtolower($input[BatchHeader::BUSINESS_TYPE])
                ),
                MerchantDetail::GSTIN                          => $input[BatchHeader::GSTIN],
                MerchantDetail::PROMOTER_PAN                   => $input[BatchHeader::PROMOTER_PAN],
                MerchantDetail::BUSINESS_REGISTERED_ADDRESS    => $input[BatchHeader::COMPANY_ADDRESS_LINE_1],
                MerchantDetail::BUSINESS_REGISTERED_ADDRESS_L2 => $input[BatchHeader::COMPANY_ADDRESS_LINE_2],
                MerchantDetail::BUSINESS_REGISTERED_CITY       => $input[BatchHeader::COMPANY_ADDRESS_CITY],
                MerchantDetail::BUSINESS_REGISTERED_STATE      => $input[BatchHeader::COMPANY_ADDRESS_STATE],
                MerchantDetail::BUSINESS_REGISTERED_COUNTRY    => $input[BatchHeader::COMPANY_ADDRESS_COUNTRY],
                MerchantDetail::BUSINESS_REGISTERED_PIN        => $input[BatchHeader::COMPANY_ADDRESS_PINCODE],
            ]
        );
    }

    /**
     * @param string $partnerId
     * @param Entity $subMerchant
     *
     * @return void
     * @throws BadRequestValidationFailureException
     */
    static function addTagAndAttributeForCapitalSubmerchant(string $partnerId, Entity $subMerchant): void
    {
        app('trace')->info(
            TraceCode::CAPITAL_SUBMERCHANT_TAG,
            [
                'partner_id'     => $partnerId,
                'submerchant_id' => $subMerchant->getId(),
                'tag_prefix'     => Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX,
            ]
        );

        // append the tag in current mode
        SubMerchantTaggingJob::dispatch(
            Mode::LIVE, $partnerId,
            $subMerchant->getId(),
            Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX
        );
        SubMerchantTaggingJob::dispatch(
            Mode::TEST,
            $partnerId,
            $subMerchant->getId(),
            Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX
        );

        /**
         * We ned to force LIVE mode here, because when batch service calls v1/submerchants/batch API or partner adds capital
         * submerchant in TEST mode, the following will happen:
         * i.   when creating a submerchant for banking, Merchant\Core calls
         *      `getPartnerSubmerchantData` -> `getBankingAccountStatus` -> `(new Attribute\Core())->fetchKeyValuesByMode`
         *      with LIVE mode. This sets $this->repo->merchant_attribute driver's connection to LIVE
         * ii.  In Attribute\Service::upsert, `fetchKeyValues` is called. This happens in LIVE mode because of this ^.
         *      The driver is reused.
         * iii. After that the new attribute entity is created and saved using RepositoryManager's saveOrFail
         * iv.  This saveOrFail implementation uses the underlying Entity's `saveOrFail`
         *      (see `saveOrFailImplementation` in Base\Repository)
         * v.   This will resolve the connection to whatever is the default DB connection. In TEST mode this is TEST.
         * vi.  After this, `fetchKeyValues` is called again! And this happens in LIVE mode because of (i.)
         * vii. And so, even though the Attribute entity gets saved, it is not retrieved and the code does not set
         *      CAPITAL_CARDS_ELIGIBLE feature flag after creating the Attribute entity
         *
         * Moreover, when X dashboard loads, it retrieves X_MERCHANT_INTENT group of Merchant\Attributes from LIVE mode.
         */

        $currentMode = app('rzp.mode') ?? Mode::TEST;

        app('repo')->merchant_attribute->connection(Mode::LIVE);

        app('basicauth')->setModeAndDbConnection(Mode::LIVE);

        (new Attribute\Entity)->setConnection(Mode::LIVE);

        app('trace')->info(
            TraceCode::CAPITAL_SUBMERCHANT_ATTRIBUTE,
            [
                'partner_id'      => $partnerId,
                'submerchant_id'  => $subMerchant->getId(),
                'attribute_group' => Attribute\Group::X_MERCHANT_INTENT,
                'attribute_value' => [
                    "type"  => Attribute\Type::CAPITAL_LOC_EMI,
                    "value" => "true",
                ],
            ]
        );

        (new Attribute\Service)->upsert(
            Attribute\Group::X_MERCHANT_INTENT,
            [
                [
                    "type"  => Attribute\Type::CAPITAL_LOC_EMI,
                    "value" => "true",
                ]
            ],
            Product::BANKING,
            $subMerchant
        );

        app('basicauth')->setModeAndDbConnection($currentMode);
    }

    /**
     * @param Entity $subMerchant
     * @param Entity $partner
     * @param array $createCapitalApplicationInput
     * @param string $source
     *
     * @return void
     * @throws BadRequestException
     */
    static function createCapitalApplicationForSubmerchant(Entity $subMerchant, Entity $partner, array $createCapitalApplicationInput, string $source): void
    {
        $url = Constants::CREATE_CAPITAL_APPLICATION_LOS_URL;

        $createCapitalApplicationInput["merchant_id"] = $subMerchant->getId();

        $user = $subMerchant->owners(Product::BANKING)->first();

        $headers = [
            'X-Service-Name' => app('basicauth')->getInternalApp() ?? 'batch',
            'X-Auth-Type'    => 'internal',
        ];

        app('trace')->info(
            TraceCode::CAPITAL_SUBMERCHANT_APPLICATION_REQUEST,
            [
                'request' => $url,
                'body'    => $createCapitalApplicationInput,
                'headers' => $headers,
            ]
        );

        try
        {
            $response = app('losService')->sendRequest(
                $url,
                $createCapitalApplicationInput,
                $headers
            );

            $response = app('losService')->parseResponse($response);

            app('trace')->info(
                TraceCode::CAPITAL_SUBMERCHANT_APPLICATION_RESPONSE,
                [
                    'response' => $response,
                ]
            );

            $properties = [
                "source"         => $source,
                "partner_id"     => $createCapitalApplicationInput[Constants::LEAD_SOURCE_ID],
                "merchant_id"    => $subMerchant->getId(),
                "product_id"     => $createCapitalApplicationInput[Constants::PRODUCT_ID],
                "batch_id"       => app('request')->header(RequestHeader::X_Batch_Id) ?? null
            ];

            app('diag')->trackOnboardingEvent(EventCode::PARTNERSHIPS_CAPITAL_APPLICATION_CREATED, $partner, null, $properties);
        }
        catch (Throwable $ex)
        {
            app('trace')->error(
                TraceCode::BAD_REQUEST_ADDED_SUBMERCHANT_BUT_CC_APPLICATION_NOT_CREATED,
                [
                    'exception'   => $ex,
                    'description' => 'Account created but LOC application could not be created. Please contact support.',
                ]
            );

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null,
                PublicErrorDescription::BAD_REQUEST_ADDED_SUBMERCHANT_BUT_CC_APPLICATION_NOT_CREATED
            );
        }
    }

}
