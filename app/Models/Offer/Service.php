<?php

namespace RZP\Models\Offer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Feature\Constants as Feature;

class Service extends Base\Service
{
    const PROXY_ROUTES = [
        //'offer_create',
        //'offer_update',
        'offer_fetch_multiple',
        'offer_fetch_by_id',
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

        return $offer->toArrayPublic();
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

        return $offer->toArrayPublic();
    }

    public function fetch(string $id)
    {
        $offer = $this->repo->offer->findByPublicIdAndMerchant($id, $this->merchant);

        return $offer->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $offers = $this->repo->offer->fetch($input, $this->merchant->getId());

        return $offers->toArrayPublic();
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
}
