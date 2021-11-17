<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Http\Request as CurrentRequest;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\PaymentLink\Entity;
use RZP\Models\Store\Service;
use RZP\Trace\Tracer;
use View;
use Request;
use RZP\Trace\TraceCode;

class StoreController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new Service();

    }

    public function create()
    {
        $input = Request::all();

        $response = $this->service()->create($input);

        return ApiResponse::json($response);
    }

    public function getByMerchant()
    {
        $response = $this->service()->getByMerchant();

        return ApiResponse::json($response);
    }

    public function delete()
    {
        $response = $this->service()->delete();

        return ApiResponse::json($response);
    }

    public function update()
    {
        $input = Request::all();

        $response = $this->service()->update($input);

        return ApiResponse::json($response);
    }

    public function validateSlug()
    {
        $input = Request::all();

        $slug = $input['slug'];

        $gimli  = $this->app['elfin']->driver('gimli');

        $exists =  ($gimli->expand($slug) !== null);

        return ApiResponse::json(compact('exists'));
    }

    public function addProduct()
    {
        $input = Request::all();

        $response = $this->service()->addProduct($input);

        return ApiResponse::json($response);
    }

    public function updateProduct(string $id)
    {
        $input = Request::all();

        $response = $this->service()->updateProduct($id, $input);

        return ApiResponse::json($response);
    }

    public function fetchProducts()
    {
        $input = Request::all();

        $response = $this->service()->fetchProducts($input);

        return ApiResponse::json($response);
    }

    public function getProduct($productId)
    {
        $response = $this->service()->getProduct($productId);

        return ApiResponse::json($response);
    }

    public function patchProduct($productId)
    {
        $input = Request::all();

        $response = $this->service()->patchProduct($productId, $input);

        return ApiResponse::json($response);
    }

    public function uploadImage()
    {
        $response = $this->service()->uploadImage($this->input);

        return ApiResponse::json($response);
    }

    public function fetchOrders()
    {
        $input = Request::all();

        $response = $this->service->fetchOrders($input);

        return ApiResponse::json($response);
    }

    public function getHostedPage($slug)
    {
        $id = $this->getStoreIdFromSlug($slug);

        return $this->getHostedPageById($id);
    }

    public function getHostedPageById($id)
    {
        try {
            $payload = $this->service()->getHostedPageData($id);

            return View::make('store.index', ['data' => $payload]);
        }
        catch(BadRequestException | BadRequestValidationFailureException $e)
        {
            $data = ['error_code' => $e->getCode(), 'message' => $e->getMessage(), 'data' => $e->getData()];

            return View::make('payment_link.error_payment_link', ['data' => $data]);
        }
    }

    public function getHostedPageForProductDetail($slug, $productId)
    {
        $id = $this->getStoreIdFromSlug($slug);

        try {
            $payload = $this->service()->getHostedPageProductDetailData($id, $productId);

            return View::make('store.product_detail', ['data' => $payload]);
        }
        catch(BadRequestException | BadRequestValidationFailureException $e)
        {
            $data = ['error_code' => $e->getCode(), 'message' => $e->getMessage(), 'data' => $e->getData()];

            return View::make('payment_link.error_payment_link', ['data' => $data]);
        }

    }

    public function getHostedPageData($slug)
    {
        $id = $this->getStoreIdFromSlug($slug);

        $response = $this->service()->getHostedPageData($id);

        return ApiResponse::json($response);
    }

    public function getHostedPageDataOptions($slug, CurrentRequest $request)
    {
        return $this->createOrderOptions($slug, $request);
    }

    public function redirectSlugToHostedPage($slug, $param1 = null)
    {
        return redirect()->route('store_hosted_page_by_slug', ['slug' => $slug]);
    }

    public function createOrder(string $id)
    {
        $input = Request::all();

        $response = $this->service()->createOrder($id, $input);

        return ApiResponse::json($response);
    }

    public function createOrderOptions(string $id, CurrentRequest $request)
    {
        $response = ApiResponse::json([]);

        $origin = $request->headers->get('origin');

        $urls = $this->app['config']->get('app.payment_store_allowed_cors_url');

        if (in_array($origin, $urls) === true)
        {
            $response->headers->set('Access-Control-Allow-Origin', $origin);

            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

            return $response;
        }

        $response->headers->set(
            'Access-Control-Allow-Origin',
            $this->app['config']->get('app.payment_store_hosted_base_url')
        );

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }

    protected function getStoreIdFromSlug($slug)
    {
        $gimli        = $this->app['elfin']->driver('gimli');

        $slugMetadata = $gimli->expandAndGetMetadata($slug);

        // Renders 404 if no metadata available(error/exception at Gimli side)
        if ($slugMetadata === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $this->ba->setModeAndDbConnection($slugMetadata['mode']);

        return $slugMetadata['id'];
    }
}
