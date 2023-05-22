<?php

namespace RZP\Models\EntityOrigin;

use Razorpay\OAuth\Token as OAuthToken;
use Razorpay\OAuth\Application as OAuthApp;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Key\Metric;
use RZP\Models\PaymentLink;
use RZP\Constants\Entity as E;
use RZP\Http\BasicAuth\ClientAuthCreds;
use Razorpay\OAuth\Client as OAuthClient;

class Core extends Base\Core
{
    const PARTNER_KEY_REGEX = '/^(rzp_(test|live)_partner_[a-zA-Z0-9]{14})$/';
    const OAUTH_KEY_REGEX   = '/^(rzp_(test|live)_oauth_[a-zA-Z0-9]{14})$/';

    /**
     * Origin entity can be an instance of Merchant entity or an Oauth application
     *
     * @param Base\PublicEntity $entity
     * @param                   $originEntity
     *
     * @return Entity
     */
    public function create(Base\PublicEntity $entity, $originEntity): Entity
    {
        $entityOrigin = $this->build($entity, $originEntity);

        $this->repo->saveOrFail($entityOrigin);

        return $entityOrigin;
    }

    public function createFromInternalApp(array $input): Entity
    {
        (new Validator)->validateInput('create_from_internal_app', $input);

        $entity = $this->fetchEntityByType($input[Entity::ENTITY_TYPE], $input[Entity::ENTITY_ID]);
        $origin = $this->fetchEntityByType($input[Entity::ORIGIN_TYPE], $input[Entity::ORIGIN_ID]);

        return $this->create($entity, $origin);
    }

    /**
     * @param Base\PublicEntity $entity
     */
    public function createEntityOrigin(Base\PublicEntity $entity, $originType = null)
    {
        try
        {
            $entityOrigin = $this->fetchEntityOrigin($entity);

            if (empty($entityOrigin) === false)
            {
                // override origin type (introduced for route marketplace usecase)
                if (empty($originType) === false)
                {
                    $entityOrigin[Entity::ORIGIN_TYPE] = $originType;
                }

                $this->repo->saveOrFail($entityOrigin);
            }

            return $entityOrigin;
        }
        catch (\Throwable $e)
        {
            // The payment should not be blocked even if the origin cannot be created. Log an error and proceed.
            $this->trace->critical(TraceCode::ORIGIN_SET_FAILED,
                [
                    'message'           => $e->getMessage(),
                    Entity::ENTITY_TYPE => $entity->getEntity(),
                    Entity::ENTITY_ID   => $entity->getId(),
                    'stack_trace'       => $e->getTraceAsString(),
                ]);
            $dimensions = array(Entity::ENTITY_TYPE => $entity->getEntity());
            $this->trace->count(Metric::ENTITY_ORIGIN_CREATE_FAILED_TOTAL, $dimensions);
        }
    }

    /**
     * @param Base\PublicEntity $entity
     *
     * @return Entity|null
     */
    public function fetchEntityOrigin(Base\PublicEntity $entity)
    {
        $receiver       = $entity->receiver;      // Example receiver: Qr_code entity

        $subscriptionId = null;

        // check if subscription payment and is payment entity
        if ($entity->getEntityName() === E::PAYMENT)
        {
            $subscriptionId = $entity->getSubscriptionId();
        }

        //
        // If the txn has an associated subscription entity, fetch the subscription's entity_origin.
        // If the txn has an associated receiver entity, it is a VA txn. We extract the VA from the receiver
        // and set the VA's entity_origin as the entity_origin for this txn.
        // If the txn does not have either of those, basic auth is used to extract the origin entity's details.
        //
        if (optional($entity->order)->getProductType() === Order\ProductType::PAYMENT_LINK_V2)
        {
            $originEntity = $this->getOriginEntityFromPaymentLinkId($entity->order->getProductId());
        }
        else if (optional($entity->order)->getProductType() === Order\ProductType::INVOICE)
        {
            $originEntity = $this->getOriginEntityFromInvoice($entity->order->getProductId());
        }
        else if (empty($subscriptionId) === false)
        {
            $originEntity = $this->getOriginEntityFromSubscription($subscriptionId);
        }
        else if (empty($receiver) === false)
        {
            $originEntity = $this->getOriginEntityFromReceiver($receiver);
        }
        else
        {
            $originEntity = $this->getOriginEntityFromAuth();
        }

        //
        // Non null origin details are returned only for public auth, partner auth and bearer auth.
        // Return if null values are returned.
        //
        if (empty($originEntity) === true)
        {
            // if origin entity is not created as a fallback try to get public key from payment
            // if public key is not found in payment check for public key in order.
            if( $entity->getEntityName() === E::PAYMENT && $entity->getPublicKey() != null)
            {
                $this->trace->info(TraceCode::SET_ORIGIN_FROM_PAYMENT_PUBLIC_KEY, [
                    'payment_id'  => $entity->getId(),
                    'method'      => $entity->getMethod()
                ]);
                $dimensions = array('Method' => $entity->getMethod());
                $this->trace->count(Metric::ENTITY_ORIGIN_CREATE_FROM_PAYMENT_PUBLIC_KEY, $dimensions);
                $originEntity = $this->getOriginEntityFromPublicKey($entity->getPublicKey());
            }
            else if(optional($entity->order)->getPublicKey() !== null)
            {
                $this->trace->info(TraceCode::SET_ORIGIN_FROM_ORDER_PUBLIC_KEY, [
                    'payment_id'    => $entity->getId(),
                    'method'        => $entity->getMethod(),
                    'product_type'  => $entity->order->getProductType()
                ]);
                $dimensions = array('product_type' => $entity->order->getProductType(), 'Method' => $entity->getMethod());
                $this->trace->count(Metric::ENTITY_ORIGIN_CREATE_FROM_ORDER_PUBLIC_KEY, $dimensions);
                $originEntity = $this->getOriginEntityFromPublicKey($entity->order->getPublicKey());
            }
            else if($entity->getEntityName() === E::TRANSFER && $entity->isOrderTransfer() === true)
            {
                $this->trace->info(TraceCode::SET_ORIGIN_FROM_ORDER_PUBLIC_KEY, [
                    'transfer_id'  => $entity->getId(),
                ]);
                $originEntity = $this->getOriginEntityFromPublicKey($entity->source->getPublicKey());
            }
        }
        return empty($originEntity) === false  ? $this->build($entity, $originEntity) : null;
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

        return ($originType === Constants::APPLICATION or $originType === Constants::MARKETPLACE_APPLICATION);
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
     * Origin entity can be an instance of Merchant entity or an Oauth application
     *
     * @param Base\PublicEntity                 $entity
     * @param Merchant\Entity|OAuthApp\Entity   $originEntity
     * @param array                             $input
     *
     * @return Entity
     */
    protected function build(Base\PublicEntity $entity, $originEntity, array $input = []): Entity
    {
        $entityOrigin = new Entity;

        $entityOrigin->generateId();

        // since entity here can be external entity, we use build instead of associate for entity
        $input[Entity::ENTITY_ID]   = Entity::stripDefaultSign($entity->getId());
        $input[Entity::ENTITY_TYPE] = $entity->getEntityName();

        $entityOrigin->build($input);

        $entityOrigin->origin()->associate($originEntity);

        return $entityOrigin;
    }

    /**
     * Extracts the VA from the receiver and returns the VA's origin entity
     *
     * @param Base\PublicEntity $receiver
     *
     * @return mixed|null
     */
    protected function getOriginEntityFromReceiver(Base\PublicEntity $receiver)
    {
        // A receiver will always have an associated VA entity
        $virtualAccount = $receiver->virtualAccount;

        $vaEntityOrigin = optional($virtualAccount)->entityOrigin;

        $originEntity = optional($vaEntityOrigin)->origin;

        // incase of non VA QR code we need to fetch entity origin from QR code Id.
        if(empty($virtualAccount) === true && $receiver->getEntity() === 'qr_code')
        {
            return $this->getOriginEntityFromQrCode($receiver->getId());
        }
        return $originEntity;
    }

    protected function fetchEntityByType(string $entityType, string $entityId)
    {
        $entity = null;

        switch ($entityType)
        {
            case Constants::APPLICATION:
                $entity = (new OAuthApp\Repository)->findOrFail($entityId);
                break;

            case Constants::SUBSCRIPTION:
                $entity =  $this->app['module']
                                ->subscription
                                ->fetchSubscription(
                                    $this->merchant,
                                    $entityId
                                );

                break;

            case Constants::PAYMENT_LINK:
                // Creating dummy payment link entity since this is created in non-api db
                // fetching in api master db will not find the pl.
                $entity = (new PaymentLink\Entity())->setId($entityId);
                break;

            default:
                $entityClass = E::getEntityObject($entityType);
                $entityId    = $entityClass->verifyIdAndSilentlyStripSign($entityId);

                $entity = $this->repo->$entityType->findOrFail($entityId);
        }

        return $entity;
    }

    /**
     * Returns origin entity for the subscription if present
     *
     * @param string $subscriptionId
     *
     * @return mixed|null
     */
    protected function getOriginEntityFromSubscription(string $subscriptionId)
    {
        $entityOrigin = $this->repo->entity_origin->fetchByEntityTypeAndEntityId('subscription', $subscriptionId);

        return optional($entityOrigin)->origin;
    }

    /**
     * Returns origin entity for the Qr code  if present
     *
     * @param string $qrCodeId
     *
     * @return mixed|null
     */
    protected function getOriginEntityFromQrCode(string $qrCodeId)
    {
        $entityOrigin = $this->repo->entity_origin->fetchByEntityTypeAndEntityId('qr_code', $qrCodeId);

        return optional($entityOrigin)->origin;
    }

    /**
     * Returns origin entity for the invoice if present
     *
     * @param string $invoiceId
     *
     * @return mixed|null
     */
    protected function getOriginEntityFromInvoice(string $invoiceId): mixed
    {
        $entityOrigin = $this->repo->entity_origin->fetchByEntityTypeAndEntityId('invoice', $invoiceId);

        return optional($entityOrigin)->origin ?? $this->getOriginEntityFromAuth();
    }

    /**
     * Returns origin entity for the payment link if present
     *
     * @param string $paymentLinkId
     *
     * @return mixed|null
     */
    protected function getOriginEntityFromPaymentLinkId(string $paymentLinkId)
    {
        $entityOrigin = $this->repo->entity_origin->fetchByEntityTypeAndEntityId('payment_link', $paymentLinkId);

        $origin = optional($entityOrigin)->origin ?? $this->getOriginEntityFromAuth();

        return $origin;
    }

    /**
     * Extracts the origin entity from BasicAuth
     *
     * @return mixed|null
     */
    protected function getOriginEntityFromAuth()
    {
        list($originType, $originId) = app('basicauth')->getOriginDetailsFromAuth();

        $originEntity = null;

        switch ($originType)
        {
            case Constants::APPLICATION:
                $originEntity = (new OAuthApp\Repository)->findOrFail($originId);
                break;

            default:
                break;
        }

        return $originEntity;
    }

    /**
     * Extracts the origin entity from public key
     *
     * @param string $publicKey
     *
     * @return mixed|null
     */
    public function getOriginEntityFromPublicKey(string $publicKey)
    {
        try
        {
            // public key will be in format of rzp_mode_partner_clientID-acc_accountId in case of partner auth
            // and rzp_mode_oauth_clientID in case of oauth
            $publicKey = explode('-', $publicKey)[0];

            $keyId = substr($publicKey, -14);

            $applicationId = null;

            if (preg_match(self::PARTNER_KEY_REGEX, $publicKey) === 1)
            {
                $applicationId = (new OAuthClient\Repository)->getClientByIdAndEnv($keyId, ClientAuthCreds::$clientModes[$this->mode])
                                                             ->getApplicationId();
            }
            else if (preg_match(self::OAUTH_KEY_REGEX, $publicKey) === 1)
            {
                $token = (new OAuthToken\Repository)->findByTypePublicTokenAndMode($keyId, $this->mode);

                $applicationId = $token->getClient()->getApplicationId();
            }

            return  $applicationId !== null ? (new OAuthApp\Repository)->findOrFail($applicationId) : null;
        }
        catch (\Throwable $e)
        {
            // Should not fail even if the origin extraction from public key is failed.
            $this->trace->critical(TraceCode::SET_ORIGIN_FROM_PUBLIC_KEY_FAILED,
                [
                    'message'           => $e->getMessage(),
                    'publicKey'         => $publicKey,
                    'stack_trace'       => $e->getTraceAsString(),
                ]
            );

            return null;
        }

    }
}
