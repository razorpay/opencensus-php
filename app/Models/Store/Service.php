<?php

namespace RZP\Models\Store;

use Request;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Service extends Base\Service
{
    protected $core;

    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->payment_link;
    }

    public function create(array $input): array
    {
        $entity = $this->core->create($input, $this->merchant);

        return $entity->toArrayPublic();
    }

    public function getByMerchant(): array
    {
        $store = $this->core->getByMerchant($this->merchant);

        return $store->toArrayPublic();
    }

    public function delete(): array
    {
        $entity = $this->core->delete($this->merchant);

        return $entity->toArrayPublic();
    }

    public function update(array $input): array
    {
        $entity = $this->core->update($input, $this->merchant);

        return $entity->toArrayPublic();
    }

    public function addProduct(array $input): array
    {
        $product = $this->core->addProduct($input, $this->merchant);

        return $product->toStoreProductArrayPublic();
    }

    public function updateProduct(string $productId, array $input)
    {
        $product =  $this->core->updateProduct($productId, $input, $this->merchant);

        return $product->toStoreProductArrayPublic();
    }

    public function fetchProducts(array $input): array
    {
        return $this->core->fetchProducts($input, $this->merchant);
    }

    public function getProduct(string $productId)
    {
        $product = $this->core->getProduct($productId, $this->merchant);

        return $product->toStoreProductArrayPublic();
    }

    public function patchProduct(string $productId, array $input)
    {
        $product = $this->core->patchProduct($productId, $input, $this->merchant);

        return $product->toStoreProductArrayPublic();
    }

    public function uploadImage(array $input)
    {
        (new Validator())->validateInput('uploadImages', $input);

        return $this->core->uploadImage($input, $this->merchant);
    }

    public function getHostedPageData(string $id)
    {
        $store = $this->repo->store->findByPublicId($id);

        $merchant = $store->merchant;

        $activeStore = $this->core->getStoreEntity($merchant);

        if ($store->getPublicId() !== $activeStore->getPublicId())
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'This store does not exist');
        }

        return $this->core->getViewData($store);
    }

    // Need to merge the above function validations into a single function
    public function getHostedPageProductDetailData(string $id, string $productId)
    {
        $store = $this->repo->store->findByPublicId($id);

        $merchant = $store->merchant;

        $activeStore = $this->core->getStoreEntity($merchant);

        if ($store->getPublicId() !== $activeStore->getPublicId())
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'This store does not exist');
        }

        $productId = \RZP\Models\PaymentLink\PaymentPageItem\Entity::stripDefaultSign($productId);

        $product = $this->repo->payment_page_item->findByIdAndPaymentLinkEntityAndMerchantOrFail($productId, $store, $merchant);

        return $this->core->getViewDataForProductDetailPage($store, $product);
    }

    protected function setModeAndMerchant(string $id)
    {
        try
        {
            $this->app['basicauth']->setModeAndDbConnection('live');

            $store = $this->repo->store->findByPublicId($id);

            $this->mode = Mode::LIVE;
        }
        catch (\Exception $e)
        {
            $this->app['basicauth']->setModeAndDbConnection('test');

            $store = $this->repo->store->findByPublicId($id);

            $this->mode = Mode::TEST;
        }

        $this->app['basicauth']->setMerchant($store->merchant);

        return $store->merchant;
    }

    public function createOrder(string $id, array $input): array
    {
        $store = $this->getStoreAndSetModeAndMerchant($id);

        $data = (new Core)->createOrder($store,$input);

        for($i = 0; $i < count($data[Entity::LINE_ITEMS]); $i++)
        {
            $data[Entity::LINE_ITEMS][$i] = $data[Entity::LINE_ITEMS][$i]->toArrayPublic();
        }

        $data[Entity::ORDER] = $data[Entity::ORDER]->toArrayPublic();

        return $data;
    }

    protected function getStoreAndSetModeAndMerchant(string $id)
    {
        $store = null;

        try
        {
            $this->app['basicauth']->setModeAndDbConnection('live');

            $store = $this->repo->store->findByPublicId($id);
        }
        catch (\Exception $e)
        {
            $this->app['basicauth']->setModeAndDbConnection('test');

            $store = $this->repo->store->findByPublicId($id);
        }

        $this->app['basicauth']->setMerchant($store->merchant);

        return $store;
    }

}
