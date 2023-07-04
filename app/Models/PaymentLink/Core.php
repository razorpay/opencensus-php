<?php

namespace RZP\Models\PaymentLink;

use App;
use Cache;
use Imagick;
use ImagickPixel;
use Carbon\Carbon;
use phpseclib\Crypt\AES;
use RZP\Constants\Environment;
use RZP\Encryption\AESEncryption;
use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\User;
use RZP\Models\Order;
use RZP\Services\NoCodeAppsService;
use Illuminate\Support\Facades\Config;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Trace\Tracer;
use RZP\Models\Invoice;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Jobs\NotifyRas;
use RZP\Models\Customer;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Settings;
use RZP\Models\LineItem;
use RZP\Models\FileStore;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Jobs\AppsRiskCheck;
use RZP\Services\UfhService;
use RZP\Constants\Entity as E;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Invoice\Entity as IE;
use RZP\Exception\BaseException;
use RZP\Models\Currency\Currency;
use RZP\Jobs\PaymentPageProcessor;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Services\MerchantRiskClient;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Exception\BadRequestException;
use RZP\Models\PaymentLink\ElfinWrapper;
use RZP\Models\PaymentLink\Template\UdfSchema;
use RZP\Models\PaymentLink\PaymentPageItem as PPI;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\PaymentLink\Template\Hosted as HostedTemplate;
use RZP\Models\Order\OrderMeta\Order1cc\Fields as Fields;
use \RZP\Models\Address\Entity as AddressEntity;

class Core extends Base\Core
{
    /**
     * Elfin: Url shortening service
     */
    protected $elfin;

    /**
     * Payment link's hosted base url.
     * @var string
     */
    protected $plHostedBaseUrl;

    protected $paymentHandleHostedBaseUrl;

    protected $merchantRiskService;

    const PAYMENT_PAGE_ITEM_LAST_SYNC_TIMESTAMP = 'PAYMENT_PAGE_ITEM_LAST_SYNC_TIMESTAMP';

    const AMOUT_QUANTITY_TAMPERED = "Amount or quantity has been tampered. Please try again.";

    const REQUIRED_AMOUNT           = 'required_amount';
    const REQUIRED_MIN_AMOUNT       = 'required_min_amount';
    const REQUIRED_MIN_QUANTITY     = 'required_min_quantity';
    const PHONE                     = 'phone';
    const ADDRESS                   = 'address';

    public function __construct()
    {
        parent::__construct();

        $this->elfin           = $this->app['elfin'];
        $this->plHostedBaseUrl = $this->app['config']->get('app.payment_link_hosted_base_url');
        $this->paymentHandleHostedBaseUrl = $this->app['config']->get('app.payment_handle_hosted_base_url');
        $this->merchantRiskService = $this->app['merchantRiskClient'];
    }

    /**
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @param  User\Entity     $user
     *
     * @return Entity
     * @throws BadRequestException
     * @throws BaseException
     */
    public function create(array $input, Merchant\Entity $merchant, User\Entity $user = null): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_LINK_CREATE_REQUEST, $input);

        //bulk upload flow
        $this->validateBulkUploadFlow($input,$merchant);

        $paymentLink = Tracer::inSpan(['name' => 'payment_page.create.generate_id'], function() {
            return (new Entity)->generateId();
        });

        // Association of merchant must happens before build() call as the same is needed in validations
        Tracer::inSpan(['name' => 'payment_page.create.associate_merchant'], function() use ($paymentLink, $merchant) {
            $paymentLink->merchant()->associate($merchant);
        });

        Tracer::inSpan(['name' => 'payment_page.create.associate_user'], function() use ($paymentLink, $user) {
            $paymentLink->user()->associate($user);
        });

        $settings = $input[Entity::SETTINGS] ?? [];

        $settings[Entity::VERSION] = Version::V2;

        (new Validator())->validatePayerNameAndExpiryForCreate($merchant, $input);

        Tracer::inSpan(['name' => 'payment_page.create.build'], function() use ($paymentLink, $input) {
            $paymentLink->build($input);
        });

        if (($paymentLink->getViewType() === ViewType::FILE_UPLOAD_PAGE) and
            (isset($settings[Entity::UDF_SCHEMA])))
        {
            $udfSchema = json_decode($settings[Entity::UDF_SCHEMA], true);

            $allFields = $this->generateAllFieldsFromUDF($udfSchema, $input);

            $settings[Entity::ALL_FIELDS] = json_encode($allFields);

        }

        $validator = new Validator;

        if(array_key_exists(Entity::SLUG, $input) === true
            && $paymentLink->getViewType() !== ViewType::PAYMENT_HANDLE
            && array_get($settings, Entity::CUSTOM_DOMAIN, "") === "")
        {
            $validator->validateGeneralSlug([
                Entity::SLUG    => $input[Entity::SLUG],
            ]);
        }

        if($paymentLink->getViewType() !== ViewType::PAYMENT_HANDLE
            && array_get($settings, Entity::CUSTOM_DOMAIN, "") !== "")
        {
            $validator->validateCDSSlug([
                Entity::SLUG            => array_get($input, Entity::SLUG),
                Entity::CUSTOM_DOMAIN   => array_get($settings, Entity::CUSTOM_DOMAIN, "")
            ]);
        }

        Tracer::inSpan(['name' => 'payment_page.create.short_url'], function() use ($paymentLink, $input, $settings) {
            $this->createAndSetShortUrl($paymentLink, $input[Entity::SLUG] ?? null, array_get($settings, Entity::CUSTOM_DOMAIN));
        });

        $this->repo->transaction(function() use ($paymentLink, $settings, $input)
        {
            Tracer::inSpan(['name' => 'payment_page.create.upsert_settings'], function() use ($paymentLink, $settings) {
                $this->upsertSettings($paymentLink, $settings);
            });

            Tracer::inSpan(['name' => 'payment_page.create.create_page'], function() use ($paymentLink) {
                $this->repo->saveOrFail($paymentLink);
            });

            Tracer::inSpan(['name' => 'payment_page.create.create_items'], function() use ($input, $paymentLink) {
                $this->createPaymentPageItems($input, $paymentLink);
            });

            $this->createCustomUrl($paymentLink, $input[Entity::SLUG] ?? null, array_get($settings, Entity::CUSTOM_DOMAIN));
        });

        Tracer::inSpan(['name' => 'payment_page.create.load_relations'], function() use ($paymentLink) {
            $this->repo->loadRelations($paymentLink);
        });
        $this->trace->info(TraceCode::PAYMENT_LINK_CREATED, $paymentLink->toArrayPublic());

        Tracer::inSpan(['name' => 'payment_page.create.events'], function() use ($input, $paymentLink) {
            $this->trackPaymentPageCreatedEvent($paymentLink, $input);
        });

        $this->trace->count(Metric::PAYMENT_PAGE_CREATED_TOTAL, $paymentLink->getMetricDimensions());

        Tracer::inSpan(['name' => 'payment_page.create.dedupe_actions'], function() use ($paymentLink, $merchant) {
            $this->dispatchDedupeCall($paymentLink, $merchant);
        });

        Tracer::inSpan(['name' => 'payment_page.create.dispatch.app_risk_check'], function() use ($paymentLink) {
            $this->dispatchAppRiskCheck($paymentLink);
        });

        Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
            $this->dispatchHostedCache($paymentLink);
        });

        return $paymentLink;
    }

    public function generateAllFieldsFromUDF(array $udfSchema, array $input): array
    {
        $allFields = [];

        $fieldCount = 1;

        foreach ($udfSchema as $udf)
        {
            $allFields[$udf['title']] = 'field_'.$fieldCount;

            $fieldCount++;
        }

        // we need to add payment_page_items as well, as they also should be shown as separate columns in report
        if (isset($input[Entity::PAYMENT_PAGE_ITEMS]) === true)
        {
            foreach ($input[Entity::PAYMENT_PAGE_ITEMS] as $item)
            {
                if ((isset($item[Item\Entity::ITEM]) === true) and
                    (isset($item[Item\Entity::ITEM][Item\Entity::NAME]) === true))
                {
                    $itemName = $item[Item\Entity::ITEM][Item\Entity::NAME];
                    $allFields[$itemName] = 'field_'.$fieldCount;

                    $fieldCount++;
                }
            }
        }

        return $allFields;
    }


    public function  updateAllFieldsFromUDF(array $udfSchema, array $allFields, array $input): array
    {
        $newFields = [];

        $totalFields = count($allFields);
        foreach ($udfSchema as $udf)
        {
            $title = $udf['title'];

            if (array_key_exists($title, $allFields) === false)
            {
                $totalFields++;

                $newFields[$title] = 'field_'.$totalFields;
            }
        }

        if (isset($input[Entity::PAYMENT_PAGE_ITEMS]) === true)
        {
            foreach ($input[Entity::PAYMENT_PAGE_ITEMS] as $item)
            {
                if ((isset($item[Item\Entity::ITEM]) === true) and
                    (isset($item[Item\Entity::ITEM][Item\Entity::NAME]) === true) and
                    (array_key_exists($item[Item\Entity::ITEM][Item\Entity::NAME], $allFields) === false))
                {
                    $totalFields++;

                    $itemName = $item[Item\Entity::ITEM][Item\Entity::NAME];
                    $newFields[$itemName] = 'field_'.$totalFields;
                }
            }
        }

        return array_merge($allFields, $newFields);
    }

    public function validateBulkUploadFlow($input, $merchant, $paymentLink = null)
    {
        if ((isset($input[Entity::VIEW_TYPE]) === true and
                $input[Entity::VIEW_TYPE] === Entity::VIEW_TYPE_FILE_UPLOAD_PAGE) or
            ((isset($paymentLink) === true) and
            (($paymentLink->getViewType()) !== null) and
            $paymentLink->getViewType() === Entity::VIEW_TYPE_FILE_UPLOAD_PAGE))
        {
            if ($merchant->isFeatureEnabled(Feature::FILE_UPLOAD_PP) === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT);
            }

            $this->trace->info(
                    TraceCode::PAYMENT_PAGE_BULK_UPLOAD,
                    [
                        Entity::MERCHANT_ID => $this->merchant->getPublicId()
                    ]);

            $settings = $input['settings'];

            if (!isset($settings[Entity::UDF_SCHEMA])) {

                throw new BadRequestValidationFailureException(
                    'Mandatory field Primary reference ID missing.');
            }

            $udfSchema  = json_decode($settings[Entity::UDF_SCHEMA], true);

            $this->checkForMandatoryPrimaryAndSecondaryRefIds($udfSchema);

        }

    }

    public function checkForMandatoryPrimaryAndSecondaryRefIds(array $udfSchema)
    {
        // check 1: both primary_ref_id and sec_ref_id_1 should be present
        $primaryRefJson = array_first($udfSchema, function($json) {
            return $json['name'] === Entity::PRI_REF_ID;
        });

        if ($primaryRefJson === null)
        {
            throw new BadRequestValidationFailureException(
                'Mandatory field Primary reference ID missing.');
        }

        $secRefJson = array_first($udfSchema, function($json) {
            return $json['name'] === PaymentPageRecord\Entity::SECONDARY_1;
        });

        if ($secRefJson === null)
        {
            throw new BadRequestValidationFailureException(
                'Mandatory field Secondary reference ID 1 missing.');
        }

        // check 2: required: true should be true for primary_ref_id and sec_ref_id_1

        if ($primaryRefJson['required'] === false)
        {
            throw new BadRequestValidationFailureException(
                'Primary reference ID cannot be optional field.');
        }

        if ($secRefJson['required'] === false)
        {
            throw new BadRequestValidationFailureException(
                'Secondary reference ID 1 cannot be optional field.');
        }

        // check 3: primary_ref_id and secondary_ref_id 1 cannot have same title

        if (strcasecmp($primaryRefJson['title'], $secRefJson['title']) === 0)
        {
            throw new BadRequestValidationFailureException(
                'Primary reference ID and Secondary reference ID 1 cannot be have same title.');
        }

        // check 4: number of secondary ref id's should be <= 5
        $secRefIdsCount = 0;
        foreach ($udfSchema as $udf) {
            if ((isset($udf['name']) === true) and (PaymentPageRecord\Entity::isSecondaryRefId($udf['name'])))
            {
                $secRefIdsCount++;
            }
        }

        if ($secRefIdsCount > 5)
        {
            throw new BadRequestValidationFailureException(
                "Number of secondary reference ID's cannot be more than 5");
        }
    }

    public function createPaymentHandle(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_HANDLE_CREATE_PAYMENT_PAGE,
            [
                Entity::SLUG        => $input[Entity::SLUG],
                Entity::MERCHANT_ID => $this->merchant->getPublicId()
            ]);

        $paymentPage = Tracer::inSpan(['name' => Constants::HT_PH_CREATE_REQUEST_CREATE_PP], function() use($input, $merchant)
        {
            return $this->createPaymentPageForPaymentHandle($input,  $merchant);
        });

        $this->upsertDefaultPaymentHandleForMerchant($input[Entity::SLUG], $paymentPage->getPublicId());

        $this->trace->info(
            TraceCode::PAYMENT_HANDLE_PAYMENT_PAGE_CREATED,
            [
                Entity::SLUG      => $input[Entity::SLUG],
                "payment_page" =>  $paymentPage
            ]);

        return $paymentPage;
    }

    /**
     * @param array $input
     * @return array
     * @throws BadRequestValidationFailureException
     */
    public function updatePaymentHandle(array $input): array
    {
        [$handleOld, $handlePageId] = $this->getPaymentHandleAndPaymentPageIDLinkedWithIt();

        if($handleOld === $input[Entity::SLUG] || $this->slugExists($input[Entity::SLUG]) === true)
        {
            throw new BadRequestValidationFailureException(
                'Payment Handle already taken.');
        }

        $handleNew = $input[Entity::SLUG];

        if(empty($handleOld) === true)
        {
            throw new BadRequestValidationFailureException(
                'Payment Handle does not exists for this merchant.');
        }

        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant(
            $handlePageId,
            $this->merchant);

        /**
         * previous payment handle will be deactivated in updating the payment
         * handle
         */
        $this->repo->transaction(function() use ($paymentLink, $handleNew)
        {
            $this->createCustomUrlForPaymentHandle($paymentLink, $handleNew);
        });

        $this->upsertDefaultPaymentHandleForMerchant($input[Entity::SLUG], $handlePageId);

        $url = $this->paymentHandleHostedBaseUrl . '/' . $input[Entity::SLUG];

        $response = [];

        $response[Entity::URL] = $url;

        $response[Entity::TITLE] = $this->merchant->getBillingLabel();

        $response[Entity::SLUG] = $input[Entity::SLUG];

        if(empty($handlePageId) === true)
        {
            return $response;
        }

        $paymentLink->setShortUrl($url);

        $this->repo->saveOrFail($paymentLink);

        $response[Entity::ID] = $paymentLink->getPublicId();

        return $response;
    }

    public function getPaymentHandleByMerchant(Merchant\Entity $merchant): array
    {
        $merchantSettings = Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
            ->all();

        if (empty($merchantSettings) === true || empty($merchantSettings[ENTITY::DEFAULT_PAYMENT_HANDLE]) === true)
        {
            throw new BadRequestValidationFailureException(
                'Payment Handle does not exists for this merchant. Please create a new one');
        }

        $this->trace->info(TraceCode::PAYMENT_HANDLE_MERCHANT_SETTINGS, [
            Entity::SETTINGS  => array_get($merchantSettings, Entity::DEFAULT_PAYMENT_HANDLE),
        ]);

        $response[Entity::TITLE] = $merchant->getBillingLabel();

        $response[Entity::SLUG] =  $merchantSettings[ENTITY::DEFAULT_PAYMENT_HANDLE][Entity::DEFAULT_PAYMENT_HANDLE];

        $response[Entity::URL] = $this->paymentHandleHostedBaseUrl . '/' . $response[Entity::SLUG];

        $handlePageId = array_get($merchantSettings, Entity::DEFAULT_PAYMENT_HANDLE . '.' . Entity::DEFAULT_PAYMENT_HANDLE_PAGE_ID);

        if(empty($handlePageId) === true)
        {
            return $response;
        }

        $response[ENTITY::ID] = $handlePageId;

        return $response;
    }

    public function createSubscription(Entity $paymentLink, array $input, Merchant\Entity $merchant)
    {
        $ppItemId = $input[Entity::PAYMENT_PAGE_ITEM_ID];

        $ppItemId = Entity::stripDefaultSign($ppItemId);

        $ppItem = $this->repo->payment_page_item->findOrFailPublic($ppItemId);

        $planId = $ppItem->getPlanId();

        if (empty($planId) === true)
        {
            throw new BadRequestValidationFailureException(
                'plan is not present to create a subscription'
            );
        }

        $subscriptionDetails = $ppItem->getProductConfig(PaymentPageItem\Entity::SUBSCRIPTION_DETAILS);

        $subscriptionInput = $this->buildInputForSubscription($planId, $subscriptionDetails, $input);

        $headers['X-Razorpay-Source'] = 'subscription_button';

        $headers['X-Razorpay-SourceId'] = $paymentLink->getId();

        $responseJson = $this->app['module']->subscription->createSubscription($subscriptionInput, $merchant, $headers);

        $this->repo->transaction(function() use ($ppItem)
        {
            $this->repo->payment_page_item->lockForUpdateAndReload($ppItem);

            $ppItem->incrementQuantitySold(1);

            $this->repo->payment_page_item->saveOrFail($ppItem);

        });

        $this->trace->count(Metric::PAYMENT_PAGE_SUBSCRIPTION_CREATED, $paymentLink->getMetricDimensions());

        return ['subscription_id' => $responseJson['id']];
    }

    protected function buildInputForSubscription(string $planId, $subscriptionDetails, array $input)
    {
        $totalCount = $subscriptionDetails['total_count'] ?? 120;

        $quantity = $subscriptionDetails['quantity'] ?? 1;

        $customerNotify = $subscriptionDetails['customer_notify'] ?? 1;

        $inputForSubscription = [
            'plan_id'        => 'plan_'.$planId,
           'total_count'     => $totalCount,
           'quantity'        => $quantity,
           'customer_notify' => $customerNotify,
           'notes'           => $input[Entity::NOTES] ?? null,
       ];

        return $inputForSubscription;
    }

    /**
     * @param  Entity $paymentLink
     * @param  array  $input
     *
     * @return Entity
     * @throws BadRequestException
     * @throws BaseException
     */
    public function update(Entity $paymentLink, array $input): Entity
    {
        $udfSchema = $paymentLink->getSettings(Entity::UDF_SCHEMA);

        $this->trace->info(
            TraceCode::PAYMENT_LINK_UPDATE_REQUEST,
            [
                Entity::ID    => $paymentLink->getId(),
                Entity::INPUT => $input,
            ]);


        (new Validator())->validatePayerNameAndExpiryForUpdate($this->merchant, $input, $udfSchema);

        $settingCustomDomain = $paymentLink->getSettings(Entity::CUSTOM_DOMAIN);
        $settingCustomDomain = is_string($settingCustomDomain) ? $settingCustomDomain : "";

        $this->validateBulkUploadFlow($input,$this->merchant,$paymentLink);

        Tracer::inSpan(['name' => 'payment_page.update'], function() use($paymentLink, $input, $settingCustomDomain)
        {
            $this->repo->transaction(function () use ($paymentLink, $input) {

                Tracer::inSpan(['name' => 'payment_page.update.lock_and_reload'], function() use($paymentLink)
                {
                    $this->repo->payment_link->lockForUpdateAndReload($paymentLink);
                });

                $settings = $input[Entity::SETTINGS] ?? [];

                if (isset($input[Entity::PAYMENT_PAGE_ITEMS]) === true)
                {
                    Tracer::inSpan(['name' => 'payment_page.update.item_as_put'], function() use($input, $paymentLink)
                    {
                        (new PaymentPageItem\Core)->updatePaymentPageItemsAsPut(
                            $input[Entity::PAYMENT_PAGE_ITEMS],
                            $this->merchant,
                            $paymentLink
                        );
                    });

                    $settings[Entity::VERSION] = Version::V2;
                }

                if ($paymentLink->getViewType() === ViewType::FILE_UPLOAD_PAGE)
                {
                    $allFields = json_decode($paymentLink->getSettings(Entity::ALL_FIELDS), true);

                    if (isset($settings[Entity::UDF_SCHEMA])) {

                        $udfSchema = json_decode($settings[Entity::UDF_SCHEMA], true);

                        $allFields = $this->updateAllFieldsFromUDF($udfSchema, $allFields, $input);

                        $settings[Entity::ALL_FIELDS] = json_encode($allFields);

                    }
                }

                $paymentLink->edit($input);

                $validator = new Validator;

                if(array_key_exists(Entity::SLUG, $input) === true
                    && $paymentLink->getViewType() !== ViewType::PAYMENT_HANDLE
                    && array_get($settings, Entity::CUSTOM_DOMAIN, "") === "")
                {
                    $validator->validateGeneralSlug([
                        Entity::SLUG    => array_get($input, Entity::SLUG)
                    ]);
                }

                if($paymentLink->getViewType() !== ViewType::PAYMENT_HANDLE
                    && array_get($settings, Entity::CUSTOM_DOMAIN, "") !== "")
                {
                    $validator->validateCDSSlug([
                        Entity::SLUG            => array_get($input, Entity::SLUG),
                        Entity::CUSTOM_DOMAIN   => array_get($settings, Entity::CUSTOM_DOMAIN, ""),
                    ], $paymentLink);
                }

                $this->changeStatusAfterUpdateIfApplicable($paymentLink);

                Tracer::inSpan(['name' => 'payment_page.update.upsert_settings'], function() use($paymentLink, $settings)
                {
                    $this->upsertSettings($paymentLink, $settings);
                });

                Tracer::inSpan(['name' => 'payment_page.update.save_or_fail'], function() use($paymentLink)
                {
                    $this->repo->saveOrFail($paymentLink);
                });
            });

            $input[Entity::SETTINGS_CUSTOM_DOMAIN_KEY] = $settingCustomDomain;

            $this->updateShortUrlIfApplicable($paymentLink, $input);

            $this->repo->transaction(function () use ($paymentLink, $input) {
                $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

                $this->createCustomUrl($paymentLink, $input[Entity::SLUG] ?? null, array_get($input, Entity::SETTINGS . "." . Entity::CUSTOM_DOMAIN));
            });

            Tracer::inSpan(['name' => 'payment_page.update.load_relations'], function() use($paymentLink)
            {
                $this->repo->loadRelations($paymentLink);
            });

            Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
                $this->dispatchHostedCache($paymentLink);
            });

            $this->trace->info(TraceCode::PAYMENT_LINK_UPDATED, $paymentLink->toArrayPublic());
        });

        return $paymentLink;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $entity
     *
     * @return array
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function getSlugAndDomain(Entity $entity, string $settingsCustomDomain): array
    {
        $nocodeCore = new NocodeCustomUrl\Core;

        $nocodeEntity = $nocodeCore->getExistingLinkedEntity($entity->getId(), $entity->getMerchantId());

        if (empty($nocodeEntity) === true || empty($settingsCustomDomain) === true)
        {
            [$domain] = $this->getShortenUrlRequestParams($entity);

            return [$entity->getSlugFromShortUrl(), $domain];
        }

        return [$nocodeEntity->getSlug(), $nocodeEntity->getDomain()];
    }

    /**
     * Attempts recreating short URL for payment link in case of new slug in patch input
     *
     * @param Entity $paymentLink
     * @param array  $input
     *
     * @throws BadRequestException
     * @throws BaseException
     */
    public function updateShortUrlIfApplicable(Entity $paymentLink, array $input)
    {
        $entitySettingsCustomDomain = $input[Entity::SETTINGS_CUSTOM_DOMAIN_KEY];

        [$entitySlug, $entityDomain] = $this->getSlugAndDomain($paymentLink, $entitySettingsCustomDomain);

        $inputDomain = array_get($input, Entity::SETTINGS . "." . Entity::CUSTOM_DOMAIN, "");

        $inputSlug = $input[Entity::SLUG] ?? null;

        if ($inputSlug === $entitySlug)
        {
            $domain = NocodeCustomUrl\Entity::determineDomainFromUrl($entityDomain);

            if ($inputDomain === $domain || $inputDomain === $entitySettingsCustomDomain)
            {
                return;
            }
        }

        Tracer::inSpan(['name' => 'payment_page.create_and_set_short_url'], function() use($paymentLink, $inputSlug, $inputDomain)
        {
            $this->createAndSetShortUrl($paymentLink, $inputSlug, $inputDomain);
        });

        Tracer::inSpan(['name' => 'payment_page.update_url.save_or_fail'], function() use($paymentLink)
        {
            $this->repo->saveOrFail($paymentLink);
        });
    }

    public function deactivate(Entity $paymentLink): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_DEACTIVATE_REQUEST,
            [
                Entity::ID => $paymentLink->getPublicId(),
            ]);

        Tracer::inSpan(['name' => 'payment_page.deactivate.transaction'], function() use($paymentLink)
        {
            $this->repo->transaction(function () use ($paymentLink) {

                Tracer::inSpan(['name' => 'payment_page.deactivate.lock_and_reload'], function() use($paymentLink)
                {
                    $this->repo->payment_link->lockForUpdateAndReload($paymentLink);
                });

                Tracer::inSpan(['name' => 'payment_page.deactivate.validate'], function() use($paymentLink)
                {
                    $paymentLink->getValidator()->validateDeactivateOperation();
                });

                Tracer::inSpan(['name' => 'payment_page.deactivate.change_status'], function() use($paymentLink)
                {
                    $this->changeStatus($paymentLink, Status::INACTIVE, StatusReason::DEACTIVATED);
                });

                Tracer::inSpan(['name' => 'payment_page.deactivate.save_or_fail'], function() use($paymentLink) {
                    return $this->repo->saveOrFail($paymentLink);
                });
            });
        });

        $this->trace->info(TraceCode::PAYMENT_LINK_DEACTIVATED, $paymentLink->toArrayPublic());

        return $paymentLink;
    }

    public function activate(Entity $paymentLink, array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_ACTIVATE_REQUEST,
            [
                Entity::ID    => $paymentLink->getPublicId(),
                Entity::INPUT => $input,
            ]);

        Tracer::inSpan(['name' => 'payment_page.activate.transaction'], function() use($paymentLink, $input)
        {
            $this->repo->transaction(function () use ($paymentLink, $input) {

                Tracer::inSpan(['name' => 'payment_page.activate.lock_and_reload'], function() use($paymentLink)
                {
                    $this->repo->payment_link->lockForUpdateAndReload($paymentLink);
                });

                Tracer::inSpan(['name' => 'payment_page.activate.validate'], function() use($paymentLink)
                {
                    $paymentLink->getValidator()->validateActivateOperation();
                });

                $paymentLink->edit($input);

                Tracer::inSpan(['name' => 'payment_page.activate.validate'], function() use($paymentLink)
                {
                    $paymentLink->getValidator()->validateShouldActivationBeAllowed();
                });

                Tracer::inSpan(['name' => 'payment_page.activate.change_status'], function() use($paymentLink)
                {
                    $this->changeStatus($paymentLink, Status::ACTIVE, null);
                });

                Tracer::inSpan(['name' => 'payment_page.activate.save_or_fail'], function() use($paymentLink)
                {
                    $this->repo->saveOrFail($paymentLink);
                });
            });

            Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
                $this->dispatchHostedCache($paymentLink);
            });
        });

        $this->trace->info(TraceCode::PAYMENT_LINK_ACTIVATED, $paymentLink->toArrayPublic());

        return $paymentLink;
    }

    /**
     * Sends email/sms notifications to a customer with a payment link
     *
     * @param  Entity $paymentLink
     * @param  array  $input
     */
    public function sendNotification(Entity $paymentLink, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_SEND_NOTIFICATION,
            [
                Entity::ID    => $paymentLink->getId(),
                Entity::INPUT => $input,
            ]);

        Tracer::inSpan(['name' => 'payment_page.send_notification.validate'], function() use($input, $paymentLink)
        {
            $paymentLink->getValidator()->validateSendNotification($input);
        });

        Tracer::inSpan(['name' => 'payment_page.send_notification.notify_by_email_and_sms'], function() use($paymentLink, $input)
        {
            (new Notifier)->notifyByEmailAndSms($paymentLink, $input);
        });
    }

    /**
     * Validates if new payment initiation should be allowed or not.
     * Note : Only quantity validations are done here because it is being called early in the flow of
     * create payment
     *
     * @param array $input
     *
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */

    public function validatePaymentPagePaymentFromInput(array $input)
    {
        if (array_key_exists(Payment\Entity::PAYMENT_LINK_ID, $input) === false)
        {
            return;
        }

        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            throw new BadRequestValidationFailureException(
                'order_id is required to create payment for payment page'
            );
        }
        $order = $this->repo->order->findByPublicIdAndMerchant(
            $input[Payment\Entity::ORDER_ID],
            $this->merchant);


        $paymentLinkId = $input[Payment\Entity::PAYMENT_LINK_ID];

        $this->trace->info(
            TraceCode::PAYMENT_PAGE_PAYMENT_VALIDATION,
            [
                'input'     => $paymentLinkId,
            ]);

        $paymentLink   = $this->repo->payment_link->findByPublicIdAndMerchant($paymentLinkId, $this->merchant);

        // 3. Validates payment link is active and has payment slots available
        if (($paymentLink->isPayable() === false) or
            ($this->hasPaymentSlots($paymentLink, $order) === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_LINK_NOT_PAYABLE,
                null,
                [
                    E::PAYMENT_LINK => $paymentLink->toArrayPublic(),
                ]);
        }
    }
    /**
     * Validates if new payment initiation should be allowed or not.
     * Note: This is intentionally not in Validator class, because there is much logic(probably more very soon) and it
     * accesses repository as well.
     * quantity validations has been moved to separate function because that is being called early in the flow of
     * create payment
     *
     * @param Entity         $paymentLink
     * @param Payment\Entity $payment
     *
     * @throws BadRequestException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateIsPaymentInitiatable(Entity $paymentLink, Payment\Entity $payment)
    {
        $this->trace->count(Metric::PAYMENT_PAGE_PAYMENT_ATTEMPTS_TOTAL, $paymentLink->getMetricDimensions());

        $paymentLink->getValidator()->validatePaymentCurrency($payment);

        // 2. Validates Payment notes (UDF values), if applicable
        $udfSchema = new UdfSchema($paymentLink);

        if ($udfSchema->exists() === true)
        {
            $paymentNotes = $payment->getNotes()->toArray();

            $udfSchema->validate($paymentNotes);
        }

        if ($payment->hasOrder() === false) {
            throw new BadRequestValidationFailureException(
                'order_id is required to create payment for payment page'
            );
        }


        $order = $payment->order;

        $lineItems = $order->lineItems()->get();

        if ($lineItems->count() === 0)
        {
            throw new BadRequestValidationFailureException(
                'order does not belongs to the given payment page'
            );
        }

        foreach ($lineItems as $lineItem)
        {
            if ($lineItem->getRefType() !== E::PAYMENT_PAGE_ITEM)
            {
                throw new BadRequestValidationFailureException(
                    'order does not belongs to the given payment page'
                );
            }

            $paymentPageItem = $lineItem->ref;

            if ($paymentLink->getId() !== $paymentPageItem->paymentLink->getId())
            {
                throw new BadRequestValidationFailureException(
                    'order does not belongs to the given payment page'
                );
            }
        }

        if ($this->hasRequiredAmountAndQuantity($paymentLink, $order) === false)
        {
            throw new BadRequestValidationFailureException(
                self::AMOUT_QUANTITY_TAMPERED
            );
        }
    }

    public function handleNocodeAppsPaymentEvent(Payment\Entity $payment)
    {
        $this->trace->info(TraceCode::NO_CODE_APPS_PAYMENT_EVENT_RECEIVED, [
            'payment'   => $this->getPaymentContextForLogging($payment),
        ]);

        $env = $this->app['env'];

        if (Environment::isEnvironmentQA($env) || $env === Environment::TESTING)
        {
            $this->processNocodeAppsPaymentEvent($payment);

            return;
        }

        PaymentPageProcessor::dispatch($this->mode, [
            'payment_id'    => $payment->getId(),
            'start_time'    => millitime(),
            'event'         => PaymentPageProcessor::NO_CODE_APPS_PAYMENT_EVENT,
        ]);
    }

    public function postPaymentCaptureUpdatePaymentPage(Payment\Entity $payment)
    {
        $this->trace->info(TraceCode::PAYMENT_LINK_POST_PAYMENT_CAPTURE_EVENT_RECIEVED, [
            'payment'   => $this->getPaymentContextForLogging($payment),
        ]);

        $env = $this->app['env'];

        if (Environment::isEnvironmentQA($env) || $env === Environment::TESTING)
        {
            $this->postPaymentCaptureAttemptProcessing($payment);

            return;
        }

        PaymentPageProcessor::dispatch($this->mode, [
            'payment_id'    => $payment->getId(),
            'start_time'    => millitime(),
            'event'         => PaymentPageProcessor::PAYMENT_CAPTURE_EVENT,
        ]);
    }

    public function postPaymentRefundUpdatePaymentPageDispatcher(Payment\Refund\Entity $refund)
    {
        $context = $this->getRefundContext($refund);
        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_PROCESS_INIT, $context);

        PaymentPageProcessor::dispatch($this->mode, [
            'event'     => PaymentPageProcessor::REFUND_PROCESSED_EVENT,
            'refund_id' => $refund->getId(),
            'merchant'  => $refund->merchant,
            'start_time'=> millitime(),
        ]);

        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_PROCESS_DISPATCHED, $context);
    }

    public function postPaymentRefundUpdatePaymentPage(Payment\Refund\Entity $refund)
    {
        assertTrue($refund->payment->hasPaymentLink());

        $context = $this->getRefundContext($refund);

        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_PROCESS_START, $context);

        $this->repo->transaction(function() use ($refund) {
            $lineItems = $refund->payment->order->lineItems()->get();
            $unitsSold  = 0;

            // Partial refund is not supported, will be handling the use case in future
            if ($refund->payment->isFullyRefunded() === true)
            {
                $unitsSold = $lineItems->sum(function ($item) {
                    return $item->getQuantity();
                });
            }

            $this->updateDonationGoalTrackerKeys($refund->payment->paymentLink,  [
                Entity::SOLD_UNITS          => $unitsSold,
                Entity::COLLECTED_AMOUNT    => $refund->getAmount(),
                Entity::SUPPORTER_COUNT     => $unitsSold === 0 ? 0 : 1,
            ], true);
        });

        $this->updateHostedCache($refund->payment->paymentLink);

        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_PROCESS_COMPLETED, $context);
    }

    /**
     * This method is called post a payment capture is attempted (failed or success) in Processor/Authorize. Refer below
     * cases on what this method handdles.
     *
     * @param Payment\Entity $payment
     */
    public function postPaymentCaptureAttemptProcessing(Payment\Entity $payment, $async = false)
    {
        assertTrue($payment->hasPaymentLink());

        $paymentLink = $payment->paymentLink;

        $this->trace->info(
            TraceCode::PAYMENT_LINK_PAYMENT_CAPTURE_PROCESS,
            [
                'payment_id'        => $payment->getId(),
                'payment_status'    => $payment->getStatus(),
                'payment_link'      => $paymentLink->toArrayPublic(),
                'async'             => $async,
                'payment'           => $this->getPaymentContextForLogging($payment),
            ]);

        //
        // Case 1: If payment was not captured (i.e. stuck in authorized state), refund it immediately and return as
        // there is nothing else to be done here.
        //
        $shouldRefundPayment = ($payment->isCaptured() === false);

        if ($shouldRefundPayment === true)
        {
            $this->refundPayment($paymentLink, $payment);
            return;
        }

        $this->repo->transaction(function() use ($paymentLink, $payment, & $shouldRefundPayment)
        {
            $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

            //
            // Case 2: If payment is captured and there the link is still payable, accept the payment and update entity
            // Note: Updating entity happens in transaction with lock on pl entity, so other process doesn't read & work
            // on bad value.
            //
            if ($paymentLink->isPayable() === true)
            {
                $this->updatePaymentLinkAfterPaymentCapture($paymentLink, $payment);
            }
            //
            // Case 3: If payment is captured but now the link is not payable, refund it immediately
            // Note: We are just setting up a flag here and not initiating the refund here actually, because this block
            // is wrapped in a db transaction. We do actual refund outside this block.
            //
            else
            {
                // If payment is autocaptured and payment page is not payable, then we blindly refund the payment
                if ($payment->getAutoCaptured() === true)
                {
                    $shouldRefundPayment = true;
                }
                else
                {
                    // In case payment is not auto captured,(late auth, upi payment edge cases etc)
                    // the merchant has captured manually and expects the payment
                    // to be captured. In such edge cases, even though the page is expired, we update the quantities
                    $this->updatePaymentLinkAfterPaymentCapture($paymentLink, $payment);
                }
            }
        });

        // Follow up to Case 3 (Refer above ^ comment)
        if ($shouldRefundPayment === true)
        {
            $this->refundPayment($paymentLink, $payment);
            return;
        }

        try
        {
            $this->createInvoiceIfEnabled($paymentLink, $payment);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }

        $this->doPostPaymentRiskActions($paymentLink, $payment);

        $this->eventPaymentPagePaid($paymentLink, $payment);

        $this->updateHostedCache($paymentLink);
    }

    public function createOrder(Entity $paymentLink, array $input)
    {
        Tracer::inSpan(['name' => 'payment_page.order.create.validate_payment_link'], function() use($paymentLink)
        {
            $paymentLink->getValidator()->validatePaymentLinkToCreateOrder();
        });

        Tracer::inSpan(['name' => 'payment_page.order.create.validate'], function() use($paymentLink, $input)
        {
            $paymentLink->getValidator()->validateInput('create_order', $input);
        });

        $input = Tracer::inSpan(['name' => 'payment_page.order.create.modify_and_validate_input'], function() use($paymentLink, $input)
        {
            return $this->modifyAndValidateInputToCreateLineItems($input, $paymentLink);
        });

        $setting =  $paymentLink->getSettings()->toArray();

        $oneCCEnabled = $setting[Entity::ONE_CLICK_CHECKOUT] ?? '0';

        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::PP_MAGIC_SETTING,
            $this->mode
        );

        $totalAmount = $this->getTotalAmountForOrder($input[Entity::LINE_ITEMS]);

        $order = Tracer::inSpan(['name' => 'payment_page.order.create.create_order'], function() use($variant, $oneCCEnabled, $totalAmount, $paymentLink, $input)
        {
            $orderReq = [
                Order\Entity::AMOUNT => $totalAmount,
                Order\Entity::CURRENCY => $paymentLink->getCurrency(),
                Order\Entity::PAYMENT_CAPTURE => true,
                Order\Entity::NOTES => $input[Order\Entity::NOTES] ?? [],
                Order\Entity::PRODUCT_TYPE => $paymentLink->getProductType(),
                Order\Entity::PRODUCT_ID => $paymentLink->getId(),
            ];
            if ($oneCCEnabled === '1' && strtolower($variant) === 'on'){
                $orderReq = array_merge($orderReq, [Fields::LINE_ITEMS_TOTAL => $totalAmount]);
            }
            return (new Order\Core)->create(
                $orderReq,
                $paymentLink->merchant
            );
        });

        Tracer::inSpan(['name' => 'payment_page.order.create.create_line_item'], function() use($order, $input)
        {
            (new LineItem\Core)->createMany($input[Entity::LINE_ITEMS], $this->merchant, $order);
        });

        $lineItems = $order->lineItems()->get();

        return [
            Entity::ORDER      => $order,
            Entity::LINE_ITEMS => $lineItems
        ];
    }

    public function setMerchantDetails(array $settings)
    {
        Tracer::inSpan(['name' => 'payment_page.merchant_details.set.validate'], function() use($settings) {
            (new Validator())->validateSetMerchantDetails($settings);
        });

        $merchant = $this->merchant;

        Tracer::inSpan(['name' => 'payment_page.merchant_details.set.upsert_and_save'], function() use($merchant, $settings)
        {
            Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
                ->upsert($settings)
                ->save();
        });

        return Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
            ->all();
    }

    public function fetchMerchantDetails()
    {
        $merchant = $this->merchant;

        return Settings\Accessor::for($merchant, Settings\Module::PAYMENT_LINK)
            ->all();
    }

    public function setReceiptDetails(Entity $paymentLink, array $input)
    {
        $validator = new Validator($paymentLink);

        Tracer::inSpan(['name' => 'payment_page.recipts.create.validate'], function() use ($validator, $input) {
            $validator->validateSetInvoiceDetails($input);
        });

        Tracer::inSpan(['name' => 'payment_page.recipts.create.upsert.settings'], function() use ($input, $paymentLink) {
            $this->upsertSettings($paymentLink, $input);
        });

        $receiptSettings = Settings\Accessor::for($paymentLink, Settings\Module::PAYMENT_LINK)
            ->all();

        $response = array_intersect_key($receiptSettings->toArray(), array_flip(Entity::INVOICE_DETAILS_KEYS));

        Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
            $this->dispatchHostedCache($paymentLink);
        });

        return $response;
    }

    public function getInvoiceDetails(string $paymentId)
    {
        $payment = Tracer::inSpan(['name' => 'payment_page.invoice.find_entity'], function() use($paymentId)
        {
            return $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);
        });

        $order = $payment->order;

        if(empty($order) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'Receipt is not generated for this payment');
        }

        $invoice = $order->invoice;

        if(empty($invoice) === true)
        {
            $invoice = $this->generateInvoiceForGetRecieptIfPosssible($payment);
        }

        $invoiceId = $invoice->getPublicId();

        $receipt = $invoice->getReceipt();

        $response = [
            'invoice_id' => $invoiceId,
            'receipt'    => $receipt,
        ];

        $invoiceCore = new Invoice\Core();

        $pdf = Tracer::inSpan(['name' => 'payment_page.invoice.get_fresh_invoice'], function() use($invoiceCore, $invoice)
        {
            return $invoiceCore->getFreshInvoicePdf($invoice);
        });

        if ($pdf === null)
        {
            return $response;
        }

        $pdfUrl = Tracer::inSpan(['name' => 'payment_page.invoice.get_signed_url'], function() use($invoice)
        {
            return (new Invoice\FileUploadUfh())->getSignedUrl($invoice);
        });

        $response['receipt_download_url'] = $pdfUrl;

        return $response;
    }

    public function sendReceipt(string $paymentId, array $input)
    {
        $payment = Tracer::inSpan(['name' => 'payment_page.receipt.send.find_payment'], function() use($paymentId)
        {
            return $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);
        });

        $order = $payment->order;

        $invoice = $order->invoice;

        if(empty($invoice) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
            null,
            null,
            'Receipt is not generated for this payment');
        }

        Tracer::inSpan(['name' => 'payment_page.receipt.send.validate_input'], function() use($input)
        {
            (new Validator)->validateInput('save_receipt_if_present', $input);
        });

        if(isset($input[Invoice\Entity::RECEIPT]) === true)
        {
            $receipt = $input[Invoice\Entity::RECEIPT];

            Tracer::inSpan(['name' => 'payment_page.receipt.send.set_attribute'], function() use($invoice, $receipt)
            {
                $invoice->setAttribute(Invoice\Entity::RECEIPT, $receipt);
            });

            Tracer::inSpan(['name' => 'payment_page.receipt.send.save'], function() use($invoice)
            {
                $this->repo->invoice->save($invoice);
            });
        }

        $invoiceCore = new Invoice\Core();

        $invoice->setRelation('entity', $invoice->entity);

        return Tracer::inSpan(['name' => 'payment_page.receipt.send.send_notification'], function() use($invoiceCore, $invoice)
        {
            return $invoiceCore->sendNotification($invoice, Invoice\NotifyMedium::EMAIL, true);
        });

    }

    public function saveReceiptForPaymentAndGeneratePdf(string $paymentId, array $input)
    {
        $payment = Tracer::inSpan(['name' => 'payment_page.receipt.save.find_payment'], function() use($paymentId)
        {
            return $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);
        });

        $order = $payment->order;

        $invoice = $order->invoice;

        if(empty($invoice) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'Receipt is not generated for this payment');
        }

        Tracer::inSpan(['name' => 'payment_page.receipt.save.validate'], function() use($input)
        {
            (new Validator)->validateInput('save_receipt', $input);
        });

        if(isset($input[Invoice\Entity::RECEIPT]) === true)
        {
            $receipt = $input[Invoice\Entity::RECEIPT];

            Tracer::inSpan(['name' => 'payment_page.receipt.save.set_attribute'], function() use($invoice, $receipt)
            {
                $invoice->setAttribute(Invoice\Entity::RECEIPT, $receipt);
            });

            Tracer::inSpan(['name' => 'payment_page.receipt.save.save'], function() use($invoice)
            {
                $this->repo->invoice->save($invoice);
            });
        }

        $invoiceCore = new Invoice\Core();

        $invoice->setRelation('entity', $invoice->entity);

        return Tracer::inSpan(['name' => 'payment_page.receipt.save.create_invoice_pdf'], function() use($invoice, $invoiceCore)
        {
            return $invoiceCore->createInvoicePdf($invoice);
        });
    }

    public function getAttributesForPaymentHandlePreview(array $input, Merchant\Entity $merchant, string $slug): array
    {
        $paymentPage = (new Entity)->generateId();

        $paymentPage->merchant()->associate($merchant);

        $settings = $input[Entity::SETTINGS] ?? [];

        $paymentPage->build($input);

        $payload = (new ViewSerializer($paymentPage))->serializeForHosted();

        $payload['is_preview'] = true;

        $payload[Entity::SETTINGS][Entity::UDF_SCHEMA] = "[{\"name\":\"comment\",\"title\":\"Comment\",\"required\":true,\"type\":\"string\",\"options\":{},\"settings\":{\"position\":1}}]";

        $payload['payment_link'][Entity::HANDLE_URL] = $this->paymentHandleHostedBaseUrl . '/' . $slug;

        $payload['payment_link'][Entity::PAYMENT_PAGE_ITEMS] = [
            Item\Entity::ITEM => [
                Item\Entity::ID        => "item_0000000000",
                Item\Entity::NAME      => "amount",
                Item\Entity::CURRENCY  => "INR",
                Item\Entity::TYPE      => "payment_page",
            ],
            PaymentPageItem\Entity::MIN_AMOUNT  => 100,
            PaymentPageItem\Entity::SETTINGS    => [
                PaymentPageItem\Entity::POSITION      => "0"
            ],
        ];
        return $payload;
    }

    public function getPaymentHandleCustomAmountEncryptionHeaders(): array
    {
        return $params = [
            AESEncryption::MODE => AES::MODE_CBC,
            AESEncryption::IV => '',
            AESEncryption::SECRET => $this->app['config']['app']['payment_handle']['secret'],
        ];
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $entity
     *
     * @return int
     */
    public function updateAndGetCapturedPaymentCount(Entity $entity): int
    {
        $capturedPaymentCount = 0;

        if ($this->repo->isTransactionActive() === false)
        {
            $this->repo->transaction(function() use ($entity, & $capturedPaymentCount) {

                $this->repo->payment_link->lockForUpdateAndReload($entity);

                $capturedPaymentCount = $this->updateAndGetCapturedPaymentCount($entity);
            });

            return $capturedPaymentCount;
        }

        $capturedPaymentCount = $this->repo->payment->getCapturedPaymentsForPaymentPage($entity);

        $this->updateCapturedPaymentCount($capturedPaymentCount, $entity);

        return $capturedPaymentCount;
    }

    /**
     * @param int                            $count
     * @param \RZP\Models\PaymentLink\Entity $entity
     * @param array                          $existingComputedSettings
     *
     * @return void
     */
    private function updateCapturedPaymentCount(int $count, Entity $entity, array $existingComputedSettings = [])
    {
        $this->repo->assertTransactionActive();

        if (empty($existingComputedSettings) === true)
        {
            $existingComputedSettings = $entity->getComputedSettings()->toArray();
        }

        $existingComputedSettings[Entity::CAPTURED_PAYMENTS_COUNT] = $count;

        $entity->getComputedSettingsAccessor()->upsert($existingComputedSettings)->save();
    }

    public function processNocodeAppsPaymentEvent(Payment\Entity $payment)
    {
        $ncaService = new NoCodeAppsService($this->app);

        $res = $ncaService->sendS2SPaymentEvent($payment);

        $this->trace->info(TraceCode::NOCODE_SERVICE_RESPONSE_RECIEVED, [$res]);
    }

    protected function addAdditionalDataToSettings(array & $settings, Entity $paymentLink)
    {
        $settings[Entity::CHECKOUT_OPTIONS] = [
            'email' => 'email',
            'phone' => 'phone',
        ];

        $settings[Entity::PAYMENT_BUTTON_LABEL] = 'Pay';
    }

    protected function createPaymentPageItems(array $input, Entity $paymentLink)
    {
        (new PaymentPageItem\Core)->createMany(
            $input[Entity::PAYMENT_PAGE_ITEMS],
            $this->merchant,
            $paymentLink
        );
    }

    protected function updatePaymentLinkAfterPaymentCapture(Entity $paymentLink, Payment\Entity $payment)
    {
        // Multiple payment process attempts to update attributes of link entity.
        $this->repo->assertTransactionActive();

        $paymentLink->incrementTotalAmountPaidBy($payment->getAdjustedAmountWrtCustFeeBearer());

        $order = $payment->order;

        $lineItems = $order->lineItems()->get();

        // for donation goal tracker
        $unitsSold          = 0;
        $collectedAmount    = 0;

        foreach ($lineItems as $lineItem)
        {
            $paymentPageItem = $lineItem->ref;

            $paymentPageItem->incrementQuantitySold($lineItem->getQuantity());

            $unitsSold  += $lineItem->getQuantity();

            $paymentPageItem->incrementTotalAmountPaidBy($lineItem->getQuantity() * $lineItem->getAmount());

            $collectedAmount    += $lineItem->getQuantity() * $lineItem->getAmount();

            $paymentPageItem->saveOrFail();
        }

        if (($paymentLink->isTimesPayableExhausted() === true) and
            ($paymentLink->getViewType() !== Entity::VIEW_TYPE_STORE))
        {
            $this->changeStatus($paymentLink, Status::INACTIVE, StatusReason::COMPLETED);
        }

        $this->repo->saveOrFail($paymentLink);

        $this->trace->info(
            TraceCode::PAYMENT_LINK_UPDATED_POST_PAYMENT_CAPTURE,
            [
                Entity::PAYMENT_ID => $payment->getId(),
                E::PAYMENT_LINK    => $paymentLink->toArrayPublic(),
            ]);

        //Update PaymentPageRecord entity

        if ($paymentLink->getViewType() === ViewType::FILE_UPLOAD_PAGE)
        {
            $notes = $payment->toArray()['notes'];

            $this->trace->info(TraceCode::PAYMENT_PAGE_RECORD_STATUS_UPDATE, [
                'payment_link_id' => $paymentLink->getId(),
                'payment_id' => $payment->getId(),
                'notes' => $notes
            ]);

            if(array_key_exists(PaymentPageRecord\Entity::PRIMARY_REF_ID,$notes) === true)
            {
                $priRefId = $notes[PaymentPageRecord\Entity::PRIMARY_REF_ID];

                $paymentPageRecord = $this->repo->payment_page_record->findByPaymentPageAndPrimaryRefIdOrFail($paymentLink->getId(),$priRefId);

                $paymentPageRecord->setStatus(PaymentPageRecord\Status::PAID);

                $this->repo->saveOrFail($paymentPageRecord);
            }

        }


        // update donation goal tracker dynamic keys if applicable
        $this->updateDonationGoalTrackerKeys($paymentLink, [
            Entity::SOLD_UNITS          => $unitsSold,
            Entity::COLLECTED_AMOUNT    => $collectedAmount,
            Entity::SUPPORTER_COUNT     => 1
        ]);

        // update captured payment count for the entity
        $this->updateCapturedPaymentCountOnthePage($paymentLink);

        $this->trace->count(Metric::PAYMENT_PAGE_PAID_TOTAL, $paymentLink->getMetricDimensions());
    }

    protected function updateDonationGoalTrackerKeys(Entity $paymentLink, array $items, bool $decrement = false): void
    {
        $this->repo->assertTransactionActive();

        $settings   = $paymentLink->getSettings()->toArray();

        if (empty(array_get($settings, Entity::GOAL_TRACKER.'.'.Entity::META_DATA, [])))
        {
            return;
        }

        $computedSettings   = $paymentLink->getComputedSettings()->toArray();

        $context = [
            "items"     => $items,
            "entity"    => [
                Entity::ID  => $paymentLink->getId(),
            ],
            Entity::GOAL_TRACKER            => $settings[Entity::GOAL_TRACKER][Entity::META_DATA],
            Entity::COMPUTED_GOAL_TRACKER   => $computedSettings,
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_DONATION_GOAL_TRACKER_UPDATES_START, $context);

        $multiplier = $decrement ? -1 : 1;
        $code       = $decrement
            ? TraceCode::PAYMENT_LINK_DONATION_GOAL_TRACKER_DECREMENT
            : TraceCode::PAYMENT_LINK_DONATION_GOAL_TRACKER_INCREMENT;

        $this->trace->info($code, $context);

        $metadaKey          = Entity::GOAL_TRACKER.'.'.Entity::META_DATA;
        $amountKey          = $metadaKey.'.'.Entity::COLLECTED_AMOUNT;
        $soldUnitKey        = $metadaKey.'.'.Entity::SOLD_UNITS;
        $supporterCountKey  = $metadaKey.'.'.Entity::SUPPORTER_COUNT;

        $amount         = ((int) array_get($computedSettings, $amountKey, "0")) + ($multiplier * $items[Entity::COLLECTED_AMOUNT]);
        $soldUnit       = ((int) array_get($computedSettings, $soldUnitKey, "0")) + ($multiplier * $items[Entity::SOLD_UNITS]);
        $supporterCount = ((int) array_get($computedSettings, $supporterCountKey, "0")) + ($multiplier * $items[Entity::SUPPORTER_COUNT]);

        array_set($computedSettings, $amountKey, $amount < 0 ? 0 : $amount);
        array_set($computedSettings, $soldUnitKey, $soldUnit < 0 ? 0 : $soldUnit);
        array_set($computedSettings, $supporterCountKey, $supporterCount < 0 ? 0 : $supporterCount);

        $paymentLink->getComputedSettingsAccessor()->upsert($computedSettings)->save();

        $this->trace->info(TraceCode::PAYMENT_LINK_DONATION_GOAL_TRACKER_UPDATES_COMPLETED, $context);
    }

    protected function createInvoiceIfEnabled(Entity $paymentLink, Payment\Entity $payment)
    {
        if($paymentLink->isReceiptEnabled() === false)
        {
            return;
        }

        $merchant = $paymentLink->merchant;

        $invoiceCreateInput = $this->getInvoiceCreateInput($paymentLink, $payment);

        $invoiceCore = (new Invoice\Core());

        $invoice = $invoiceCore->create(
            $invoiceCreateInput,
            $merchant,
            null,
            null,
            $paymentLink,
            null,
            $payment->order);

        $invoice->setStatus(Invoice\Status::PAID);

        $this->repo->save($invoice);

        $customSerialNumberEnabled = $paymentLink->isCustomSerialNumberEnabled();

        $shouldSendEmail = $customSerialNumberEnabled ? false : true;

        if ($shouldSendEmail === true)
        {
            $invoice->setRelation('entity', $invoice->entity);

            return $invoiceCore->sendNotification($invoice, Invoice\NotifyMedium::EMAIL, true);
        }

        $this->trace->count(Metric::PAYMENT_PAGE_RECEIPT_GENERATED, $paymentLink->getMetricDimensions());
    }

    public function addCustomAmountForPaymentHandleIfRequired(array & $payload, string $host, array $input)
    {
        if(isset($input[Entity::AMOUNT]) === true && $host === config('app.payment_handle_domain'))
        {
            $decryptedAmount = $this->decryptCustomAmountForPaymentHandle($input[Entity::AMOUNT]);

            if($decryptedAmount === '')
            {
                $this->trace->count(Metric::PAYMENT_HANDLE_DECRYPTION_UNSUCCESSFUL_TOTAL);

                $this->trace->info(TraceCode::PAYMENT_HANDLE_DECRYPTION_FAILED, [
                    'encryptedAmount'    => $input[Entity::AMOUNT],
                ]);

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    [
                        Entity::AMOUNT => $input[Entity::AMOUNT],
                        Entity::VIEW_TYPE => ViewType::PAYMENT_HANDLE
                    ],
                    'Amount not valid.');
            }

            $payload['data'][Entity::PAYMENT_HANDLE_AMOUNT] = $decryptedAmount;

            $this->trace->count(Metric::PAYMENT_HANDLE_DECRYPTION_SUCCESSFUL_TOTAL);

            $this->trace->info(TraceCode::PAYMENT_HANDLE_DECRYPTION_SUCCESSFUL, [
                'encryptedAmount'    => $input[Entity::AMOUNT],
            ]);
        }
    }

    public function encryptAmountForPaymentHandle(array $input): array
    {
        $params = $this->getPaymentHandleCustomAmountEncryptionHeaders();

        $encryptedAmount = (new AESEncryption($params))->encrypt($input[Entity::AMOUNT]);

        $encryptedAmount = base64_encode($encryptedAmount);

        $encryptedAmount = urlencode($encryptedAmount);

        return [Entity::ENCRYPTED_AMOUNT => $encryptedAmount];
    }

    public function decryptCustomAmountForPaymentHandle(string $encryptedAmount)
    {
        $params = $this->getPaymentHandleCustomAmountEncryptionHeaders();

        $decryptedAmount = base64_decode($encryptedAmount);

        $decryptedAmount = (new AESEncryption($params))->decrypt($decryptedAmount);

        return $decryptedAmount;
    }

    public function getTitleForPaymentHandle(Merchant\Entity $merchant): string
    {
        $title = $merchant->getBillingLabel();

        if(strlen($title) > Entity::MAX_TITLE_LENGTH)
        {
            $title = substr($title, 0, 80);
        }

        if(strlen($title) < Entity::MIN_TITLE_LENGTH)
        {
            $title = $title . rand(100, 1000);
        }

        return $title;
    }

    protected function getInvoiceCreateInput(Entity $paymentLink, Payment\Entity $payment): array
    {
        $type = Invoice\Type::INVOICE;

        $order = $payment->order;

        $customer = [
            Customer\Entity::CONTACT   => $payment->getContact(),
            Customer\Entity::EMAIL     => $payment->getEmail()
        ];

        $comment = Settings\Accessor::for($paymentLink, Settings\Module::PAYMENT_LINK)
            ->get(Entity::PAYMENT_SUCCESS_MESSAGE);

        $lineItems = $this->getLineItemsInput($order);

        $terms = $paymentLink->getAttribute(Entity::TERMS);

        $customSerialNumberEnabled = $paymentLink->isCustomSerialNumberEnabled();

        $receipt = $customSerialNumberEnabled ? null : $payment->getPublicId();

        $input = [
            IE::TYPE                => $type,
            IE::EMAIL_NOTIFY        => 0,
            IE::SMS_NOTIFY          => 0,
            IE::CUSTOMER            => $customer,
            IE::LINE_ITEMS          => $lineItems,
            IE::COMMENT             => is_string($comment) ? $comment : null,
            IE::TERMS               => $terms,
            IE::RECEIPT             => $receipt,
            IE::REMINDER_ENABLE     => false,
            IE::CURRENCY            => $paymentLink->getCurrency() ?? 'INR',
            IE::DATE                => $payment->getCapturedAt(),
        ];

        $input = array_filter(
            $input,
            function ($value) {
                return $value !== null;
            }
        );

        return $input;
    }

    protected function getLineItemsInput(Order\Entity $order)
    {
        $invoiceLineItems = [];

        $lineItems = $order->lineItems()->get()->all();

        foreach ($lineItems as $lineItem)
        {
            $invoiceLineItem = [
                LineItem\Entity::NAME           => $lineItem->getName(),
                LineItem\Entity::DESCRIPTION    => $lineItem->getDescription(),
                LineItem\Entity::AMOUNT         => $lineItem->getAmount(),
                LineItem\Entity::CURRENCY       => $lineItem->getCurrency(),
                LineItem\Entity::QUANTITY       => $lineItem->getQuantity()
            ];
            array_push($invoiceLineItems, $invoiceLineItem);
        }

        return $invoiceLineItems;
    }

    /**
     * This is called after edit/update operation. Post building the entity with request input we check if payment
     * link's status needs changing.
     *
     * Payment link's status:
     * - will be marked complete if all the stock of the pp items are exhausted
     *
     * Currently there is no other cases. Expire by edits will not affect this because that must already by at least
     * 15 minutes in future (validated via Validator method during build).
     *
     * @param Entity $paymentLink
     */
    protected function changeStatusAfterUpdateIfApplicable(Entity $paymentLink)
    {
        $this->repo->assertTransactionActive();

        if ($paymentLink->isTimesPayableExhausted() === true)
        {
            $this->changeStatus($paymentLink, Status::INACTIVE, StatusReason::COMPLETED);
        }
    }

    /**
     * Changes payment link's status. Every status change must happen via this method which keeps a uniform log of
     * status changes and probably could do further things i.e. validation etc.
     *
     * @param Entity      $paymentLink
     * @param string      $status
     * @param string|null $statusReason
     */
    protected function changeStatus(Entity $paymentLink, string $status, string $statusReason = null)
    {
        //
        // Caller of this function must be wrapped in a database txn because we are updating entity's attributes &
        // status which are shared in multiple payment process & entity operation in parallel.
        //
        $this->repo->assertTransactionActive();

        $oldStatus       = $paymentLink->getStatus();
        $oldStatusReason = $paymentLink->getStatusReason();

        $paymentLink->setStatus($status);
        $paymentLink->setStatusReason($statusReason);

        $this->trace->debug(
            TraceCode::PAYMENT_LINK_STATUS_CHANGE,
            [
                Entity::ID                 => $paymentLink->getId(),
                Entity::FROM_STATUS        => $oldStatus,
                Entity::FROM_STATUS_REASON => $oldStatusReason,
                Entity::TO_STATUS          => $status,
                Entity::TO_STATUS_REASON   => $statusReason,
            ]);
    }

    protected function trackPaymentPageCreatedEvent(Entity $paymentLink, array $input)
    {
        if (empty($input[Entity::TEMPLATE_TYPE]) === true)
        {
            return;
        }

        $customProperties[Entity::TEMPLATE_TYPE] = $input[Entity::TEMPLATE_TYPE];

        $this->app['diag']->trackPaymentPageEvent(EventCode::PAYMENT_PAGE_CREATED, $paymentLink, null, $customProperties);
    }

    /**
     * Given payment link is payable(i.e. active and not expired etc), checks if a new payment can be accepted by
     * counting existing succeeding payments (i.e. payments in created/authorized statuses).
     *
     * @param  Entity       $paymentLink
     * @param  Order\Entity $order
     *
     * @return boolean
     */
    protected function hasPaymentSlots(Entity $paymentLink, Order\Entity $order): bool
    {
        $lineItems = $order->lineItems()->get();

        $shouldCheckForPayments = $this->shouldCheckForPayments($lineItems);

        if ($shouldCheckForPayments === false)
        {
            return true;
        }

        $this->trace->info(
            TraceCode::PAYMENT_PAGE_VALIDATE_EXISTING_PAYMENTS,
            [
                'id'     => $paymentLink->getId(),
            ]);

        $succeedingPayments = $this->repo->payment->getValidatePaymentsForPaymentPages($paymentLink);

        $paymentPageItemQuantity = $this->getActivePaymentQuantityCount($succeedingPayments);

        foreach ($lineItems as $lineItem)
        {
            $paymentPageItem = $lineItem->ref;

            $neededQuantity = $this->getNeededQuantity($lineItem, $paymentPageItemQuantity);

            if ($paymentPageItem->isSlotLeft($lineItem->getQuantity() + $neededQuantity) === false)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     * @param \RZP\Models\Order\Entity       $order
     *
     * @return bool
     */
    protected function hasRequiredAmountAndQuantity(Entity $paymentLink, Order\Entity $order): bool
    {
        // represents the computed required minimum amount of the payment page.
        $minimumPageAmount = 0;

        /**
         * this map will contain all payment page item's
         * mandaotry, required amount and required quantity values
         */
        $requiredItemsMap = [];

        foreach ($paymentLink->paymentPageItems as $pageItem)
        {
            $mandatory      = $pageItem->getAttribute(PaymentPageItem\Entity::MANDATORY);

            $minPurchase    = $pageItem->getMinPurchase();

            $item = $pageItem->item;

            $itemMinAmount = $itemAmount = $item->getAmount() ?? 0;

            if ($mandatory && $pageItem->getMinAmount() !== 0 && $itemMinAmount === 0)
            {
                $itemMinAmount = $pageItem->getMinAmount();
            }

            $requiredMinQuantity = $minPurchase ?? 1;

            // add all payment page items in the map
            $requiredItemsMap[$pageItem->getId()] = [
                self::REQUIRED_AMOUNT       => $itemAmount,
                self::REQUIRED_MIN_AMOUNT   => $itemMinAmount,
                self::REQUIRED_MIN_QUANTITY => $requiredMinQuantity,
            ];

            if ($mandatory === true)
            {
                // only if the item is mandatory add calculated amount to the $minimumPageAmount
                $minimumPageAmount += $itemAmount * $requiredMinQuantity;
            }
        }

        // represents the computed amount of the order WRT line items.
        $orderRequiredFieldsAmount = 0;

        $orderMeta = $this->repo->order_meta->findByOrderIdAndType($order->getId(), \RZP\Models\Feature\Constants::ONE_CLICK_CHECKOUT);
        $shippingFee = 0;
        if($orderMeta != null){
            $shippingFee = $orderMeta->getValue()[Fields::SHIPPING_FEE];
        }
        foreach ($order->lineItems as $lineItem)
        {
            $refId = $lineItem->getAttribute(\RZP\Models\LineItem\Entity::REF_ID);

            if (array_get($requiredItemsMap, $refId) === null)
            {
                /**
                 * since $requiredItemsMap contains all items mapped
                 * any item which is not part of $requiredItemsMap
                 * does not belong to the payment  page
                 */
                throw new BadRequestValidationFailureException(
                    self::AMOUT_QUANTITY_TAMPERED
                );
            }

            if (! $this->isValidLineItemAgainstPageItem($requiredItemsMap[$refId], $lineItem))
            {
                return false;
            }

            $orderRequiredFieldsAmount += $lineItem->getQuantity() * $lineItem->getAmount();
        }
        if($shippingFee !== 0){
            $orderRequiredFieldsAmount += $shippingFee;
        }
        return $orderRequiredFieldsAmount >= $minimumPageAmount
            && $order->getAmount() === $orderRequiredFieldsAmount
            && $order->getAmount() >= $minimumPageAmount;
    }

    protected function getActivePaymentQuantityCount($payments): array
    {
        $paymentPageItemQuantity = [];

        foreach ($payments as $payment)
        {
            if ($payment->hasOrder() === true)
            {
                $order = $payment->order;

                $lineItems = $order->lineItems()->get();

                foreach ($lineItems as $lineItem)
                {
                    $paymentPageItem = $lineItem->ref;

                    if (isset($paymentPageItemQuantity[$paymentPageItem->getId()]) !== true)
                    {
                        $paymentPageItemQuantity[$paymentPageItem->getId()] = 0;
                    }

                    $paymentPageItemQuantity[$paymentPageItem->getId()] += $lineItem->getQuantity();
                }
            }
        }

        return $paymentPageItemQuantity;
    }

    protected function getNeededQuantity(LineItem\Entity $lineItem, array $paymentPageItemQuantity): int
    {
        $paymentPageItem = $lineItem->ref;

         return (int) ($paymentPageItemQuantity[$paymentPageItem->getId()] ?? 0);
    }

    protected function shouldCheckForPayments(Base\Collection $lineItems): bool
    {
        foreach ($lineItems as $lineItem)
        {
            $paymentPageItem = $lineItem->ref;

            if (is_null($paymentPageItem->getStock()) === false)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     * @param string|null                    $slug
     * @param string|null                    $customDomain
     *
     * @return void
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\BaseException
     */
    protected function createAndSetShortUrl(Entity $paymentLink, ?string $slug = null, ?string $customDomain = null)
    {
        //
        // Temporary: We ignore custom slug in test mode. Practical case is
        // merchant consumes his slug in test mode while exploring and we want
        // to avoid it. Better approach being discussed but for now this us safeguard.
        // Same check exists at updateShortUrlIfApplicable() as well.
        //
        if (($this->isTestMode() === true) and
            ($slug !== null))
        {
            $slug = null;
        }

        [$url, $params, $fail] = $this->getShortenUrlRequestParams($paymentLink, $slug, $customDomain);

        try {
            $useCustomUrlModule = $this->shouldUseCustomUrlModule($paymentLink);

            if ($useCustomUrlModule)
            {
                if (empty($customDomain) === false)
                {
                    // No shortening for custom domain flow on upsert in nocode custom urls table
                    $paymentLink->setShortUrl($url);
                    return;
                }

                $skipShortning = $this->shouldSkipShortner($paymentLink, $slug, $url);

                if ($skipShortning)
                {
                    $this->updateExistingShortUrl($slug, $paymentLink);

                    return;
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::NOCODE_CUSTOM_URL_CALLS_FAILED_COUNT);

            $this->trace->error(TraceCode::NOCODE_CUSTOM_URL_UPSERT_FAILED, $this->getCustomUrlFailedContext($paymentLink, $slug));

            $this->trace->traceException($e);
        }

        try
        {
            $shortUrl = $this->elfin->shorten($url, $params, $fail);

            $paymentLink->setShortUrl($shortUrl);
        }
        catch (BaseException $e)
        {
            // TODO: Gimli should return 4xx & Elfin service should propagate that error to callee
            if (preg_match('/Duplicate|Blacklisted/', $e->getDataAsString()) === 1)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_LINK_SLUG_GENERATE_FAILED,
                    Entity::SLUG,
                    [
                        Entity::SLUG => $slug,
                    ]);
            }

            throw $e;
        }
    }

    private function getDefaultRiskCheckUrl(string $publicPageId): string
    {
        return Config::get('app.payment_link_hosted_base_url')
            . "/"
            .  $publicPageId
            . "/view";
    }

    /**
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function getRiskCheckUrl(string $pageId, string $merchantId): ?string
    {
        $page = new Entity();

        $page->setId($pageId);

        $nocodeCore = new NocodeCustomUrl\Core;

        $nocodeEntity = $nocodeCore->getExistingLinkedEntity($page->getId(), $merchantId);

        if ($nocodeEntity === null)
        {
            return $this->getDefaultRiskCheckUrl($page->getPublicId());
        }

        return "https://" . $nocodeEntity->getDomain() . "/" . $nocodeEntity->getSlug();
    }

    public function getShortenUrlRequestParams(Entity $paymentLink, ?string $slug = null, ?string $customDomain = null): array
    {
        // Following are default set of parameters, when there is no slug passed in input
        // URL: https://pages.razorpay.in/pl_10000000000000/view OR https://pages.razorpay.in/AlphaNumMin4Max30Slug

        switch ($paymentLink->getViewType())
        {
            case ViewType::PAYMENT_HANDLE:

                $hostedBaseUrl = $this->paymentHandleHostedBaseUrl;

                break;

            default:

                $hostedBaseUrl = empty($customDomain) === true ? $this->plHostedBaseUrl : $this->buildHostedCustomDomainUrl($customDomain);

                break;
        }

        $merchant = $paymentLink->merchant;

        $org = $merchant->org;

        switch ($org->getCustomCode())
        {
            case 'axis':

                $hostedBaseUrl = $this->app['config']->get('app.payment_page_axis_hosted_base_url');

                break;
        }

        $url = $paymentLink->getHostedViewUrl($hostedBaseUrl, $slug);

        // Fail: In case not able to shorten URL, will keep above value itself as short URL and continue with creation
        $fail = false;
        // Ptype: Input request for Gimli
        $params = ['ptype' => 'link'];

        // If slug is passed in input, we override above parameters in following way
        if ($slug !== null)
        {
            // Fail: If failed to shorten the URL, do not continue with creation and fail
            $fail = true;
            // No fall back: Only use Gimli(our shortener service) and do not fall back to Bitly etc if that fails
            $this->elfin->setNoFallback();
            // Additional parameters/metadata which gets used later in rendering view endpoint

            $params += [
                'alias'          => $slug,
                'fail_if_exists' => true,
                'metadata'       => [
                    'mode'   => $this->mode,
                    'entity' => $paymentLink->getEntity(),
                    'id'     => $paymentLink->getPublicId(),
                ],
            ];
        }

        return [$url, $params, $fail];
    }

    /**
     * Called from CRON.
     * Updates status to INACTIVE, status_reason to EXPIRED of all payment links which are active and past expire_by.
     * @return array
     */
    public function expirePaymentLinks(): array
    {
        $timeStarted = microtime(true);

        $paymentLinks = Tracer::inSpan(['name' => 'payment_page.expire.get_active_and_past_expire_by_payment_link'], function()
        {
            return $this->repo->payment_link->getActiveAndPastExpireByPaymentLinks();
        });

        $summary = [
            'total_count' => $paymentLinks->count(),
            'failed_ids'  => [],
        ];

        foreach ($paymentLinks as $paymentLink)
        {
            try
            {
                Tracer::inSpan(['name' => 'payment_page.expire.payment_link.core'], function() use($paymentLink)
                {
                    $this->expirePaymentLink($paymentLink);
                });
            }
            catch (\Throwable $e)
            {
                $summary['failed_ids'][] = $paymentLink->getId();

                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::PAYMENT_LINK_EXPIRE_ERROR,
                    [
                        Entity::ID => $paymentLink->getId(),
                    ]);
            }
        }

        $summary['time_taken'] = (microtime(true) - $timeStarted) / 1000;

        $this->trace->debug(TraceCode::PAYMENT_LINK_EXPIRE_CRON_SUMMARY, $summary);

        return $summary;
    }

    /**
     * Returns an array of the payload to be consumed by the view template.
     *
     * @param  Entity $paymentLink
     *
     * @return array
     */
    public function getHostedViewPayload(Entity $paymentLink): array
    {
        // Fetch serialized view data for the view to consume
        $payload['data'] = Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_SERIALIZE], function() use ($paymentLink) {
            return $this->getSerializedFromCache($paymentLink);
        });

        // Append UDF Schema as a JSON string, if defined
        $payload[Entity::UDF_SCHEMA] = Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_SCHEMA], function() use ($paymentLink) {
            return (new UdfSchema($paymentLink))->getSchema();
        });

        return $payload;
    }

    /**
     * Returns the name of the Payment link view template to be used.
     *
     * @param  Entity $paymentLink
     *
     * @return string
     */
    public function getHostedViewTemplate(Entity $paymentLink): string
    {
        $templateId = $paymentLink->getHostedTemplateId();

        if ($templateId !== null)
        {
            $templateAccessor = new HostedTemplate($templateId);
            $view = 'hostedpage.' . $templateAccessor->getViewName();
        }
        else
        {
            switch ($paymentLink->getViewType())
            {
                case ViewType::PAYMENT_HANDLE:

                        $view = 'payment_handle.hosted_with_udf';

                        break;

                default:

                    $view = 'payment_link.hosted_with_udf';

                    break;
            }
        }

        return $view;
    }

    /**
     * Returns settings required to load button on client side
     * Has to be cached in redis after mvp
     * @param  Entity $paymentLink
     *
     * @return array
     */
    public function getHostedButtonPreferences(Entity $paymentLink)
    {
        $settings =  Settings\Accessor::for($paymentLink, Settings\Module::PAYMENT_LINK)->all();

        $merchant = $paymentLink->merchant;

        $merchantBrandColor = get_rgb_value($merchant->getBrandColorOrDefault());

        $preferences = array_intersect_key($settings->toArray(), array_flip(Entity::BUTTON_PREFERENCES_KEYS));

        $preferences['merchant_brand_color'] = $merchantBrandColor;

        $preferences += $this->serializeOrgPropertiesForPreferences($merchant);

        return [
            'is_test_mode'   => $this->isTestMode(),
            'preferences'    => $preferences,
        ];
    }

    /**
     * It uploads the images in S3 bucket and returns the image cdn urls.
     *
     * @param array           $input Includes images to be uploaded in s3 bucket.
     * @param Merchant\Entity $merchant
     *
     * @return array Image cdn urls
     * @throws \RZP\Exception\ServerErrorException
     */
    public function upload(array $input, Merchant\Entity $merchant): array
    {
        $urls = [];


        foreach ($input['images'] as $image)
        {
            $processedData = $this->processImage($image);

            $url = $this->uploadToS3($merchant, $processedData[0], $processedData[1]);

            $urls[] = $url;
        }

        $this->trace->info(TraceCode::PAYMENT_PAGE_IMAGE_UPLOAD_OPTIMIZATION, $urls);

        return $urls;
    }

    protected function uploadToS3(Merchant\Entity $merchant, UploadedFile  $imageFile,  string $fileName)
    {
        $cdn  = sprintf(
            'https://s3.ap-south-1.amazonaws.com/rzp-%s-merchant-assets',
            $this->env === 'production' ? 'prod' : 'nonprod');

        $uploadFilename = 'payment-link/description/' . $fileName;

        $ufhService = $this->app['ufh.service'];

        $file = $ufhService->uploadFileAndGetUrl(
            $imageFile,
            $uploadFilename,
            Constants::PAYMENT_LINK_DESCRIPTION,
            $merchant,
            ['Content-Disposition' => 'inline']);

        return $cdn . '/' . $file[Constants::RELATIVE_LOCATION];
    }

    protected function processImage(UploadedFile $originalImage): array
    {
        $imagick        = new Imagick();

        $rawImage = file_get_contents($originalImage->getRealPath());

        $imagick->readImageBlob($rawImage);

        $this->trace->count(Metric::PAYMENT_PAGE_IMAGE_UPLOAD_COUNT, ['image_type' => $imagick->getImageFormat()]);

        // Not compressing gif & webp images
        if (in_array($imagick->getImageFormat(), Constants::SKIP_IMAGE_COMPRESSION_FORMAT) === true)
        {
            $uploadName = $this->getUploadFileName($originalImage, $imagick);

            $imagick->destroy();

            return array($originalImage, $uploadName);
        }

        $this->resizeImageForCompression($imagick, Constants::DEFAULT_IMAGE_RESIZE_WIDTH);

        $this->setImageFormatAttributes($imagick, Constants::DEFAULT_IMAGE_COMPRESSION_QUALITY);

        $this->setImageJpgAttributes($imagick);

        return $this->processFileHandlingForImage($originalImage, $imagick);
    }

    protected function getUploadFileName(UploadedFile $file, Imagick $imagick): string
    {
        $filenameWithoutExt = str_before($file->getClientOriginalName(), '.' . $file->getClientOriginalExtension());

        $uploadName = $filenameWithoutExt ."_". UniqueIdEntity::generateUniqueId(). '.' . $imagick->getImageFormat();

        return $uploadName;
    }

    protected function processFileHandlingForImage(UploadedFile $originalImage, Imagick $imagick)
    {
        $uploadName = $this->getUploadFileName($originalImage, $imagick);

        $actualImageSizeInKb = $originalImage->getSize() / 1024;

        $tempFilePath = '/tmp/' . $uploadName;

        $imagick->writeImage($tempFilePath);

        $compressedImage = new UploadedFile($tempFilePath, $uploadName,  $imagick->getImageMimeType(), null, true);

        $compressedImageSizeInKb = $compressedImage->getSize() / 1024;

        if (($compressedImageSizeInKb > $actualImageSizeInKb) === true)
        {
            $this->trace->info(TraceCode::PAYMENT_PAGE_IMAGE_UPLOAD_OPTIMIZATION,
                    [
                        'actual size'     => $actualImageSizeInKb,
                        'compressed size' => $compressedImageSizeInKb,
                        'format'          => $imagick->getImageFormat(),
                    ]
            );

            $imagick->destroy();

            return array($originalImage, $uploadName);
        }

        $this->trace->histogram(Metric::PAYMENT_PAGE_IMAGE_COMPRESSION_HISTOGRAM, $actualImageSizeInKb - $compressedImageSizeInKb, ['image_type' => $imagick->getImageFormat()]);

        $imagick->destroy();

        return array($compressedImage, $uploadName);
    }

    protected function resizeImageForCompression(Imagick & $imagick, int $resizeWidth)
    {
        $width      = $imagick->getImageWidth();

        $height     = $imagick->getImageHeight();

        if (($width > $resizeWidth) === true)
        {
            $ratio = $width/$resizeWidth;

            $height = $height / $ratio;

            $width = $resizeWidth;
        }

        $imagick->thumbnailImage($width, $height);
    }

    protected function setImageFormatAttributes(Imagick & $imagick, int $compressionQuality)
    {
        $imagick->setImageCompressionQuality($compressionQuality);

        $imagick->setImageFormat('jpeg');

        $imagick->setBackgroundColor(new ImagickPixel('white'));

        $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
    }

    protected function setImageJpgAttributes(Imagick & $imagick)
    {
        $imagick->setSamplingFactors(array('2x2', '1x1', '1x1'));

        $profiles = $imagick->getImageProfiles("icc", true);

        $imagick->stripImage();

        if(!empty($profiles)) {
            $imagick->profileImage('icc', $profiles['icc']);
        }

        $imagick->setInterlaceScheme(Imagick::INTERLACE_JPEG);

        $imagick->setColorspace(Imagick::COLORSPACE_SRGB);
    }

    public function updatePaymentPageItem(PaymentPageItem\Entity $paymentPageItem, array $input)
    {
        $paymentPageItem = Tracer::inSpan(['name' => Constants::HT_PPI_TRANSACTION], function() use ($paymentPageItem, $input)
        {
            return $this->repo->transaction(function () use ($paymentPageItem, $input) {
                $paymentLink = $paymentPageItem->paymentLink;

                Tracer::inSpan(['name' => Constants::HT_PPI_UPDATE_LOCK], function() use($paymentLink)
                {
                    $this->repo->payment_link->lockForUpdateAndReload($paymentLink);
                });

                $this->repo->payment_page_item->reload($paymentPageItem);

                $paymentPageItem = Tracer::inSpan(['name' => Constants::HT_PPI_UPDATE_CORE], function() use($paymentPageItem, $input)
                {
                    return (new PaymentPageItem\Core)->update($paymentPageItem, $input);
                });

                Tracer::inSpan(['name' => Constants::HT_PPI_UPDATE_STATUS], function() use($paymentLink)
                {
                    $this->changeStatusAfterUpdateIfApplicable($paymentLink);
                });

                Tracer::inSpan(['name' =>  Constants::HT_PPI_UPDATE_SAVE], function() use($paymentLink) {
                    $paymentLink->saveOrFail();
                });

                return $paymentPageItem;
            });
        });

        Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentPageItem) {
            $this->dispatchHostedCache($paymentPageItem->paymentLink);
        });

        return $paymentPageItem;
    }


    public function createPaymentPageFileUploadRecord(string $paymentPageId, string $batchId, array $input)
    {
        //fetch existing payment page or throw error
        $id = Entity::silentlyStripSign($paymentPageId);
        $paymentPage = $this->repo->payment_link->findOrFail($id);

        if ($paymentPage === null)
        {
            throw new BadRequestException(
                'Payment Page not found for this Id.');
        }

        if ($paymentPage->getViewType() !== ViewType::FILE_UPLOAD_PAGE)
        {
            throw new BadRequestException(
                'Payment Page is not created for file upload.');
        }

            return (new PaymentPageRecord\Core)->createRecord(
                $paymentPage,
                $batchId,
                $input
            );
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return void
     */
    protected function updateCapturedPaymentCountOnthePage(Entity $paymentLink)
    {
        $computedSettings   = $paymentLink->getComputedSettings()->toArray();

        $context = [
            "entity"    => [
                Entity::ID  => $paymentLink->getId(),
            ],
            Entity::COMPUTED_SETTINGS   => $computedSettings,
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_UPDATING_TRANSACTION_COUNT, $context);

        $totalTransactionCountSoFar = array_get($computedSettings, Entity::CAPTURED_PAYMENTS_COUNT);

        if ($totalTransactionCountSoFar === null)
        {
            /**
             * captured payment count has never been computed for this entity
             * we will update the count by making a query and update it,
             * so that from next time we will simply increment the count
             *
             * purpose of calling $this->repo->payment->getCapturedPaymentsForPaymentPage is
             * we will not have the captured_payment_count for old payment pages.
             */
            $this->updateAndGetCapturedPaymentCount($paymentLink);

            return;
        }

        $this->updateCapturedPaymentCount(1 + (int) $totalTransactionCountSoFar, $paymentLink, $computedSettings);
    }

    /**
     * Updates the status to INACTIVE, status_reason to EXPIRED of an individual expired payment link by locking it.
     *
     * @param Entity $paymentLink
     */
    protected function expirePaymentLink(Entity $paymentLink)
    {
        $this->repo->transaction(
            function () use ($paymentLink)
            {
                $this->repo->payment_link->lockForUpdateAndReload($paymentLink);

                // Continues with expiration only if current status is active and expire_by's value is past now
                if (($paymentLink->isActive() === true) and
                    ($paymentLink->isPastExpireBy() === true))
                {
                    $this->changeStatus($paymentLink, Status::INACTIVE, StatusReason::EXPIRED);

                    $this->repo->saveOrFail($paymentLink);
                }
            });

        Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_CACHE_DISPATCH], function() use ($paymentLink) {
            $this->dispatchHostedCache($paymentLink);
        });

        $this->traceForExpire($paymentLink);
    }

    /**
     * Tracing for payment links expire action
     * Traces count as well as histogram
     *
     * @param Entity         $paymentLink
     */
    protected function traceForExpire(Entity $paymentLink)
    {
        $now = Carbon::now(Timezone::IST)->timestamp;

        $diffInTime = $now - $paymentLink->getExpireBy();

        $this->trace->histogram(Metric::PAYMENT_PAGE_EXPIRED_SEC, $diffInTime, $paymentLink->getMetricDimensions());

        $this->trace->count(Metric::PAYMENT_PAGE_EXPIRED_TOTAL, $paymentLink->getMetricDimensions());
    }

    /**
     * Initiates refund on a payment. This happens in cases as described in
     * postPaymentCaptureAttemptProcessing() method
     *
     * @param Entity         $paymentLink
     * @param Payment\Entity $payment
     */
    protected function refundPayment(Entity $paymentLink, Payment\Entity $payment)
    {
        $processor = new Payment\Processor\Processor($payment->merchant);

        $tracePayload = [
            E::PAYMENT      => $payment->toArrayPublic(),
            E::PAYMENT_LINK => $paymentLink->toArrayPublic(),
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_REQUEST, $tracePayload);

        $refund = null;

        try
        {
            //
            // We use existing payment entity's status attribute to decide which method to call for refund.
            // Additionally while calling the refund{X}Payment() method we pass reloaded payment entity because reload
            // doesn't happen in the called method. This is an additional level of check for concurrent issues. The
            // payment's refund will fail if the status has changed in between. We can't do reload before that because
            // then condition check will happen on new status.
            //
            if ($payment->isAuthorized() === true)
            {
                // based on experiment, refund request will be routed to Scrooge
                $refund = $processor->refundAuthorizedPayment($payment->reload());
            }
            else if ($payment->isCaptured() === true)
            {
                // based on experiment, refund request will be routed to Scrooge
                $refund = $processor->refundCapturedPayment($payment->reload());
            }
            else
            {
                $this->trace->critical(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_ERROR, $tracePayload);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::CRITICAL,
                TraceCode::PAYMENT_LINK_PAYMENT_REFUND_ERROR, $tracePayload);
        }

        // If refund was made, increments counter of at what payment status the refund was made
        if ($refund !== null)
        {
            $dimensions = ['payment_status' => $payment->getStatus()] + $paymentLink->getMetricDimensions();

            $this->trace->count(Metric::PAYMENT_PAGE_PAYMENT_REFUNDS_TOTAL, $dimensions);
        }

        $tracePayload = array_merge($tracePayload, [E::REFUND => optional($refund)->toArrayPublic()]);
        $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_REFUND_HANDLED, $tracePayload);
    }

    /**
     * Every payment link could have set of setting associated. Ref: Model\Settings.
     * @param  Entity $paymentLink
     * @param  array  $settings
     */
    protected function upsertSettings(Entity $paymentLink, array $settings)
    {
        if (empty($settings) === false)
        {
            $shippingInfo = $settings[Entity::SHIPPING_FEE_RULE] ?? null;
            if ($shippingInfo != null){
                $settings[Entity::SHIPPING_FEE_RULE] = json_encode($shippingInfo);
            }
            $paymentLink->getSettingsAccessor()->upsert($settings)->save();
        }
    }

    protected function getTotalAmountForOrder(array $input)
    {
        $totalAmount = 0;

        foreach ($input as $lineItem)
        {
            $totalAmount += $lineItem[LineItem\Entity::AMOUNT] * ($lineItem[LineItem\Entity::QUANTITY] ?? 1);
        }

        return $totalAmount;
    }

    protected function modifyAndValidateInputToCreateLineItems(array $input, Entity $paymentLink)
    {
        $modifiedInput = [];

        $PPIRepo = new PaymentPageItem\Repository;

        $PPIValidator = new PaymentPageItem\Validator();

        foreach ($input[Entity::LINE_ITEMS] as $lineItem)
        {
            $paymentPageItemId = $lineItem[Entity::PAYMENT_PAGE_ITEM_ID];

            unset($lineItem[Entity::PAYMENT_PAGE_ITEM_ID]);

            $paymentPageItemId = PaymentPageItem\Entity::verifyIdAndStripSign($paymentPageItemId);

            $paymentPageItem = $PPIRepo->findByIdAndPaymentLinkEntityOrFail(
                $paymentPageItemId,
                $paymentLink
            );

            $itemId = $paymentPageItem->getItemId();

            $lineItem[LineItem\Entity::ITEM_ID] = Item\Entity::getSignedId($itemId);

            $lineItem[LineItem\Entity::REF] = $paymentPageItem;

            $modifiedInput[Entity::LINE_ITEMS][] = $lineItem;

            $PPIValidator->validateAmountQuantityAndStockOfPPI($paymentPageItem, $lineItem);
        }

        $modifiedInput[Order\Entity::NOTES] = $input[Order\Entity::NOTES] ?? [];

        return $modifiedInput;
    }

    protected function serializeOrgPropertiesForPreferences(Merchant\Entity $merchant)
    {
        $org = $merchant->org;

        $branding = [
            'show_rzp_logo' => true,
            'branding_logo' => '',
        ];

        if($merchant->shouldShowCustomOrgBranding() === true)
        {
            $branding['show_rzp_logo'] = false;

            $branding['branding_logo'] = $org->getPaymentAppLogo() ?: 'https://cdn.razorpay.com/static/assets/hostedpages/axis_logo.png';

        }

        return [
            'branding'  => $branding
        ];
    }

    protected function doPostPaymentRiskActions(Entity $paymentLink, Payment\Entity $payment)
    {
        try
        {
            if ($this->mode === Mode::TEST)
            {
                return;
            }

            $riskAovInput = $this->getAovRiskCallInput($paymentLink, $payment);

            NotifyRas::dispatch($this->mode, $riskAovInput);

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, null, ['payment_page_id' => $paymentLink->getId()]);
        }
    }

    public function doDedupeAndRiskActions(Entity $paymentLink)
    {
        try
        {
            if ($this->mode !== Mode::LIVE)
            {
                return;
            }

            $riskCheckInput = $this->getRiskCheckInput($paymentLink);

            $riskCheckOutput = Tracer::inSpan(
                ['name' => 'payment_page.create.dedupe_actions.validate.risk_factor.request'],
                function() use ($riskCheckInput) {
                return $this->merchantRiskService->validateRiskFactorForMerchantRequest($riskCheckInput);
            });
            $alertInput = Tracer::inSpan(
                ['name' => 'payment_page.create.dedupe_actions.validate.risk_factor.response'],
                function () use ($riskCheckOutput, $paymentLink) {
                return $this->validateRiskFactorResponseAndGetAlertInput($riskCheckOutput, $paymentLink);
            });

            if (empty($alertInput) === true)
            {
                return;
            }

            $this->trace->count(Metric::PAYMENT_PAGE_RISK_ALERT_COUNT, $paymentLink->getMetricDimensions());

            NotifyRas::dispatch($this->mode, $alertInput);

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, null, ['payment_page_id' => $paymentLink->getId()]);
        }
    }

    protected function getRiskCheckInput(Entity $paymentLink)
    {
        $riskInput = [
            'entity_type'   => 'payment_pages',
            'client_type'   => 'payment_pages',
            'entity_id'     => $paymentLink->getId(),
            'merchant_id'   => $paymentLink->getMerchantId(),
            'fields'        => [
                [
                    'key'        => 'description',
                    'value'      => $paymentLink->getMetaDescription(),
                    'list'       => 'high_risk_list',
                    'config_key' => 'description',
                ],
                [
                    'key'        => 'description',
                    'value'      => $paymentLink->getMetaDescription(),
                    'list'       => 'authorities_list',
                    'config_key' => 'description',
                ],
                [
                    'key'        => 'description',
                    'value'      => $paymentLink->getMetaDescription(),
                    'list'       => 'brand_list',
                    'config_key' => 'description',
                ],
                [
                    'key'        => 'title',
                    'value'      => $paymentLink->getTitle(),
                    'list'       => 'high_risk_list',
                    'config_key' => 'title',
                ],
                [
                    'key'        => 'title',
                    'value'      => $paymentLink->getTitle(),
                    'list'       => 'authorities_list',
                    'config_key' => 'title',
                ],
                [
                    'key'        => 'title',
                    'value'      => $paymentLink->getTitle(),
                    'list'       => 'brand_list',
                    'config_key' => 'title',
                ],
                [
                    'key'        => 'terms',
                    'value'      => $paymentLink->getTerms(),
                    'list'       => 'high_risk_list',
                    'config_key' => 'terms',
                ],
                [
                    'key'        => 'terms',
                    'value'      => $paymentLink->getTerms(),
                    'list'       => 'authorities_list',
                    'config_key' => 'terms',
                ],
                [
                    'key'        => 'terms',
                    'value'      => $paymentLink->getTerms(),
                    'list'       => 'brand_list',
                    'config_key' => 'terms',
                ]
            ]
        ];

        return $riskInput;
    }

    protected function validateRiskFactorResponseAndGetAlertInput(array $response, Entity $paymentLink)
    {
        $riskFactorFields = (array_key_exists('fields', $response) === true) ? $response['fields'] : [];

        $dataFields = [];

        foreach ($riskFactorFields as $riskFactorField)
        {
            if ($riskFactorField['score'] > 650)
            {
                $matchedField = $riskFactorField['config_key'];

                switch ($matchedField)
                {
                    case Entity::DESCRIPTION:

                        $dataFields[Entity::DESCRIPTION] = $paymentLink->getMetaDescription();

                        break;

                    case Entity::TITLE:

                        $dataFields[Entity::TITLE] = $paymentLink->getTitle();

                        break;

                    case Entity::TERMS:

                        $dataFields[Entity::TERMS] = $paymentLink->getTerms();

                        break;
                }
            }
        }

        if (empty($dataFields) === true)
        {
            return [];
        }

        $isManaged = false;

        if (isset($response['is_managed']) === true)
        {
            $isManaged = $response['is_managed'];
        }

        return $this->getAlertServiceInput($paymentLink, $dataFields, $isManaged);
    }

    protected function getAlertServiceInput(Entity $paymentLink, array $dataFields, bool $isManaged)
    {
        $dataFields['merchant_type'] = $isManaged === true ? 'managed' : 'unmanaged';

        return [
            'merchant_id'     => $paymentLink->merchant->getMerchantId(),
            'entity_type'     => 'payment_page',
            'entity_id'       => $paymentLink->getId(),
            'category'        => 'high_risk_keywords',
            'source'          => 'pp_service',
            'data'            => $dataFields,
            'event_timestamp' => $paymentLink->getCreatedAt(),
            'event_type'      => 'create',
        ];
    }

    protected function getAovRiskCallInput(Entity $paymentLink, Payment\Entity $payment)
    {
        return [
            'merchant_id'     => $paymentLink->merchant->getMerchantId(),
            'entity_type'     => 'payment_page',
            'entity_id'       => $paymentLink->getId(),
            'category'        => 'transaction',
            'source'          => 'pp_service',
            'data'            => [
                'payment_created_at' => (string) $payment->getCreatedAt(),
                'base_amount'        => (string) $payment->getAmount(),
            ],
            'event_timestamp' => (string) $payment->getCapturedAt(),
            'event_type'      => 'captured',
        ];
    }

    public function getGrievanceEntityDetails(string $id)
    {
        $id = Entity::stripDefaultSign($id);

        $paymentPage = $this->repo->payment_link->findOrFailPublic($id);

        $merchant = $paymentPage->merchant;

        return [
            'entity'         => 'payment_page',
            'entity_id'      => $paymentPage->getPublicId(),
            'merchant_id'    => $paymentPage->merchant->getId(),
            'merchant_label' => $merchant->getBillingLabel(),
            'merchant_logo'  => $merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE),
            'subject'        => $paymentPage->getTitle(),
        ];
    }

    protected function eventPaymentPagePaid(Entity $paymentPage, Payment\Entity $payment)
    {
        $this->firePartnerWebhooksIfNeeded($paymentPage, $payment);
    }

    protected function firePartnerWebhooksIfNeeded(Entity $paymentPage, Payment\Entity $payment)
    {
        // We are sending webhooks only for payment pages now.
        if ($paymentPage->getViewType() !== ViewType::PAGE)
        {
            return;
        }

        $partnerWebhookSettings = $paymentPage->getEnabledPartnerWebhooks();

        $eventPayload = [
            ApiEventSubscriber::MAIN => $payment
        ];

        foreach ($partnerWebhookSettings as $partnerName => $partnerWebhookEnabled)
        {
            $partnerWebhookEvent = Entity::PARTNER_WEBHOOKS[$partnerName] ?? null;

            //In case the partner name is saved wrong in the settings sent via dashboard
            if (($partnerWebhookEvent === null) || ($partnerWebhookEnabled !== "1"))
            {
                continue;
            }

            $this->trace->info(TraceCode::PAYMENT_PAGE_FIRE_WEBHOOK, [$partnerWebhookEvent]);

            $this->app['events']->fire('api.'.$partnerWebhookEvent, $eventPayload);
        }
    }

    /**
     * Constructs webhook payload for zapier integration
     *
     * @param Payment\Entity $payment
     * @return array
     */
    public function constructPayloadForPartnerWebhook(Payment\Entity $payment): array
    {
        $payload = [];
        $paymentPage = $payment->paymentLink;

        $payload[E::PAYMENT] = $payment->toArrayPublic();

        if ($paymentPage === null)
        {
            return $payload;
        }

        $order = $payment->order;

        $setting = $paymentPage->getSettings()->toArray();

        $oneCCEnabled = $setting[Entity::ONE_CLICK_CHECKOUT] ?? '0';

        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::PP_MAGIC_SETTING,
            $this->mode
        );

        if ($oneCCEnabled === '1' && strtolower($variant) === 'on') {
            if ($order != null) {
                $customerDetails = $order->toArrayPublic()[Fields::CUSTOMER_DETAILS] ?? null;
                $shippingAddress = $customerDetails[Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS];
                $note = [
                    Fields::CUSTOMER_DETAILS_EMAIL => $customerDetails[Fields::CUSTOMER_DETAILS_EMAIL],
                    self::PHONE => $shippingAddress[Fields::CUSTOMER_DETAILS_CONTACT],
                    Fields::CUSTOMER_DETAILS_NAME => $shippingAddress[Fields::CUSTOMER_DETAILS_NAME],
                    self::ADDRESS => $shippingAddress[AddressEntity::LINE1] . $shippingAddress[AddressEntity::LINE2],
                    AddressEntity::CITY => $shippingAddress[AddressEntity::CITY],
                    AddressEntity::STATE => $shippingAddress[AddressEntity::STATE],
                    AddressEntity::PINCODE => $shippingAddress[AddressEntity::ZIPCODE],
                ];
                $payment->setNotes($note);
                $order->setNotes($note);
            }
        }

        $payload[E::PAYMENT_PAGE] = $paymentPage->toArrayPublic();

        $payload[E::ORDER] = $order->toArrayPublic();

        $lineItems = $order->lineItems;

        if ($lineItems !== null)
        {
            $payload[E::ORDER]['items'] = $lineItems->toArrayPublic()['items'];
        }

        $this->trace->info(TraceCode::PAYMENT_PAGE_FIRE_WEBHOOK, $payload);

        return $payload;
    }

    protected function getRefundContext(Payment\Refund\Entity $refund): array
    {
        return [
            'refund_id'         => $refund->getId(),
            'refund_status'     => $refund->getStatus(),
            "refund"            => $refund->toArrayPublic(),
            'payment_id'        => $refund->payment->getId(),
            'payment_status'    => $refund->payment->getStatus(),
        ];
    }

    private function dispatchAppRiskCheck(Entity $paymentLink)
    {
        $mode = $this->app['basicauth']->getMode() ?? Mode::LIVE;

        if ($mode !== Mode::LIVE)
        {
            return;
        }

        $request = [
            'entity_id'         => $paymentLink->getId(),
            'checks'            => ['profanity_check'],
            'payment_page_id'   => $paymentLink->getPublicId(),
            'merchant_id'       => $paymentLink->getMerchantId(),
        ];
        try {
            $this->trace->info(
                TraceCode::APPS_RISK_CHECK_SQS_PUSH_INIT,
                $request
            );
            AppsRiskCheck::dispatch($this->mode, $request);
        } catch (\Exception $e) {
            $this->trace->critical(
                TraceCode::APPS_RISK_CHECK_SQS_PUSH_FAILED,
                $request
            );
        }
    }

    public function upsertDefaultPaymentHandleForMerchant(string $slug, string $handlePageId = null): void
    {
        $input[Entity::DEFAULT_PAYMENT_HANDLE] = $slug;

        $input[Entity::DEFAULT_PAYMENT_HANDLE_PAGE_ID] = $handlePageId;

        $this->trace->info(TraceCode::PAYMENT_HANDLE_UPSERT_MERCHANT_SETTING, [
            Entity::SLUG   => $slug
        ]);

        Tracer::inSpan(['name' => Constants::HT_PH_UPSERT_MERCHANT_SETTINGS], function() use($input)
        {
            Settings\Accessor::for($this->merchant, Settings\Module::PAYMENT_LINK)
                ->upsert([
                    Entity::DEFAULT_PAYMENT_HANDLE => $input
                ])
                ->save();
        });
    }

    public function suggestionPaymentHandle($count)
    {
        $merchantBillingLabel = $this->getDefaultPHFromBillingLabel($this->merchant);

        $suggestions = [];

        if($this->slugExists($merchantBillingLabel) === false)
        {
            array_push($suggestions, $merchantBillingLabel);

            $count--;
        }

        $this->generatePaymentHandle($merchantBillingLabel, $count, $suggestions);

        return $suggestions;
    }

    protected function generatePaymentHandle(string $handle, $count, & $suggestions)
    {
        while($count > 0) {

            $randInteger = rand(1, 10000);

            $suggestedHandle = $handle . $randInteger;

            if(strlen($suggestedHandle) > Entity::MAX_SLUG_LENGTH)
            {
                $suggestedHandle = substr($handle, 0, Entity::MAX_SLUG_LENGTH - strlen((string)$randInteger))
                    . $randInteger;
            }

            if ($this->slugExists($suggestedHandle) === false) {

                array_push($suggestions, $suggestedHandle);

                $count--;
            }
        }
        return $suggestions;
    }

    public function slugExists(string $slug)
    {
        $prevMode = $this->app['basicauth']->getMode();

        $slugMetaData = $this->getSlugMetaDataForPaymentHandle($slug, $this->paymentHandleHostedBaseUrl);

        $this->app['basicauth']->setModeAndDbConnection($prevMode);

        return (empty($slugMetaData) === false);
    }

    private function dispatchDedupeCall(Entity $paymentLink, Merchant\Entity $merchant)
    {
        if ($this->mode !== Mode::LIVE)
        {
            return;
        }

        $request = [
            'event'             => PaymentPageProcessor::PAYMENT_PAGE_CREATE_DEDUPE,
            'payment_page_id'   => $paymentLink->getId(),
            'start_time'        => millitime(),
        ];

        try {
            $this->trace->info(
                TraceCode::PAYMENT_PAGE_CREATE_DEDUPE_SQS_PUSH_INIT,
                $request
            );

            PaymentPageProcessor::dispatch($this->mode, $request);

            $this->trace->info(
                TraceCode::PAYMENT_PAGE_CREATE_DEDUPE_SQS_PUSHED,
                $request
            );
        } catch (\Exception $e) {
            $this->trace->critical(
                TraceCode::PAYMENT_PAGE_CREATE_DEDUPE_SQS_PUSH_FAILED,
                $request
            );
        }
    }

    public function precreatePaymentHandle(Merchant\Entity $merchant): array
    {
        $input = $this->getDefaultValuesPaymentHandle();

        $url = $this->paymentHandleHostedBaseUrl . '/' . $input[Entity::SLUG];

        // get unique handle
        $handle = $input[Entity::SLUG];

        $pp = $this->createPaymentPageForPaymentHandle($input, $this->merchant);

        // upsert handle in merchant setting
        $this->upsertDefaultPaymentHandleForMerchant($input[Entity::SLUG], $pp->getPublicId());

        $this->trace->info(
            TraceCode::PAYMENT_HANDLE_PRECREATE_COMPLETED,
            [
                Entity::MERCHANT_ID    => $merchant->getId(),
                Entity::TITLE          => $merchant->getBillingLabel(),
                Entity::URL            => $url,
                Entity::SLUG           => $input[Entity::SLUG]
            ]);

        return [
            Entity::TITLE          => $merchant->getBillingLabel(),
            Entity::URL            => $url,
            Entity::SLUG           => $input[Entity::SLUG]
        ];
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param bool $isPrecreate
     *
     * @return Entity
     */
    protected function createPaymentPageForPaymentHandle(array $input, Merchant\Entity $merchant)
    {
        $paymentPage = (new Entity)->generateId();

        $paymentPage->merchant()->associate($merchant);

        //TODO: not sure if we need settings
        $settings = $input[Entity::SETTINGS] ?? [];

        $settings[Entity::VERSION] = Version::V2;

        $paymentPage->build($input);

        $paymentPage->setShortUrl($this->paymentHandleHostedBaseUrl . '/' . $input[Entity::SLUG]);

        Tracer::inSpan(['name' => Constants::HT_PH_CREATE_REQUEST_CREATE_PP_TRANSACTION], function() use($input, $paymentPage, $settings)
        {
            $this->repo->transaction(function () use ($paymentPage, $settings, $input) {

                $this->upsertSettings($paymentPage, $settings);

                $this->repo->saveOrFail($paymentPage);

                $this->createPaymentPageItems($input, $paymentPage);

                $this->createCustomUrlForPaymentHandle($paymentPage, $input[Entity::SLUG]);

            });
        });

        $this->trackPaymentPageCreatedEvent($paymentPage, $input);

        return $paymentPage;
    }

    /**
     * @return string
     */
    public function getHandleFromTestMode(): string
    {
        // As when we hit the precreate api, we might be in test mode, therefore we are checking
        // test mode if we have precreated handle.
        // Precreate handle entry in merchant settings will be made in live mode when we are
        // upserting the merchant setting in Payment Handle creation flow

        $prevMode = $this->mode;

        $this->app['basicauth']->setModeAndDbConnection('test');

        $merchantSetting = Settings\Accessor::for($this->merchant, Settings\Module::PAYMENT_LINK)
            ->all();

        $handle = array_get($merchantSetting, ENTITY::DEFAULT_PAYMENT_HANDLE . '.' . Entity::DEFAULT_PAYMENT_HANDLE);

        $this->app['basicauth']->setModeAndDbConnection($prevMode);

        return $handle === null ? '' : $handle;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return void
     */
    public function updateHostedCache(Entity $paymentLink)
    {
        Entity::clearHostedCacheForPageId($paymentLink->getPublicId());

        $this->buildHostedCacheAndGet($paymentLink);
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return array
     */
    private function buildHostedCacheAndGet(Entity $paymentLink): array
    {
        $serialized = (new ViewSerializer($paymentLink))->serializeForHosted();

        $cacheKey = Entity::getHostedCacheKey($paymentLink->getPublicId());

        $this->cache->put($cacheKey, $serialized, Entity::getHostedCacheTTL());

        $this->trace->count(Metric::PAYMENT_PAGE_HOSTED_CACHE_BUILD_COUNT, $paymentLink->getMetricDimensions());

        return $serialized;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return array
     */
    private function getSerializedFromCache(Entity $paymentLink): array
    {
        $serializer = new ViewSerializer($paymentLink);

        if (! $this->shouldCacheHostedResponse($paymentLink))
        {
            return $serializer->serializeForHosted();
        }

        $cacheKey = Entity::getHostedCacheKey($paymentLink->getPublicId());

        $fromCache = true;

        if ($this->cache->has($cacheKey) === true)
        {
            $cached = $this->cache->get($cacheKey);

            $cached = $serializer->updateNoneCachedHostedKeys($cached);
        }
        else
        {
            $fromCache = false;

            $cached = $this->buildHostedCacheAndGet($paymentLink);
        }

        $metrics = $fromCache === true
            ? Metric::PAYMENT_PAGE_HOSTED_CACHE_HIT_COUNT
            : Metric::PAYMENT_PAGE_HOSTED_CACHE_MISS_COUNT;

        $this->trace->count($metrics, $paymentLink->getMetricDimensions());

        return $cached;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return void
     */
    private function dispatchHostedCache(Entity $paymentLink)
    {
        if (! $this->shouldCacheHostedResponse($paymentLink))
        {
            return;
        }

        $request = [
            'event'             => PaymentPageProcessor::PAYMENT_PAGE_HOSTED_CACHE,
            'payment_page_id'   => $paymentLink->getId(),
            'start_time'        => millitime(),
        ];

        try {
            $this->trace->info(
                TraceCode::PAYMENT_PAGE_HOSTED_CACHE_SQS_PUSH_INIT,
                $request
            );

            PaymentPageProcessor::dispatch($this->mode, $request);

            $this->trace->info(
                TraceCode::PAYMENT_PAGE_HOSTED_CACHE_SQS_PUSHED,
                $request
            );
        } catch (\Exception $e) {
            $this->trace->error(
                TraceCode::PAYMENT_PAGE_HOSTED_CACHE_SQS_PUSH_FAILED,
                $request
            );
        }
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return bool
     */
    private function shouldCacheHostedResponse(Entity $paymentLink): bool
    {
        return $paymentLink->getViewType() === ViewType::PAGE;
    }

    /**
     * @param array                       $item
     * @param \RZP\Models\LineItem\Entity $lineItem
     *
     * @return bool
     */
    private function isValidLineItemAgainstPageItem(array $item, LineItem\Entity $lineItem): bool
    {
        $requiredQuantity   = $item[self::REQUIRED_MIN_QUANTITY];

        $requiredMinAmount  = $item[self::REQUIRED_MIN_AMOUNT];

        $requiredAmount     = $item[self::REQUIRED_AMOUNT];

        $lineAmount         = $lineItem->getAmount();

        $lineQuantity       = $lineItem->getQuantity();

        if ($lineQuantity < $requiredQuantity || $lineAmount < $requiredMinAmount)
        {
            return false;
        }

        $requiredTotal = $requiredAmount * $requiredQuantity;

        $lineItemTotal = $lineAmount * $lineQuantity;

        if ($requiredTotal !== 0 && $requiredQuantity === $lineQuantity && $requiredTotal !== $lineItemTotal)
        {
            return false;
        }

        return true;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     * @param string|null                    $slug
     * @param string|null                    $customDomain
     *
     * @return void
     */
    private function createCustomUrl(Entity $paymentLink, ?string $slug, ?string $customDomain = null)
    {
        if ($this->mode !== Mode::LIVE || $slug === null || ! $this->shouldUseCustomUrlModule($paymentLink))
        {
            return;
        }

        Tracer::inSpan(['name' => Constants::HT_PP_NOCODE_CUSTOM_URL_UPSERT], function() use($paymentLink, $slug, $customDomain) {
            $customUrlCore = new NocodeCustomUrl\Core();

            [$url, $params] = $this->getShortenUrlRequestParams($paymentLink, $slug, $customDomain);

            try
            {
                $customUrlCore->upsert([
                    NocodeCustomUrl\Entity::SLUG        => $slug,
                    NocodeCustomUrl\Entity::DOMAIN      => NocodeCustomUrl\Entity::determineDomainFromUrl($url),
                    NocodeCustomUrl\Entity::META_DATA   => array_get($params, 'metadata', []),
                ], $this->merchant, $paymentLink);
            }
            catch (\Throwable $e)
            {
                $this->trace->count(Metric::NOCODE_CUSTOM_URL_CALLS_FAILED_COUNT);

                $this->trace->error(TraceCode::NOCODE_CUSTOM_URL_UPSERT_FAILED, $this->getCustomUrlFailedContext($paymentLink, $slug));

                $this->trace->traceException($e);
            }
        });
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return bool
     */
    private function shouldUseCustomUrlModule(Entity $paymentLink): bool
    {
        return $paymentLink->getViewType() === ViewType::PAGE || $paymentLink->getViewType() === ViewType::PAYMENT_HANDLE;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     * @param string|null                    $slug
     * @param string                         $url
     *
     * @return bool
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    private function shouldSkipShortner(Entity $paymentLink, ?string $slug, string $url): bool
    {
        if ($slug === null || $this->isTestMode())
        {
            return false;
        }

        $core = new NocodeCustomUrl\Core;

        $isvalid = $core->validateAndDetermineShouldCreate(
            $slug,
            NocodeCustomUrl\Entity::determineDomainFromUrl($url),
            $paymentLink,
            $this->merchant
        );

        return ! $isvalid;
    }

    /**
     * @param string|null                    $slug
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return void
     */
    private function updateExistingShortUrl(?string $slug, Entity $paymentLink)
    {
        if ($slug === null)
        {
            return;
        }

        $shortUrl = $this->config->get('applications.elfin.gimli.short_url') . '/' . $slug;

        $paymentLink->setShortUrl($shortUrl);
    }

    /**
     * @param \RZP\Models\Payment\Entity $payment
     *
     * @return array
     */
    private function getPaymentContextForLogging(Payment\Entity $payment): array
    {
        return [
            "id"                => $payment->getId(),
            "status"            => $payment->getStatus(),
            "amount"            => $payment->getAmount(),
            "currency"          => $payment->getCurrency(),
            "order_id"          => $payment->getOrderId(),
            "method"            => $payment->getMethod(),
            "amount_refunded"   => $payment->getAmountRefunded(),
            "refund_status"     => $payment->getRefundStatus(),
            "captured"          => $payment->getCapture(),
            "fee"               => $payment->getFee(),
            "tax"               => $payment->getTax(),
            "late_authorized"   => $payment->isLateAuthorized(),
            "disputed"          => $payment->isDisputed(),
            "auto_captured"     => $payment->getAutoCaptured(),
            "authorized_at"     => $payment->getAuthorizeTimestamp(),
            "authenticated_at"  => $payment->getAuthenticatedTimestamp(),
            "error_code"        => $payment->getErrorCode(),
            "error_description" => $payment->getErrorDescription(),
        ];
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     * @param string|null                    $slug
     *
     * @return array
     */
    private function getCustomUrlFailedContext(Entity $paymentLink, ?string $slug): array
    {
        return [
            Entity::ID              => $paymentLink->getPublicId(),
            Entity::SHORT_URL       => $paymentLink->getShortUrl(),
            Entity::SLUG            => $slug,
        ];
    }

    public function getPaymentHandleForInput()
    {
        if(App::getFacadeRoot()->environment() === Environment::AUTOMATION ||
            (App::getFacadeRoot()->environment() === Environment::BVT))
        {
            return '@' . (new Entity)->generateId()->getId();
        }

        return $this->suggestionPaymentHandle(1)[0];
    }

    protected function getDefaultPHFromBillingLabel(Merchant\Entity $merchant): string
    {
        $billingLabel = $merchant->getBillingLabel();

        // Remove all characters other than a-z, A-Z, 0-9
        $paymentHandle = preg_replace('/[^a-zA-Z0-9-]+/', '', $billingLabel);

        $paymentHandle = '@' . strtolower($paymentHandle);

        if(strlen($paymentHandle) > Entity::MAX_SLUG_LENGTH)
        {
            $paymentHandle = substr($paymentHandle, 0, Entity::MAX_SLUG_LENGTH);
        }

        if(strlen($paymentHandle) < Entity::MIN_SLUG_LENGTH)
        {
            $paymentHandle = $paymentHandle . rand(100, 10000);
        }

        return $paymentHandle;
    }

    protected function createCustomUrlForPaymentHandle(Entity $paymentLink, string $handle)
    {
        $this->trace->info(TraceCode::PAYMENT_HANDLE_UPSERTING_CUSTOM_URL,[
            Entity::MERCHANT_ID       => $this->merchant->getId(),
            Entity::HANDLE            => $handle
        ]);

        $customUrlCore = new NocodeCustomUrl\Core();

        [$url, $params] = $this->getShortenUrlRequestParams($paymentLink, $handle);

        $this->repo->transaction(function() use ($paymentLink, $handle, $customUrlCore, $params, $url)
        {
            $customUrlCore->upsert([
                NocodeCustomUrl\Entity::SLUG        => $handle,
                NocodeCustomUrl\Entity::DOMAIN      => NocodeCustomUrl\Entity::determineDomainFromUrl($url),
                NocodeCustomUrl\Entity::META_DATA   => array_get($params, 'metadata', []),
            ], $this->merchant, $paymentLink);
        });
    }

    public function getDefaultValuesPaymentHandle(): array
    {
        $input = [];

        $input[Entity::SLUG] = $this->getPaymentHandleForInput();

        $input[Entity::TITLE] = $this->getTitleForPaymentHandle($this->merchant);

        $this->modifyInputForPaymentHandle($input);

        return $input;
    }

    public function modifyInputForPaymentHandle(array & $input)
    {
        // adding currency parameter
        $input[Entity::CURRENCY] =  empty($input[Entity::CURRENCY]) ? 'INR' : $input[Entity::CURRENCY];

        // adding empty payment_page_items
        $input[Entity::PAYMENT_PAGE_ITEMS] =  [
            [
                PPI\Entity::ITEM     => [
                    LineItem\Entity::NAME     => ENTITY::AMOUNT,
                    'currency' => $input[Entity::CURRENCY],
                ],
                PPI\Entity::SETTINGS   => [
                    PPI\Entity::POSITION     => 0
                ],
                PPI\Entity::MANDATORY  => true,
                PPI\Entity::MIN_AMOUNT => 100
            ]
        ];

        $input[ENTITY::SETTINGS]  = [
            ENTITY::UDF_SCHEMA  => "[{\"name\":\"comment\",\"title\":\"Comment\",\"required\":true,\"type\":\"string\",\"options\":{},\"settings\":{\"position\":1}}]"
        ];

        $input[ENTITY::VIEW_TYPE]  = ViewType::PAYMENT_HANDLE;
    }

    public function modifyResponseForPaymentHandle(Entity $response) : array
    {
        $modifiedResponse = [];

        $modifiedResponse[ENTITY::TITLE] = $response[ENTITY::TITLE];

        $modifiedResponse[ENTITY::ID]    = $response->getPublicId();

        $modifiedResponse[ENTITY::SLUG]  = $response->getSlugFromShortUrl();

        $modifiedResponse[ENTITY::URL]   = $response->getHandleUrl();

        return $modifiedResponse;
    }

    /**
     * Returns null when Payment Handle for the merchant is in Precreate State
     * Returns the Payment Page Id which is linked to PaymentHandle in created state
     * @return mixed|null
     */
    protected function getPaymentHandleAndPaymentPageIDLinkedWithIt()
    {
        $merchantSettings = Settings\Accessor::for($this->merchant, Settings\Module::PAYMENT_LINK)
            ->all();

        $paymentHandle = array_get($merchantSettings, Entity::DEFAULT_PAYMENT_HANDLE . '.' . Entity::DEFAULT_PAYMENT_HANDLE);

        $handlePageId= array_get($merchantSettings, Entity::DEFAULT_PAYMENT_HANDLE . '.' . Entity::DEFAULT_PAYMENT_HANDLE_PAGE_ID);

        return [$paymentHandle, $handlePageId];
    }

    protected function createMockPaymentPageForPaymentHandle(): Entity
    {
        $paymentLinkParams = $this->getDefaultValuesPaymentHandle();

        $paymentLink = (new Entity)->generateId();

        $paymentLink->merchant()->associate($this->merchant);

        $paymentLink->build($paymentLinkParams);

        return $paymentLink;
    }

    /**
     * Fetches the metadata despite entity being soft deleted
     *
     * @param string $slug
     * @param string $host
     * @return NocodeCustomUrl\Entity|null
     * @throws BadRequestValidationFailureException
     */
    public function getSlugMetaDataForPaymentHandle(string $slug, string $host)
    {
        $domain = null;

        $paymentHandleHost = $this->app['config']->get('app.payment_handle_hosted_base_url');

        $paymentHandleDomain = NocodeCustomUrl\Entity::determineDomainFromUrl($paymentHandleHost);

        if (empty($host) !== true)
        {
            $domain = NocodeCustomUrl\Entity::determineDomainFromUrl($host);
        }

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $slugMetaData = $this
            ->repo
            ->nocode_custom_url
            ->findByAttributes([
                Entity::SLUG    => $slug,
                Entity::DOMAIN  => $domain,
            ], true);

        if(empty($slugMetaData) === true)
        {
            $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

            $slugMetaData = $this
                ->repo
                ->nocode_custom_url
                ->findByAttributes([
                    Entity::SLUG    => $slug,
                    Entity::DOMAIN  => $domain,
                ], true);
        }

        if(empty($slugMetaData) === true)
        {
            $gimli        = $this->app['elfin']->driver('gimli');

            $slugMetaData = $gimli->expandAndGetMetadata($slug);
        }

       return $slugMetaData;
    }

    /**
     * @param string $customDomain
     *
     * @return string
     */
    private function buildHostedCustomDomainUrl(string $customDomain): string
    {
        $protocol = $this->config->get("services.custom_domain_service.hosted.protocol");

        return $protocol . "://" . $customDomain;
    }

    /**
     * @param \RZP\Models\Payment\Entity $payment
     *
     * @return \RZP\Models\Invoice\Entity
     * @throws \RZP\Exception\BadRequestException
     */
    private function generateInvoiceForGetRecieptIfPosssible(Payment\Entity $payment): Invoice\Entity
    {
        $error = new BadRequestException(
            ErrorCode::BAD_REQUEST_ERROR,
            null,
            null,
            'Receipt is not generated for this payment');

        if (empty($payment->paymentLink) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'Receipt cannot be generated for this payment as there are no payment page linked');
        }

        if($payment->paymentLink->isReceiptEnabled() === false)
        {
            $this->trace->info(TraceCode::PAYMENT_PAGE_RECIEPT_NOT_ENABLED, ["payment_id" => $payment->getPublicId()]);
            throw $error;
        }

        try
        {
            $this->trace->info(TraceCode::PAYMENT_PAGE_CREATE_INVOICE, ["payment_id" => $payment->getPublicId()]);
            $this->createInvoiceIfEnabled($payment->paymentLink, $payment);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            throw $error;
        }

        $order = $payment->order;

        if (empty($order) === true)
        {
            $this->trace->info(TraceCode::PAYMENT_PAGE_ORDER_EMPTY, ["payment_id" => $payment->getPublicId()]);
            throw $error;
        }

        $invoice = $order->invoice;

        if (empty($invoice) === true)
        {
            $this->trace->info(TraceCode::PAYMENT_PAGE_INVOICE_STILL_EMPTY, ["payment_id" => $payment->getPublicId()]);
            throw $error;
        }

        return $invoice;
    }

}
