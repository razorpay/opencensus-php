<?php

namespace RZP\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use RZP\Models\Checkout\Order\Service as CheckoutOrderService;

class CheckoutOrderController extends Controller
{
    protected $service = CheckoutOrderService::class;

    public function create(Request $request): JsonResponse
    {
        $data = $this->service()->create($request->all());

        return Response::json($data);
    }

    public function close(Request $request, string $checkoutOrderId): JsonResponse
    {
        $this->service()->close($request->all(), $checkoutOrderId);

        return Response::json([], 204);
    }

    public function createCheckoutOrdersPartition(): JsonResponse
    {
        $response = $this->service()->createCheckoutOrdersPartition();

        return Response::json($response);
    }
}
