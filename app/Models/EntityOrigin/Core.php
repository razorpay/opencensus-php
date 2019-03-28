<?php

namespace RZP\Models\EntityOrigin;

use Razorpay\OAuth\Application as OAuthApp;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * Origin entity can be an instance of Merchant entity or an Oauth application
     *
     * @param Base\PublicEntity                 $entity
     * @param Merchant\Entity|OAuthApp\Entity   $originEntity
     * @param array                             $input
     *
     * @return Entity
     */
    public function create(Base\PublicEntity $entity, $originEntity, array $input = []): Entity
    {
        $entityOrigin = new Entity;

        $entityOrigin->build($input);

        $entityOrigin->origin()->associate($originEntity);

        $entityOrigin->entity()->associate($entity);

        $this->repo->saveOrFail($entityOrigin);

        return $entityOrigin;
    }

    /**
     * @param Base\PublicEntity $entity
     *
     * @return void
     */
    public function createEntityOrigin(Base\PublicEntity $entity)
    {
        try
        {
            list($originType, $originId) = app('basicauth')->getOriginDetailsFromAuth();

            $originEntity = $this->fetchOriginEntity($originType, $originId);

            //
            // Non null origin details are returned only for public auth, partner auth and bearer auth.
            // Return if null values are returned.
            //
            if (empty($originEntity) === true)
            {
                return;
            }

            $entityOrigin = $this->create($entity, $originEntity);

            // @todo : Remove later, not required
            $this->trace->info(TraceCode::ORIGIN_CREATED,
                                    [
                                        Entity::ID => $entityOrigin->getId(),
                                    ]);
        }
        catch (\Throwable $e)
        {
            // The payment should not be blocked even if the origin cannot be created. Log an error and proceed.
            $this->trace->critical(TraceCode::ORIGIN_SET_FAILED,
                                    [
                                        'message'           => $e->getMessage(),
                                        Entity::ENTITY_TYPE => $entity->getEntity(),
                                        Entity::ENTITY_ID   => $entity->getId(),
                                    ]);

            return;
        }
    }

    /**
     * @param Base\PublicEntity $entity
     *
     * @return bool
     */
    public function isOriginApplication(Base\PublicEntity $entity): bool
    {
        $entityOrigin = $entity->entityOrigin;

        //
        // If the origin (merchant / application) is defined for the source entity (payment, refund etc),
        // fetch the origin, else, return null.
        //
        $origin     = optional($entityOrigin)->origin;
        $originType = optional($origin)->getEntityName();

        return ($originType === Constants::APPLICATION);
    }

    /**
     * @param Base\PublicEntity $entity
     *
     * @return mixed
     */
    public function getOrigin(Base\PublicEntity $entity)
    {
        $entityOrigin = $entity->entityOrigin;

        $origin = optional($entityOrigin)->origin;

        return $origin;
    }

    /**
     * Origin type and id can be null.
     *
     * @param $originType
     * @param $originId
     *
     * @return mixed|null
     */
    protected function fetchOriginEntity($originType, $originId)
    {
        $originEntity = null;

        switch ($originType)
        {
            case Constants::MERCHANT:
                // If the merchant's credentials are used, fetch the merchant entity directly from the BasicAuth
                $originEntity = app('basicauth')->getMerchant();
                break;

            case Constants::APPLICATION:
                $originEntity = (new OAuthApp\Repository)->find($originId);
                break;

            default:
                break;
        }

        if ($originEntity === null)
        {
            $authType                  = app('basicauth')->getAuthType();
            $hasPartnerAuthCallbackKey = app('basicauth')->hasPartnerAuthCallbackKey();
            $routeName                 = app('router')->currentRouteName();

            $this->trace->critical(
                TraceCode::ORIGIN_INVALID_TYPE,
                [
                    Entity::ORIGIN_TYPE             => $originType,
                    Entity::ORIGIN_ID               => $originId,
                    'auth_type'                     => $authType,
                    'has_partner_auth_callback_key' => $hasPartnerAuthCallbackKey,
                    'route_name'                    => $routeName,
                ]
            );
        }

        return $originEntity;
    }
}
