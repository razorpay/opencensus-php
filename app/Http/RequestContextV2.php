<?php

namespace RZP\Http;

/**
 * Class RequestContextV2
 *
 * @package RZP\Http
 */
final class RequestContextV2
{
    /**
     * @var \Razorpay\Edge\Passport\Passport|null
     */
    public $passport;

    /**
     * The $passport will always exists, whether request came directly or via edge(hence carrying passport jwt).
     * @var boolean
     */
    public $hasPassportJwt = false;

    /**
     * Value is true if attributes of passport from edge do not match with what is evaluated at api's end.
     * Note that $passport variable is (updated to)correct value still and should only be used in application.
     * This flag is used to send response header for functional environment only for coverage.
     * @var boolean
     */
    public $passportAttrsMismatch = false;

    /**
     * @deprecated This is not a well defined attribute and hence avoid using
     * it as it might be removed later.
     *
     * Authentication flow type e.g. key, oauth and partner.
     * @var string|null
     */
    public $authFlowType;

    /**
     * Request trace id fetched from request header attached by Kong proxy.
     * @var string|null
     */
    public $edgeTraceId;

    /**
     * @deprecated This is not a well defined attribute and hence avoid using
     * it as it might be removed later.
     *
     * Authentication type e.g. public, private, internal etc.
     * @var string|null
     */
    public $authType;

    /**
     * @deprecated This is not a well defined attribute and hence avoid using
     * it as it might be removed later.
     *
     * Whether it was a proxy e.g. dashboard proxy for private routes.
     * @var boolean|null
     */
    public $proxy;

    /**
     * @deprecated This is not a well defined attribute and hence avoid using
     * it as it might be removed later.
     *
     * This is a temporary measure, only to be used for signing order's payment
     * response. In future key would not be known to application.
     * @var \RZP\Models\Key\Entity|null
     */
    public $key;

    /**
     * Todo: Figure out lazy loading for this and $user attribute.
     * Todo: Refer existing cases ensure no increase in db/cache calls.
     *
     * @var \RZP\Models\Merchant\Entity|null
     */
    public $merchant;

    /**
     * @var \RZP\Models\User\Entity|null
     */
    public $user;
}
