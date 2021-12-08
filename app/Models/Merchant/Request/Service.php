<?php

namespace RZP\Models\Merchant\Request;

use Cache;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Batch\Entity as Batch;


class Service extends Base\Service
{

    protected $cache;

    public function __construct()
    {
        parent::__construct();

        $this->cache = $this->app['cache'];
    }

    /*
     * This function to be used when Merchant asks for specific product related status on relevant page on dashboard.
     */
    public function getForFeatureTypeAndName(string $type, string $featureName)
    {
        $merchantId = $this->merchant->getId();

        $input = [
            Entity::NAME => $featureName,
            Entity::TYPE => $type,
        ];

        $request = (new Core)->fetch($input, $merchantId, true);

        if (isset($request[Entity::ID]) === true)
        {
            $id = $request[Entity::ID];

            return $this->get($id);
        }

        return $request;
    }

    public function getAll(array $input)
    {
        $input[Constants::EXPAND] = [Entity::MERCHANT, 'merchant.merchantDetail'];

        return (new Core)->fetch($input);
    }

    public function getStatusLog(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $merchantRequest = $this->repo->merchant_request->findOrFailPublic($id);

        return $merchantRequest->states->toArrayPublic();
    }

    public function get(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        return (new Core)->getMerchantRequestDetails($id);
    }

    /**
     * Creates a merchant request. If the merchant request is for a product activation, it also adds the submissions
     * in the settings table.
     *
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function create(array $input)
    {
        $core = new Core;

        $input[Entity::MERCHANT_ID] = $this->merchant->getId();

        $input[Entity::STATUS] = Status::UNDER_REVIEW;

        $request = $core->createMerchantRequest($input);

        return $core->getMerchantRequestDetails($request->getId());
    }

    public function update(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        $request = $this->repo->merchant_request->findOrFailPublic($id);

        $core = new Core;

        $core->updateMerchantRequest($request, $input);

        return $core->getMerchantRequestDetails($id);
    }

    public function bulkUpdate(array $input)
    {
        return (new Core)->bulkUpdateMerchantRequests($input);
    }

    public function getRejectionReasons()
    {
        return RejectionReasons::REJECTION_REASONS_MAPPING;
    }

    public function issueOneTimeToken()
    {
        do
        {
            $token = bin2hex(random_bytes(20));
        }
        while($this->cache->has($token));

        // Generate One Time Token valid for 5 minutes
        $ttl = 5 * 60;
        $this->cache->put($token, ['merchantId' => $this->merchant->getId(), 'mode' => $this->mode], $ttl);

        return [
            Batch::TOKEN =>  $token
        ];
    }

    public function isValidOneTimeToken($token): bool
    {
        return $this->cache->has($token);
    }

    /**
     * @param $token
     * @return mixed
     * @throws \Exception when token is invalid or expired
     */
    public function consumeOneTimeToken($token)
    {
        $value = $this->cache->pull($token);

        if ($value === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BATCH_UPLOAD_INVALID_TOKEN);
        }

        $this->app['rzp.mode'] = $value['mode'];
        $this->app['basicauth']->setModeAndDbConnection($value['mode']);

        $merchant = $this->repo->merchant->find($value['merchantId']);
        $this->app['basicauth']->setMerchant($merchant);

        return $merchant;
    }
}
