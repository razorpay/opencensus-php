<?php

namespace RZP\Models\Offer;

use App;
use Carbon\Carbon;
use Monolog\Logger;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Offer\SubscriptionOffer\Entity as SubscriptionOfferEntity;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use Razorpay\Trace\Logger as Trace;

class OffersEngine extends Base\Core
{
    private $auth;

    protected $payment;

    protected $order;

    protected $isDummyPayment;

    public function __construct()
    {
        parent::__construct();

        $this->auth = App::getFacadeRoot()['basicauth'];
    }

    /**
     * @throws BadRequestException
     */
    public function createOffer(Entity &$offer, array $subscriptionInput, array $input)
    {
        try {

            $tenureDiscountMap = $this->getTenureDiscountMapForEMI($offer);

            $oeRequest = $this->buildRequestForOffersEngine($offer, $subscriptionInput, $tenureDiscountMap, $input);

            if ($this->auth->isAdminAuth() === false)
            {
                $oeResponse = $this->app['offers_engine']->createOffer($oeRequest);
            }
            else {
                $oeResponse = $this->app['offers_engine']->adminCreateOffer($oeRequest);
            }

            if (empty($oeResponse))
            {
                $this->trace->count(Metric::OFFERS_ENGINE_CREATE_OFFER_RESPONSE_NIL);
                // raise slack alert
                $this->trace->debug(TraceCode::OFFERS_ENGINE_CREATE_OFFER_RESPONSE_NIL, [
                    'api_response' => $offer,
                    'offers_engine_response' => $oeResponse,
                ]);
                throw new Exception\ServerErrorException(
                    'Unable to process this request.', ErrorCode::SERVER_ERROR);
            }

            $convertedResponse = [];

            if ($oeResponse != null) {

                // emi_subvention can be null too in case of false, set the value so comparison will not fail on this field
                if ($offer[Entity::EMI_SUBVENTION] !== true)
                {
                    $offer[Entity::EMI_SUBVENTION] = false;
                }

                $convertedResponse = $this->convertOffersEngineResponseToEntityOffer($oeResponse);

                $offer = $convertedResponse[Constants::OFFER];
            }

            $this->traceOffersDiff($offer, $convertedResponse, $tenureDiscountMap, $subscriptionInput);

        }
        catch (\Exception $exception)
        {
            $this->trace->count(Metric::OFFERS_ENGINE_CREATE_OFFER_FAIL);
            $this->trace->traceException($exception, Logger::ERROR,
                TraceCode::OFFERS_ENGINE_CREATE_OFFER_FAIL, [
                'api_response' => $offer
            ]);
            throw new Exception\ServerErrorException(
                'Unable to process this request.', ErrorCode::SERVER_ERROR);
        }
    }

    private function traceOffersDiff(Entity $offerApi, array $convertedOeResponse, $tenureDiscountMapAPI, $subscriptionInputAPI)
    {
        // get differences in API offer and OE converted offer
        $differences = $this->compareOffers($offerApi, $convertedOeResponse[Constants::OFFER]);

        $mismatchPresent = false;
        if (!empty($tenureDiscountMapAPI))
        {
            // sort the array to ensure mismatch does not happen due to ordering of keys
            ksort($tenureDiscountMapAPI);
            $tenureDiscountMapOE = $convertedOeResponse[Constants::TENURE_DISCOUNT_MAP];
            ksort($tenureDiscountMapOE);

            if ($tenureDiscountMapAPI !== $tenureDiscountMapOE)
            {
                $mismatchPresent = true;

                // raise slack alert
                $this->trace->info(TraceCode::CREATE_OFFER_RESPONSE_MISMATCH, [
                    'tenureDiscountMapAPI' => $tenureDiscountMapAPI,
                    'tenureDiscountMapOE' => $tenureDiscountMapOE,
                ]);
            }
        }

        if (!empty($subscriptionInputAPI))
        {
            ksort($subscriptionInputAPI);
            $subscriptionInputOE = $convertedOeResponse[Constants::SUBSCRIPTION_FIELDS];
            ksort($subscriptionInputOE);

            if ($subscriptionInputAPI !== $subscriptionInputOE)
            {
                $mismatchPresent = true;
                $this->trace->info(TraceCode::CREATE_OFFER_RESPONSE_MISMATCH, [
                    'subscriptionInputAPI' => $subscriptionInputAPI,
                    'subscriptionInputOE' => $subscriptionInputOE,
                ]);
            }
        }

        if (!empty($differences))
        {
            $this->trace->info(TraceCode::CREATE_OFFER_RESPONSE_MISMATCH, [
                'differences' => $differences,
            ]);
        }

        if ($mismatchPresent === true)
        {
            $this->trace->count(Metric::CREATE_OFFER_RESPONSE_MISMATCH);
        }
    }

    public function update(Entity $offer, array $input)
    {
        try {
            if (isset($input[Entity::ACTIVE]) === false)
            {
                return $offer;
            }

            $state = '';
            if ($input[Entity::ACTIVE] === 1 || $input[Entity::ACTIVE] === true)
            {
                $state = Constants::UPDATE_STATE_CREATED;
            }
            else
            {
                $state = Constants::STATE_DISABLED;
            }

            $offersEngineInput = [
                Constants::OFFER_ID => $offer->getId(),
                Constants::OFFER => [
                    Constants::METADATA => [
                        Constants::STATE => $state,
                        Constants::ADVERTISER_ID => 'rzp.merchant.'.$offer->getMerchantId()
                    ]
                ],
                'field_masks' => ["metadata.state"]
            ];

            // try to update offer in OE
            if ($this->auth->isAdminAuth() === false)
            {
                $offersEngineResponse = $this->app['offers_engine']->updateOffer($offer->getPublicId(), $offersEngineInput);
            }
            else
            {
                $offersEngineResponse = $this->app['offers_engine']->adminUpdateOffer($offer->getPublicId(), $offersEngineInput);
            }

            if (empty($offersEngineResponse))
            {
                $this->trace->count(Metric::OFFERS_ENGINE_UPDATE_OFFER_RESPONSE_NIL);
                // raise slack alert
                $this->trace->debug(TraceCode::OFFERS_ENGINE_UPDATE_OFFER_RESPONSE_NIL, [
                    'api_response' => $offer,
                    'offers_engine_response' => $offersEngineResponse,
                ]);
            }
        }
        catch (\Exception $exception)
        {
            $this->trace->count(Metric::OFFERS_ENGINE_UPDATE_OFFER_FAIL);
            $this->trace->traceException($exception, Logger::ERROR,
                TraceCode::OFFERS_ENGINE_UPDATE_OFFER_FAIL, [
                'api_response' => $offer,
                'exception' => $exception,
            ]);
            throw new Exception\ServerErrorException(
                'Unable to process this request.', ErrorCode::SERVER_ERROR);

        }
    }


// *Functions converting API Offer to Offers Engine Offer*
    private function buildRequestForOffersEngine(Entity $offer, array $subscriptionInput, $tenureDiscountMap, $input)
    {
        $offersEngineRequest =
            [
                Constants::METADATA => $this->getOffersEngineMetadata($offer),
                Constants::SPEC     => $this->getOffersEngineSpec($offer, $subscriptionInput, $tenureDiscountMap, $input),
            ];

        return [
            Constants::OFFER => $offersEngineRequest,
            Constants::PUBLISH => $this->getOfferChannelProperties($offer),
        ];
    }

    private function getOffersEngineMetadata(Entity $offer): array
    {
        // initialise metadata
        $metadata = [];

        $metadata[Constants::NAME] = $offer->getName();

        $metadata[Constants::DISPLAY_NAME] = $offer->getName();

        $metadata[Constants::DESCRIPTION] = $offer->getDisplayText();

        $metadata[Constants::TERMS] = [
            Constants::TERMS_AND_CONDITIONS => strval($offer->getTerms()),
        ];

        $metadata[Constants::ADVERTISER_ID] = 'rzp.merchant.' . $offer->getMerchantId();

        // **offer_id**
        $metadata[Constants::OFFER_ID] = $offer->getId();

        // getUser for merchant dashboard and getAdmin for admin dashboard
        if ($this->app['basicauth']->getUser() !== null)
        {
            $metadata[Constants::CREATED_BY_ID] = $this->app['basicauth']->getUser()->getEmail();
        }
        else
        {
            $metadata[Constants::CREATED_BY_ID] = $this->app['basicauth']->getAdmin()->getEmail();
        }

        // offer will be in 'CREATED' state, then auto publish changes it to published for rzp offers
        $metadata[Constants::STATE] = "STATE_CREATED"; // enum = 2

        // offer_on (beneficiary_type) is 'SELF'
        $metadata[Constants::OFFER_ON] = "BENEFICIARY_TYPE_SELF"; // enum = 1

        $metadata[Constants::CURRENCY] = 'INR';

        // initialise schedules (NOTE - no `schedule` field for API offers)
        $metadata[Constants::SCHEDULES] = [
            Constants::STARTS_AT => $offer->getStartsAt(),
            Constants::ENDS_AT => $offer->getEndsAt(),
        ];

        // these fields are not applicable for rzp offers - terms.url,
        // image_url, redemption, brand_id, schedules.schedule, labels
        return $metadata;
    }

    private function getOffersEngineSpec(Entity $offer, array $subscriptionInput, array $tenureDiscountMap, array $input): array
    {
        $spec = [];

        // allowed_channel for rzp offers is 'RZP_CHECKOUT'
        $spec[Constants::ALLOWED_CHANNELS] = [Constants::CHANNEL_RZP_CHECKOUT]; // enum = 1

        /* funding is done by SELF for rzp_offers
           bearer is advertiser / publisher (merchant) for rzp offers
           funding split type is percentage
           funding split value is 100 %
           i.e. 100% is borne by publisher for rzp offers */
        $spec[Constants::FUNDING] = [
            Constants::TYPE => Constants::BENEFICIARY_TYPE_SELF,
            Constants::FUNDING_SPLIT =>  [
                [
                    Constants::TYPE => Constants::VALUE_OPTION_PERCENTAGE, // enum = 2
                    Constants::FUNDING_BEARER => Constants::USER_TYPE_PUBLISHER, // enum = 2
                    Constants::VALUE => 100,
                ]
            ],
        ];

        $spec[Constants::BENEFITS_TYPES] = [$this->getOfferSpecBenefitType($offer)];

        $spec[Constants::USAGE_LIMITS] = $this->getUsageLimits($offer);

        $spec[Constants::RULE_GROUPS] = $this->getRuleGroups($offer,
            $tenureDiscountMap,
            $subscriptionInput,
            $spec[Constants::BENEFITS_TYPES][0],
            $input);

        return $spec;
    }

    private function getTenureDiscountMapForEMI(Entity $offer)
    {
        // get merchant_paybacks if offer is emi_subvention
        if ($offer->isNoCostEmi())
        {
            // fetch payback map for only no cost emi
            return $this->getMerchantPaybackMap($offer);

        }
        else if ($offer->isLowCostEmi())
        {
            // set the percent rate for corresponding tenure for lc emi
            return [
                $offer[Entity::EMI_DURATIONS][0] => $offer[Entity::PERCENT_RATE],
            ];
        }

        return [];
    }

    private function getMerchantPaybackMap(Entity $offer)
    {

        $tenureDiscountMap = [];

        $emiRepo = new Emi\Repository();

        $emiPlans = $emiRepo->fetchByParams($offer->getEmiDurations(), $offer->getIssuer(), $offer->getPaymentNetwork(), $offer->getPaymentMethodType());

        foreach ($emiPlans as $emiPlan)
        {
            $tenureDiscountMap[$emiPlan['duration']] = $emiPlan['merchant_payback'];
        }

        return $tenureDiscountMap;
    }

    private function getOfferSpecBenefitType(Entity $offer): string
    {
        if ($offer->isNoCostEmi() === true)
        {
            return Constants::BENEFIT_TYPE_NO_COST_EMI;
        }

        if ($offer->isLowCostEmi() === true)
        {
            return Constants::BENEFIT_TYPE_LOW_COST_EMI;
        }

        return Constants::API_OFFER_BENEFIT_MAP[$offer->getOfferType()];
    }

    private function getUsageLimits(Entity $offer): array
    {
        $usageLimits = [];

        if ($offer->getMaxOfferUsage() !== null)
        {
            $usageLimits[] = [
                Constants::MAXIMUM_VALUE => $offer->getMaxOfferUsage(),
                Constants::ON => Constants::LIMIT_ON_OFFER,
                Constants::LIMIT_TYPE => Constants::LIMIT_TYPE_COUNT,
            ];
        }

        // no limit can be applied if either of these is empty
        if ($offer->getMaxPaymentCount() !== null && $offer->getPaymentMethod() !== null)
        {
            $usageLimits[] = [
                Constants::MAXIMUM_VALUE => $offer->getMaxPaymentCount(),
                Constants::ON => Constants::LIMIT_ON_CARD_NUMBER,
                Constants::LIMIT_TYPE => Constants::LIMIT_TYPE_COUNT,

            ];
        }

        return $usageLimits;
    }

    private function getRuleGroups(Entity $offer,
                                   array $tenureDiscountMap,
                                   array $subscriptionInput,
                                   string $benefitType,
                                   array $input)
    {

        $discoverConditionString = $this->getDiscoverWhenCondition($offer, $subscriptionInput);

        $discountType = Constants::BENEFIT_DISCOUNT_MAP[$benefitType];

        $discoverRules = $this -> getOfferDiscoverRules(
            $offer, $tenureDiscountMap,
            $discountType,
            $discoverConditionString);

        $availRules = $this -> getOfferAvailRules(
            $offer, $tenureDiscountMap,
            $discountType,
            $discoverConditionString);

        $redeemRules = $this->getRedeemRules( $offer,
            $discountType,
            $input);

        $rules = [
          Constants::CHANNEL_RZP_CHECKOUT . ".".Constants::STAGE_DISCOVER  => $discoverRules,
          Constants::CHANNEL_RZP_CHECKOUT . ".".Constants::STAGE_AVAIL => $availRules,
        ];

        if (!empty($redeemRules))
        {
            $rules[Constants::CHANNEL_RZP_CHECKOUT . ".".Constants::STAGE_REDEEM] = $redeemRules;
        }

        return $rules;
    }

    private function getDiscoverWhenCondition(Entity $offer, array $subscriptionInput): string
    {
        // discover when condition is same for all tenures in case of nc / lc emi too for rzp_offers
        $discoverConditionWhenArray = array();

        if ($offer->getMinAmount() !== null)
        {
            array_push($discoverConditionWhenArray, 'Order.TotalAmount >= ' . $offer->getMinAmount());
        }

        if ($offer->getMaxOrderAmount() !== null)
        {
            array_push($discoverConditionWhenArray, 'Order.TotalAmount <= ' . $offer->getMaxOrderAmount());
        }

        if (empty($subscriptionInput) !== true)
        {
            if (isset($subscriptionInput[SubscriptionOfferEntity::REDEMPTION_TYPE]))
            {
                $cycle = 0;
                switch ($subscriptionInput[SubscriptionOfferEntity::REDEMPTION_TYPE]){
                    case 'forever':
                        $cycle = PHP_INT_MAX;
                        break;
                    case 'single':
                        $cycle = 1;
                        break;
                    case 'cycle':
                        $cycle = $subscriptionInput[SubscriptionOfferEntity::NO_OF_CYCLES];
                        break;
                }
                array_push($discoverConditionWhenArray, 'Subscription.NoOfCycles <= ' . $cycle);
                array_push($discoverConditionWhenArray, 'Subscription.NoOfCycles > 0');
            }

        }
        // convert conditions array to a string
        $whenCondition = implode(' && ', $discoverConditionWhenArray);

        return $whenCondition !== '' ? $whenCondition : 'true';
    }

    private function getOfferDiscoverRules(Entity $offer, array $tenureDiscountMap, string $benefitType, string $discoverConditionWhenString)
    {
        $benefits = [];

        if ($offer->isLowCostEmi() || $offer->isNoCostEmi() )
        {
            // fetch issuer based on type of offer - issuer is stored in 'issuer' or 'payment network'
            $issuer = $offer->getIssuer();
            if ($issuer === '')
            {
                $issuer = $offer->getPaymentNetwork();
            }

            foreach ($offer->getEmiDurations() as $duration)
            {
                // append a benefit within no cost emi for each offer tenure in discover
                $benefits[$benefitType][] = [
                    Constants::DISCOUNT => [
                        Constants::PERCENTAGE_DISCOUNT => $tenureDiscountMap[$duration],
                        Constants::APPLICABLE_ON => 'Order.total_amount'
                    ],
                    Constants::TENURE => $duration,
                    Constants::ISSUER => $issuer,
                ];
            }
        }
        else
        {
            $benefits = $this->getThenForNonEmi($offer,$benefitType);
        }

        $discoverConditions[Constants::RULES][] = [
            Constants::WHEN => $discoverConditionWhenString,
            Constants::THEN => [$benefits],
        ];

        return $discoverConditions;
    }

    private function getOfferAvailRules(Entity $offer, array $tenureDiscountMap, string $benefitType, string $discoverConditionWhenString)
    {

        // avail when condition is same for all tenures in case of no_cost_emi too for rzp_offers
        $availConditionWhenArray = array();

        // append discover condition first
        array_push($availConditionWhenArray, $discoverConditionWhenString);

        if ($offer->getPaymentMethod() !== null)
        {
            // note - if specified, only one payment method allowed per offer in API
            array_push($availConditionWhenArray, 'PaymentInstrument.Method == "' . $offer->getPaymentMethod() . '"');
        }

        // set payment_method_type if not null (NOTE - if null it means both are allowed in case of cards)
        if ($offer->getPaymentMethodType() !== null)
        {
            array_push($availConditionWhenArray, 'PaymentInstrument.CardType == "' . $offer->getPaymentMethodType() . '"');
        }

        // set issuer if not null
        // note - only one issuer can be set in an offer currently in API
        if ($offer->getIssuer() !== null)
        {
            $issuerKey = 'PaymentInstrument.Issuer';

            switch ($offer->getPaymentMethod()) {
                case Payment\METHOD::CARD:
                case Payment\METHOD::EMI:
                    // set coBranding partner for issuer if iin is not set and issuer is set
                    if ($offer->getIins() === null)
                    {
                        array_push($availConditionWhenArray, 'PaymentInstrument.CardCobrandingPartner == "NA"');
                    }
                    break;
                case Payment\METHOD::WALLET:
                    $issuerKey = 'PaymentInstrument.Wallet';
                    break;
                case Payment\METHOD::CARDLESS_EMI:
                case Payment\METHOD::PAYLATER:
                    $issuerKey = 'PaymentInstrument.Provider';
                    break;

            }
            array_push($availConditionWhenArray, $issuerKey . ' == "' . $offer->getIssuer() . '"');
        }

        if ($offer->getPaymentNetwork() !== null)
        {
            array_push($availConditionWhenArray, 'PaymentInstrument.CardNetwork == "' . $offer->getPaymentNetwork() . '"');
        }

        if ($offer->getIins() !== null)
        {
            $iinStr = implode(', ', $offer->getIins());

            array_push($availConditionWhenArray, 'PaymentInstrument.Iin in [' . $iinStr . ']');
        }

        if($offer->isInternational() === true)
        {
            array_push($availConditionWhenArray, 'PaymentInstrument.IsCardInternational == true' );
        }

        // convert conditions array to string with && logic
        $availConditionWhen = implode(' && ', $availConditionWhenArray);

        if ($offer->isNoCostEmi() || $offer->isLowCostEmi())
        {
            $availConditions = [];
            foreach ($offer->getEmiDurations() as $duration) {
                // append tenure condition too for nc emi, separately for each tenure
                $availConditionTenure = $availConditionWhen ;
                $availConditionTenure = $availConditionTenure . ' && PaymentInstrument.EmiTenure == ' . strval($duration);

                // fetch issuer based on type of offer - issuer is stored in 'issuer' or 'payment network'
                $issuer = $offer->getIssuer();
                if ($issuer === '')
                {
                    $issuer = $offer->getPaymentNetwork();
                }

                $availConditions[Constants::RULES][] = [

                    Constants::WHEN => $availConditionTenure,

                    Constants::THEN => [
                        [
                            $benefitType => [
                                [
                                    Constants::DISCOUNT => [
                                        Constants::PERCENTAGE_DISCOUNT => $tenureDiscountMap[$duration],
                                        Constants::APPLICABLE_ON => 'Order.total_amount'
                                    ],
                                    Constants::TENURE => $duration,
                                    Constants::ISSUER => $issuer,
                                ]
                            ]
                        ]
                    ]
                ];
            }
            return $availConditions;
        }

        $benefits = $this->getThenForNonEmi($offer, $benefitType);
        $availConditions[Constants::RULES][] = [
            Constants::WHEN => $availConditionWhen,
            Constants::THEN => [$benefits],
        ];
        return $availConditions;

    }

    private function getRedeemRules(Entity $offer,
                                    string $benefitType,
                                    array $input,
    )
    {
        // Redeem is only applicable for UPI offers
        if ($offer->getPaymentMethod() !== Payment\Method::UPI ||
            !isset($input[Payment\Method::UPI]))
        {
            return null;
        }

        $redeemConditionWhenArray = array();

        // Process UPI apps
        $this->processUpiConditionCreate(
            $input,
            Constants::APPS,
            Constants::ALL,
            Constants::UPI_APP,
            $redeemConditionWhenArray
        );

        $this->processUpiConditionCreate(
            $input,
            \RZP\Models\Upi\Turbo\Constants::PAYER_ACCOUNT_TYPE,
            Constants::ALL,
            Constants::UPI_PAYER_ACCOUNT,
            $redeemConditionWhenArray
        );

        if (empty($redeemConditionWhenArray))
        {
            return;
        }
        // convert conditions array to string with && logic
        $redeemConditionWhen = implode(' && ', $redeemConditionWhenArray);

        $benefits = $this->getThenForNonEmi($offer, $benefitType);
        $redeemConditions[Constants::RULES][] = [
            Constants::WHEN => $redeemConditionWhen,
            Constants::THEN => [$benefits],
        ];
        return $redeemConditions;

    }

    // processUpiConditionCreate creates the when condition for UPI apps and payer account type
    function processUpiConditionCreate(array $input, string $key, string $constant, string $method, array &$redeemConditionWhenArray): void
    {
        if (!empty($input[Payment\Method::UPI][$key]))
        {
            $values = $input[Payment\Method::UPI][$key];

            if (!in_array($constant, $values, true))
            {
                $valuesString = implode('","', $values);
                $functionCallString = Constants::PAYMENT_INSTRUMENT. '.' . $method . '("' . $valuesString . '")';
                $redeemConditionWhenArray[] = $functionCallString;
            }
        }
    }

    private function getThenForNonEmi(Entity $offer,  string $benefitType)
    {
        if ($offer->getFlatCashback() !== null)
        {
            $benefits[$benefitType][] = [
                Constants::FLAT_DISCOUNT => $offer->getFlatCashback(),
                Constants::APPLICABLE_ON => 'Order.total_amount'
            ];
        }
        else
        {
            $benefits[$benefitType][] = [
                Constants::PERCENTAGE_DISCOUNT => $offer->getPercentRate(),
                Constants::MAX_DISCOUNT => $offer->getMaxCashback(),
                Constants::APPLICABLE_ON => 'Order.total_amount',   // might not be needed for 'already_discounted'
            ];
        }
        return $benefits;
    }

    private function getOfferChannelProperties(Entity $offer): array
    {
        $channelProperties = [];

        // block value = 0 means do not block payment
        // so we should continue txn on failure if block = 0
        $channelProperties[Constants::BLOCKING] = ($offer[Entity::BLOCK] === false);

        if ($offer->isDefaultOffer() === true)
        {
            $channelProperties[Constants::OFFER_TYPE] = Constants::OFFER_TYPE_STAGE_REGULAR;
        }
        else
        {
            $channelProperties[Constants::OFFER_TYPE] = Constants::OFFER_TYPE_STAGE_HIDDEN;
        }

        $channelProperties[Constants::AUTO_APPLY] = false;

        $channelProperties[Constants::CHANNEL_NAME] = Constants::CHANNEL_RZP_CHECKOUT;

        return $channelProperties;
    }

// *Functions converting Offers Engine Offer to API Offer*

    /**
     * @param array $offersEngineResponse
     * @return array
     * @throws Exception\BadRequestException
     */
    public function convertOffersEngineResponseToEntityOffer(array $offersEngineResponse)
    {
        $offer = new Entity();

        $offer->setExternal(true);

        if ($offersEngineResponse[Constants::OFFER] === null || $offersEngineResponse[Constants::PUBLISH] === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFERS_ENGINE_RESPONSE_EMPTY, null,
                [
                    Constants::OFFER_ID => $offer->getPublicId(),
                ]);
        }

        // Extract offer metadata
        $this->mapOfferMetadata($offersEngineResponse[Constants::OFFER][Constants::METADATA], $offer);

        // Map offer channel properties
        $this->mapChannelProperties($offersEngineResponse[Constants::PUBLISH], $offer);

        // Map offer spec and set additional attributes
        $response = $this->mapOfferSpecAndSetAttributes($offersEngineResponse[Constants::OFFER][Constants::SPEC], $offer);

        // not present in oe
        $offer[Entity::ERROR_MESSAGE] = Entity::DEFAULT_ERROR_MESSAGE;

        if (!empty($response[Constants::SUBSCRIPTION_FIELDS]))
        {
            $offer[Entity::PRODUCT_TYPE] = Order\ProductType::SUBSCRIPTION;

            $response[Constants::SUBSCRIPTION_FIELDS][SubscriptionOfferEntity::APPLICABLE_ON] = Constants::SUBSCRIPTION_APPLICABLE_ON_BOTH;
        }

        return [
            Constants::OFFER => $offer,
            Constants::SUBSCRIPTION_FIELDS => $response[Constants::SUBSCRIPTION_FIELDS],
            Constants::TENURE_DISCOUNT_MAP => $response[Constants::TENURE_DISCOUNT_MAP],
        ];
    }

    private function mapOfferMetadata(array $offerMetadata, Entity $offer)
    {

        $offer->setAttribute(Entity::ID, Entity::verifyIdAndStripSign($offerMetadata[Constants::OFFER_ID]));
        $offer->setAttribute(Entity::NAME, $offerMetadata[Constants::NAME]);
        $offer->setAttribute(Entity::DISPLAY_TEXT, $offerMetadata[Constants::DESCRIPTION]);
        $offer->setAttribute(Entity::TERMS, $offerMetadata[Constants::TERMS][Constants::TERMS_AND_CONDITIONS]);
        $offer->setAttribute(Entity::MERCHANT_ID, str_replace('rzp.merchant.', '', $offerMetadata[Constants::ADVERTISER_ID]));
        $offer->setAttribute(Entity::STARTS_AT, $offerMetadata[Constants::SCHEDULES][Constants::STARTS_AT]);
        $offer->setAttribute(Entity::ENDS_AT, $offerMetadata[Constants::SCHEDULES][Constants::ENDS_AT]);

        // set ACTIVE flag to false only if state is disabled, true for all
        // other states as filtering for EXPIRED is on 'ends_at' not state
        if ($offerMetadata[Constants::STATE] === Constants::STATE_DISABLED)
        {
            $offer->setAttribute(Entity::ACTIVE, false);
        } else
        {
            $offer->setAttribute(Entity::ACTIVE, true);
        }
    }

    private function mapChannelProperties(array $channelProperties, Entity $offer)
    {
        $offer->setAttribute(Entity::BLOCK, $channelProperties[Constants::BLOCKING] === true ? 0 : 1);
        $offer->setAttribute(Entity::DEFAULT_OFFER, $channelProperties[Constants::OFFER_TYPE] === Constants::OFFER_TYPE_STAGE_REGULAR ? 1 : 0);
    }

    private function mapOfferSpecAndSetAttributes(array $offersEngineSpec, Entity $offer): array
    {
        $availRuleGroup = $this->fetchRuleGroupForStage($offersEngineSpec, Constants::STAGE_AVAIL );

        if ($availRuleGroup === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_AVAIL_RULE_GROUP_NOT_FOUND, null,
                [
                    Constants::OFFER_ID => $offer->getId(),
                ]);
        }

        // Set emi_subvention and offer_type
        $this->setEmiSubventionAndOfferType($offer, $offersEngineSpec);

        // Set max_offer_usage and max_payment_count from usage_limits
        $this->setUsageLimits($offer, $offersEngineSpec);

        $subscriptionFields = [];
        $tenureDiscountMap = [];
        $offerType = $offersEngineSpec[Constants::BENEFITS_TYPES][0];

        // Loop through each availRule in the response array.
        foreach ($availRuleGroup[Constants::RULES] as $availRule)
        {
            // Extract common fields for both 'no_cost_emi' and other offer types.
            $this->extractCommonFields($offer, $offerType, $availRule, $tenureDiscountMap, $subscriptionFields);
        }

        // For nc/lc emi, set the array of emi durations in the offer object
        $offer[Entity::EMI_DURATIONS] = [];
        if ($offerType === Constants::BENEFIT_TYPE_NO_COST_EMI
            || $offerType === Constants::BENEFIT_TYPE_LOW_COST_EMI )
        {
            $offer->setAttribute(Entity::EMI_DURATIONS, array_keys($tenureDiscountMap));
        }

        $this->setFromRedeemRules($offersEngineSpec,  $offer);

        return [
            Constants::TENURE_DISCOUNT_MAP => $tenureDiscountMap,
            Constants::SUBSCRIPTION_FIELDS => $subscriptionFields,
        ];
    }


    private function setFromRedeemRules(array $offersEngineSpec, Entity $offer)
    {
        $redeemRuleGroup = $this->fetchRuleGroupForStage($offersEngineSpec, Constants::STAGE_REDEEM );

        if ($redeemRuleGroup === null)
        {
            return;
        }

        // Loop through each availRule in the response array.
        foreach ($redeemRuleGroup[Constants::RULES] as $redeemRule)
        {
            $whenExpression = $redeemRule[Constants::WHEN];
            $appsRegex = '/' . Constants::PAYMENT_INSTRUMENT . '\.'. Constants::UPI_APP .'\("([^"]+)"(?:,"([^"]+)")*\)/';
            $PayerAccountTypeRegex = '/' . Constants::PAYMENT_INSTRUMENT .'\.'. Constants::UPI_PAYER_ACCOUNT .'\("([^"]+)"(?:,"([^"]+)")*\)/';
            $offer->setUpiApps($this->extractValuesFromFunctionCalls($whenExpression, $appsRegex));
            $offer->setPayerAccountType($this->extractValuesFromFunctionCalls($whenExpression, $PayerAccountTypeRegex));
        }
    }


    private function extractValuesFromFunctionCalls(string $functionCallString, string $regex): array
    {
        $extractedValues = [];

        // Perform the regex match
        if (preg_match($regex, $functionCallString, $matches)) {
            // The entire match is in $matches[0]
            // Extract values from matches[1] and split them
            $valuesString = $matches[0];

            // Extracting individual values from the matched string
            preg_match_all('/"([^"]+)"/', $valuesString, $innerMatches);

            // Combine all matches into a single array
            $extractedValues = $innerMatches[1];
        }

        return $extractedValues;
    }

    private function fetchRuleGroupForStage(array $offersEngineSpec, string $stage)
    {
        return $offersEngineSpec[Constants::RULE_GROUPS][Constants::CHANNEL_RZP_CHECKOUT . '.' . $stage];
    }

    private function setEmiSubventionAndOfferType(Entity $offer, array $offersEngineSpec)
    {
        $benefitType = $offersEngineSpec[Constants::BENEFITS_TYPES][0];
        // set emi_subvention and offer_type
        if ($benefitType === Constants::BENEFIT_TYPE_NO_COST_EMI
            || $benefitType === Constants::BENEFIT_TYPE_LOW_COST_EMI)
        {
            // emi_subvention is true for nce
            $offer->setAttribute(Entity::EMI_SUBVENTION, true);
            // if no_cost_emi the type is always instant discount
            $offer->setAttribute(Entity::TYPE, Constants::INSTANT_OFFER);
        } else {
            // emi_subvention is false for other offers
            $offer->setAttribute(Entity::EMI_SUBVENTION, false);

            $offer->setAttribute(Entity::TYPE, Constants::BENEFIT_API_OFFER_MAP[$benefitType]);
        }
    }

    private function setUsageLimits(Entity $offer, array $offersEngineSpec)
    {
        // set max_offer_usage and max_payment_count from usage_limits
        foreach ($offersEngineSpec[Constants::USAGE_LIMITS] as $usageLimit)
        {
            if ($usageLimit[Constants::ON] === Constants::LIMIT_ON_OFFER)
            {
                $offer->setAttribute(Entity::MAX_OFFER_USAGE, $usageLimit[Constants::MAXIMUM_VALUE]);
            }
            else if ($usageLimit[Constants::ON] === Constants::LIMIT_ON_CARD_NUMBER)
            {
                $offer->setAttribute(Entity::MAX_PAYMENT_COUNT, $usageLimit[Constants::MAXIMUM_VALUE]);
            }
        }
    }

    private function extractCommonFields(Entity $offer, string $offerType, array $availCondition,  &$tenureDiscountMap, &$subscriptionFields)
    {
        $whenExpression = $availCondition[Constants::WHEN];

        $discountType = Constants::BENEFIT_DISCOUNT_MAP[$offerType];

        if ($offerType === Constants::BENEFIT_TYPE_NO_COST_EMI
            || $offerType === Constants::BENEFIT_TYPE_LOW_COST_EMI )
        {
            $emiDur = $availCondition[Constants::THEN][0][$discountType][0][Constants::TENURE];

            $percentDiscount = $availCondition[Constants::THEN][0][$discountType][0][Constants::DISCOUNT][Constants::PERCENTAGE_DISCOUNT];

            $tenureDiscountMap[$emiDur] = $percentDiscount;

            if ($offerType === Constants::BENEFIT_TYPE_LOW_COST_EMI)
            {
                $offer->setAttribute(Entity::PERCENT_RATE, $percentDiscount);
            }
        }
        else
        {
            // set fields based on flat or percent discount, set others to null
            if ($availCondition[Constants::THEN][0][$discountType][0][Constants::FLAT_DISCOUNT] !== null)
            {
                $offer->setAttribute(Entity::FLAT_CASHBACK, $availCondition[Constants::THEN][0][$discountType][0][Constants::FLAT_DISCOUNT]);
                $offer->setAttribute(Entity::PERCENT_RATE, null);
                $offer->setAttribute(Entity::MAX_CASHBACK, null);
            } else
            {
                $offer->setAttribute(Entity::FLAT_CASHBACK, null);
                $offer->setAttribute(Entity::PERCENT_RATE, $availCondition[Constants::THEN][0][$discountType][0][Constants::PERCENTAGE_DISCOUNT]);
                if ($availCondition[Constants::THEN][0][$discountType][0][Constants::MAX_DISCOUNT] !== null) {
                    $offer->setAttribute(Entity::MAX_CASHBACK, $availCondition[Constants::THEN][0][$discountType][0][Constants::MAX_DISCOUNT]);
                } else {
                    // set max cashback to 0 if not present
                    // there are no active offers with max_cashback nil and creation os such offers is not allowed for percent discount.
                    $offer->setAttribute(Entity::MAX_CASHBACK, 0);
                }
            }
        }

        $this->extractAndSetConditions($whenExpression, $offer, $subscriptionFields);
    }

    private function extractAndSetConditions($whenExpression, Entity $offer, &$subscriptionFields)
    {
        // Extract the other conditions from the 'when' expression and set them in the offer object.
        $conditions = explode(' && ', $whenExpression);

        foreach ($conditions as $condition)
        {
            $parts = explode(' ', $condition, 3);
            $field = $parts[0];
            $operator = $parts[1];
            $value = $parts[2];
            // handle strings from conditions
            // remove \"
            $value = str_replace('"', '', $value);
            $value = str_replace('\\', '', $value);
            // Check and set the corresponding field in the offer object.
            // NOTE - PaymentInstrument.EmiTenure is ignored as it is handled differently
            switch ($field) {
                case 'Order.TotalAmount':
                    if ($operator === '>=') {
                        $offer->setAttribute(Entity::MIN_AMOUNT, (int)$value);
                    } elseif ($operator === '<=') {
                        $offer->setAttribute(Entity::MAX_ORDER_AMOUNT, (int)$value);
                    }
                    break;
                case 'PaymentInstrument.Method':
                    $offer->setAttribute(Entity::PAYMENT_METHOD, $value);
                    break;
                case 'PaymentInstrument.CardType':
                    $offer->setAttribute(Entity::PAYMENT_METHOD_TYPE, $value);
                    break;
                // issuer, wallet, provider mean the same in API
                case 'PaymentInstrument.Issuer':
                case 'PaymentInstrument.Wallet':
                case 'PaymentInstrument.Provider':
                    $offer->setAttribute(Entity::ISSUER, $value);
                    break;
                case 'PaymentInstrument.CardNetwork':
                    $offer->setAttribute(Entity::PAYMENT_NETWORK, $value);
                    break;
                case 'PaymentInstrument.Iin':
                    // remove prefix '['
                    $iinArrayStr = substr($value, 1);
                    // remove suffix ']'
                    $iinArrayStr = basename($iinArrayStr, ']');
                    $iinArr = explode(', ', $iinArrayStr);
                    $iins = [];
                    foreach ($iinArr as $iin) {
                        // remove '\"'
                        $iins[] = $iin;
                    }
                    $offer->setAttribute(Entity::IINS, $iins);
                    break;
                case 'PaymentInstrument.IsCardInternational':
                    $offer->setAttribute(Entity::INTERNATIONAL, $value);
                    break;
                case 'Subscription.RedemptionType':
                    $subscriptionFields[SubscriptionOfferEntity::REDEMPTION_TYPE] =
                        Constants::SUBSCRIPTION_TYPE_ENUM_TO_VALUE_MAP[$value];
                    break;
                case 'Subscription.NoOfCycles':
                    if ($operator === '>')
                    {
                     break;
                    }
                    $int = (int)$value;
                    if ($int === 1){
                        $subscriptionFields[SubscriptionOfferEntity::REDEMPTION_TYPE] = 'single';
                    }elseif ($int === PHP_INT_MAX){
                        $subscriptionFields[SubscriptionOfferEntity::REDEMPTION_TYPE] = 'forever';
                    }else{
                        $subscriptionFields[SubscriptionOfferEntity::REDEMPTION_TYPE] = 'cycle';
                        $subscriptionFields[SubscriptionOfferEntity::NO_OF_CYCLES] = $value;
                    }
                    break;
            }
        }
    }

    private function compareOffers(Entity $apiOffer, Entity $oeOffer)
    {
        $differences = [];

        // Define an array of fields to compare
        // note - fields not compared are id, percent_rate(variable in UTs), description, terms,
        // active, checkout_display(not present in OE),
        // linked_offer_ids(feature na), payment_count(not needed in OE), processing_time(not present in OE),
        // display_text, error_message, current_offer_usage, product_type
        $fieldsToCompare = [
            Entity::ID, Entity::MERCHANT_ID, Entity::NAME, Entity::PAYMENT_METHOD, Entity::PAYMENT_METHOD_TYPE,
            Entity::IINS, Entity::BLOCK, Entity::TYPE, Entity::MIN_AMOUNT, Entity::MAX_CASHBACK,
            Entity::FLAT_CASHBACK, Entity::EMI_SUBVENTION, Entity::EMI_DURATIONS,
            Entity::STARTS_AT, Entity::ENDS_AT, Entity::PAYMENT_NETWORK, Entity::ISSUER,
            Entity::MAX_OFFER_USAGE, Entity::DEFAULT_OFFER, Entity::MAX_ORDER_AMOUNT, Entity::INTERNATIONAL,
        ];

        // if offer is not a no cost emi, compare percent_rate too
        if ($apiOffer[Entity::EMI_SUBVENTION] != 1 || $apiOffer[Entity::EMI_SUBVENTION] != true)
        {
            $fieldsToCompare[] = Entity::PERCENT_RATE;
        }

        foreach ($fieldsToCompare as $field) {
            // Check if both values are not empty
            if ((!empty($apiOffer[$field]) || !empty($oeOffer[$field]))
                && $apiOffer[$field] !== $oeOffer[$field])
            {
                $differences[$field] = [
                    'apiOffer' => $apiOffer[$field],
                    'oeOffer' => $oeOffer[$field],
                ];
            }
        }

        return $differences;
    }

    public function redeemOnOffersEngine(Payment\Entity $payment,Entity $offer): void
    {
        if ((new Core)->shouldRouteToOffersEngine($payment->getMerchantId(), Constants::OFFERS_ENGINE_VALIDATE_OFFER_EXP) === false){
            return;
        }
        $offer = $payment->getOffer();

        if ($offer === null)
        {
            return;
        }

        $input = $this->getDefaultTransactionInput($payment,$offer);


        try
        {
            $this->app['offers_engine']->redeem($payment->getMerchantId(),$input);
        }
        catch (\Exception $e)
        {
            // ignore until this is in shadow mode
            $this->traceTransactionFailure("redeem", $input, $e);

        }
    }
    public function failOnOffersEngine(Payment\Entity $payment): void
    {
        if ((new Core)->shouldRouteToOffersEngine($payment->getMerchantId(), Constants::OFFERS_ENGINE_VALIDATE_OFFER_EXP) === false){
            return;
        }

        $offer = $payment->getOffer();

        if ($offer === null)
        {
            return;
        }

        $input = $this->getDefaultTransactionInput($payment,$offer);

        try
        {
            $this->app['offers_engine']->failPayment($payment->getMerchantId(),$input);
        }
        catch (\Exception $e)
        {
            // ignore until this is in shadow mode,
            // ignore if transaction is already marked as failed as well
            $this->traceTransactionFailure("failed", $input, $e);

        }
    }

    /**
     * @throws \Exception
     */
    public function availOnOffersEngine(Payment\Entity $payment, Entity $offer, array $benefitApplied): void
    {

        $input = $this->getDefaultTransactionInput($payment, $offer);

        $input['benefit_applied'] = $benefitApplied;

        $input['customer_indentifier'] = $this->getCustomerContactIdentifier($payment,$offer);

        try
        {
            $this->app['offers_engine']->avail($payment->getMerchantId(), $input);
        }
        catch (\Exception $e) {
            $this->traceTransactionFailure("avail", $input, $e);
            throw $e;
        }
    }

    private function getCustomerContactIdentifier(Payment\Entity $payment, Entity $offer): array
    {
        $identifier = [
            Constants::MOBILE_NUMBER => $payment->getContact(),
            Constants::EMAIL => $payment->getEmail(),
        ];
        if (empty($offer->getMaxPaymentCount()) === false)
        {
            $par = (new Core())->getParValue($payment, false);

            if (empty($par) === false)
            {
                // todo: do not persist par here once offers engine ramp up is 100% done
                $identifier[Constants::CARD_NUMBER] = $par;
            }
        }
        return $identifier;
    }

    public function getDefaultTransactionInput(Payment\Entity $payment, Entity $offer): array
    {
        return [
            'offer_id'          => $offer->getPublicId(),
            'transaction_id'    => $payment->getPublicId(),
            'channel'           => Constants::CHANNEL_RZP_CHECKOUT,
            'check_usage'       => true,
            'transaction_amount'=> $payment->getAmount(),
            'transaction_currency'=> $payment->getCurrency(),
        ];
    }

    private function traceTransactionFailure(string $action, array $input, \Exception $e)
    {
        $this->trace->count(Metric::OFFERS_ENGINE_TRANSACTION_FAILURE, [
            'action' => $action
        ]);

        $this->trace->traceException(
            $e,
            Logger::ERROR,
            TraceCode::OFFERS_ENGINE_TRANSACTION_FAILURE,
            [
            'action' => $action,
            'input' => $input,
        ]);
    }

    /**
     * @throws ServerErrorException
     */
    public function validateOffer(string $merchantId, Entity $offer, Payment\Entity $payment, Order\Entity $order, bool $isDummyPayment, string $cardIin = "")
    {
        $this->payment = $payment;

        $this->order = $order;

        $this->isDummyPayment = $isDummyPayment;

        try
        {
            $fact = $this->buildValidateFact(!empty($offer->getMaxPaymentCount()), $cardIin);

            $response = $this->app['offers_engine']->validateOffer($merchantId, [
                'offer_id' => $offer->getPublicId(),
                'fact' => $fact,
            ]);

            if (isset($response['error']))
            {
                // if card number is necessary, rebuild the fact and call again
                if (in_array($response['error']['internal_error_code'],
                    [Constants::VALIDATE_CARD_NUMBER_REQUIRED_ERROR,
                    Constants::VALIDATE_MISSING_FACT_ERROR], true) === true )
                {
                    $fact = $this->buildValidateFact(!empty($offer->getMaxPaymentCount()), $cardIin);

                    $response = $this->app['offers_engine']->validateOffer($merchantId, [
                        'offer_id' => $offer->getPublicId(),
                        'fact' => $fact,
                    ]);
                }
            }
            return $response;
        }
        catch (\Exception $exception)
        {
            $this->trace->count(Metric::OFFERS_ENGINE_VALIDATE_OFFER_FAIL,
            [
                'offer_type' => $offer->getOfferType(),
                'emi_subvention' => $offer->getEmiSubvention(),
            ]);

            $this->trace->traceException(
                $exception,
                Logger::ERROR,
                TraceCode::OFFERS_ENGINE_VALIDATE_OFFER_FAIL, [
                    'exception' => $exception->getMessage(),
                ]);

            throw new Exception\ServerErrorException(
                'Unable to process this request.', ErrorCode::SERVER_ERROR);
        }
    }

    private function buildValidateFact(bool $validateWithCardPAR, string $cardIin): array
    {
        $fact = array();
        $instrumentFact = array();

        // ORDER
        $fact[Constants::ORDER_FACT] = [
            Constants::ORDER_TOTAL_AMOUNT => $this->order->getAmount(),
            Constants::ORDER_CURRENCY  => 'INR', // setting default INR as API offers does not have currency
        ];

        $method = $this->payment->getMethod();

        if ($this->payment->isMethodCardOrEmi() === true)
        {
            $iinEntity = $this->repo->iin->find($cardIin);
            $coBrandingPartner =  $iinEntity->getCobrandingPartner();
            if($coBrandingPartner === null)
            {
                $coBrandingPartner = "NA";
            }

            $instrumentFact = [
                Constants::CARD_TYPE => strtolower($this->payment->card->getType()),
                Constants::CARD_NETWORK => $this->payment->card->getNetworkCode(),
                Constants::IIN => $cardIin,
                Constants::ISSUER => $this->payment->card->getIssuer(),
                Constants::IS_CARD_INTERNATIONAL => $this->payment->card->isInternational(),
                Constants::CARD_COBRANDING_PARTNER => $coBrandingPartner,
            ];
        }

        switch ($method)
        {
            case Payment\Method::PAYLATER:
            case Payment\Method::UPI:
            case Payment\Method::CARD:
                break;
            case Payment\Method::EMI:
                $instrumentFact[Constants::EMI_TENURE] = $this->payment->emiPlan->getDuration();
                break;
            case Payment\Method::NETBANKING:
                $instrumentFact =  [Constants::ISSUER => $this->payment->getBank()];
                break;
            case Payment\Method::WALLET:
                $instrumentFact = [Constants::WALLET =>  $this->payment->getWallet()];
                break;
            case Payment\Method::CARDLESS_EMI:
                $instrumentFact[Constants::PROVIDER] = $this->payment->getIssuer();
                break;
        }

        // PAY_LATER, UPI methods do not have any checks other than the method
        $instrumentFact[Constants::METHOD] = $method;

        // NOTE - card international handling is not considered in oe - fallback to API

        $fact[Constants::PAYMENT_INSTRUMENT_FACT] = $instrumentFact;

        $fact[Constants::CUSTOMER_FACT] = [
            Constants::MOBILE_NUMBER => $this->payment->getContact(),
            Constants::EMAIL => $this->payment->getEmail(),
        ];

        $card_number = Constants::DUMMY_PAYMENT_CARD_NUMBER;

        if ($validateWithCardPAR === true)
        {
            if ($this->payment->isMethodCardOrEmi() === true)
            {
                    $card_number = $this->getCardParValue();
            }
        }

        $fact[Constants::CUSTOMER_FACT][Constants::CARD_NUMBER] = $card_number;

        if ($this->payment->getSubscriptionId() !== null)
        {
            $fact[Constants::SUBSCRIPTION_FACT] = [
                SubscriptionOfferEntity::NO_OF_CYCLES => 1,
            ];
        }

        return $fact;
    }

    // getCardParValue fetches par value of card if applicable
    protected function getCardParValue()
    {
        // Skip card usage check if payment method is not card or emi
        // or if the max payment count is not present
        if ($this->payment->isMethodCardOrEmi() === false)
        {
            return null;
        }
        // Offer max usage will only be applicable for cards network which have exposed PAR api
        if ((new Card\Core())->checkIfFetchingParApplicable($this->payment->card->getNetwork()) === false)
        {
            $this->trace->info(
                TraceCode::OFFER_CARD_USAGE_CHECK,
                [
                    'network' => $this->payment->card->getNetwork(),
                    'message' => 'Max offer usage per card is not applicable on this card'
                ]);
            return null;
        }

        $providerReferenceId = $this->payment->card->getProviderReferenceId();

        // If provider_reference_id is not null, then we just return the same
        if (empty($providerReferenceId) !== true) {
            return $providerReferenceId;
        }
        $core = new Core();

        return $core->getParValue($this->payment, $this->isDummyPayment);
    }
}
