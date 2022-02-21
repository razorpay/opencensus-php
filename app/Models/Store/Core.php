<?php

namespace RZP\Models\Store;

use Cache;
use Carbon\Carbon;
use Requests;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\LineItem;
use RZP\Models\Item;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Settings;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Constants\Entity as E;
use RZP\Exception\BaseException;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Models\PaymentLink\PaymentPageItem;


class Core extends Base\Core
{
    protected $entityRepo;

    protected $elfin;

    const IMAGE_UPLOAD_BASE_PATH = 'https://s3.ap-south-1.amazonaws.com/rzp-%s-merchant-assets';

    public function __construct()
    {
        parent::__construct();

        $this->entityRepo      = $this->repo->store;

        $this->elfin           = $this->app['elfin'];
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_STORE_CREATE_REQUEST, $input);

        (new Validator())->validateStoreExistsForMerchant($merchant);

        $store = (new Entity)->generateId();

        $store->build($input);

        $store->merchant()->associate($merchant);

        $this->createAndSetStoreUrl($store, $input[Entity::SLUG]);

        $this->repo->transaction(function() use ($store, $merchant)
        {
            $this->entityRepo->saveOrFail($store);

            $this->setStoreForMerchant($merchant, $store);
        });

        $this->trace->count(Metric::PAYMENT_STORE_CREATED_TOTAL);

        return $store;
    }

    /**
     * @throws BadRequestException
     */
    public function getByMerchant(Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_STORE_FETCH_BY_MERCHANT_REQUEST, []);

        return $this->getStoreEntity($merchant);
    }

    /**
     * @throws BadRequestException
     */
    public function delete(Merchant\Entity $merchant)
    {
        $this->trace->info(TraceCode::PAYMENT_STORE_DELETE_REQUEST, []);

        $store = $this->getStoreEntity($merchant);

        $this->repo->transaction(function() use ($merchant, $store)
        {
            $this->entityRepo->lockForUpdateAndReload($store);

            $store->setStatus(Status::INACTIVE);

            $this->entityRepo->saveOrFail($store);

            $this->deleteStoreForMerchant($merchant);
        });

        $this->trace->count(Metric::PAYMENT_STORE_DELETED_TOTAL);

        return $store;
    }

    /**
     * @throws BadRequestException
     */
    public function update(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_STORE_UPDATE_REQUEST, $input);

        $storeEntity = $this->getStoreEntity($merchant);

        $storeEntity->edit($input);

        $this->repo->saveOrFail($storeEntity);

        $this->updateStoreUrlIfApplicable($storeEntity, $input);

        $settings = $input[Entity::SETTINGS] ?? [];

        $this->upsertSettings($storeEntity, $settings);

        $this->trace->count(Metric::PAYMENT_STORE_UPDATED_TOTAL);

        return $storeEntity;
    }

    /**
     * @throws BadRequestException
     */
    public function addProduct(array $input, Merchant\Entity $merchant): PaymentPageItem\Entity
    {
        $entity = $this->getStoreEntity($merchant);

        $entity->getValidator()->validateInput('addProduct', $input);

        $entity->getValidator()->validateDiscountAndSellingPrice($input);

        $this->updateDiscountedPriceIfApplicable($input);

        $ppItemInput = $this->getPPItemCreateInputFromProductInput($input);

        $ppiCore = new PaymentPageItem\Core();

        $product = $ppiCore->create($ppItemInput, $merchant, $entity);

        $this->trace->count(Metric::PAYMENT_STORE_PRODUCT_CREATED_TOTAL);

        return $product;
    }

    public function updateProduct(string $productId, array $input, Merchant\Entity $merchant)
    {
        $store = $this->getStoreEntity($merchant);

        $store->getValidator()->validateInput('editProduct', $input);

        $store->getValidator()->validateDiscountAndSellingPrice($input);

        $productId = PaymentPageItem\Entity::stripDefaultSign($productId);

        $product = $this->repo->payment_page_item->findByIdAndPaymentLinkEntityAndMerchantOrFail($productId, $store, $merchant);

        $store->getValidator()->validateStock($input, $product);

        $this->updateDiscountedPriceIfApplicable($input);

        $ppItemInput = $this->getPPItemUpdateInputFromProductInput($input);

        $ppiCore = new PaymentPageItem\Core();

        $ppiCore->update($product, $ppItemInput);

        $product->reload();

        $this->trace->count(Metric::PAYMENT_STORE_PRODUCT_UPDATED_TOTAL);

        return $product;
    }

    public function fetchProducts(array $input, Merchant\Entity $merchant)
    {
        $storeEntity = $this->getStoreEntity($merchant);

        $products = [];

        $this->addInputFieldsAsRequired($input, $storeEntity);

        $items = $this->repo->payment_page_item->fetch($input, $merchant->getPublicId());

        foreach ($items as $item)
        {
            array_push($products, $item->toStoreProductArrayPublic());
        }

        return $products;
    }

    public function getProduct(string $productId, Merchant\Entity $merchant)
    {
        $storeEntity = $this->getStoreEntity($merchant);

        $productId = PaymentPageItem\Entity::stripDefaultSign($productId);

        return $this->repo->payment_page_item->findByIdAndPaymentLinkEntityAndMerchantOrFail($productId, $storeEntity, $merchant);
    }

    public function patchProduct(string $productId, array $input, Merchant\Entity $merchant)
    {
        $store = $this->getStoreEntity($merchant);

        $store->getValidator()->validateInput('patchProduct', $input);

        $productId = PaymentPageItem\Entity::stripDefaultSign($productId);

        $product = $this->repo->payment_page_item->findByIdAndPaymentLinkEntityAndMerchantOrFail($productId, $store, $merchant);

        $this->doProductStatusActionIfPresent($product, $input);

        $this->trace->count(Metric::PAYMENT_STORE_PRODUCT_PATCH_TOTAL);

        return $product;
    }

    public function createOrder(Entity $store, array $input)
    {
        $activeStore = $this->getStoreEntity($store->merchant);

        $store->getValidator()->validateStoreIsActiveStore($store, $activeStore);

        $store->getValidator()->validateInput('create_order', $input);

        $shippingFees = $store->getSettings(Entity::SHIPPING_FEES);

        $shippingFees = (gettype($shippingFees) === 'string') ? (int)$shippingFees : 0;

        $input =$this->modifyAndValidateInputToCreateLineItems($input, $store);

        $totalAmount = $this->getTotalAmountForOrder($input[Entity::LINE_ITEMS], $shippingFees, $store->getId());

        $order = (new Order\Core)->create(
            [
                Order\Entity::AMOUNT => $totalAmount,
                Order\Entity::CURRENCY => $store->getCurrency(),
                Order\Entity::PAYMENT_CAPTURE => true,
                Order\Entity::NOTES => $input[Order\Entity::NOTES] ?? [],
                Order\Entity::PRODUCT_TYPE => $store->getProductType(),
                Order\Entity::PRODUCT_ID => $store->getId(),
            ],
            $store->merchant
        );

        (new LineItem\Core)->createMany($input[Entity::LINE_ITEMS], $this->merchant, $order);

        $lineItems = $order->lineItems()->get();

        return [
            Entity::ORDER      => $order,
            Entity::LINE_ITEMS => $lineItems
        ];
    }

    /*````````````````````````````````*/

    /**
     * @throws BadRequestException
     */
    public function getStoreEntity(Merchant\Entity $merchant)
    {
        $storeExistsSetting = Settings\Accessor::for($merchant, Settings\Module::PAYMENT_STORE)
            ->all();

        if (empty($storeExistsSetting) === true || empty($storeExistsSetting[Constants::MERCHANT_SETTING_STORE_KEY]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Store does not exists for this merchant. Please create a new one',
                null,
                null);
        }

        $storeId = $storeExistsSetting[Constants::MERCHANT_SETTING_STORE_KEY];

        return $this->entityRepo->findByPublicIdAndMerchant($storeId, $merchant);
    }

    public function getViewData(Entity $store): array
    {
        return [
            'key_id'           => $this->getMerchantKeyId($store->merchant),
            'is_test_mode'     => ($this->mode === Mode::TEST),
            'environment'      => $this->app->environment(),
            E::MERCHANT        => $this->serializeMerchantForHosted($store->merchant),
            'store'            => $this->serializeStoreForHosted($store),
            'base_url'         => $this->config['app']['url'],
            'checkout_id'      => 'pl_'.$store->getId(),
            'keyless_header'   => $this->getKeylessAuth($store->merchant),
        ];
    }

    public function getViewDataForProductDetailPage(Entity $store, PaymentPageItem\Entity $product): array
    {
        $data = $this->getViewData($store);

        $this->addMetaTagsForProductDetailPage($store, $product, $data);

        return $data;
    }

    protected function getKeylessAuth(Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();
        $mode = $this->mode ?? Mode::LIVE;

        return $this->app['keyless_header']->get(
            $merchantId,
            $mode);
    }

    protected function addMetaTagsForProductDetailPage(Entity $store, PaymentPageItem\Entity $product, array & $data)
    {
        $name = $product->itemWithTrashed->getName();

        $metaTitle = 'Buy '.$name." @ ".$store->getTitle();

        $metaDescription = $product->itemWithTrashed->getDescription();

        $images = $product->getProductConfig(Entity::PRODUCT_IMAGES);

        $metaImage = $images[0] ?? $data['merchant']['image'];

        $data['meta_tags'] = [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_image'       => $metaImage
        ];
    }

    protected function updateStoreUrlIfApplicable(Entity $store, array $input)
    {
        if ((($slug = $input[Entity::SLUG] ?? null) !== null) and
            ($slug !== $store->getSlugFromStoreUrl()))
        {
            $this->createAndSetStoreUrl($store, $slug);

            $this->repo->saveOrFail($store);
        }
    }

    public function uploadImage(array $input, Merchant\Entity $merchant): array
    {
        $urls = [];
        // Todo: Uncomment below line and remove line below that once devops issue(refer pr desc) fixed.
        // $cdn  = $this->config->get('url.cdn.' . $this->env);
        $cdn  = sprintf(
            self::IMAGE_UPLOAD_BASE_PATH,
            $this->env === 'production' ? 'prod' : 'nonprod');

        foreach ($input['images'] as $image)
        {

            $uploadFilename = 'payment-link/description/' . UniqueIdEntity::generateUniqueId();

            $ufhService = $this->app['ufh.service'];

            $file = $ufhService->uploadFileAndGetUrl(
                $image,
                $uploadFilename,
                Constants::PAYMENT_LINK_DESCRIPTION,
                $merchant,
                ['Content-Disposition' => 'inline']);

            $urls[] = $cdn . '/' . $file[Constants::RELATIVE_LOCATION];
        }

        return $urls;
    }

    protected function upsertSettings(Entity $store, array $settings)
    {
        if (empty($settings) === false)
        {
            $store->getSettingsAccessor()->upsert($settings)->save();
        }
    }

    protected function getMerchantKeyId(Merchant\Entity $merchant)
    {
        $key = $this->repo->key->getFirstActiveKeyForMerchant($merchant->getId());

        return optional($key)->getPublicKey($this->mode);
    }

    protected function serializeMerchantForHosted(Merchant\Entity $merchant): array
    {
        return [
            'id'               => $merchant->getId(),
            'name'             => $merchant->getBillingLabel(),
            'image'            => $merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE),
            'brand_color'      => get_rgb_value($merchant->getBrandColorOrOrgPreference()),
            'brand_text_color' => get_brand_text_color($merchant->getBrandColorOrDefault()),
            'support_details'  => $this->getMerchantSupportDetails($merchant),
        ];
    }

    protected function serializeStoreForHosted(Entity $store): array
    {
        $serialized = $store->toArrayPublic();

        $serialized['products'] = $this->serializeProducts($store, $store->merchant);

        return $serialized;
    }

    protected function createAndSetStoreUrl(Entity $store, string $slug = null)
    {
        list($url, $params, $fail) = $this->getShortenUrlRequestParams($store, $slug);

        try
        {
            $this->elfin->shorten($url, $params, $fail);

            $store->setStoreUrl($url);
        }
        catch (BaseException $e)
        {
            if (preg_match('/Duplicate|Blacklisted/', $e->getDataAsString()) === 1)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Failed to create store with given slug, please try a different value',
                    Entity::SLUG,
                    null);
            }

            throw $e;
        }
    }

    protected function getShortenUrlRequestParams(Entity $store, string $slug = null): array
    {
        $hostedBaseUrl =  $this->app['config']->get('app.payment_store_hosted_base_url');

        $url = $store->getHostedViewUrl($hostedBaseUrl, $slug);

        $params = ['ptype' => 'link'];

        $fail = true;

        $this->elfin->setNoFallback();

        $params += [
            'alias'          => $slug,
            'fail_if_exists' => true,
            'metadata'       => [
                'mode'   => $this->mode,
                'entity' => $store->getEntity(),
                'id'     => $store->getPublicId(),
            ],
        ];

        return [$url, $params, $fail];
    }

    protected function setStoreForMerchant(Merchant\Entity $merchant, Entity $store)
    {
        Settings\Accessor::for($merchant, Settings\Module::PAYMENT_STORE)
            ->upsert([Constants::MERCHANT_SETTING_STORE_KEY => $store->getPublicId()])
            ->save();
    }

    protected function deleteStoreForMerchant(Merchant\Entity $merchant)
    {
        Settings\Accessor::for($merchant, Settings\Module::PAYMENT_STORE)
            ->delete(Constants::MERCHANT_SETTING_STORE_KEY)
            ->save();
    }

    protected function getPPItemCreateInputFromProductInput(array $input): array
    {
        return [
            PaymentPageItem\Entity::STOCK          => $input[Entity::PRODUCT_STOCK],
            PaymentPageItem\Entity::MANDATORY      => false,
            PaymentPageItem\Entity::MIN_PURCHASE   => 1,
            PaymentPageItem\Entity::PRODUCT_CONFIG => [
                Entity::PRODUCT_IMAGES        => $input[Entity::PRODUCT_IMAGES] ?? [],
                Entity::PRODUCT_SELLING_PRICE  => $input[Entity::PRODUCT_SELLING_PRICE] ?? null,
            ],
            E::ITEM => [
                Item\Entity::NAME        => $input[Entity::PRODUCT_NAME],
                Item\Entity::DESCRIPTION => $input[Entity::DESCRIPTION] ?? '',
                Item\Entity::AMOUNT      => $input[Entity::PRODUCT_DISCOUNTED_PRICE],
                Item\Entity::CURRENCY    => Currency::INR,
            ]
        ];
    }

    protected function getPPItemUpdateInputFromProductInput(array $input): array
    {
        return [
            PaymentPageItem\Entity::STOCK          => $input[Entity::PRODUCT_STOCK],
            PaymentPageItem\Entity::MANDATORY      => false,
            PaymentPageItem\Entity::MIN_PURCHASE   => 1,
            PaymentPageItem\Entity::PRODUCT_CONFIG => [
                Entity::PRODUCT_IMAGES        => $input[Entity::PRODUCT_IMAGES] ?? [],
                Entity::PRODUCT_SELLING_PRICE  => $input[Entity::PRODUCT_SELLING_PRICE] ?? null,
            ],
            E::ITEM => [
                Item\Entity::NAME        => $input[Entity::PRODUCT_NAME],
                Item\Entity::DESCRIPTION => $input[Entity::DESCRIPTION] ?? '',
                Item\Entity::AMOUNT      => $input[Entity::PRODUCT_DISCOUNTED_PRICE],
            ]
        ];
    }

    protected function doProductStatusActionIfPresent(PaymentPageItem\Entity $product, array & $input)
    {
        if(isset($input[Entity::STATUS]) === false)
        {
            return;
        }

        switch ($input[Entity::STATUS])
        {
            case Entity::PRODUCT_STATUS_ACTIVE:

                $product->itemWithTrashed->setDeletedAt(null);

                break;

            case Entity::PRODUCT_STATUS_INACTIVE:

                $product->itemWithTrashed->setDeletedAt(Carbon::now()->timestamp);

                break;
        }

        $this->repo->item->saveOrFail($product->itemWithTrashed);

        $product->setUpdatedAt(Carbon::now()->timestamp);

        $this->repo->payment_page_item->saveOrFail($product);

        unset($input[Entity::STATUS]);
    }

    protected function getMerchantSupportDetails(Merchant\Entity $merchant)
    {
        $supportDetails = $this->repo->merchant_email->getEmailByType(Merchant\Email\Type::SUPPORT, $merchant->getId());

        if ($supportDetails !== null)
        {
            $supportDetails = $supportDetails->toArrayPublic();

            return [
                'support_email'  => $supportDetails[Merchant\Email\Entity::EMAIL],
                'support_mobile' => $supportDetails[Merchant\Email\Entity::PHONE]
            ];
        }

        return ['support_email' => '', 'support_mobile' => ''];
    }

    protected function updateDiscountedPriceIfApplicable(array & $input)
    {
        if ((isset($input[Entity::PRODUCT_DISCOUNTED_PRICE]) === true) &&
            (!empty($input[Entity::PRODUCT_DISCOUNTED_PRICE]) === true))
        {
            return;
        }

        $input[Entity::PRODUCT_DISCOUNTED_PRICE] = $input[Entity::PRODUCT_SELLING_PRICE];
    }

    protected function addInputFieldsAsRequired(array & $input, Entity $store)
    {

        $input += [
            PaymentPageItem\Entity::PAYMENT_LINK_ID => $store->getId()
        ];

        if(isset($input[Entity::STATUS]) === false)
        {
            return;
        }

        switch ($input[Entity::STATUS])
        {
            case Entity::PRODUCT_STATUS_ACTIVE:

                $input += [
                    PaymentPageItem\Entity::ITEM_DELETED_AT => null,
                ];

                break;

            case Entity::PRODUCT_STATUS_INACTIVE:

                $input += [
                    PaymentPageItem\Entity::ITEM_DELETED_AT => Carbon::now()->timestamp,
                ];

                break;
        }

        unset($input[Entity::STATUS]);
    }

    protected function serializeProducts(Entity $store, Merchant\Entity $merchant): array
    {
        $productArray = [];

        $products = $this->repo->payment_page_item->fetchAllItemsOfPaymentLink($store, $merchant, true);

        foreach ($products as $product)
        {
            if ($product->itemWithTrashed->getDeletedAt() === null)
            {
                array_push($productArray, $product->toStoreProductArrayPublic());
            }
        }

        return $productArray;
    }

    protected function getTotalAmountForOrder(array $input, $additionalAmount = 0, string $store_id)
    {
        $totalAmount = 0;

        foreach ($input as $lineItem)
        {
            $totalAmount += $lineItem[LineItem\Entity::AMOUNT] * ($lineItem[LineItem\Entity::QUANTITY] ?? 1);
        }

        if (!empty($additionalAmount) === true)
        {
            $totalAmount += $additionalAmount;
        }

        return $totalAmount;
    }

    protected function modifyAndValidateInputToCreateLineItems(array $input, Entity $store)
    {
        $modifiedInput = [];

        $PPIValidator = new PaymentPageItem\Validator();

        foreach ($input[Entity::LINE_ITEMS] as $lineItem)
        {
            $paymentPageItemId = $lineItem[Entity::PAYMENT_PAGE_ITEM_ID];

            unset($lineItem[Entity::PAYMENT_PAGE_ITEM_ID]);

            $paymentPageItemId = PaymentPageItem\Entity::verifyIdAndStripSign($paymentPageItemId);

            $paymentPageItem = $this->repo->payment_page_item->findByIdAndPaymentLinkEntityAndMerchantOrFail(
                $paymentPageItemId,
                $store,
                $store->merchant
            );

            $itemId = $paymentPageItem->getItemId();

            $lineItem[LineItem\Entity::ITEM_ID] = Item\Entity::getSignedId($itemId);

            $lineItem[Entity::AMOUNT] = $paymentPageItem->item->getAmount();

            $lineItem[LineItem\Entity::REF] = $paymentPageItem;

            $modifiedInput[Entity::LINE_ITEMS][] = $lineItem;

            $PPIValidator->validateAmountQuantityAndStockOfPPI($paymentPageItem, $lineItem);
        }

        $modifiedInput[Order\Entity::NOTES] = $input[Order\Entity::NOTES] ?? [];

        return $modifiedInput;
    }

}
