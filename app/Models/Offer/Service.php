<?php

namespace RZP\Models\Offer;

use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use Cache;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Card as Card;
use RZP\Models\Order\ProductType;
use RZP\Models\Feature\Constants as Feature;

class Service extends Base\Service
{
    const PROXY_ROUTES = [
        'offer_create',
        'offer_update',
        'offer_fetch_multiple',
        'offer_fetch_by_id',
        'offer_fetch_subscription',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->route = $this->app['api.route'];

        $this->validateAccess();
    }

    public function create(array $input)
    {
        $offer = $this->core->create($input);

        return $offer->toArrayProxy();
    }

    public function createBulk(array $input)
    {
        (new Validator)->validateInput('create_bulk', $input);

        $this->trace->info(TraceCode::OFFER_CREATE_BULK, $input);

        $offer = $input['offer'];

        $merchantIds = $input['merchant_ids'];

        $success  = 0;
        $failures = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $this->core->withMerchant($merchant)->create($offer);

                $success += 1;
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e);

                $failures[] = $merchantId;
            }
        }

        $summary  = [
            'success'  => $success,
            'failures' => $failures
        ];

        $this->trace->info(TraceCode::OFFER_CREATE_BULK, $summary);

        return $summary;
    }

    public function update(string $id, array $input)
    {
        $this->trace->info(TraceCode::OFFER_UPDATE_REQUEST, $input);

        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        $offer = $this->core->update($offer, $input);

        return $offer->toArrayProxy();
    }

    public function fetch(string $id)
    {
        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        if ($offer->getProductType() === ProductType::SUBSCRIPTION)
        {
            $offer = $this->repo->offer->fetchSubscriptionOfferById($offer->getId(), false, true);
        }

        return $offer->toArrayProxy();
    }

    public function fetchMultiple(array $input)
    {
        $offers = $this->repo->offer->fetch($input, $this->merchant->getId());

        return $offers->toArrayProxy();
    }

    public function fetchOffersSubscription(array $input)
    {
        $paymentMethods = $input['payment_methods'] ?? ['card', 'upi'];

        $offers = $this->repo->offer->fetchOffersSubscription($paymentMethods, $this->merchant->getId());

        return $offers->toArrayProxy();
    }

    public function bulkDeactivateOffers()
    {
        if (empty($_FILES) === true)
        {
            return [];
        }
        $file = file_get_contents($_FILES['file']['tmp_name']);
        $rows = explode("\n", str_replace("\r", "", $file));

        return $this->core->bulkDeactivateOffers($rows);
    }

    public function deactivate()
    {
        $disabledOffers = $this->core->deactivate();

        return $disabledOffers;
    }

    protected function validateAccess()
    {
        $route = $this->route->getCurrentRouteName();

        //
        // Applying this check only on offers CRU
        //
        if (in_array($route, self::PROXY_ROUTES, true) === false)
        {
            return;
        }

        //
        // All merchants have access to offer routes over proxy auth
        //
        if ($this->auth->isProxyAuth() === true)
        {
            return;
        }

        //
        // Merchants with this feature can also access and create offers over private auth
        //
        if ($this->auth->getMerchant()->isFeatureEnabled(Feature::OFFER_PRIVATE_AUTH) === true)
        {
            return;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
    }

    public function validateCheckoutOffers($input)
    {
        (new Validator())->validateInput('validate_checkout_offers', $input);

        $applicableOffers = [];

        $orderEntity = $this->repo->order->findByPublicIdAndMerchant($input['order_id'], $this->merchant);

        if (isset($input["card"]["number"]) === false and isset($input["card"]["token"]) === false)
        {
            return $applicableOffers;
        }
        $cardNumber = null;

        if (isset($input["card"]["token"]) === true)
        {
            $cardNumber = (new Card\CardVault)->getCardNumber($input["card"]["token"]);
        }

        else
        {
            $cardNumber = $input["card"]["number"];
        }

        $iin = substr($cardNumber, 0, 6);

        $iinEntity = $this->repo->iin->find($iin);

        if (isset($iinEntity) === false)
        {
            throw new Exception\BadRequestException('BAD_REQUEST_ERROR', 'iin', null, 'iin not found');
        }

        $payment = Payment\Service::getNewInstance()->getDummyPayment($orderEntity, $iinEntity);

        $verbose = $this->isVerboseLogEnabled();

        $offerIds = $input['offers'];

        Entity::verifyIdAndStripSignMultiple($offerIds);

        $offers = $this->repo->offer->findMany($offerIds);
        //iterating over all offers
        foreach ($offers as $offer)
        {
            $checker = new Checker($offer, $verbose);
            //validating whether offer is applicable for payment or not
            if ($checker->checkApplicabilityForPaymentBeforeCheckout($payment, $orderEntity) === true)
            {
                //adding the offer public id to return list
                $applicableOffers[] = $offer->getPublicId();
            }
        }

        return $applicableOffers;
    }

    protected function isVerboseLogEnabled(): bool
    {
        try
        {
            $verbose = (bool) Cache::get(ConfigKey::OFFER_LOG_VERBOSE);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR);

            $verbose = false;
        }

        return $verbose;
    }

    /**
     * Checks If Offer is Existing and can be Applied on the Amount
     * Used by Subscription Service to validate even before forcing an offer, on subscription creation
     * @param $input
     * @return array
     */
    public function fetchOffersDiscountForSubscription($input): array
    {
        return $this->core->fetchOffersDiscountForSubscription($input);
    }

    /**
     * Fetches Offers that can be applied on a subscription
     * Used By subscription Service to Show On Hosted Page
     * @param $input
     * @return array
     */
    public function fetchOffersPreferenceForSubscription($input): array
    {
        return $this->core->fetchOffersPreferenceForSubscription($input);
    }
}
