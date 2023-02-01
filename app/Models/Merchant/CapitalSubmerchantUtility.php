<?php

namespace RZP\Models\Merchant;

use Throwable;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\User\Role;
use RZP\Constants\Product;
use RZP\Base\RepositoryManager;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\App;
use RZP\Jobs\SubMerchantTaggingJob;
use RZP\Error\PublicErrorDescription;
use Illuminate\Foundation\Application;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Http\Controllers\LOSController;
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
        return [
            BatchHeader::ANNUAL_TURNOVER_MIN => $input[BatchHeader::ANNUAL_TURNOVER_MIN],
            BatchHeader::ANNUAL_TURNOVER_MAX => $input[BatchHeader::ANNUAL_TURNOVER_MAX],
            Constants::LEAD_SOURCE           => "Partner",
            Constants::LEAD_SOURCE_ID        => $partner->getId(),
            Constants::SOURCE_DETAILS        => $partner->getName(),
            Constants::PRODUCT_ID            => Constants::CAPITAL_CORPORATE_CARD_PRODUCT_ID
        ];
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
     * @param Entity $partner
     * @param Entity $subMerchant
     *
     * @return void
     * @throws BadRequestValidationFailureException
     */
    static function addTagAndAttributeForCapitalSubmerchant(Entity $partner, Entity $subMerchant): void
    {
        app('trace')->info(
            TraceCode::CAPITAL_SUBMERCHANT_TAG,
            [
                'partner_id'     => $partner->getId(),
                'submerchant_id' => $subMerchant->getId(),
                'tag_prefix'     => Constants::CAPITAL_PARTNERSHIP_TAG_PREFIX,
            ]
        );

        // append the tag in current mode
        SubMerchantTaggingJob::dispatch(
            Mode::LIVE, $partner->getId(),
            $subMerchant->getId(),
            Constants::CAPITAL_PARTNERSHIP_TAG_PREFIX
        );
        SubMerchantTaggingJob::dispatch(
            Mode::TEST,
            $partner->getId(),
            $subMerchant->getId(),
            Constants::CAPITAL_PARTNERSHIP_TAG_PREFIX
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
                'partner_id'      => $partner->getId(),
                'submerchant_id'  => $subMerchant->getId(),
                'attribute_group' => Attribute\Group::X_MERCHANT_INTENT,
                'attribute_value' => [
                    "type"  => Attribute\Type::CORPORATE_CARDS,
                    "value" => "true",
                ],
            ]
        );

        (new Attribute\Service)->upsert(
            Attribute\Group::X_MERCHANT_INTENT,
            [
                [
                    "type"  => Attribute\Type::CORPORATE_CARDS,
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
     * @param array  $createCapitalApplicationInput
     *
     * @return void
     * @throws IntegrationException
     * @throws Throwable
     */
    static function createCapitalApplicationForSubmerchant(Entity $subMerchant, array $createCapitalApplicationInput): void
    {
        $url = Constants::CREATE_CAPITAL_APPLICATION_LOS_URL;

        $createCapitalApplicationInput["merchant_id"] = $subMerchant->getId();

        $user = $subMerchant->owners(Product::BANKING)->first();

        $headers = [
            'X-Merchant-Id'    => $subMerchant->getId(),
            'X-Merchant-Email' => $subMerchant->getEmail(),
            'X-User-Id'        => optional($user)->getId(),
            'X-User-Role'      => Role::OWNER,
            'X-Auth-Type'      => 'proxy',
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
            $response = (new LOSController())->sendRequestAndParseResponse(
                $url,
                $createCapitalApplicationInput,
                $headers
            );

            app('trace')->info(
                TraceCode::CAPITAL_SUBMERCHANT_APPLICATION_RESPONSE,
                [
                    'response' => $response,
                ]
            );
        }
        catch (Throwable $ex)
        {
            app('trace')->error(
                TraceCode::BAD_REQUEST_ADDED_SUBMERCHANT_BUT_CC_APPLICATION_NOT_CREATED,
                [
                    'exception'   => $ex,
                    'description' => 'Account created but corporate card application could not be created. Please contact support.',
                ]
            );

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null,
                PublicErrorDescription::BAD_REQUEST_ADDED_SUBMERCHANT_BUT_CC_APPLICATION_NOT_CREATED
            );
        }
    }

}
