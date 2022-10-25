<?php

namespace RZP\Models\Checkout\Order;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Constants\HyperTrace;
use RZP\Http\Route;
use RZP\Models\Base\Service as BaseService;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason as NonVAQrCodeCloseReason;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Core as NonVAQrCodeCore;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as NonVAQrCodeEntity;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Service as NonVAQrCodeService;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class Service extends BaseService
{
    /** @var Route */
    protected $route;

    public function __construct()
    {
        parent::__construct();

        $this->route = $this->app['api.route'];
    }

    /**
     *
     * @param array $input
     * @return array
     */
    public function create(array $input): array
    {
        $this->traceRequest(TraceCode::CREATE_CHECKOUT_ORDER_REQUEST, $input);

        $checkoutOrder = $this->core()->create($input);

        $receiverArray = $this->handleReceiver($checkoutOrder, $input);

        return array_merge($checkoutOrder->toArrayPublic(), $receiverArray);
    }

    /**
     * @param array $input
     * @param string $checkoutOrderId
     * @return void
     */
    public function close(array $input, string $checkoutOrderId): void
    {
        $this->traceRequest(
            TraceCode::CLOSE_CHECKOUT_ORDER_REQUEST,
            array_merge($input, ['checkout_order_id' => $checkoutOrderId])
        );

        (new Validator())->validateInput('close', $input);

        /** @var Entity $checkoutOrder */
        $checkoutOrder = $this->repo->checkout_order->findByPublicIdAndMerchant($checkoutOrderId, $this->merchant);

        $qrCode = $this->repo->qr_code->findActiveQrCodeByCheckoutOrder($checkoutOrder);

        if ($qrCode !== null)
        {
            (new NonVAQrCodeCore())->close($qrCode, NonVAQrCodeCloseReason::ON_DEMAND);
        }

        if ($checkoutOrder->getClosedAt() !== null)
        {
            return;
        }

        $checkoutOrder->setStatus(Status::CLOSED);
        $checkoutOrder->setCloseReason($input[Entity::CLOSE_REASON]);
        $checkoutOrder->setClosedAt(Carbon::now()->getTimestamp());

        $this->repo->saveOrFail($checkoutOrder);
    }

    protected function handleReceiver(Entity $checkoutOrder, array $input): array
    {
        $receiverType = $input[Entity::RECEIVER_TYPE] ?? '';

        switch ($receiverType) {
            case ConstantsEntity::QR_CODE:
                $qrCodeArray = $this->createQrCode($checkoutOrder);

                return [
                    ConstantsEntity::QR_CODE => $qrCodeArray,
                    'request' => [
                        'method' => 'GET',
                        // polling url
                        'url' => $this->route->getUrlWithPublicAuthInQueryParam(
                            'qr_code_fetch_payment_status',
                            ['id' => $qrCodeArray['id']]
                        ),
                    ],
                ];

            default:
                return [];
        }
    }

    protected function createQrCode(Entity $checkoutOrder): array
    {
        $input = [
            NonVAQrCodeEntity::ENTITY_ID => $checkoutOrder->getId(),
            NonVAQrCodeEntity::ENTITY_TYPE => ConstantsEntity::CHECKOUT_ORDER,
            NonVAQrCodeEntity::REQ_AMOUNT => $checkoutOrder->getFinalAmount(),
        ];

        $additionalAttributes = [
            NonVAQrCodeEntity::CUSTOMER_ID,
            NonVAQrCodeEntity::DESCRIPTION,
            NonVAQrCodeEntity::NAME,
            NonVAQrCodeEntity::NOTES,
        ];

        foreach ($additionalAttributes as $attribute) {
            $function = 'get' . studly_case($attribute);

            if (!method_exists($checkoutOrder, $function)) {
                continue;
            }

            $value = $checkoutOrder->{$function}();

            $value = ($value instanceof Arrayable) ? $value->toArray() : $value;

            if (!empty($value)) {
                $input[$attribute] = $value;
            }
        }

        if (isset($input[NonVAQrCodeEntity::CUSTOMER_ID]))
        {
            $checkoutOrder->setPublicCustomerIdAttribute($input);
        }

        return Tracer::inspan(
            ['name' => HyperTrace::QR_CODE_CREATE_FOR_CHECKOUT],
            static function () use ($input) {
                return (new NonVAQrCodeService())->createForCheckout($input);
            }
        );
    }

    protected function traceRequest(string $traceCode, array $input): void
    {
        $this->trace->info($traceCode, [
            'input' => $input,
        ]);
    }
}
