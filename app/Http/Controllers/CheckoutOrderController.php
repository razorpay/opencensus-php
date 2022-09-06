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

    public function close(Request $request): JsonResponse
    {
        $data = $this->service()->close($request->all());

        return Response::json($data);
    }
}
