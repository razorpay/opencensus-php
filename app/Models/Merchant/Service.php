<?php

namespace RZP\Models\Merchant;

use DB;
use Mail;
use Cache;
use Config;
use Request;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use Razorpay\OAuth\Token as OAuthToken;
use Razorpay\Spine\DataTypes\Dictionary;
use Razorpay\OAuth\Client as OAuthClient;
use Razorpay\OAuth\Application as OAuthApplication;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Trace\Tracer;
use RZP\Models\Terminal\Category;
use RZP\Models\User;
use RZP\Models\Offer;
use RZP\Models\Payout;
use RZP\Models\Coupon;
use RZP\Models\Contact;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Address;
use RZP\Models\Pricing;
use RZP\Models\Customer;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Promotion;
use RZP\Models\Admin\Org;
use RZP\Models\Settlement;
use RZP\Http\CheckoutView;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Group;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Services\DiagClient;
use RZP\Base\RuntimeManager;
use RZP\Base\JitValidator;
use RZP\Models\Pricing\Plan;
use RZP\Models\Payment\Refund;
use RZP\Services\HubspotClient;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\ConfigKey;
use RZP\Modules\Migrate\Migrate;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Methods;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Admin as MainAdmin;
use RZP\Models\Admin\Org\Hostname;
use RZP\Services\SalesForceClient;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Jobs\SubMerchantTaggingJob;
use RZP\Error\PublicErrorDescription;
use RZP\Jobs\CallBackFillReferredApp;
use RZP\Mail\Merchant\EsEnabledNotify;
use RZP\Exception\BadRequestException;
use RZP\Models\Partner\RateLimitBatch;
use RZP\Jobs\CallBackFillMerchantApps;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Comment\Core as CommentCore;
use RZP\Mail\InstrumentRequest\StatusNotify;
use RZP\Models\Settlement\SettlementTrait;
use RZP\Models\Batch\Header as BatchHeader;
use RZP\Models\Batch\Status as BatchStatus;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Constants\Entity as EntityConstants;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Merchant\AutoKyc\Escalations;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Payment\Config as PaymentConfig;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Models\Pricing\Entity as PricingEntity;
use RZP\Models\BulkWorkflowAction as BulkAction;
use RZP\Models\Pricing\Feature as PricingFeature;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Workflow\Service as WorkflowService;
use RZP\Models\Merchant\PurposeCode\PurposeCodeList;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\PayoutLink\Service as PayoutLinkService;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Services\Pagination\Entity as PaginationEntity;
use RZP\Models\Merchant\Detail\Status as MerchantStatus;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;
use RZP\Models\Merchant\Methods\DefaultMethodsForCategory;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Gateway\Terminal\Service as TerminalService;
use RZP\Models\Workflow\Action\Core as WorkFlowActionCore;
use RZP\Models\Workflow\Action\Entity as WorkFlowActionEntity;
use RZP\Constants\{Environment, Mode, Entity as CE, Product};
use RZP\Mail\Merchant\CreateSubMerchant as CreateSubMerchantMail;
use RZP\Models\Batch\Helpers\SubMerchant as SubMerchantBatchHelper;
use RZP\Models\Partner\SubMerchantBatchUtility as SubMerchantBatchUtil;
use RZP\Models\Merchant\Detail\BusinessType as MerchantDetBusinessType;
use RZP\Models\Merchant\Balance\BalanceConfig\Service as BalanceConfigService;
use RZP\Mail\Merchant\CreateSubMerchantPartner as CreateSubMerchantPartnerForPG;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForPG;
use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantPartner as CreateSubMerchantPartnerForX;
use RZP\Mail\Merchant\IncreaseTransactionLimitRequestApprove as TransactionLimitMerchantMail;
use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForX;

class Service extends Base\Service
{
    use Notify;
    use SettlementTrait;

    const COUPON_RESPONSE               = 'apply_coupon';
    const OAUTH_MAIL                    = 'oauth_mail';
    const TALLY_AUTH_OTP_MAIL           = 'tally_auth_otp_mail';
    const MERCHANT_MAIL                 = 'merchant_mail';
    const SUPPORT_DETAILS               = 'support_details';
    const ES_ON_DEMAND_ANNOUNCEMENT_TAG = 'es-on-demand.announcement-early-settlement';
    const OFFSET                        = 'offset';

    const DEFAULT_SUBMERCHANT_FETCH_LIMIT = 100;

    const SECOND    = 1;
    const MINUTE    = 60 * self::SECOND;
    const HOUR      = 60 * self::MINUTE;

    const BOOTSTRAP_ACCESS_MAPS_CACHE_REQUEST_RULES = [
        'source'        => 'array',
        'source.mids'   => 'array|min:1|max:10000',
        'source.mids.*' => 'string|unsigned_id',
    ];

    const IMPERSONATION_ACCESS_MAPS_REQUEST_RULES = [
        'source'       => 'array',
        'source.ids'   => 'array|min:1|max:10000',
        'source.ids.*' => 'string|unsigned_id',
    ];

    const MERCHANT_DATA_NOT_FOUND_ON_DRUID              = 'merchant data not found on druid';
    const SEGMENT_DATA_USER_BUSINESS_CATEGORY           = 'user_business_category';
    const SEGMENT_DATA_ACTIVATION_STATUS                = 'activation_status';
    const SEGMENT_DATA_MCC                              = 'mcc';
    const SEGMENT_DATA_ACTIVATED_AT                     = 'activated_at';
    const SEGMENT_DATA_USER_ROLE                        = 'user_role';
    const SEGMENT_DATA_FIRST_TRANSACTION_TIMESTAMP      = 'first_transaction_timestamp';
    const SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION  = 'user_days_till_last_transaction';
    const SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV           = 'merchant_lifetime_gmv';
    const SEGMENT_DATA_AVERAGE_MONTHLY_GMV              = 'average_monthly_gmv';
    const SEGMENT_DATA_PRIMARY_PRODUCT_USED             = 'primary_product_used';
    const SEGMENT_DATA_PPC                              = 'ppc';
    const SEGMENT_DATA_MTU                              = 'mtu';
    const SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS     = 'average_monthly_transactions';
    const SEGMENT_DATA_PG_ONLY                          = 'pg_only';
    const SEGMENT_DATA_PL_ONLY                          = 'pl_only';
    const SEGMENT_DATA_PP_ONLY                          = 'pp_only';

    const DEFAULT_MIN_HOURS_TO_START_TICKET_CREATION_AFTER_ACTIVATION_FORM_SUBMISSION   =   24;
    // Should be decided by marketing team
    const NEOSTONE_UTM_RULES = [
        [User\Constants::UTM_CAMPAIGN => 'Facebook_RZPx_CA_Conv_NewAcquisItion_India_Owners_2555_MF_All_24082021', User\Constants::UTM_SOURCE => 'Facebook', User\Constants::UTM_MEDIUM => 'CPC'],
        [User\Constants::UTM_CAMPAIGN => '', User\Constants::UTM_SOURCE => 'rx_ca_neostone', User\Constants::UTM_MEDIUM => ''],
        [User\Constants::UTM_CAMPAIGN => 'Facebook_RZPx_CA_Conv_NewAcquisItion_India_Entrepreneurship_2555_M_All_07092021', User\Constants::UTM_SOURCE => 'Facebook', User\Constants::UTM_MEDIUM => 'CPC'],
    ];


    /**
     * Creates a merchant and saves in database
     *
     * @param  array $input
     * @return array
     */
    public function create(array $input): array
    {
        if (empty($input[Entity::ADMINS]) === false)
        {
            Admin\Entity::verifyIdAndStripSignMultiple($input[Entity::ADMINS]);
        }

        if (empty($input[Entity::ORG_ID]) === true)
        {
            // If the organization ID is not present,
            // assume the organization is razorpay
            $input[Entity::ORG_ID] = Org\Entity::RAZORPAY_ORG_ID;

            $this->trace->info(
                TraceCode::MERCHANT_ORG_NOT_GIVEN,
                [
                    'merchant_email' => $input[Entity::EMAIL],
                    'merchant_name'  => $input[Entity::NAME],
                ]);
        }
        else
        {
            Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);
        }

        /** @var Entity $merchant */
        $merchant = $this->core()->create($input);

        $this->enableBusinessBankingIfApplicable($merchant);

        $merchantData = $this->saveMerchantAndApplyCoupon($merchant, $input);

        $this->setDefaultLateAuthConfigForMerchant($merchant);

        return $merchantData;
    }

    public function syncStakeholderFromMerchant($input)
    {
        $data = $this->core()->syncStakeholderFromMerchant($input);

        return $data;
    }

    public function createSubMerchantViaBatch(array $input)
    {
        $merchantId = $this->app['request']->header(RequestHeader::X_ENTITY_ID) ?? null;

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $data = (new RateLimitBatch())->partnerSubmerchantInvite($merchant, $input);

        return $data;
    }

    public function bulkOnboardSubMerchantViaBatch(array $input)
    {
        $requeststartAt = millitime();

        $tracePayload = [
            BatchHeader::MERCHANT_NAME   => $input[BatchHeader::MERCHANT_NAME],
            BatchHeader::MERCHANT_EMAIL  => $input[BatchHeader::MERCHANT_EMAIL],
            BatchHeader::PARTNER_ID      => $input[BatchHeader::PARTNER_ID],
        ];

        try
        {
            $configs = SubMerchantBatchHelper::getConfigParamsFromEntry($input);

            $data = (new SubMerchantBatchUtil())->processSubMerchantEntry($input, $configs);

            $this->trace->count(Metric::BATCH_UPLOAD_BY_ADMIN_TOTAL);
        }
        catch (BaseException $e)
        {
            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $tracePayload);

            $error = $e->getError();

            $data[BatchHeader::STATUS]            = BatchStatus::FAILURE;
            $data[BatchHeader::ERROR_CODE]        = $error->getPublicErrorCode();
            $data[BatchHeader::ERROR_DESCRIPTION] = $error->getDescription();

            $this->trace->count(Metric::BATCH_UPLOAD_BY_ADMIN_FAILURE_TOTAL);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $tracePayload);

            $data[BatchHeader::STATUS]     = BatchStatus::FAILURE;
            $data[BatchHeader::ERROR_CODE] = ErrorCode::SERVER_ERROR;
        }

        $this->trace->histogram(Metric::BATCH_UPLOAD_BY_ADMIN_LATENCY, millitime() - $requeststartAt);

        return $data;
    }

    /**
     * We need the merchant param for batch. This can be removed once the code is restructured
     * in a way that batch can call just core class functions.
     * Source param is to track the origin of sub-merchant creation in data lake. Bulk,Single,Admin,etc.
     *
     * @param array         $input
     * @param Entity|null   $merchant
     * @param string        $source
     *
     * @return array
     * @throws BadRequestException
     */
    public function createSubMerchant(array $input, Entity $merchant = null, string $source = PartnerConstants::ADD_ACCOUNT, bool $optimizeCreationFlow = false): array
    {
        $merchant = $merchant ?? $this->merchant;

        $isLinkedAccount = (bool) ($input['account'] ?? false);

        $isPartner = $merchant->isPartner();

        $hasAggregatorFeature = $merchant->hasAggregatorFeature();

        //
        // Cannot create sub-merchant if all following conditions are met:
        // 1. Not a linked account
        // 2. Is neither a partner nor has an aggregator feature (for BC we allow the feature)
        // 3. Is a partner of type pure-platform
        //
        if ($isLinkedAccount === false)
        {
            if (($isPartner === false) and ($hasAggregatorFeature === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT);
            }
            else if ($merchant->isPurePlatformPartner() === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT);
            }
        }

        $output =  $this->createSubMerchantAndSetRelations($merchant, $isLinkedAccount, $input, $optimizeCreationFlow);

        $data = [
            'status'       => 'success',
            'merchant_id'  => $output['id'] ?? null,
            'partner_id'   => $merchant->getId(),
            'source'       => $source,
            'product_group'=> $input[Entity::PRODUCT] ?? Product::PRIMARY
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP,
            $merchant, null,
            $data);

        $this->trace->info(TraceCode::PARTNERSHIP_SUBMERCHANT_SIGNUP, [
            'data' => $data
        ]);

        $this->app->hubspot->trackSubmerchantSignUp($merchant->getEmail());

        $dimension = [
            'partner_type' => $merchant->getPartnerType(),
            'source'       => $source
        ];

        $this->trace->count(PartnerMetric::SUBMERCHANT_CREATE_TOTAL, $dimension);

        if ($isLinkedAccount === true)
        {
            $this->app->hubspot->trackLinkedAccountCreation($output['email'] ?? null);
        }

        return $output;
    }

    public function createLinkedAccount(array $input)
    {
        $this->trace->info(
            TraceCode::LINKED_ACCOUNT_CREATE_REQUEST_VIA_BATCH,
            [
                'parent_merchant_id'    => $this->merchant->getId(),
                'linked_account_name'   => $input[BatchHeader::ACCOUNT_NAME],
            ]
        );

        $submerchantInput = $this->extractSubmerchantInput($input);

        $linkedAccountArray = $this->createSubMerchantAndSetRelations($this->merchant, true, $submerchantInput);

        if (isset($linkedAccountArray['id']) === false)
        {
            throw new Exception\LogicException(
                'Linked account creation failed.',
                null,
                $linkedAccountArray
            );
        }

        $this->app->hubspot->trackLinkedAccountCreation($linkedAccountArray['email'] ?? null);

        $linkedAccountId = $linkedAccountArray['id'];

        $linkedAccount = $this->repo->merchant->find($linkedAccountId);

        $bankAccountDetails = $this->extractBankAccountDetails($input);

        (new Merchant\Detail\Core())->saveMerchantDetails($bankAccountDetails, $linkedAccount);

        $this->repo->reload($linkedAccount);

        $accountStatus = ($linkedAccount->isActivated() === true) ? 'Activated' : 'Not Activated';

        $input[BatchHeader::ACCOUNT_ID]         = Merchant\Account\Entity::getSignedId($linkedAccountId);
        $input[BatchHeader::ACCOUNT_STATUS]     = $accountStatus;
        $input[BatchHeader::ACTIVATED_AT]       = $linkedAccount->getActivatedAt();

        $this->trace->info(
            TraceCode::LINKED_ACCOUNT_CREATE_VIA_BATCH_SUCCESSFUL,
            [
                'linked_account_id'     => $linkedAccountId,
                'parent_merchant_id'    => $this->merchant->getId(),
                'linked_account_name'   => $input[BatchHeader::ACCOUNT_NAME],
            ]
        );

        return $input;
    }

     /**
     * Change 2fa setting of merchant (enable/disable)
     *
     * @param array  $input
     *
     * @return array
     */
    public function change2faSetting(array $input)
    {
        $this->merchant->getValidator()->validateInput('change2faSetting', $input);

        return $this->core()->change2faSetting(
            $this->user,
            $this->merchant,
            $input);

    }

    /**
     * resets the merchant settlement schedule to default
     *
     * @param array $input
     * @return array
     */
    public function resetSettlementSchedule(array $input): array
    {
        (new Validator)->validateInput('reset_settlement_schedule', $input);

        return $this->core()->resetSettlementSchedule($input['merchant_ids']);
    }

    /**
     * We create this mapping only in case of non-linked accounts if
     * 1. Is partner of type fully-managed
     * 2. Is partner of type aggregator and has exception given for optional sub-merchant email
     * (For now all aggregators are allowed for backward compatibility till future phases)
     * 3. Is not a partner but has aggregator feature (for backward compatibility)
     *
     * @param string $ownerId
     * @param Entity $subMerchant
     * @param Entity $aggregatorMerchant
     * @param string|null $product
     */
    protected function attachSubMerchantOwnerIfApplicable(
        string $ownerId,
        Entity $subMerchant,
        Entity $aggregatorMerchant,
        string $product = null)
    {
        $isPartner = $aggregatorMerchant->isPartner();

        $hasAggregatorFeature = $aggregatorMerchant->hasAggregatorFeature();

        $isOptionalEmailAllowed = $aggregatorMerchant->isOptionalEmailAllowedAggregator();

        $subMerchantEmailIsSame = ($aggregatorMerchant->getEmail() === $subMerchant->getEmail());

        if (($aggregatorMerchant->isFullyManagedPartner() === true) or
            //Remove the following line later as aggregator isn't supposed to
            //have dashboard access eventually. This is for BC.
            ($aggregatorMerchant->isAggregatorPartner() === true) or
            (($isOptionalEmailAllowed === true) and ($subMerchantEmailIsSame === true)) or
            (($isPartner === false) and ($hasAggregatorFeature === true)))
        {
            $this->core()->attachSubMerchantOwner($ownerId, $subMerchant, $product);
        }
    }

    public function saveMerchantAndApplyCoupon(Entity $merchant, array $input)
    {
        $this->repo->saveOrFail($merchant);

        $merchantData = $merchant->toArrayPublic();

        $couponResponse = $this->applyCouponOnSignUp($input, $merchant);

        $merchantData[self::COUPON_RESPONSE] = $couponResponse;

        return $merchantData;
    }

    protected function applyCouponOnSignUp(array $input, Entity $merchant)
    {
        $result = [];

        if (isset($input[Entity::COUPON_CODE]) === true)
        {
            $couponInput = [
                Coupon\Entity::CODE        => $input[Entity::COUPON_CODE],
                Coupon\Entity::MERCHANT_ID => $merchant->getId()
            ];

            try
            {
                $result = (new Coupon\Service)->apply($couponInput);
            }
            catch (\Throwable $e)
            {
                $result = ['message' => $e->getMessage()];
            }
        }

        return $result;
    }

    public function getBillingLabelSuggestions(): array
    {
        return $this->core()->getBillingLabelSuggestions($this->merchant);
    }

    public function patchMerchantBillingLabelAndDba($input)
    {
        $this->core()->editMerchantBillingLabelAndDba($this->merchant, $input);

        return [
            Entity::ID => $this->merchant->getId(),
            Entity::BILLING_LABEL => $this->merchant->getBillingLabel(),
            Merchant\Detail\Entity::BUSINESS_DBA => $this->merchant->getDbaName()
        ];
    }

    public function edit(string $id, array $input): array
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        // when the funds are released via bulk action then in that scenario
        // need to call to the new settlement service for updating the disable feature status
        // this is being done to have the data consistent within api and settlements service
        $newSettlementService = (new Bucket\Core)->shouldProcessViaNewService($id);

        if(isset($input['hold_funds']) === true)
        {
            $action = $input['hold_funds'] == 1 ? Merchant\Action::HOLD_FUNDS : Merchant\Action::RELEASE_FUNDS;
            (new Validator())->validateRiskPermissionForAction($merchant, $action);
            if($newSettlementService === true)
            {
                $this->core()->toggleMerchantHoldInNewSettlementService($merchant, $action, Mode::LIVE);

                $this->core()->toggleMerchantHoldInNewSettlementService($merchant, $action, Mode::TEST);

                if ($action === Merchant\Action::RELEASE_FUNDS) {
                    (new MerchantActionNotification())->removeNotificationTag($merchant, $action);
                }
            }
        }

        if (empty($input[Entity::GROUPS]) === false)
        {
            Group\Entity::verifyIdAndStripSignMultiple($input[Entity::GROUPS]);
        }

        if (empty($input[Entity::ADMINS]) === false)
        {
            Admin\Entity::verifyIdAndStripSignMultiple($input[Entity::ADMINS]);
        }

        if (isset($input[Entity::ORG_ID]) === true)
        {
            Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);
        }

        $merchant = $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input) {
            $merchant = $this->core()->edit($merchant, $input);

            if (isset($input[Entity::FEE_BEARER]) === true)
            {
                $merchantId = $merchant->getId();

                // add feebearer tag if fee_bearer field is set to customer
                // else remove feebearer tag
                if ($input[Entity::FEE_BEARER] === 'customer')
                {
                    $this->insertTag($merchantId, 'feebearer');
                }
                else
                {
                    $this->deleteTag($merchantId, 'feebearer');
                }
            }

            return $merchant;
        });

        return $merchant->toArrayPublic();
    }

    public function editRiskAttributes(string $id, array $input): array
    {
        // not adding a lock, as the chances of race condition is extremely rare
        $this->trace->info(
            TraceCode::MERCHANT_EDIT_RISK_ATTRIBUTES,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        (new Validator)->validateRiskAttributes($input);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === false)
        {
            $newMerchant = clone $merchant;

            $newMerchant->edit($input);

            // in case diff is empty,
            // the workflow handle method will throw an undefined index exception as part of redactFields
            // we can introduce a additonal check to get the diff and verify if its empty or not
            (new Validator)->validateRiskAttributesHasDiff($merchant, $newMerchant);

            $this->app['workflow']
                ->setEntity($merchant->getEntity())
                ->handle($merchant, $newMerchant);
        }

        return $this->edit($id, $input);
    }

    /**
     * Old flow: Sends a mail to the sub-merchant email telling them about
     * account creation. Adds aggregator in cc if the sub-merchant
     * email is different.
     *
     * Partners flow: Sends a mail to the partner informing about the account
     * addition. If the sub-merchant email is different then sends an email
     * to the user created with this email with a password reset email.
     *
     * @param Entity $subMerchant
     * @param Entity $aggregator
     * @param string $product
     * @param User\Entity|null $user
     * @param bool $createdNewUser
     * @param bool $retry
     */
    protected function sendSubMerchantCreationMail(
        Entity $subMerchant,
        Entity $aggregator,
        string $product,
        User\Entity $user = null,
        bool $createdNewUser = false,
        bool $retry = false)
    {
        $isPartnerFlow = $aggregator->isPartner();

        $subMerchant = $subMerchant->toArray();

        $aggregator = $aggregator->toArray();

        if ($isPartnerFlow === true)
        {
            $this->sendNewSubMerchantCreationMails($subMerchant, $aggregator, $product, $user, $createdNewUser, $retry);
        }
        else
        {
            $createSubMerchantMail = new CreateSubMerchantMail($subMerchant, $aggregator);

            Mail::queue($createSubMerchantMail);
        }
    }

    /**
     * @param array $subMerchant
     * @param array $aggregator
     * @param string $product
     * @param User\Entity|null $user
     * @param bool $createdNewUser
     * @param bool $retry
     */
    protected function sendNewSubMerchantCreationMails(
        array $subMerchant,
        array $aggregator,
        string $product,
        User\Entity $user = null,
        bool $createdNewUser = false,
        bool $retry = false)
    {
        // This mail goes to the partner who has added the sub-merchant. If the partner is adding the merchant then mail
        // is sent to both partner and merchant but when partner sends the mail as a reminder to merchant for setting
        // the password, mail is only sent to merchant and not to the partner. In this case, retry is true and partner
        // does not get any mail.
        // Skip sending mail to partner from batch service.

        if ($retry === false and $this->app['basicauth']->isBatchApp() === false)
        {
            switch ($product)
            {
                case Product::PRIMARY:
                    $createSubMerchantPartnerMail = new CreateSubMerchantPartnerForPG($subMerchant, $aggregator);
                    Mail::queue($createSubMerchantPartnerMail);
                    break;

                case Product::BANKING:
                    $createSubMerchantPartnerMail = new CreateSubMerchantPartnerForX($subMerchant, $aggregator);
                    Mail::queue($createSubMerchantPartnerMail);
                    break;
            }
        }

        if ($subMerchant[Entity::EMAIL] === $aggregator[Entity::EMAIL])
        {
            return;
        }

        $orgId = (isset($subMerchant['org_id']) === true) ? $subMerchant['org_id'] : $subMerchant['org']['id'];

        /** @var Org\Entity $org */
        $org = $this->repo->org->find($orgId);

        $hostname = $org->getPrimaryHostName();

        $org = $org->toArrayPublic();

        $org[Hostname\Entity::HOSTNAME] = $hostname;

        $mailUserData = $createdNewUser ? $user : null;

        // If user was just created then we need to pass the details to the mailer for sending
        // reset password link.
        switch ($product)
        {
            case Product::PRIMARY:
                $createSubMerchantAffiliateMail = new CreateSubMerchantAffiliateForPG($subMerchant, $aggregator, $org, $mailUserData);
                Mail::queue($createSubMerchantAffiliateMail);
                break;

            case Product::BANKING:
                $createSubMerchantAffiliateMail = new CreateSubMerchantAffiliateForX($subMerchant, $aggregator, $org, $mailUserData);
                Mail::queue($createSubMerchantAffiliateMail);
                break;
        }
    }

    public function editEmail($id, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $orignalEmail = $merchant->getEmail();

        $merchant = $this->core()->editEmail($merchant, $input);

        $newEmail = $merchant->getEmail();

        // handle user management on PG
        $this->core()->changeMerchantUsersEmail($merchant, $orignalEmail, $newEmail, Product::PRIMARY);

        // handle user management on X
        $this->core()->changeMerchantUsersEmail($merchant, $orignalEmail, $newEmail, Product::BANKING);

        return $merchant->toArrayPublic();
    }

    public function getUserStatusForEmailUpdateSelfServe($input)
    {
        $this->trace->info(TraceCode::EMAIL_USER_STATUS_FOR_EDIT_EMAIL, $input);

        (new Validator())->validateInput('editMerchantEmailSelfServe', $input);

        $input[Entity::EMAIL]   = mb_strtolower($input[Entity::EMAIL]);

        $merchant = $this->app['basicauth']->getMerchant();

        $product = $this->app['basicauth']->getRequestOriginProduct();

        $status = $this->core()->getUserStatusForEmailUpdateSelfServe($input[Entity::EMAIL], $merchant, $product);

        if ($status[Constants::IS_USER_EXIST] === false)
        {
            // this flow is used by owner user only : basic auth user is same as owner user
            $ownerUser = $this->app['basicauth']->getUser();

            $this->saveMerchantEmailUpdateData($ownerUser->getEmail(), $merchant->getId(), $input);

            $this->core()->sendMailForEditMerchantEmailSelfServe($ownerUser, $input[Entity::EMAIL]);

            $this->trace->info(TraceCode::EMAIL_SENT_FOR_EDIT_MERCHANT_EMAIL, []);
        }

        return $status;
    }

    public function editMerchantEmailAndTransferOwnershipToEmailUser(array $input): array
    {
        (new Validator())->validateInput('editMerchantEmailSelfServe', $input);

        $input[Entity::EMAIL]   = mb_strtolower($input[Entity::EMAIL]);

        $this->trace->info(TraceCode::MERCHANT_EDIT_EMAIL_REQUEST, $input);

        $merchant = $this->app['basicauth']->getMerchant();

        $user = $this->repo->user->getUserFromEmailOrFail($input[Entity::EMAIL]);

        // user to whom ownership is being transfered should not have any cross org merchant
        (new Validator())->validateUserDoesNotBelongToMerchantsInMultipleOrgsForEmailUpdate($user);

        // this flow is used by owner user only : basic auth user is same as owner user
        $currentOwner = $this->app['basicauth']->getUser();

        $this->core()->editMerchantEmailAndTransferOwnershipToUser($user, $currentOwner, $merchant, $input);

        $this->invalidatePreviousRequestForEmailUpdate($merchant, $currentOwner);

        return  [
            Constants::LOGOUT_SESSIONS_FOR_USERS => [$user->getId(), $currentOwner->getId()]
        ];
    }

    public function editMerchantEmailCreateNewUserAndTransferOwnerShip($input)
    {
        (new Validator())->validateInput('changeEmailToken', $input);

        $data = $this->getMerchantEmailUpdateData($input[Entity::MERCHANT_ID]);

        // reset token expires before cache data: throw token expire exception on cache expire
        if (is_null($data) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
        }

        $merchantId = $data[Entity::MERCHANT_ID];

        $this->trace->info(TraceCode::MERCHANT_EDIT_EMAIL_REQUEST,[
            Entity::MERCHANT_ID => $merchantId
        ]);

        $input = array_merge($input, $data);

        // using merchant_id from cache
        $input[Entity::MERCHANT_ID] = $merchantId;

        $merchant = $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $currentOwnerUser = $this->repo->user->getUserFromEmailOrFail($input[Constants::CURRENT_OWNER_EMAIL]);

        (new Validator())->validateUserIsOwnerForMerchant($currentOwnerUser->getId(), $input[Entity::MERCHANT_ID]);

        $this->core()->createNewUserAndTransferOwnerShip($input, $merchant, $currentOwnerUser);

        $this->deleteMerchantEmailUpdateData($input[Entity::MERCHANT_ID]);

        return [
            Constants::LOGOUT_SESSIONS_FOR_USERS => [$currentOwnerUser->getId()]
        ];
    }

    protected function deleteMerchantEmailUpdateData($merchantId)
    {
        $cacheKey = $this->getMerchantEmailUpdateCacheKey($merchantId);

        $this->app->cache->delete($cacheKey);
    }

    protected function invalidatePreviousRequestForEmailUpdate($merchant, $currentOwnerUser)
    {
        // delete cached data to invalidated any previous request : data will be missing if link is accessed
        $this->deleteMerchantEmailUpdateData($merchant->getId());

        // invalidate Token
        (new User\Service())->setAndSaveResetPasswordToken($currentOwnerUser, null);
    }


    protected function saveMerchantEmailUpdateData($userEmail, $merchantId, $input)
    {
        $data = [
            Constants::CURRENT_OWNER_EMAIL    => $userEmail,
            Entity::EMAIL                     => $input[Entity::EMAIL],
            Entity::MERCHANT_ID               => $merchantId,
            Constants::REATTACH_CURRENT_OWNER => (bool) ($input[Constants::REATTACH_CURRENT_OWNER] ?? false),
            Constants::SET_CONTACT_EMAIL      => (bool) ($input[Constants::SET_CONTACT_EMAIL] ?? false)
        ];

        $cacheKey = $this->getMerchantEmailUpdateCacheKey($merchantId);

        $this->app->cache->put($cacheKey, $data, Constants::MERCHANT_EMAIL_UPDATE_CACHE_TTL);
    }

    protected function getMerchantEmailUpdateData($merchantId)
    {
        $cacheKey = $this->getMerchantEmailUpdateCacheKey($merchantId);

        return $this->app->cache->get($cacheKey);
    }

    protected function getMerchantEmailUpdateCacheKey($merchantId)
    {
        return sprintf(Constants::MERCHANT_EMAIL_UPDATE_CACHE_KEY, $merchantId);
    }


    public function correctMerchantOwnerForBanking($id): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $primaryOwner = $merchant->primaryOwner();

        $bankingOwner = $merchant->primaryOwner('banking');

        if ((empty($primaryOwner) === true) or
            (empty($bankingOwner) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_OWNER_NOT_EXISTS,
                null,
                [
                    'primary_owner' => $primaryOwner,
                    'banking_owner' => $bankingOwner
                ]);
        }

        if ($primaryOwner !== $bankingOwner)
        {
            // Demote banking owner to view_only role
            (new User\Core)->detachAndAttachMerchantUser($bankingOwner, $merchant->getId(), 'view_only', 'banking');

            // Promote the owner in PG to owner in banking
            (new User\Core)->detachAndAttachMerchantUser($primaryOwner, $merchant->getId(), 'owner', 'banking');
        }

        return $merchant->toArrayPublic();
    }

    public function editConfig(array $input): array
    {
        // Adds uploaded logo's url to the input.
        $this->uploadLogoIfFound($input);

        //remove the email field from payload
        if($this->merchant->org->isFeatureEnabled(Feature\Constants::ORG_EMAIL_UPDATE_2FA_ENABLED) === true)
        {
            unset($input['transaction_report_email']);
        }

        $this->core()->editConfig($this->merchant, $input);

        return $this->merchant->toArrayConfig();
    }

    /**
     * @throws BadRequestException
     */
    public function editEmail2FA(array $input): array
    {
        if($this->merchant->org->isFeatureEnabled(Feature\Constants::ORG_EMAIL_UPDATE_2FA_ENABLED) === false)
        {
            return $this->editConfig($input);
        }
        else if(array_key_exists("otp",$input) === false or array_key_exists("token",$input) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,null,[
                "internal_error_code" =>ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
                "description" => PublicErrorDescription::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
            ]);
        }

        $this->trace->info(TraceCode::INPUT_OTP_TOKEN_CHECK, [
            'otp_exists' => array_key_exists("otp",$input),
            'token_exists'=> array_key_exists("token",$input),
        ]);

        $input[User\Entity::MEDIUM] = "email";
        $input[User\Entity::ACTION] = "verify_contact";

        $data=[];

        $user = $this->auth->getUser();

        $content = [
            "otp"=>$input["otp"],
            "token"=>$input["token"]
        ];

        try {
            $response = (new User\Core)->verifyUserThroughEmail($content, $this->merchant, $user);
        }
        catch (\Exception $e)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,null,[
                "internal_error_code" =>ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
                "description" => PublicErrorDescription::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
            ]);
        }

        $data["transaction_report_email"] = $input["transaction_report_email"];

        $this->core()->editConfig($this->merchant, ["transaction_report_email"=>$input["transaction_report_email"]]);

        return $data;
    }

    public function deleteMerchantLogo(): array
    {
        $this->merchant->setLogoUrl(null);

        $this->repo->saveOrFail($this->merchant);

        return $this->merchant->toArrayConfig();
    }

    protected function uploadLogoIfFound(&$input)
    {
        // if ($input->hasFile('logo') and $input['logo']->isValid())
        if (isset($input['logo']))
        {
            // Store the logos in AWS
            $logoUrl = (new Logo)->setUpMerchantLogo($input);

            $input['logo_url'] = $logoUrl;
            unset($input['logo']);
        }
    }

    // This is on internal auth
    public function fetch(string $id): array
    {
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
            $id, ['methods', Entity::GROUPS, Entity::ADMINS]);

        return $merchant->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $merchants = $this->repo->merchant->fetch($input);

        return $merchants->toArrayPublic();
    }

    public function fetchConfig(bool $isInternal = false): array
    {
        $merchantId = $this->merchant->getId();

        $configList = Entity::CONFIG_LIST;

        if ($isInternal === true)
        {
            $configList = array_merge($configList, Entity::INTERNAL_CONFIG_LIST);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId, $configList);

        $response = $merchant->toArray();

        $response['logo_large_size_url'] = $this->merchant->getFullLogoUrlWithSize(Logo::LARGE_SIZE);

        $response[Refund\Constants::REFUND_STATUS_FILTER] =
            (new Refund\Service)->getRefundStatusFilterFlagForMerchantDashboard($merchantId);

        if ($isInternal === true)
        {
            $response[Entity::MAX_PAYMENT_AMOUNT] =  $this->merchant->getMaxPaymentAmount();

            $response['is_suspended'] =  $this->merchant->isSuspended();

            $response['is_live'] = $this->merchant->isLive();

            $response[Entity::ORG_ID] =  $this->merchant->getOrgId();

            $response['org_custom_code'] =  $this->merchant->org->getCustomCode();

            $response['brand_color'] = $this->merchant->getBrandColorOrOrgPreference();

            $supportDetails = $this->repo->merchant_email->getEmailByType(Merchant\Email\Type::SUPPORT, $merchant->getId());

            if ($supportDetails !== null)
            {
                $supportDetails = $supportDetails->toArrayPublic();

                $response['support_email']  = $supportDetails[Merchant\Email\Entity::EMAIL];

                $response['support_mobile'] = $supportDetails[Merchant\Email\Entity::PHONE];
            }
        }

        $response += (new CheckoutView())->addOrgInformationInResponse($this->merchant);

        return $response;
    }

    public function getPaymentFailureAnalysis($input)
    {
        (new Validator())->validateInput('get_payment_failure_analysis', $input);

        (new Validator())->validateRangeForFailureAnalysis($input);

        $merchant = app('basicauth')->getMerchant();

        $startTime = microtime(true);
        $failureAnalysisData = $this->repo->payment->fetchPaymentsFailureAnalysisData($input['from'], $input['to'], $merchant->getId());

        $this->trace->info(TraceCode::MERCHANT_FAILURE_ANALYSIS_QUERY_TIME, [
            MerchantConstants::QUERY_EXECUTION_TIME            => (microtime(true) - $startTime),
            MerchantConstants::FAILURE_ANALYSIS_FOR_TIME_RANGE => $input['to'] - $input['from'],
        ]);

        $response = [
            MerchantConstants::SUMMARY => [
                MerchantConstants::NUMBER_OF_TOTAL_PAYMENTS      => 0,
                MerchantConstants::NUMBER_OF_SUCCESSFUL_PAYMENTS => 0,
            ],
            MerchantConstants::FAILURE_DETAILS => [
                MerchantConstants::CUSTOMER_DROP_OFF => 0,
                MerchantConstants::BANK_FAILURE      => 0,
                MerchantConstants::BUSINESS_FAILURE  => 0,
                MerchantConstants::OTHER_FAILURE     => 0,
            ],
        ];

        foreach ($failureAnalysisData as $data)
        {
            $this->addPaymentCountInResponseForFailureAnalysis($response, $data);
        }

        return $response;
    }

    protected function addPaymentCountInResponseForFailureAnalysis(&$response, $data)
    {
        $response[MerchantConstants::SUMMARY][MerchantConstants::NUMBER_OF_TOTAL_PAYMENTS] += $data->count;

        $status = $data->status;

        if (in_array($status, [Payment\Status::AUTHORIZED, Payment\Status::CAPTURED, Payment\Status::REFUNDED]) === true)
        {
            $response[MerchantConstants::SUMMARY][MerchantConstants::NUMBER_OF_SUCCESSFUL_PAYMENTS] += $data->count;
        }
        elseif ($status === Payment\Status::FAILED)
        {
            $errorSourceCategory = $this->getErrorSourceCategoryForFailureAnalysis($data->internal_error_code, $data->method);

            $response[MerchantConstants::FAILURE_DETAILS][$errorSourceCategory] += $data->count;
        }
    }

    protected function getErrorSourceCategoryForFailureAnalysis($errorCode, $method)
    {
        list($errorCodeJson,) = $this->app['error_mapper']->getErrorMapping($errorCode, $method);

        if ((isset($errorCodeJson['source']) === true) and
            (key_exists($errorCodeJson['source'], MerchantConstants::ERROR_SOURCE_CATEGORY) === true))
        {
            return MerchantConstants::ERROR_SOURCE_CATEGORY[$errorCodeJson['source']];
        }

        return MerchantConstants::OTHER_FAILURE;
    }

    public function shouldShowSettlementUxRevamp(): bool
    {
        $variant = $this->app->razorx->getTreatment($this->merchant->getId(),
            Merchant\RazorxTreatment::SETTLEMENT_UX_REVAMP,
            $this->mode
        );

        $result = (strtolower($variant) === 'on');

        return $result;
    }

    public function getPrimaryBalance()
    {
        return $this->repo->balance->getMerchantBalanceByType($this->merchant->getId(),
                Merchant\Balance\Type::PRIMARY)->toArrayPublic();
    }

    public function fetchBalance($merchantId = null)
    {
        if ($merchantId === null)
        {
            $merchantId = $this->merchant->getId();
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        //
        // For non-activated merchants in live mode, simply return 0.
        // For these merchants, balance entity is not yet created so
        // we need to create the exception here.
        //
        if (($this->mode === Mode::LIVE) and
            ($merchant->isActivated() === false) and
            (Account::isNodalAccount($merchantId) === false))
        {
            // TODO need to discuss this
            $balance[Balance\Entity::ID]      = $merchantId;
            $balance[Balance\Entity::BALANCE] = 0;

            return $balance;
        }

        $balance = $this->repo->balance->getMerchantBalance($merchant);

        return $balance->toArray();
    }

    public function fetchAccountBalances(array $input)
    {
        $merchantId = $this->merchant->getId();

        $balance = $this->repo->balance->fetch($input, $merchantId);

        return $balance->toArrayPublic();
    }

    public function updateLockedBalance(array $input, string $balanceId)
    {
        /** @var Balance\Entity $balance */
        $balance = $this->repo->balance->findOrFailById($balanceId);

        $balance->getValidator()->validateInput(Balance\Validator::LOCKED_BALANCE, $input);

        $lockedBalance = $input[Merchant\Balance\Entity::LOCKED_BALANCE];

        $oldLockedBalance = $balance->getLockedBalance();

        $traceData = [
            'input'                     => $input,
            'balance_id'                => $balanceId,
            'balance_type'              => $balance->getType(),
            'balance_account_type'      => $balance->getAccountType(),
            'current_locked_balance'    => $oldLockedBalance,
        ];

        $this->trace->info(TraceCode::LOCKED_BALANCE_UPDATE_REQUEST, $traceData);

        if (($balance->isTypeBanking() === false) or
            ($balance->isAccountTypeShared() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOCKED_BALANCE_UPDATE_NON_BANKING,
                null,
                $traceData);
        }

        $balance->setLockedBalance($lockedBalance);

        $this->repo->saveOrFail($balance);

        $response = [
            'balance_id'            => $balance->getId(),
            'current_balance'       => $balance->getBalance(),
            'old_locked_balance'    => $oldLockedBalance,
            'new_locked_balance'    => $balance->getLockedBalance(),
        ];

        $this->trace->info(
            TraceCode::LOCKED_BALANCE_UPDATE_RESPONSE,
            $response);

        return $response;
    }

    public function editAmountCredits($merchantId, $input)
    {
        (new Validator)->validateInput('edit_credits', $input);

        $amountCredits = $input['credits'];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $balance = $this->repo->balance->editMerchantAmountCredits($merchant, $amountCredits);

        return $balance->toArray();
    }

    public function assignPricingPlan($id, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_PRICING_PLAN_ASSIGN_REQUEST,
            [
                'merchant_id' => $id,
                'input'       => $input
            ]);

        /** @var Entity $merchant */
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if (isset($input['pricing_plan_id']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_ID_REQURED,
                'pricing_plan_id');
        }

        $orgId = $merchant->org->getId();

        /** @var Plan $plan */
        $plan = $this->repo->pricing->getPricingPlanByIdAndOrgId($input['pricing_plan_id'], $orgId);

        // validate if this plan can be set for this merchant.
        // Refer: https://github.com/razorpay/api/issues/324

        $methods = $this->repo->methods->getMethodsForMerchant($merchant);

        (new Methods\Core)->validatePricingPlanForMethods($merchant, $plan, $methods, false);

        $this->validatePricingPlanForFeeBearer($merchant, $plan);

        $originalPricingPlan = null;

        if (empty($merchant->pricing) === false)
        {
            $originalPricingPlan = $merchant->pricing->getPlanName();
        }

        list($original, $dirty) = [
            // Current plan
            ['pricing_plan' => $originalPricingPlan],
            // New plan
            ['pricing_plan' => $plan->first()->getPlanName()],
        ];

        $this->app['workflow']
             ->setEntity($merchant->getEntity())
             ->handle($original, $dirty);

        $merchant->setPricingPlan($input['pricing_plan_id']);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, SlackActions::ASSIGN_PRICING, $input);

        return $plan->toArrayPublic();
    }

    public function isAdminLoggedInAsMerchant()
    {
        $isAdminLoggedInAsMerchant = $this->app['basicauth']->isAdminLoggedInAsMerchantOnDashboard();

        $this->trace->info(TraceCode::IS_ADMIN_LOGGED_IN_AS_MERCHANT, ["isAdminLoggedInAsMerchant" => $isAdminLoggedInAsMerchant]);

        if ( $isAdminLoggedInAsMerchant === true )
        {
            return ["is_admin_as_merchant" => true];
        }
        return ["is_admin_as_merchant" => false];
    }

    public function assignSettlementScheduleIncludingLinkedAccounts($id, $input)
    {
        $this->trace->info(TraceCode::SCHEDULE_ASSIGN_RAZORX_SUCCESS, []);

        $merchantIds = $this->repo->merchant->fetchLinkedAccountMids($id);

        array_unshift($merchantIds, $id);

        $scheduleTaskCore = new ScheduleTask\Core();

        $succeededIds = [];

        $scheduleTask = $this->repo->transaction(function() use($merchantIds, $id, $input, $scheduleTaskCore, & $succeededIds)
        {
            $parentMerchantScheduleTask = null;

            foreach ($merchantIds as $merchantId)
            {
                $merchant = $this->repo->merchant->findByIdAndOrgId($merchantId, $this->auth->getOrgId());

                $input[ScheduleTask\Entity::TYPE] = ScheduleTask\Type::SETTLEMENT;

                $scheduleTaskObj = $scheduleTaskCore->createOrUpdate($merchant, $merchant, $input);

                $parentMerchantScheduleTask = ($merchantId === $id) ? $scheduleTaskObj : $parentMerchantScheduleTask;

                array_push($succeededIds, $merchantId);
            }
            return $parentMerchantScheduleTask;
        });

        $this->trace->info(
            TraceCode::SCHEDULE_ASSIGNED_SUCCESSFULLY,
            [
                "count"        => count($succeededIds),
                "merchant_ids" => $succeededIds
            ]
        );

        return $scheduleTask->toArrayPublic();
    }

    public function assignSettlementSchedule($id, $input)
    {
        $this->trace->info(
            TraceCode::SCHEDULE_ASSIGN_REQUEST,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        $variant = $this->app->razorx->getTreatment($id, RazorxTreatment::UPDATE_LINKED_ACCOUNT_SCHEDULES_FEATURE, $this->mode ?? Mode::LIVE);

        if(strtolower($variant) === 'on')
        {
            return $this->assignSettlementScheduleIncludingLinkedAccounts($id, $input);
        }
        else
        {
            $merchant = $this->repo->merchant->findByIdAndOrgId($id, $this->auth->getOrgId());

            $input[ScheduleTask\Entity::TYPE] = ScheduleTask\Type::SETTLEMENT;

            $scheduleTask = (new ScheduleTask\Core)->createOrUpdate($merchant, $merchant, $input);

            return $scheduleTask->toArrayPublic();
        }
    }

    public function bulkAssignSchedule(array $input): array
    {
        $startTime = millitime();

        $this->trace->info(TraceCode::MERCHANT_SCHEDULE_BULK_REQUEST, $input);

        $this->increaseAllowedSystemLimits();

        (new Validator)->validateInput('bulk_assign_schedule', $input);

        $merchantIds = $input['merchant_ids'];
        $schedule    = $input['schedule'];

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->app['workflow']->skipWorkflows(function() use ($merchantId, $schedule)
                {
                    $this->assignSettlementSchedule($merchantId, $schedule);
                });
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException(
                    $t,
                    \Razorpay\Trace\Logger::ERROR,
                    TraceCode::MERCHANT_SCHEDULE_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'input'       => $schedule,
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        $timeTaken = millitime() - $startTime;

        $this->trace->info(
            TraceCode::BULK_ACTION_RESPONSE_TIME,
            [
                'action'          => 'assign_schedule',
                'time_taken'      => $timeTaken,
            ]);

        return [
            'total_count'  => count($merchantIds),
            'failed_count' => count($failedIds),
            'failed_ids'   => $failedIds
        ];
    }

    public function bulkAssignPricing(array $input): array
    {
        $startTime = millitime();

        $this->trace->info(TraceCode::MERCHANT_PRICING_BULK_REQUEST, $input);

        $this->increaseAllowedSystemLimits();

        (new Validator)->validateInput('bulk_assign_pricing', $input);

        $merchantIds   = $input['merchant_ids'];
        unset($input['merchant_ids']);

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->app['workflow']->skipWorkflows(function() use ($merchantId, $input)
                {
                    $this->assignPricingPlan($merchantId, $input);
                });
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException(
                    $t,
                    Trace::ERROR,
                    TraceCode::MERCHANT_PRICING_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'input'       => $input,
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        // Tracing all ids together for ease of re-running in case of errors. The dashboard error
        // display is not that convenient and can be lost. Collecting from the previous logs of
        // individual failures is more time consuming.
        $this->trace->error(TraceCode::MERCHANT_PRICING_BULK_ALL_FAILED_IDS, [ 'failed_ids' => $failedIds]);

        $timeTaken = millitime() - $startTime;

        $this->trace->info(
            TraceCode::BULK_ACTION_RESPONSE_TIME,
            [
                'action'          => 'assign_pricing',
                'time_taken'      => $timeTaken,
            ]);

        return [
            'total_count'  => count($merchantIds),
            'failed_count' => count($failedIds),
            'failed_ids'   => $failedIds
        ];
    }

    public function bulkSubmerchantAssign($input)
    {
        $validator = (new Validator);

        $submerchantAssignBatchCollection = new Base\PublicCollection;

        $validator->validateBulkSubmerchantAssignCount($input);

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        $validator->validateBatchId($batchId);

        $idempotencyKey = null;

        $this->trace->info(
            TraceCode::BATCH_SERVICE_SUBMERCHANT_ASSIGN_BULK_REQUEST,
            [
                'batch_id'  => $batchId,
                'input'     => $input,
            ]);

        $terminalService = new Terminal\Service;

        foreach($input as $item)
        {
            try
            {
                $this->repo->transaction(function() use (& $item,
                                                         & $submerchantAssignBatchCollection,
                                                         & $batchId,
                                                         & $idempotencyKey,
                                                         $validator,
                                                         $terminalService)
                {
                    $validator->validateInput('bulk_submerchant_assign', $item);

                    $idempotencyKey = $item['idempotency_key'];

                    $data = $this->processEntryForBulkSubmerchantAssign(
                        $item, $batchId, $idempotencyKey, $terminalService);

                    $submerchantAssignBatchCollection->push($data);
                });

            }
            catch(Exception\BaseException $exception)
            {
                $this->trace->traceException($exception,
                    Trace::ERROR,
                    TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST
                );

                $exceptionData = [
                    'batch_id'        => $batchId,
                    'idempotency_key' => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $exception->getError()->getDescription(),
                        Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
                    ],
                    Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
                ];

                $submerchantAssignBatchCollection->push($exceptionData);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->traceException($throwable,
                    Trace::CRITICAL,
                    TraceCode::BATCH_SERVICE_BULK_EXCEPTION
                );

                $exceptionData = [
                    'batch_id'        => $batchId,
                    'idempotency_key' => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $throwable->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
                    ],
                    Error::HTTP_STATUS_CODE => 500,
                ];

                $submerchantAssignBatchCollection->push($exceptionData);
            }
        }

        $this->trace->info(
            TraceCode::BATCH_SERVICE_SUBMERCHANT_ASSIGN_BULK_RESPONSE,
            [
                'batch_id'  => $batchId,
                'output'    => $submerchantAssignBatchCollection->toArrayWithItems(),
            ]);

        return $submerchantAssignBatchCollection->toArrayWithItems();
    }

    protected function processEntryForBulkSubmerchantAssign($item, $batchId, $idempotencyKey, $terminalService)
    {
        $terminalId     = $item['terminal_id'];
        $submerchantId  = $item['submerchant_id'];

        /*
            Idempotency is checked inside Terminal/Core before assigning a
            terminal to a merchant to whom that terminal has been already assigned.
        */
        $terminalService->addMerchantToTerminal($terminalId, $submerchantId);

        return [
            'batch_id'        => $batchId,
            'submerchant_id'  => $submerchantId,
            'idempotency_key' => $idempotencyKey,
            'terminal_id'     => $terminalId,
            'status'          => 'SUCCESS',
            'failure_reason'  => null,
        ];
    }

    public function migrateMerchantToSettlementSchedules($input)
    {
        $this->trace->info(TraceCode::SCHEDULE_MIGRATION_INITIATED);

        if (isset($input['merchant_ids']))
        {
            $merchants = $this->repo->merchant->findMany($input['merchant_ids']);
        }
        else
        {
            $merchants = $this->repo->merchant->getFewMerchantsWithNoCorrespondingScheduleTasks();
        }

        $migrationSummary = [
            'migrated_ids_count' => 0,
            'failed_ids'         => [],
        ];

        foreach ($merchants as $merchant)
        {
            try
            {
                $defaultDelay = Entity::DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

                $schedule = (new Schedule\Core)->getOrCreateDefaultSchedule($defaultDelay);

                $input = [
                    ScheduleTask\Entity::METHOD      => null,
                    ScheduleTask\Entity::TYPE        => ScheduleTask\Type::SETTLEMENT,
                    ScheduleTask\Entity::SCHEDULE_ID => $schedule->getId()
                ];

                (new ScheduleTask\Core)->createOrUpdate($merchant, $merchant, $input);

                $migrationSummary['migrated_ids_count'] += 1;
            }
            catch (\Exception $ex)
            {
                $merchantId = $merchant->getId();

                $this->trace->info(
                    TraceCode::SCHEDULE_MIGRATION_FAILED,
                    [
                        'merchant_id' => $merchantId,
                        'schedule_id' => $schedule->getId(),
                        'error'       => $ex->getMessage(),
                    ]);

                $migrationSummary['failed_ids'][] = $merchantId;
            }
        }

        $migrationSummary['fail_count'] = count($migrationSummary['failed_ids']);

        $this->trace->info(TraceCode::SCHEDULE_MIGRATION_COMPLETE, $migrationSummary);

        return $migrationSummary;
    }

    public function getPricingPlan($id)
    {
        $orgId = $this->auth->getOrgId();

        $merchant = $this->repo->merchant->findByIdAndOrgId($id, $orgId);

        $pricingPlanId = $merchant->getPricingPlanId();

        $plan = new Plan;

        if (empty($pricingPlanId) === false)
        {
            Org\Entity::verifyIdAndSilentlyStripSign($orgId);

            $plan = $this->repo->pricing->getPricingPlanByIdAndOrgId($pricingPlanId, $orgId);
        }

        return $plan->toArrayPublic();
    }

    public function proxyGetPricingPlan()
    {
        $merchant = $this->repo->merchant->find($this->merchant->getId());

        $pricingPlanId = $merchant->getPricingPlanId();

        $plan = new Plan;

        if (empty($pricingPlanId) === false)
        {
            $plan = $this->repo->pricing->getPricingPlanByIdWithProductAndFeatureFilter($pricingPlanId);
        }

        return $plan->toArrayProxy();
    }

    public function sendActivationEmail(array $input)
    {
        $act = new Activate($this->app);

        $response = [];

        foreach ($input['ids'] as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                if ($merchant->isActivated())
                {
                    $act->sendActivationEmail($merchant);

                    $response[$merchantId] = 'Queued merchant activation email';
                }
                else
                {
                    $response[$merchantId] = 'Merchant is not activated';
                }
            }
            catch (\Exception $e)
            {
                $response[$merchantId] = $e->getMessage();
            }
        }

        return $response;
    }

    public function liveEnable($id)
    {
        $this->trace->info(
            TraceCode::MERCHANT_LIVE_ENABLE_REQUEST,
            [
                'merchant_id' => $id,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        if ($merchant->isLive())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_LIVE);
        }

        if ($merchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED);
        }

        $oldMerchant = clone $merchant;

        $merchant->liveEnable();

        // Triggering
        $workflow = $this->app['workflow']
                         ->setEntity($merchant->getEntity())
                         ->handle($oldMerchant, $merchant);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, 'enable');

        return $merchant->toArrayPublic();
    }

    public function liveDisable($id)
    {
        $this->trace->info(
            TraceCode::MERCHANT_LIVE_DISABLE_REQUEST,
            [
                'merchant_id' => $id,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        if ($merchant->isLive() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE);
        }

        $oldMerchant = clone $merchant;

        $merchant->liveDisable();

        // Triggering
        $workflow = $this->app['workflow']
                         ->setEntity($merchant->getEntity())
                         ->handle($oldMerchant, $merchant);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, 'disable');

        return $merchant->toArrayPublic();
    }

    public function toggleInternational($input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_INTERNATIONAL_TOGGLE_REQUEST,
            [
                'input'     => $input,
            ]);

        (new Validator)->validateInput('toggleInternational', $input);

        $toggleValue = (bool) ($input['international'] ?? false);

        $merchant = $this->core()->toggleInternational($this->merchant, $toggleValue);

        return $merchant->toArrayPublic();
    }

    public function action($id, array $input, bool $useWorkflows = true)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT_ACTION,
            [
                'merchant_id' => $id,
                'input'       => $input,
                'useWorkflows'=> $useWorkflows
            ]);

        if (isset($input[Constants::USE_WORKFLOWS]))
        {
            unset($input[Constants::USE_WORKFLOWS]);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchant = $this->core()->action($merchant, $input, $useWorkflows);

        return $merchant->toArrayPublic();
    }

    /**
     * Todo : $id is Not used to fetch merchant. Kept to support Backward Compatible.
     * @param $id
     * @param $input
     * @return array
     */
    public function addBankAccount($id, $input)
    {
        $merchant = app('basicauth')->getMerchant();

        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $merchant);

        // Using Request::input() since we do not want the file as input to log
        $this->logActionToSlack($merchant, SlackActions::EDIT_BANK_DETAILS, Request::input());

        return $ba->toArray();
    }

    public function editBankAccount($id, $input)
    {
        $bankAccount = $this->repo->bank_account->findOrFailPublic($id);

        $bankAccount = (new BankAccount\Core)->editBankAccount($bankAccount, $input);

        return $bankAccount->toArray();
    }

    public function bankAccountUpdate(array $input)
    {
        $merchant = app('basicauth')->getMerchant();

        $ba =  (new BankAccount\Core)->bankAccountUpdate($merchant, $input);

        return $ba->toArray();
    }

    public function bankAccountUpdatePostPennyTestingWorkflow(array $input)
    {
        [$merchant, $merchantDetails] = (New Merchant\Detail\Core())->getMerchantAndSetBasicAuth($input[Constants::MERCHANT_ID]);

        $ba =  (new BankAccount\Core)->bankAccountUpdatePostPennyTestingWorkflow($merchant, $input);

        return $ba->toArray();
    }

    /**
     * This function returns if there any open workflow actions associated with the current bank account entity of a
     * merchant. @todo: Replace this with a more generic approach based on primary entity
     *
     * @param $id
     *
     * @return bool
     */
    public function getBankAccountChangeStatus($id)
    {
        return (($this->getBankAccountChangeViaWorkflowStatus($id) === true) or
                ($this->getBankAccountChangeViaPennyTestingStatus($id)));
    }

    public function getProductInternationalStatus() :array
    {
        $merchantCore = new Merchant\Core;

        $merchant = $this->merchant;

        $response['data'] = $merchantCore->getProductInternationalStatus($merchant);

        return $response;
    }

    public function openWorkflowExists(string $workflowType) : bool
    {
        $merchantCore = new Merchant\Core;

        $merchant = $this->merchant;

        [$entityId, $entity] = $merchantCore->fetchWorkflowData($workflowType, $merchant);

        $actions = (new Action\Core())->fetchOpenActionOnEntityOperation(
            $entityId,
            $entity,
            Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::PERMISSION]);

        $actions = $actions->toArray();

        // If there are any action in progress
        return (empty($actions) === false);
    }

    /**
     * @return bool
     */
    public function getWebsiteStatus()
    {
        $type = Constants::ADDITIONAL_WEBSITE;

        return $this->openWorkflowExists($type);
    }

    public function getBankAccount($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $ba = $this->repo->bank_account->getBankAccount($merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        return $ba->toArray();
    }

    public function getOwnBankAccount()
    {
        $ba = $this->repo->bank_account->getBankAccount($this->merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        return $ba->toArrayPublic();
    }

    public function generateTestBankAccounts()
    {
        $merchants = $this->repo->merchant->fetchMerchantWhereTestBankIsNull();
        $fetched   = $merchants->count();

        $core = new BankAccount\Core;

        $count = 0;

        foreach ($merchants as $merc)
        {
            $core->createTestBankAccount($merc);
            $count++;
        }

        return ['fetched' => $fetched, 'processed' => $count];
    }

    public function getBanks($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $banks = (new Methods\Core)->getEnabledAndDisabledBanks($merchant);

        return $banks;
    }

    public function getEnabledBanks()
    {
        $methods = (new Methods\Core)->getEnabledAndDisabledBanks($this->merchant);

        return $methods['enabled'];
    }

    public function getOrgDetails(string $id): array
    {
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations($id, [CE::ORG]);

        $org = $merchant->org->toArrayPublic();

        $org[Org\Entity::PRIMARY_HOST_NAME] = $merchant->org->getPrimaryHostName();

        return $org;
    }

    public function setPaymentBanks($id, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $enabledDisabledBanks = (new Methods\Core)->setPaymentBanksForMerchant(
            $merchant, $input);

        $this->logActionToSlack($merchant, SlackActions::ASSIGN_BANKS);

        return $enabledDisabledBanks;
    }

    public function getFeeBearer()
    {
        $feeBearer = $this->merchant->isFeeBearerCustomer();

        return $feeBearer;
    }

    public function getMerchantDataForSegmentAnalysis()
    {

        $merchant = $this->app['basicauth']->getMerchant();

        $merchantDetails = $merchant->merchantDetail;

        /*
         * removing it temporarily
         *
        $firstTransactionTimeStamp = $this->repo->useSlave(function () use ($merchant)
        {
            return $this->repo->payment->getMerchantFirstAuthorizedPaymentTimeStamp($merchant->getId());
        });
        */

        $firstTransactionTimeStamp = null;

        $data = $this->getDataFromDruid($merchant->getId());

        return [
            self::SEGMENT_DATA_USER_BUSINESS_CATEGORY          => $merchantDetails->getBusinessCategory(),
            self::SEGMENT_DATA_ACTIVATION_STATUS               => $merchantDetails->getActivationStatus(),
            self::SEGMENT_DATA_MCC                             => $merchant->getCategory(),
            self::SEGMENT_DATA_ACTIVATED_AT                    => $merchant->getactivatedAt(),
            self::SEGMENT_DATA_USER_ROLE                       => $this->app['basicauth']->getUserRole(),
            self::SEGMENT_DATA_FIRST_TRANSACTION_TIMESTAMP     => $firstTransactionTimeStamp,
            self::SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION => $data[self::SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION] ?: null,
            self::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV          => $data[self::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV] ?: null,
            self::SEGMENT_DATA_AVERAGE_MONTHLY_GMV             => $data[self::SEGMENT_DATA_AVERAGE_MONTHLY_GMV] ?: null,
            self::SEGMENT_DATA_PRIMARY_PRODUCT_USED            => $data[ self::SEGMENT_DATA_PRIMARY_PRODUCT_USED] ?: null,
            self::SEGMENT_DATA_PPC                             => $data[self::SEGMENT_DATA_PPC] ?: null,
            self::SEGMENT_DATA_MTU                             => isset($data[self::SEGMENT_DATA_MTU]) ? $data[self::SEGMENT_DATA_MTU] : null,
            self::SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS    => $data[self::SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS] ?: null,
            self::SEGMENT_DATA_PG_ONLY                         => isset($data[self::SEGMENT_DATA_PG_ONLY]) ? $data[self::SEGMENT_DATA_PG_ONLY] : null,
            self::SEGMENT_DATA_PL_ONLY                         => isset($data[self::SEGMENT_DATA_PL_ONLY]) ? $data[self::SEGMENT_DATA_PL_ONLY] : null,
            self::SEGMENT_DATA_PP_ONLY                         => isset($data[self::SEGMENT_DATA_PP_ONLY]) ? $data[self::SEGMENT_DATA_PP_ONLY] : null
        ];
    }

    protected function getDataFromDruid($merchantId)
    {
        $query = 'select *from druid.segment_fact as merchant_data where merchant_data.merchant_details_merchant_id = \'%s\'';

        $query = sprintf($query, $merchantId);

        $content = [
            'query' => $query
        ];

        $druidService = $this->app['druid.service'];

        list($error, $data) = $druidService->getDataFromDruid($content);

        if (empty($error) === false)
        {
            return null;
        }

        if (isset($data[0]) === false)
        {
            $this->trace->info(TraceCode::DRUID_REQUEST_FAILURE, [
                'message' => self::MERCHANT_DATA_NOT_FOUND_ON_DRUID
            ]);

            return null;
        }

        return $data[0];
    }

    public function getPaymentMethods()
    {
        $formattedMethods = (new Methods\Core)->getFormattedMethods($this->merchant);

        // Licious has dependency on this field in their android app
        if ($this->merchant->getId() === '5yZ76HWrvL9g2l')
        {
            $formattedMethods['http_status_code'] = 200;
        }

        return $formattedMethods;
    }

    public function setPaymentMethods($merchantId, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        return (new Methods\Core)->setPaymentMethods($merchant, $input);
    }

    public function editMethods($input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $this->merchant->getId(),
                'input' => $input,
            ]);

        if($this->merchant->isFeatureEnabled(Feature\Constants::EDIT_METHODS) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        (new Validator)->validateInput('edit_methods', $input);

        return (new Methods\Core)->editMethods($input);
    }

    public function editMerchantMethods($mid, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'input'         => $input,
                'merchant_id'   => $mid
            ]);

        (new Validator)->validateInput('edit_merchant_methods', $input);

        $merchant =  $this->repo->merchant->findOrFailPublic($mid);

        return (new Methods\Core)->editMethods($input, $merchant);
    }

    public function patchMerchantBeneficiaryCode()
    {
        $data = (new BankAccount\Core)->updateBeneficiaryCodes();

        return $data;
    }

    /**
     * Send beneficiary registration request for ALL activated merchants
     *
     * @param array  $input
     * @param string $channel
     *
     * @return array
     */
    public function getMerchantBeneficiary(array $input, string $channel): array
    {
        $response = (new BankAccount\Beneficiary)->register($input, $channel);

        return $response;
    }

    /**
     *   Generate and Send the beneficiary file to nodal account's bank
     *   if a new merchant has been activated since
     *   if (monday)  - 3 days
     *   else         - 1 day
     *
     * @param array $input
     * @param string $channel
     *
     * @return array
     */
    public function postMerchantBeneficiary(array $input, string $channel): array
    {
        $response = (new BankAccount\Beneficiary)->registerBetweenTimestamps($input, $channel);

        return $response;
    }

    public function getCheckoutPreferences($input)
    {
        $merchant = $this->merchant;

        (new Validator)->setStrictFalse()->validateInput(Validator::PREFERENCES, $input);

        $preferences = (new Checkout)->getPreferences($merchant, $this->mode, $input);

        return $preferences;
    }

    public function getInternalCheckoutPreferences($merchantId)
    {
       $this->merchant = $this->repo->merchant->findOrFail($merchantId);

       $this->app['basicauth']->setMerchant($this->merchant);

       return $this->getCheckoutPreferences([]);
    }

    public function getAutoDisabledMethods($merchantId)
    {
        $startTime = millitime();

        $data = [];

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $category = $merchant->getCategory();

        $category2 = $merchant->getCategory2();

        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        $timeTakenInDB = millitime() - $startTime;

        $data['auto_disabled_methods']= DefaultMethodsForCategory::getDefaultDisabledMethodsForInstrumentRequestFromMerchantCategories($category, $category2);

        if($merchantDetails['activation_status']!='activated')
        {
            $data['kyc_enabled'] = false;
        }
        else
        {
            $data['kyc_enabled'] = true;
        }

        $timeTakenTotal = millitime() - $startTime;

        $this->trace->info(
            TraceCode::AUTO_DISABLED_METHODS_RESPONSE_TIME,
            [
                'time_taken_db'         => $timeTakenInDB,
                'time_taken_total'      => $timeTakenTotal,
            ]);

        return $data;
    }

    public function getGSTDetails(): array
    {
        return $this->merchant->merchantDetail->toArrayGST();
    }

    public function editGSTDetails(array $input): array
    {
        $merchantDetail = $this->merchant->merchantDetail;

        $merchantDetail->getValidator()->validateIsGSTEditable($input);

        $merchantDetail->edit($input);

        $this->repo->saveOrFail($merchantDetail);

        return $merchantDetail->toArrayGST();
    }

    /**
     * sends daily reports for all merchants that are currently live
     * Returns an array with the keys: `skipped`, and `sent`,
     * each containing the number of merchants in each category
     * @return array debug response
     */
    public function sendDailyReportForAllMerchants($input)
    {
        return (new DailyReport)->sendReportForAllMerchants($input);
    }

    public function notifyMerchantsHoliday($input)
    {
        (new Validator)->validateInput('holiday_notify', $input);

        RuntimeManager::setTimeLimit(300);

        $this->trace->info(TraceCode::MERCHANT_NOTIFY_HOLIDAY);

        $response = (new HolidayNotification)->send($input);

        $this->trace->info(TraceCode::MERCHANT_NOTIFY_HOLIDAY, $response);

        return $response;
    }

    public function updatePaymentMethods($merchantId, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantMethods = (new Methods\Core)->getPaymentMethods($merchant);

        $disabledBanks = $merchantMethods->getDisabledBanks();

        $unsupportedBanks = Netbanking::findUnsupportedBanks($disabledBanks);

        // to remove banks which are now not supported.
        $disabledBanks = array_diff($disabledBanks, $unsupportedBanks);

        if (isset($input[Methods\Entity::DISABLED_BANKS]) === true)
        {
            $inputBanks = $input[Methods\Entity::DISABLED_BANKS];

            $disabledBanks = array_unique(array_merge($disabledBanks, $inputBanks));
        }
        else if (isset($input[Methods\Entity::ENABLED_BANKS]) === true)
        {
            $inputBanks = $input[Methods\Entity::ENABLED_BANKS];

            $disabledBanks = array_unique(array_diff($disabledBanks, $inputBanks));

            unset($input[Methods\Entity::ENABLED_BANKS]);
        }

        $input[Methods\Entity::DISABLED_BANKS] = $disabledBanks;

        return (new Methods\Core)->setPaymentMethods($merchant, $input);
    }

    public function updateHdfcDebitEmiPaymentMethods($input)
    {

        $this->trace->info(
            TraceCode::UPDATE_HDFC_DEBIT_EMI_VALUE_REQUEST,
            $input
        );

        $count = $input['count'] ?? 100;

        $sucessCount = 0;
        $failureCount = 0;
        $totalCount = 0;

        // fetch methods from slave, debit_emi_provider value as null
        $methods = $this->repo->useSlave(function() use ($count)
        {
            return $this->repo->methods->fetchMethodsToUpdateHdfcDebitEmiValue($count);;

        });

        foreach ($methods as $method)
        {
            $totalCount++;

            $merchantId = $method->getMerchantId();
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $debitEmiProvider = 0;

                if ($merchant->isActivated() === true)
                {

                    $category = $merchant->getCategory();

                    $category2 = $merchant->getCategory2();

                    $autoDisabledMethods = DefaultMethodsForCategory::getDefaultDisabledMethodsForInstrumentRequestFromMerchantCategories($category, $category2);

                    if (in_array(Methods\Entity::HDFC_DEBIT_EMI, $autoDisabledMethods) === false)
                    {
                        $debitEmiProvider = 1;
                    }
                }

                $method->setAttribute(Methods\Entity::DEBIT_EMI_PROVIDERS, $debitEmiProvider);

                $this->repo->saveOrFail($method);

                $sucessCount++;
            }
            catch(\Throwable $ex)
            {
                $data = ["merchant_id" => $merchantId];

                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::UPDATE_HDFC_DEBIT_EMI_VALUE_FAILED,
                    $data);

                $failureCount++;
            }
        }

        $res = ["count"=>$count, "success"=> $sucessCount, "failure"=>$failureCount, "total"=>$totalCount];

        $this->trace->info(
            TraceCode::UPDATE_HDFC_DEBIT_EMI_VALUE_RESPONSE,
            $res
        );

        return $res;
    }

    public function updateMethodsForMultipleMerchants($input)
    {
        $startTime = millitime();

        $this->trace->info(
            TraceCode::MERCHANT_METHODS_BULK_UPDATE,
            $input);

        $this->increaseAllowedSystemLimits();

        (new Methods\Validator)->validateInput('bulk_assign_methods', $input);

        $merchantIds = $input['merchants'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->app['workflow']->skipWorkflows(function() use ($merchantId, $input)
                {
                    $this->updatePaymentMethods($merchantId, $input['methods']);
                });

                $successCount++;
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException(
                    $t,
                    Trace::ERROR,
                    TraceCode::MERCHANT_METHODS_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'input'       => $input['methods'],
                    ]);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $timeTaken = millitime() - $startTime;

        $this->trace->info(
            TraceCode::BULK_ACTION_RESPONSE_TIME,
            [
                'action'          => 'update_methods',
                'time_taken'      => $timeTaken,
            ]);

        $response['total']     = count($merchantIds);
        $response['success']   = $successCount;
        $response['failed']    = $failedCount;
        $response['failedIds'] = $failedIds;

        return $response;
    }

    public function updateMerchantsBulk(array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_BULK_UPDATE_REQUEST,
            $input
        );

        if ((isset($input['attributes']) === true) and
            (isset($input['action']) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Both Action and Attributes should not be sent.');
        }

        (new Validator)->validateInput('updateMerchantsBulk', $input);

        if (isset($input['action']) === true)
        {
            $action = $input['action'];

            (new Validator)->validateAdminPermissionForAction($action);

            if(in_array($action, Constants::BULK_RISK_ACTIONS) === true)
            {
                $mode = $this->mode ??  Mode::LIVE ;

                $variant = $this->app->razorx->getTreatment(
                    UniqueIdEntity::generateUniqueId(),
                    BulkAction\Constants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,
                    $mode);

                $this->trace->info(
                    TraceCode::MERCHANT_BULK_RISK_ACTION_RAZORX_VARIANT,
                    [
                        "razorx_variant" => $variant,
                        "mode"           => $mode,
                    ]
                );

                if(strtolower($variant) === 'on')
                {
                    // NOTE: if succesfull will throw early workflow exception
                    // if non succesfull will throw an exception
                    // (due to validation or workflow creation error)
                    (new BulkAction\Core())->handleBulkAction($input);
                }
            }
        }

        $merchantIds = $input['merchant_ids'];

        unset($input['merchant_ids']);

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                if (isset($input['attributes']) === true)
                {
                    $this->edit($merchantId, $input['attributes']);
                }
                else
                {
                    $this->action($merchantId, $input,false);
                }

                (new MerchantActionNotification())->updateNotificationTag($merchantId,$input);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function updateChannelForMultipleMerchants(array $input)
    {
        (new Validator)->validateInput('update_channel', $input);

        $this->trace->info(
            TraceCode::MERCHANT_CHANNEL_BULK_UPDATE_REQUEST,
            $input
        );

        $merchantIds = $input['merchant_ids'];

        $channel = $input['channel'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                // update channel in merchant entity
                $this->edit($merchantId, ['channel' => $channel]);

                $data = (new Transaction\BulkUpdate)->updateMultipleTransactions($merchantId, $channel);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_CHANNEL_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function updateBankAccountForMultipleMerchants(array $input)
    {
        (new Validator)->validateInput('updateBankAccount', $input);

        $this->trace->info(
            TraceCode::MERCHANT_BANK_ACCOUNT_BULK_UPDATE_REQUEST,
            $input
        );

        $merchantIds = $input['merchant_ids'];

        $bankAccount = $input['bank_account'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        $bankAccountCore = new BankAccount\Core;

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $bankAccountCore->createOrChangeBankAccount($bankAccount, $merchant);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_BANK_ACCOUNT_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function getOffers(string $mid)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $offers = (new Offer\Core)->fetchOffers($merchant);

        return $offers->toArrayAdmin();
    }

    public function getMerchantFeatures()
    {
        return (new Feature\Service)->getFeaturesForMerchantPublic($this->merchant);
    }

    public function getEarlySettlementPricingForMerchant(): array
    {
        $mid = $this->merchant->getId();

        $key1 = $mid . '_on_demand_es_pricing';
        $key2 = $mid . '_scheduled_es_pricing';

        return [
            $key1 => Cache::get('espricing:' . $key1) ?? 0.3,
            $key2 => Cache::get('espricing:' . $key2) ?? 0.2
        ];
    }

    public function getScheduledEarlySettlementPricingForMerchant(): array
    {
        $pricingPlanId = $this->merchant->getPricingPlanId();

        $scheduledPricings = $this->repo->pricing
                              ->getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId($pricingPlanId,
                                                                                           PricingFeature::ESAUTOMATIC,
                                                                                           false);

        if ($scheduledPricings->isEmpty() === true)
        {
            $pricingPlanId = $this->addDefaultScheduledEarlySettlementPricingForMerchant($pricingPlanId);

            $scheduledPricings = $this->repo->pricing
                                  ->getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId($pricingPlanId,
                                                                                               PricingFeature::ESAUTOMATIC,
                                                                                               false);
        }

        $finalSchedulePricing = new Pricing\Entity();

        foreach ($scheduledPricings as $scheduledPricing)
        {
            if($scheduledPricing->getPercentRate() >= $finalSchedulePricing->getPercentRate())
            {
                $finalSchedulePricing = $scheduledPricing;
            }
        }

        $this->trace->info(
            TraceCode::ES_PRICING_SHOWN_TO_MERCHANT,
            [
                'id' => $finalSchedulePricing->getId(),
                'percent_rate' => $finalSchedulePricing->getPercentRate()
            ]
        );

        return $finalSchedulePricing->toArrayPublic() + ['fee_bearer' => $this->merchant->getFeeBearer()];
    }

    public function addDefaultScheduledEarlySettlementPricingForMerchant($pricingPlanId)
    {
        if ($this->merchant->isPostpaid() === false)
        {
            return $this->repo->transactionOnLiveAndTest(function () use($pricingPlanId)
            {
                // Replicates plan for this merchant if it was shared
                if ($this->repo->merchant->fetchMerchantsCountWithPricingPlanId($pricingPlanId) !== 1)
                {
                    $newPlan = (new Pricing\Service())->replicatePlanAndAssign($this->merchant,
                                $this->repo->pricing->getPlanByIdOrFailPublic($pricingPlanId));

                    $this->merchant->refresh();

                    $pricingPlanId = $newPlan->getId();
                }

                $defaultScheduledEarlySettlementPricings = $this->getDefaultScheduledEarlySettlementPricing($pricingPlanId);

                foreach($defaultScheduledEarlySettlementPricings as $scheduledEarlySettlementPricing)
                {
                    $updatedPlanRule = (new Pricing\Service())->addPlanRule($pricingPlanId, $scheduledEarlySettlementPricing);
                }

                return $pricingPlanId;
            });
        }
        else
        {
            throw new Exception\LogicException('ES scheduled Default Pricing cannot be assigned to postpaid merchant.',
                                                ErrorCode::SERVER_ERROR_NO_ES_PRICING_FOR_POSTPAID_MERCHANT);
        }
    }

    public function getDefaultScheduledEarlySettlementPricing($pricingPlanId)
    {
        $scheduledEarlySettlementmethods = [
            Payment\Method::AEPS,
            Payment\Method::CARD,
            Payment\Method::CARDLESS_EMI,
            Payment\Method::EMI,
            Payment\Method::NETBANKING,
            Payment\Method::PAYLATER,
            Payment\Method::TRANSFER,
            Payment\Method::UPI,
            Payment\Method::WALLET,
        ];

        $defaultScheduledEarlySettlementPricings = [];

        foreach($scheduledEarlySettlementmethods as $method)
        {
            $pricingRule = [
                'product'             => Product::PRIMARY,
                'feature'             => PricingFeature::ESAUTOMATIC,
                'payment_method'      => $method,
                'percent_rate'        => 15,
                'amount_range_active' => 0,
                'amount_range_max'    => 0,
                'amount_range_min'    => 0,
                'fee_bearer'          => $this->merchant->getFeeBearer(),
            ];

            array_push($defaultScheduledEarlySettlementPricings, $pricingRule);
        }

        $scheduledInternaltionalRules = $this->repo->pricing
                                        ->getPricingRulesByPlanIdFeatureAndInternationalWithoutOrgId($pricingPlanId,
                                                                                                    PricingFeature::ESAUTOMATIC,
                                                                                                    true)->toArray();

        $esInternationCardRule = array_filter($scheduledInternaltionalRules, function($scheduledInternaltionalRule)
        {
            return (isset($scheduledInternaltionalRule['payment_method']) === true &&
                    $scheduledInternaltionalRule['payment_method'] === Payment\Method::CARD);
        });

        if(empty($esInternationCardRule) === true)
        {
            $cardInternationalEsRule = [
                'product'             => Product::PRIMARY,
                'feature'             => PricingFeature::ESAUTOMATIC,
                'payment_method'      => Payment\Method::CARD,
                'international'       => 1,
                'percent_rate'        => 0,
                'amount_range_active' => 0,
                'amount_range_max'    => 0,
                'amount_range_min'    => 0,
                'fee_bearer'          => $this->merchant->getFeeBearer(),
            ];

            array_push($defaultScheduledEarlySettlementPricings, $cardInternationalEsRule);
        }

        $paypalWalletEsRule = [
            'product'             => Product::PRIMARY,
            'feature'             => PricingFeature::ESAUTOMATIC,
            'payment_method'      => Payment\Method::WALLET,
            'payment_network'     => Payment\Processor\Wallet::PAYPAL,
            'percent_rate'        => 0,
            'amount_range_active' => 0,
            'amount_range_max'    => 0,
            'amount_range_min'    => 0,
            'fee_bearer'          => $this->merchant->getFeeBearer(),
        ];

        array_push($defaultScheduledEarlySettlementPricings, $paypalWalletEsRule);

        return $defaultScheduledEarlySettlementPricings;
    }

    public function getInstantRefundsPricingForMerchant(): array
    {
        // Merchant's pricing plan id
        $pricingPlanId = $this->merchant->getPricingPlanId();

        //
        // To decide whether to display pricing on the merchant dashboard -
        // There are some pricing plan variations which cannot be displayed the merchant dashboard
        // as per the current design
        // These include :
        // 1. Pricing plan has more than 6 rules for Instant Refunds
        // 2. Instant Refunds mode level pricing plan
        // 3. Pricing is not consistent across methods for Instant Refunds
        // 4. Pricing is defined on percent rate for Instant Refunds
        //
        $isCustomPricingPlan = false;

        $instantRefundsPricingRules = $this->repo->pricing->getPricingRulesByPlanIdProductAndFeatureWithoutOrgId(
            $pricingPlanId,
            Product::PRIMARY,
            PricingFeature::REFUND
        );

        // While fetching default pricing rules
        $pricingMethod = Payment\Method::CARD;

        //
        // Merchant does not have merchant specific pricing for Instant Refunds, default pricing plan is applied and
        // hence we can display default pricing plan
        //
        if ($instantRefundsPricingRules->isEmpty() === true)
        {
            $planId = Pricing\Fee::DEFAULT_INSTANT_REFUNDS_PLAN_ID;

            $merchantId = $this->merchant->getId();

            $variant = $this->app->razorx->getTreatment(
                $merchantId,
                Merchant\RazorxTreatment::INSTANT_REFUNDS_DEFAULT_PRICING_V1,
                $this->mode
            );

            //
            // Instant Refunds v2 pricing is now default - not behind a razorx anymore
            // Instant Refunds v1 Pricing is behind razorx for merchants in transition phase
            //
            if ($variant !== RefundConstants::RAZORX_VARIANT_ON)
            {
                $planId = Pricing\Fee::DEFAULT_INSTANT_REFUNDS_PLAN_V2_ID;
            }

            $instantRefundsDefaultPricingRules = $this->repo->pricing->getInstantRefundsDefaultPricingPlanForMethod(
                PricingFeature::REFUND,
                $pricingMethod,
                Product::PRIMARY,
                $planId
            );

            $finalRulesToBeFormatted = $instantRefundsDefaultPricingRules;
        }
        else
        {
            //
            // Merchant has merchant specific pricing rules -
            // we need to figure out if its a complex pricing plan which cannot be shown on merchant dashboard
            //
            [$isCustomPricingPlan, $pricingMethod] = $this->isComplexInstantRefundsPricing($instantRefundsPricingRules);

            $finalRulesToBeFormatted = $instantRefundsPricingRules->where(Pricing\Entity::PAYMENT_METHOD, $pricingMethod);
        }

        $formattedRules = $isCustomPricingPlan ? [] : $this->getFormattedInstantRefundsPricingPlan($finalRulesToBeFormatted);

        //
        // If rules are more than 6, we cannot display on the merchant dashboard as per current design,
        // hence treating it as complex / custom pricing
        //
        if (count($formattedRules) > Constants::MAX_RULES_TO_BE_DISPLAYED)
        {
            $isCustomPricingPlan = true;

            $formattedRules = [];
        }

        $result = [
            Constants::CUSTOM_PRICING => $isCustomPricingPlan,
            Constants::RULES          => $formattedRules,
        ];

        return $result;
    }

    protected function getFormattedInstantRefundsPricingPlan($instantRefundsPricingRules): array
    {
        $fieldsToExpose = [
            Pricing\Entity::AMOUNT_RANGE_MIN,
            Pricing\Entity::AMOUNT_RANGE_MAX,
            Pricing\Entity::FIXED_RATE,
        ];

        //
        // array_values - to avoid numeric keys being present in the map
        //
        $filtered = array_values($instantRefundsPricingRules->map->only($fieldsToExpose)->toArray());

        if ($this->isInstantRefundsPricingAmountRangeActive($instantRefundsPricingRules) === false)
        {
            $filtered = $this->getInstantRefundsPricingInDefaultSlabs($filtered);
        }

        return array_sort_recursive($filtered);
    }

    protected function isComplexInstantRefundsPricing($instantRefundsPricingRules)
    {
        if ($this->isInstantRefundsModeLevelPricing($instantRefundsPricingRules) === true)
        {
            return [true, null];
        }

        if ($this->isInstantRefundsPercentageRatePricing($instantRefundsPricingRules) === true)
        {
            return [true, null];
        }

        $uniqueMethods = array_unique($instantRefundsPricingRules->pluck(Pricing\Entity::PAYMENT_METHOD)->toArray());

        if ($this->isInstantRefundsDefaultMethodPricing($uniqueMethods) === true)
        {
            // If distinct - pricing is not complex
            if ($this->isInstantRefundsDefaultMethodPricingDistinct($instantRefundsPricingRules) === true)
            {
                return [false, null];
            }

            //
            // If duplicate rules are present - we may not be able to display rules on the merchant dashboard
            // Hence, complex
            //
            return [true, null];
        }

        if ($this->isInstantRefundsPricingConsistentAcrossMethods($instantRefundsPricingRules, $uniqueMethods) === true)
        {
            //
            // Pricing has been defined consistently for all the methods
            // hence picking card
            //
            return [false, Payment\Method::CARD];
        }

        //
        // Pricing is complex and cannot be displayed on the merchant dashboard
        //
        return [true, null];
    }

    /**
     * @param $instantRefundsPricingRules
     * @return bool
     */
    protected function isInstantRefundsModeLevelPricing($instantRefundsPricingRules) : bool
    {
        $modes = $instantRefundsPricingRules->pluck(Pricing\Entity::PAYMENT_METHOD_TYPE)->toArray();

        //
        // If the distinct mode is not null, it is considered as mode level pricing
        //
        if (!((count(array_unique($modes)) === 1) and
            (end($modes) === null)))
        {
            return true;
        }

        return false;
    }

    /**
     * @param $instantRefundsPricingRules
     * @return bool
     */
    protected function isInstantRefundsPercentageRatePricing($instantRefundsPricingRules) : bool
    {
        $percentRateRules = $instantRefundsPricingRules->pluck(Pricing\Entity::PERCENT_RATE)->toArray();

        //
        // If the distinct percent rate is not empty (0), it is considered as percentage rate pricing
        //
        if (!((count(array_unique($percentRateRules)) === 1) and
            (empty(end($percentRateRules)) === true)))
        {
            return true;
        }

        return false;
    }

    /**
     * @param $uniqueMethods
     * @return bool
     */
    protected function isInstantRefundsDefaultMethodPricing($uniqueMethods) : bool
    {
        //
        // If the distinct method is null, it is considered as default method all pricing
        //
        if ((count($uniqueMethods) === 1) and
            (end($uniqueMethods) === null))
        {
            return true;
        }

        return false;
    }

    /**
     * Checks if there are any duplicate rules in the default method instant refunds pricing plan
     *
     * @param $instantRefundsPricingRules
     * @return bool
     */
    protected function isInstantRefundsDefaultMethodPricingDistinct($instantRefundsPricingRules) : bool
    {
        //
        // These are the fields to compare for check for duplicacy
        //
        $fieldsToCompare = [
            Pricing\Entity::PAYMENT_METHOD_TYPE,
            Pricing\Entity::AMOUNT_RANGE_MIN,
            Pricing\Entity::AMOUNT_RANGE_MAX,
        ];

        $rulesToCompare = array_sort_recursive($instantRefundsPricingRules->map->only($fieldsToCompare)->toArray());

        $distinctRules = [];

        // Identifying distinct rules
        foreach ($rulesToCompare as $ruleToCompare)
        {
            if (in_array($ruleToCompare, $distinctRules, true) === false)
            {
                $distinctRules[] = $ruleToCompare;
            }
        }

        // Is distinct
        if (count($distinctRules) === count($rulesToCompare))
        {
            return true;
        }

        // Duplicate rules found
        return false;
    }

    /**
     * If instant refunds pricing is defined method wise, this function checks and validates
     * if pricing has been defined consistently for all the supported instant refunds methods
     *
     * @param $instantRefundsPricingRules
     * @param $uniqueMethods
     * @return bool
     */
    protected function isInstantRefundsPricingConsistentAcrossMethods($instantRefundsPricingRules, $uniqueMethods) : bool
    {
        // Fields to compare for consistency
        $fieldsToCompare = [
            Pricing\Entity::PAYMENT_METHOD_TYPE,
            Pricing\Entity::AMOUNT_RANGE_MIN,
            Pricing\Entity::AMOUNT_RANGE_MAX,
            Pricing\Entity::FIXED_RATE,
        ];

        $instantRefundSupportedMethods = Payment\Method::INSTANT_REFUND_SUPPORTED_METHODS;

        $instantRefundPricingMethods = array_merge(
            $instantRefundSupportedMethods,
            [null]
        );

        $allRules = [];

        //
        // Checking if pricing is defined for all the supported instant refunds pricing methods
        //
        if ((array_diff($uniqueMethods, $instantRefundSupportedMethods) === array_diff($instantRefundSupportedMethods, $uniqueMethods)) or
            (array_diff($uniqueMethods, $instantRefundPricingMethods) === array_diff($instantRefundPricingMethods, $uniqueMethods)))
        {
            $grouped = $instantRefundsPricingRules->groupBy(Pricing\Entity::PAYMENT_METHOD);

            foreach ($grouped as $key => $group)
            {
                $groupRules = array_sort_recursive($group->map->only($fieldsToCompare)->toArray());

                if (in_array($groupRules, array_values($allRules), true) === false)
                {
                    $allRules[$key] = $groupRules;
                }
            }

            // If there is only 1 set of rules - it means pricing has been defined consistently across all the required methods
            if (count($allRules) === 1)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $instantRefundsPricingRules
     * @return bool
     */
    protected function isInstantRefundsPricingAmountRangeActive($instantRefundsPricingRules) : bool
    {
        $amountRangeActive = $instantRefundsPricingRules->pluck(Pricing\Entity::AMOUNT_RANGE_ACTIVE)->toArray();

        if ((count(array_unique($amountRangeActive)) === 1) and
            (end($amountRangeActive) === false))
        {
            return false;
        }

        return true;
    }

    /**
     * @param $filteredRules
     * @return array
     */
    protected function getInstantRefundsPricingInDefaultSlabs($filteredRules)
    {
        $instantRefundsDefaultPricingSlabs = [
            [
                Pricing\Entity::AMOUNT_RANGE_MIN => 0,
                Pricing\Entity::AMOUNT_RANGE_MAX => 100000,
            ],
            [
                Pricing\Entity::AMOUNT_RANGE_MIN => 100000,
                Pricing\Entity::AMOUNT_RANGE_MAX => 2500000,
            ],
            [
                Pricing\Entity::AMOUNT_RANGE_MIN => 2500000,
                Pricing\Entity::AMOUNT_RANGE_MAX => 4294967295,
            ],
        ];

        $fixedRate = $filteredRules[0][Pricing\Entity::FIXED_RATE];

        $filtered = [];

        //
        // Super imposing the fixed rate into the default slabs
        //
        foreach ($instantRefundsDefaultPricingSlabs as $instantRefundsDefaultPricingSlab)
        {
            $instantRefundsDefaultPricingSlab[Pricing\Entity::FIXED_RATE] = $fixedRate;

            $filtered[] = $instantRefundsDefaultPricingSlab;
        }

        return $filtered;
    }

    /**
     * Add a merchant to OnDemandEnabledMailingList mailing lists and remove from OnDemandNotEnabledMailingList.
     *
     * @param string $merchantId
     */
    public function addMerchantToOnDemandEnabledMailingList(string $merchantId)
    {
        $merchantCore = $this->core();

        $merchant =  $this->repo->merchant->findOrFail($merchantId);

        $merchantCore->addMerchantEmailToMailingList($merchant, [Constants::LIVE_SETTLEMENT_ON_DEMAND]);

        $merchantCore->removeMerchantEmailToMailingList($merchant, [Constants::LIVE_SETTLEMENT_DEFAULT]);
    }

    /**
     * Remove a merchant from OnDemandEnabledMailingList mailing lists and add to OnDemandNotEnabledMailingList.
     *
     * @param string $merchantId
     */
    public function removeMerchantFromOnDemandEnabledMailingList(string $merchantId)
    {
        $merchantCore = $this->core();

        $merchant =  $this->repo->merchant->findOrFail($merchantId);

        $merchantCore->removeMerchantEmailToMailingList($merchant, [Constants::LIVE_SETTLEMENT_ON_DEMAND]);

        $merchantCore->addMerchantEmailToMailingList($merchant, [Constants::LIVE_SETTLEMENT_DEFAULT]);
    }

    public function getOnDemandEarlySettlementPricingForMerchant($pricingFeature = PricingFeature::PAYOUT)
    {
        // This is a wrapper over getPricingPlans to fetch payout pricing for given
        // pricingPlanId along with corresponding rules
        $pricingPlanId = $this->merchant->getPricingPlanId();

        $onDemandPricing = $this->repo->pricing
                                    ->getPricingRulesByPlanIdProductFeaturePaymentMethodOrgId($pricingPlanId,
                                                                                         Product::PRIMARY,
                                                                                         $pricingFeature,
                                                                                         Payout\Method::FUND_TRANSFER);

        // We do not expect multiple rows of primary-payout-fund_transfer for a given planId
        return $onDemandPricing;
    }

    public function updateOnDemandPricingForMerchantBeforeEnableSchedule($pricingFeature = PricingFeature::PAYOUT)
    {
        //
        // W.e.f March 2020 Product wants to provide es on demand with under 20 bps if scheduled is enabled too.
        // In case the bps value is already less than 20 then don't change
        // In case it's greater than 20 then
            // Check if the pricing plan is used by more than 1 merchant
                // If yes than replicate plan and assign new plan for the merchant
            // Update pricing plan for the merchant payout (could be old or new duplicated)
            // and update payout pricing to 15bps
        // For merchants who have payout pricing percent rate lesser than 20 already won't get the change
        //
        $onDemandPricing = $this->getOnDemandEarlySettlementPricingForMerchant($pricingFeature);

        if(empty($onDemandPricing) === false)
        {
            $onDemandPricingRuleId = $onDemandPricing->getId();

            if ($onDemandPricing->getPercentRate() < 20)
            {
                return $onDemandPricing->toArrayPublic()['percent_rate'];
            }

            $planId = $this->merchant->getPricingPlanId();

            $plan = $this->repo->pricing->getPlanByIdOrFailPublic($planId);

            // Replicates plan for this merchant if it was shared
            if ($this->repo->merchant->fetchMerchantsCountWithPricingPlanId($planId) !== 1)
            {
                // Replicate also takes care of assigning the plan to the merchant
                $newPlan = (new Pricing\Service())->replicatePlanAndAssign($this->merchant, $plan);

                $this->merchant->refresh();

                $plan = $newPlan;

                // Currently we have just one rule id for on demand
                $onDemandPricingRuleId = $this->getOnDemandEarlySettlementPricingForMerchant($pricingFeature)->getId();
            }

            $updatedPlanRule = (new Pricing\Service())->updatePlanRule($plan->getId(),
                                                    $onDemandPricingRuleId,
                                                    ['percent_rate' => 15]);

            return $updatedPlanRule['percent_rate'];
        }
        else
        {
            return null;
        }
    }

    public function enableScheduledEs(): array
    {
        $userRole = $this->repo
                         ->merchant
                         ->getMerchantUserMapping(
                            $this->merchant->getId(),
                            $this->user->getId(),
                            null,
                            Product::PRIMARY)
                         ->pivot
                         ->role;

        if (($userRole !== User\Role::ADMIN) and ($userRole !== User\Role::OWNER))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED,
                                                    'role',
                                                    $userRole);
        }

        $pricingForMerchant = $this->getScheduledEarlySettlementPricingForMerchant();

        $schedule = (new Schedule\Repository)->getScheduleByPeriodIntervalAnchorHourDelayAndType(
                                                Schedule\Period::HOURLY,
                                                1,
                                                null,
                                                0,
                                                0,
                                                ScheduleTask\Type::SETTLEMENT);

        if ($schedule === null)
        {
            throw new Exception\LogicException(
                'Schedule for Scheduled Automatic settlement was not found.',
                ErrorCode::BAD_REQUEST_UNKNOWN_SCHEDULE
            );
        }

        $scheduledTasks = (new ScheduleTask\Core)->getMerchantSettlementScheduleTasks($this->merchant, false);

        $this->repo->transactionOnLiveAndTest(function () use($schedule, $scheduledTasks, &$pricingForMerchant)
        {
            foreach ($scheduledTasks as $scheduledTask)
            {
                $input = [
                    ScheduleTask\Entity::METHOD      => $scheduledTask[ScheduleTask\Entity::METHOD],
                    ScheduleTask\Entity::TYPE        => $scheduledTask[ScheduleTask\Entity::TYPE],
                    ScheduleTask\Entity::SCHEDULE_ID => $schedule->getId()
                ];

                $this->app['workflow']->skipWorkflows(function() use ($input)
                {
                    (new ScheduleTask\Core)->createOrUpdate($this->merchant, $this->merchant, $input);
                });
            }

            // Pricing plan updates, if required, need not be blocked by workflows.
            $this->app['workflow']->skipWorkflows(function() use(&$pricingForMerchant)
            {
                $onDemandPayoutPricing = $this->updateOnDemandPricingForMerchantBeforeEnableSchedule();

                $settlementOndemandPricing = $this->updateOnDemandPricingForMerchantBeforeEnableSchedule(PricingFeature::SETTLEMENT_ONDEMAND);

                $pricingForMerchant['on_demand_percent_rate'] = [$onDemandPayoutPricing, $settlementOndemandPricing] ;
            });

            $this->addOrRemoveMerchantFeatures([
                                                    Entity::FEATURES => [
                                                        Feature\Constants::ES_AUTOMATIC => 1
                                                    ],
                                                    Feature\Entity::SHOULD_SYNC => 1]);

            // Updating the merchant_config for merchants migrated to new settlement service
            if ($this->merchant->isFeatureEnabled(Feature\Constants::NEW_SETTLEMENT_SERVICE) === true)
            {
                (new Settlement\Core)->MigrateMerchantConfiguration($this->merchant->getId(),Settlement\Core::PAYOUT,Mode::LIVE);

                (new Settlement\Core)->MigrateMerchantConfiguration($this->merchant->getId(),Settlement\Core::PAYOUT,Mode::TEST);

            }

        });

        // All the mail sending steps are taken out of the transactionOnLiveAndTest.
        // We want the flow to not get disturbed or reverted for any issues that may happen with mailer.
        try
        {
            $this->sendMailsPostEnableScheduledEs($pricingForMerchant);
        }

        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FEATURE_ENABLE_EARLY_SETTLEMENT_MAIL_FAILED,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'user_id' => $this->user->getId()
                ]);
        }

        return ['success' => true];
    }

    public function sendMailsPostEnableScheduledEs(array $pricingForMerchant = null)
    {
        // Check merchant corresponding tags to see if it is a key account
        $tags = $this->merchant->tagNames();

        array_walk($tags, function(& $tag)
        {
            $tag = substr($tag, 0, 2);
        });
        if (in_array('KA', $tags) === true)
        {
            // For key accounts, send feature enabled mail to Capital product team
            $kamMailerData[EsEnabledNotify::TO_EMAIL] = EsEnabledNotify::KAM_MAILING_LIST_EMAILS;

            $kamMailerData[EsEnabledNotify::TO_NAME] = EsEnabledNotify::KAM_MAILING_LIST_NAMES;

            $kamMailerData[EsEnabledNotify::SUBJECT] = EsEnabledNotify::KAM_MAILER_SUBJECT;

            $kamMailerData[EsEnabledNotify::VIEW] = EsEnabledNotify::KAM_MAILER_VIEW;

            $kamMailerData[EsEnabledNotify::MERCHANT_DATA] = $this->merchant->toArrayPublic();

            $esNotifyKAMEmail = new EsEnabledNotify($kamMailerData);

            Mail::queue($esNotifyKAMEmail);
        }

        if (isset($pricingForMerchant) === true)
        {
            // Fetch all userIds which belong to the merchant and are either owner, finance or admin type
            $merchantOwnerAdminUsersCollections = (new MerchantUser\Repository)->findByRolesAndMerchantId([User\Entity::OWNER,
                                                                                                           User\Entity::ADMIN,
                                                                                                           User\Role::FINANCE],
                                                                                                           $this->merchant->getId());

            $merchantOwnerAdminUsers = array_unique($merchantOwnerAdminUsersCollections
                                                    ->pluck(User\Entity::USER_ID)
                                                    ->toArray());

            // Fetch their corresponding names and email id's
            $userNamesAndEmailsCollections = (new User\Repository)->findMany($merchantOwnerAdminUsers, [User\Entity::NAME, User\Entity::EMAIL]);

            $userNamesAndEmails = $userNamesAndEmailsCollections->pluck(User\Entity::NAME, User\Entity::EMAIL)->toArray();

            $pricingForMerchantPercentRate = number_format(floatval($pricingForMerchant[Pricing\Entity::PERCENT_RATE]) / 100, 2);

            $merchantMailerData[EsEnabledNotify::TO_EMAIL] = array_keys($userNamesAndEmails);

            // Add capital support to receiver's list
            array_push($merchantMailerData[EsEnabledNotify::TO_EMAIL], MailConstants::MAIL_ADDRESSES[MailConstants::CAPITAL_SUPPORT]);

            $merchantMailerData[EsEnabledNotify::TO_NAME] = array_values($userNamesAndEmails);

            array_push($merchantMailerData[EsEnabledNotify::TO_NAME], MailConstants::HEADERS[MailConstants::CAPITAL_SUPPORT]);

            $merchantMailerData[Pricing\Entity::PERCENT_RATE] = $pricingForMerchantPercentRate;

            $merchantMailerData[EsEnabledNotify::SUBJECT] = EsEnabledNotify::MERCHANT_MAILER_SUBJECT;

            $merchantMailerData[EsEnabledNotify::VIEW] = EsEnabledNotify::MERCHANT_MAILER_VIEW;

            $esNotifyMerchantEmail = new EsEnabledNotify($merchantMailerData);

            Mail::queue($esNotifyMerchantEmail);
        }
    }

    public function addOrRemoveMerchantFeatures(array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_UPDATE,
            $input);

        $merchant = $this->merchant;

        $shouldSync = (bool) ($input[Feature\Entity::SHOULD_SYNC] ?? false);

        $EsOnDemandFeature = (new Feature\Repository)->findByEntityTypeEntityIdAndName(
            $merchant->getEntity(),
            $merchant->getId(),
            Feature\Constants::ES_ON_DEMAND);

        $input['es_enabled'] = ($EsOnDemandFeature === null) ? false : true;

        $merchant->validateInput('feature', $input);

        $featuresToAdd = $this->getFeatureNamesToAdd($input['features']);

        $this->addFeatures($featuresToAdd, $shouldSync);

        $featuresToRemove = $this->getFeatureNamesToRemove($input['features']);

        $this->removeFeatures($featuresToRemove, $shouldSync);

        $data = (new Feature\Service)->getFeaturesForMerchantPublic($merchant);

        return $data;
    }

    /**
     * used for fetching referred merchants of a particular merchant
     */
    public function fetchReferredMerchants()
    {
        $merchantId = $this->merchant->getId();

        return $this->repo->merchant->fetchReferredMerchants($merchantId);
    }

    /**
     * Bulk add or remove tags from a list of merchant_ids
     *
     * Input:
     *
     * name = Tag_Name
     * action = insert/delete
     * merchant_ids = [array, of, ids]
     *
     * @param array $input
     *
     * @return array
     */
    public function bulkTag(array $input): array
    {
        $this->trace->info(TraceCode::MERCHANT_TAGS_BULK_REQUEST, $input);

        (new Validator)->validateInput('bulk_tag', $input);

        $merchantIds = $input['merchant_ids'];
        // Action: 'insert' or 'delete'
        $action  = $input['action'];
        $tagName = $input['name'];

        $tagFunction = $action . 'Tag';

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                //
                // Calls either:
                // $this->insertTag() or $this->deleteTag()
                //
                $this->{$tagFunction}($merchantId, $tagName);
            }
            catch (\Throwable $t)
            {
                $this->trace->error(
                    TraceCode::MERCHANT_TAGS_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'tag_name'    => $tagName
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        $result =  [
            'total_count'  => count($merchantIds),
            'failed_count' => count($failedIds),
            'failed_ids'   => $failedIds
        ];

        $this->trace->info(TraceCode::MERCHANT_TAGS_BULK_RESPONSE, $result);

        return $result;
    }

    /**
     * used for getting tags of the merchant
     * @param string $id
     */
    public function getTags($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        return $merchant->tagNames();
    }

    /**
     * used for adding tags to merchant
     * This function uses retag(), which overwrites all previous tags
     * with the ones passed in the $input array
     *
     * @param string $id
     * @param array  $input which contains the tags of the merchant
     * @param bool   $slackNotify
     *
     * @return
     */
    public function addTags($id, $input, $slackNotify = false)
    {
        return $this->core()->addTags($id, $input, $slackNotify);
    }

    /**
     * used for deleting a single tag of a merchant
     * @param string $id
     * @param string $tagName tag which has to be deleted
     */
    public function deleteTag($id, $tagName)
    {
        return $this->core()->deleteTag($id, $tagName);
    }

    /**
     * Tag a merchant for a single tag
     *
     * @param string $merchantId
     * @param string $tagName
     *
     * @return mixed
     */
    public function insertTag(string $merchantId, string $tagName)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchant->tag($tagName);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        return $merchant->tagNames();
    }

    /**
     * This function is used for updating key access of a merchant
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     */
    public function updateKeyAccess(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchant = $this->core()->updateKeyAccess($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function markGratisTransactionPostpaid($input)
    {
        $this->trace->info(
            TraceCode::GRATIS_TO_POSTPAID_INPUT,
            $input);

        $merchantIds = $input['merchant_ids'];

        $from = $input['from'];

        $successIds = [];

        $failedIds = [];

        $merchantCore = $this->core();

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $merchantCore->markGratisTransactionPostpaid($merchantId, $from);

                $successIds[] = $merchantId;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'success_ids' => $successIds,
            'failed_ids'  => $failedIds,
        ];

        $this->trace->info(
            TraceCode::GRATIS_TO_POSTPAID_RESPONSE,
            $response);

        return $response;
    }

    public function getUsers()
    {
        $merchantId = $this->merchant->getId();

        $product = $this->auth->getRequestOriginProduct();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $users = $this->core()->getUsers($merchant, $product);

        return $users;
    }

    public function getInternalUsers($merchantId, $product)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $users = $this->core()->getUsers($merchant, $product);

        return $users;
    }

    public function createBatches(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $batches = $this->core()->createBatches($merchant, $input);

        return $batches;
    }

    public function sendPayoutMailForMultipleMerchants(array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_PAYOUT_NOTIFICATION_REQUEST,
            $input
        );

        (new Validator)->validateInput('payout_mail', $input);

        $merchantsData = $input['content'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantsData as $merchantData)
        {
            try
            {
                $merchantId = $merchantData['merchant_id'];

                $email = $merchantData['email'] ?? null;

                $processed = $this->sendPayoutMail($merchantId, $email);

                if ($processed === true)
                {
                    $successCount++;
                }
                else
                {
                    $failedCount++;

                    $failedIds[] = $merchantId;
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response['total']     = count($merchantsData);
        $response['success']   = $successCount;
        $response['failed']    = $failedCount;
        $response['failedIds'] = $failedIds;

        $this->trace->info(
            TraceCode::MERCHANT_PAYOUT_NOTIFICATION_RESPONSE,
            $response
        );

        return $response;
    }

    /**
     * Return all submerchants of the master merchant (for aggregator model only)
     *
     * 1. We do not want all the aggregator merchant to download the complete report
     *    so its behind aggregator_report feature
     * 2. We will have to write the logic to fetch all its submerchants based on tags
     * 3. Currently feature will be enabled only for e-Mitra, and merchants will be hard coded.
     *
     * @return array
     */
    public function getSubmerchants(): array
    {
        $merchantId = $this->merchant->getId();

        $merchants = $this->fetchReferredMerchants();

        return array_merge([$merchantId], $merchants->pluck('id')->toArray());
    }

    protected function sendPayoutMail(string $merchantId, string $email = null)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        list($from, $to) = $this->getTimestamps();

        $processed = $this->core()->sendPayoutMail($merchant, $from, $to, $email);

        return $processed;
    }

    private function getTimestamps()
    {
        $from = Carbon::today(Timezone::IST)->getTimestamp();
        $to   = Carbon::tomorrow(Timezone::IST)->getTimestamp() - 1;

        return [$from, $to];
    }

    /**
     * Gets the feature names to be added. A feature needs to be added to merchant
     * only if the value in input is equal to the default value of the feature
     *
     * @param  array $features
     *
     * @return array
     */
    private function getFeatureNamesToAdd(array $features): array
    {
        $featureNames = [];

        foreach ($features as $name => $value)
        {
            $value = (bool) $value;

            $defaultValue = Feature\Constants::getFeatureValue(
                    Feature\Constants::$visibleFeaturesMap[$name]['feature']);

            if ($value === $defaultValue)
            {
                $featureNames[] = Feature\Constants::$visibleFeaturesMap[$name]['feature'];
            }
        }

        return $featureNames;
    }

    /**
     * Gets the feature names to be removed. A feature needs to be removed from a
     * merchant only if the value in input is opposite of the default value of the feature
     *
     * @param  array $features
     *
     * @return array
     */
    private function getFeatureNamesToRemove(array $features): array
    {
        $featureNames = [];

        foreach ($features as $name => $value)
        {
            $value = (bool) $value;

            $defaultValue = Feature\Constants::getFeatureValue(
                    Feature\Constants::$visibleFeaturesMap[$name]['feature']);

            if ($value !== $defaultValue)
            {
                $featureNames[] = Feature\Constants::$visibleFeaturesMap[$name]['feature'];
            }
        }

        return $featureNames;
    }

    private function addFeatures(array $featureNames, bool $shouldSync = false)
    {
        $merchant = $this->merchant;

        if (count($featureNames) > 0)
        {
            $featureParams = [
                Feature\Entity::ENTITY_ID    => $merchant->getId(),
                Feature\Entity::ENTITY_TYPE  => 'merchant',
                Feature\Entity::NAMES        => $featureNames,
                Feature\Entity::SHOULD_SYNC  => $shouldSync
            ];

            (new Feature\Service)->addFeatures($featureParams);
        }
    }

    private function removeFeatures($featureNames, bool $shouldSync = false)
    {
        $merchant = $this->merchant;

        $entityId = $merchant->getId();

        foreach ($featureNames as $featureName)
        {
            $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                Feature\Constants::MERCHANT,
                $entityId,
                $featureName);

            if ($feature !== null)
            {
                $this->repo->feature->deleteAndSyncIfApplicableOrFail($feature, $shouldSync);
            }
        }
    }

    public function getMerchantDetails()
    {
        $data = [];

        /**
         * Merchant needs to be set using X-Razorpay-account header.
         * Setting Merchant in header validates admin access to
         * that merchant in admin access middleware.
         */
        if (empty($this->merchant) === false)
        {
            $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
                $this->merchant->getId(), ['methods', Entity::GROUPS, Entity::ADMINS]);

            // Merchant to array public
            $data = $merchant->toArrayPublic();

            // Merchant confirmed details
            $data['confirmed'] = $this->getMerchantConfirmed($merchant);

            $data['balance_configs'] = (new BalanceConfigService)->getMerchantBalanceConfigs();

            // Fetch formatted merchant details.
            $data['merchant_details'] = (new Detail\Service)->getMerchantDetailsForAdmin();

            $data['is_inheritance_parent']  =  $merchant->isInheritanceParent();

            $data['tags'] = $merchant->tagNames();

            // Fetch method specific custom_text.Doing only for cred now.
            $methods = $this->merchant->getMethods();

            if (empty($methods) === false)
            {
                (new Methods\Core)->addCustomTextForCredIfApplicable($this->merchant, $methods,$data['methods']);
            }
        }

        return $data;
    }

    /**
     * returns merchant info along with merchant details
     */
    public function internalGetMerchant($merchantId)
    {
        $data = [];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $data[EntityConstants::MERCHANT] = $merchant->toArrayPublic();

        $merchantDetail = $merchant->merchantDetail;

        $data[EntityConstants::MERCHANT_DETAIL] = isset($merchantDetail) === true ? $merchantDetail->toArrayPublic() : [];

        $supportInformation = (new PayoutLinkService())->getMerchantSupportSettings($merchant);

        $data[self::SUPPORT_DETAILS] = $supportInformation;

        $data[EntityConstants::MERCHANT][EntityConstants::FEATURE] = $merchant->getEnabledFeatures();
        $data[EntityConstants::MERCHANT][EntityConstants::METHODS] = $this->repo->methods->getMethodsForMerchant($merchant);

        $businessDetails = (new BusinessDetail\Service())->fetchBusinessDetailsForMerchant($merchantId);

        $data[BusinessDetail\Entity::WEBSITE_DETAILS] = $businessDetails->getWebsiteDetails();

        return $data;
    }

    public function fetchEligiblePricingPlansAndUpdateCorporatePricingRule(int $limit)
    {
        $successCount = 0;
        $failedCount = 0;
        $total = 0;
        $failedIds = [];
        $this->increaseAllowedSystemLimits();

        $planIds = (new Pricing\Core)->fetchEligiblePlansWithMissingCorporateRule($limit);

        $this->trace->info(
            TraceCode::PRICING_PLAN_IDS_FETCH,
            [
                'count'   => count($planIds),
                'planIds' => $planIds,
            ]);

        // increase system timeout
        foreach ($planIds as $planId)
        {
            $total++;
            $this->trace->info(
                TraceCode::PRICING_UPDATE_START,
                [
                    'plan_id' => $planId
                ]);
            try
            {
                $this->repo->transactionOnLiveAndTest(function() use ($planId)
                {
                    $plan = $this->repo->pricing->getPricingPlanById($planId);

                    $data = $plan->toArray();

                    $orgId = $data[0]['org_id'];

                    $rule =  [
                        'payment_method'            => 'card',
                        'fixed_rate'                =>  0,
                        'type'                      => 'pricing',
                        'feature'                   => 'payment',
                    ];

                    // for all card rules with type x and subtype null, add a rule with type business
                    // if network level rule exists, add a network rule with type business as well

                    $rulesWithTypeCreditNull = $this->getCardRuleWithTypeAndSubtype($data,"credit", null);
                    // if count of rule with type credit and sub type null is zero, copy
                    if (count($rulesWithTypeCreditNull) !== 0)
                    {
                        $this->addRuleForType($plan, $rule, "credit", $orgId, $rulesWithTypeCreditNull);
                    }

                    $rulesWithTypeDebitNull = $this->getCardRuleWithTypeAndSubtype($data, "debit", null);

                    if (count($rulesWithTypeDebitNull) !== 0)
                    {
                        $this->addRuleForType($plan, $rule, "debit", $orgId, $rulesWithTypeDebitNull);
                    }

                    $rulesWithTypePrepaidNull = $this->getCardRuleWithTypeAndSubtype($data, "prepaid", null);

                    if (count($rulesWithTypePrepaidNull) !== 0)
                    {
                        $this->addRuleForType($plan, $rule, "prepaid", $orgId, $rulesWithTypePrepaidNull);
                    }

                    $rulesWithTypeNullNull = $this->getCardRuleWithTypeAndSubtype($data,null, null);

                    $this->addRuleForType($plan, $rule, null, $orgId, $rulesWithTypeNullNull);

                    $this->trace->info(
                        TraceCode::PRICING_UPDATE_FINISH,
                        [
                            'planId' => $planId
                        ]);
                });

                $successCount++;
            }
            catch (\Throwable $ex)
            {
                $failedIds[] = $planId;
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::PRICING_BULK_UPDATE_EXCEPTION, ['plan_id' => $planId]);
                $failedCount++;
            }
        }

        $this->trace->info(
            TraceCode::PRICING_UPDATE_FINISH_ALL,
            ["success"=> $successCount, "failed"=> $failedCount,  "total" => $total, "failedIds" => $failedIds]);

        return ["success"=> $successCount, "failed"=> $failedCount,  "total" => $total, "failedIds" => $failedIds];
    }

    protected function addRuleForType($plan, $newPricingRule, $type, $orgId, array $existingPricingRulesWithTypeNull = [])
    {
        $newPricingRule['percent_rate'] = 300;

     //   $rule['international'] = $rule['international'] ? '1' : '0';

        $newPricingRule['payment_method_type'] = $type;

        $newPricingRule['payment_method_subtype'] = 'business';

        $addedRules = 0;

        for($i = 0; $i<count($existingPricingRulesWithTypeNull); $i++)
        {
            $paymentNetwork =  $existingPricingRulesWithTypeNull[$i]['payment_network'];

            if($paymentNetwork === null || ($paymentNetwork === 'MC' || $paymentNetwork === 'VISA' || $paymentNetwork === 'RUPAY'))
            {
                $newPricingRule['payment_network'] = $paymentNetwork;

                (new Pricing\Core())->addPlanRule($plan, $newPricingRule, $orgId);

                if($paymentNetwork === null)
                {
                    $addedRules++;
                }
            }
        }

        if($type === null && $addedRules === 0)
        {
            // atleast one card null business null rule is added
            (new Pricing\Core())->addPlanRule($plan, $newPricingRule, $orgId);
        }
    }

    protected function getCardRuleWithTypeAndSubtype(array $rules, $type, $subtype)
    {
        $data = [];
        foreach ($rules as $rule)
        {
            if(($rule['product'] !== 'primary') or ($rule['feature'] !== 'payment') or ($rule['type'] !== 'pricing'))
            {
                continue;
            }

            if($rule['payment_method'] !== 'card'){
                continue;
            }

            if($rule['international'] !== false){
                continue;
            }

            if($rule['payment_method_type'] !== $type){
                continue;
            }

            if($rule['payment_method_subtype'] !== $subtype){
                continue;
            }


            $data[] = $rule;
        }

        return $data;
    }

    /**
     * returns merchant's submissionDate
     */
    public function internalGetMerchantSubmissionDate($merchantId)
    {
        (new Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        $detailService = new Detail\Service();

        return ['first_l2_submission_timestamp' => ($detailService)->getFirstL2SubmissionDate()];
    }
    public function getRejectionReasons($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $currentActivationState = $merchant->currentActivationState();

        if ($currentActivationState === null)
        {
            return (new Base\PublicCollection())->toArrayPublic();
        }

        return $currentActivationState->rejectionReasons()->get()->toArrayPublic();
    }

    public function sendMerchantEmail($merchantId, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_SEND_EMAIL_REQUEST,
            $input
        );

        (new Validator)->validateInput(self::MERCHANT_MAIL, $input);

        $emailType = $input["type"];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $data = $input["data"];

        $data['contact_name'] =  $merchant->getName();

        $data['contact_email'] = $merchant->getEmail();

        $this->trace->info(
            TraceCode::MERCHANT_SEND_EMAIL_REQUEST,
            $data
        );

        switch ($emailType)
        {
            case Constants::MERCHANT_INSTRUMENT_STATUS_UPDATE:

                (new Validator)->validateInput(Constants::INSTRUMENT_STATUS_UPDATE_MERCHANT_MAIL, $data);

                $mail = new StatusNotify($data);

                break;

            default:
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_EMAIL_TYPE,
                    null,
                    [
                        'type' => $emailType,
                    ]);
        }

        Mail::queue($mail);

        return ['success' => true];
    }
    /**
     * returns merchant id, name and website
     */
    public function getMerchantBulk($input)
    {
        $ids = $input["ids"];

        $merchants = $this->repo->merchant->findMany($ids, [Entity::ID, Entity::NAME, Entity::WEBSITE]);

        $data = $merchants->toArrayPublic();

        return $data;
    }
    /**
     * Will provide if merchant is confirmed or not.
     *
     * @param  $merchant
     * @return bool
     */
    public function getMerchantConfirmed($merchant)
    {
        $parentId = $merchant->getParentId();

        // Market place sub accounts are confirmed.
        if (empty($parentId) === false)
        {
            return true;
        }
        else
        {
            $owner = $this->core()->getMerchantConfirmedOwner($merchant);

            // True if an confirmed owner is present.
            return !empty($owner);
        }
    }

    /**
     * Sends a mail to the merchant when an action is taken
     * on oauth access to his account
     * In case of tally auth application, sends an otp via mail
     *
     * @param array $input
     * @param string $type
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function sendOAuthNotification(array $input, string $type): array
    {
        if($type === 'tally_auth_otp')
        {
            return $this->sendTallyAuthOTPMail($input, $type);
        }
        else
        {
            return $this->sendOAuthMail($input, $type);
        }
    }

    /**
     * Sends a mail to the merchant when an action is taken
     * on oauth access to his account
     *
     * @param array $input
     * @param string $type
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    protected function sendOAuthMail(array $input, string $type): array
    {
        $this->trace->info(
            TraceCode::SEND_OAUTH_MAIL_REQUEST,
            [
                'type'  => $type,
                'input' => $input
            ]);

        (new Validator)->validateInput(self::OAUTH_MAIL, $input);

        $merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

        $user     = $this->repo->user->findOrFail($input[User\Entity::USER_ID]);

        $client   = (new OAuthClient\Repository)->findOrFail($input[OAuthToken\Entity::CLIENT_ID]);

        $mailer   = $this->getOAuthMailerClassByType($type);

        $data = [
            'merchant'    => $merchant->toArrayPublic(),
            'user'        => $user->toArrayPublic(),
            'application' => $client->application->toArrayPublic(),
        ];

        Mail::queue((new $mailer($data)));

        $this->sendCompetitorAppAuthorizedEmail($merchant, $client);

        return ['success' => true];
    }

    /**
     * Sends an email to support team informing them that a merchant has authorized
     * an application owned by a competitor like Juspay.
     *
     * @param Entity             $merchant
     * @param OAuthClient\Entity $client
     */
    protected function sendCompetitorAppAuthorizedEmail(Entity $merchant, OAuthClient\Entity $client)
    {
        $application = $client->application;

        if ($this->shouldSendCompetitorAppAuthorizedEmail($merchant, $application) === false)
        {
            return;
        }

        $type = 'competitor_app_authorized';

        $mailer = $this->getOAuthMailerClassByType($type);

        $data = [
            'merchant'    => [
                Entity::ID            => $merchant->getId(),
                Entity::NAME          => $merchant->getName(),
                Entity::WEBSITE       => $merchant->getWebsite(),
                Entity::BILLING_LABEL => $merchant->getBillingLabel(),
            ],
            'application' => [
                OAuthApplication\Entity::NAME => $application->getName(),
            ]
        ];

        Mail::queue((new $mailer($data)));
    }

    /**
     * Sends an OTP via mail to the merchant
     * on tally auth integration request to his account
     *
     * @param array $input
     * @param string $type
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    protected function sendTallyAuthOTPMail(array $input, string $type): array
    {
        (new Validator)->validateInput(self::TALLY_AUTH_OTP_MAIL, $input);

        $this->trace->info(
            TraceCode::SEND_TALLY_AUTH_OTP_MAIL_REQUEST,
            [
                'type'          => $type,
                'merchant_id'   => $input[Entity::MERCHANT_ID],
                'user_id'       => $input[User\Entity::USER_ID],
                'client_id'     => $input[OAuthToken\Entity::CLIENT_ID]
            ]);

        $merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

        $user     = $this->repo->user->findOrFail($input[User\Entity::USER_ID]);

        $client   = (new OAuthClient\Repository)->findOrFail($input[OAuthToken\Entity::CLIENT_ID]);

        $mailer   = $this->getOAuthMailerClassByType($type);

        $logoUrl = null;

        if (empty($client->application['logo_url']) === false)
        {
            $cdnName = $this->app->environment() === Environment::PRODUCTION ? 'cdn' : 'betacdn';

            // Constructing the cdn url for logo. We save multiple sizes of logo, using large here by adding the `_large` after the id.
            $logoUrl = 'https://' . $cdnName . '.razorpay.com' . preg_replace('/\.([^\.]+$)/', '_large.$1', $client->application['logo_url']);
        }

        $data = [
            'merchant'    => [
                'name' => $merchant['name'],
                'id'   => $merchant['id']
            ],
            'application' => [
                'name'     => $client->application['name'],
                'logo_url' => $logoUrl
            ],
            'user'        => [
                'name'  => $user['name']
            ],
            'otp'         => $input['otp'],
            'email'       => $input['email']
        ];

        Mail::queue((new $mailer($data)));

        return ['success' => true];
    }

    /**
     * @param Entity                  $merchant
     * @param OAuthApplication\Entity $app
     *
     * @return bool
     */
    protected function shouldSendCompetitorAppAuthorizedEmail(
        Entity $merchant,
        OAuthApplication\Entity $app): bool
    {
        // Do not send the email if the application is not a competitor to us
        if (in_array($app->getId(), Feature\Type::S2S_APPLICATION_IDS) === false)
        {
            return false;
        }

        // Do not send the email if the merchant has already authorized the app before
        $appAuthorized = $this->repo
                              ->merchant_access_map
                              ->findMerchantAccessMapOnEntityId($merchant->getId(),
                                                                $app->getId(),
                                                                AccessMap\Entity::APPLICATION);

        if ($appAuthorized !== null)
        {
            return false;
        }

        return true;
    }

    /**
     * Returns OAuth mailer class name by event type. Also validates that
     * the same exists. If not throws a bad request exception.
     *
     * @param string $type
     *
     * @return string
     *
     * @throws Exception\BadRequestException
     */
    protected function getOAuthMailerClassByType(string $type): string
    {
        $mailer = 'RZP\\Mail\\OAuth\\' . studly_case($type);

        if (class_exists($mailer) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OAUTH_MAIL_TYPE,
                null,
                [
                    'type' => $type,
                ]);
        }

        return $mailer;
    }

    public function fetchAnalytics(array $input): array
    {
        $input = (new Core())->processMerchantAnalyticsQuery($this->merchant->getId(), $input);

        return $this->app['eventManager']->query($input);
    }

    /**
     * Creates submerchant User and associates with the submerchant as owner.
     *
     * @param string $merchantId
     * @param array  $input
     *
     * @return array
     */
    public function createSubMerchantUser($merchantId, array $input): array
    {
        /** @var Entity $subMerchant */
        $subMerchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $input[User\Entity::MERCHANT_ID] = $this->merchant->getId();

        $input[User\Entity::EMAIL] = $this->validateAndGetEmailInput($subMerchant, $this->merchant, $input);

        $this->validateAggregatorSubMerchantRelation($subMerchant, $this->merchant);

        (new Validator)->validateInput('createSubMerchantUser', $input);

        unset($input[User\Entity::MERCHANT_ID]);

        list($subMerchantUser, $createdNew) =
            $this->createOrFetchUserAndAttachMerchant($subMerchant, $input[User\Entity::EMAIL]);

        // Sends Account linked communication emails to users.
        (new User\Service)->sendAccountLinkedCommunicationEmail($subMerchantUser, $subMerchant, $createdNew);

        $subMerchantUser = $subMerchantUser->toArrayPublic();

        return $subMerchantUser;
    }

    /**
     * In case of partners, we create the sub-merchant's user in case the email
     * is different from partner's. There might be some rare cases where the user
     * with the provided email already exists, in which case we would want to attach
     * that user to the sub-merchant created as owner. Same can happen when trying to
     * create login for a submerchant in the old aggregator model where we will
     * just attach the user found instead of creating one.
     *
     * @param Entity $subMerchant
     * @param string $email
     * @param string|null $product
     *
     * @return array
     */
    public function createOrFetchUserAndAttachMerchant(Entity $subMerchant, string $email, string $product = null): array
    {
        $created = false;

        /** @var User\Entity $subMerchantUser */
        $subMerchantUser = $this->repo->user->getUserFromEmail($email);

        if (empty($subMerchantUser) === true)
        {
            $subMerchantUser = $this->createUserAndAttachMerchant($subMerchant, $email, $product);

            $created = true;
        }
        else
        {
            $this->core()->attachSubMerchantOwner($subMerchantUser->getId(), $subMerchant, $product);
        }

        return [$subMerchantUser, $created];
    }

    protected function createUserAndAttachMerchant(Entity $subMerchant, string $email, string $product = null): User\Entity
    {
        $userData = $this->formatUserCreationData($email, $subMerchant);

        $subMerchantUser = (new User\Core)->create($userData);

        $this->core()->attachSubMerchantOwner($subMerchantUser->getId(), $subMerchant, $product);

        return $subMerchantUser;
    }

    /**
     * In case of `create login` in the old aggregator flow, the email is taken from
     * the sub-merchant for creating an owner for the account.
     * In case of partner type aggregator the email comes in input.
     *
     * @param  Entity $subMerchant
     * @param  Entity $partnerMerchant
     * @param  array $input
     *
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateAndGetEmailInput(Entity $subMerchant, Entity $partnerMerchant, array $input)
    {
        if (empty($input[User\Entity::EMAIL]) === true)
        {
            return $subMerchant->getEmail();
        }

        $isAggregatorPartner = $partnerMerchant->isAggregatorPartner();

        $isFullyManagedPartner = $partnerMerchant->isFullyManagedPartner();

        $subEmailIsSameAsPartner = ($subMerchant->getEmail() === $partnerMerchant->getEmail());

        $subMerchantHasLessThanTwoOwners = ($subMerchant->owners()->count() < 2);

        $inviteEmailSameAsSelf = ($input[User\Entity::EMAIL] === $subMerchant->getEmail());

        //
        // In case of a partner of type `aggregator` or `fully_managed` having created a sub-merchant
        // without providing email explicitly, we want to provide the ability to create
        // an owner for the sub-merchant, with an email, later.
        // In both old aggregator and partners flow, we never expect the total number of
        // owners for a merchant to be greater than 2 (1 for partner and 1 for sub-merchant).
        //
        // If the sub-merchant email is changed later then the invite may still need to be
        // sent for login but that should only be to the merchant email.
        //
        if ((($isAggregatorPartner === true) or ($isFullyManagedPartner === true)) and
            (($subEmailIsSameAsPartner or $inviteEmailSameAsSelf) === true) and
            ($subMerchantHasLessThanTwoOwners === true))
        {
            return $input[User\Entity::EMAIL];
        }

        throw new Exception\BadRequestValidationFailureException(
            PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_MERCHANT_USER);
    }

    public function formatUserCreationData(string $email, Entity $subMerchant)
    {
        $dummyPass = bin2hex(random_bytes(20));

        return [
            User\Entity::NAME                  => $subMerchant->getName(),
            User\Entity::EMAIL                 => $email,
            User\Entity::PASSWORD              => $dummyPass,
            User\Entity::PASSWORD_CONFIRMATION => $dummyPass,
            User\Entity::CAPTCHA_DISABLE       => User\Validator::DISABLE_CAPTCHA_SECRET,
        ];
    }

    public function enableEmiMerchantSubvention(string $id, string $emiPlanId, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $emiPlan = $this->repo->emi_plan->handleFindOrFailPublic($emiPlanId);

        return $this->core()->enableEmiMerchantSubvention($merchant, $emiPlan, $input);
    }

    public function getDummyRazorX()
    {
        $variant = $this->app->razorx->getTreatment('123', 'dummy', 'mode');

        return ['variant' => $variant];
    }

    protected function createSubMerchantAndSetRelationsInternal($input,
                                                                $merchant,
                                                                $isLinkedAccount,
                                                                $ownerId,
                                                                $product,
                                                                $optimizeCreationFlow = false)
    {
        $enableDashboardAccess = (bool) ($input['dashboard_access'] ?? false);

        $allowReversals = (bool) ($input['allow_reversals'] ?? false);

        $this->checkDashboardAccessForAllowReversals($enableDashboardAccess, $allowReversals);

        unset($input['dashboard_access']);

        unset($input['allow_reversals']);

        /** @var  Core */
        $merchantCore = $this->core();

        /** @var Entity */
        $subMerchant = $merchantCore->createSubMerchant($input, $merchant, $isLinkedAccount, false, $optimizeCreationFlow);

        $newUser = null;

        $createdNewUser = false;

        if ($isLinkedAccount === false)
        {
            if($optimizeCreationFlow === true)
            {
                SubMerchantTaggingJob::dispatch($this->mode, $merchant->getId(), $subMerchant->getId());
            }
            else
            {
                $merchantCore->addSubMerchantReferral($merchant, $subMerchant);
            }

            $this->attachSubMerchantOwnerIfApplicable($ownerId, $subMerchant, $merchant, $product);

            // Partner and sub-merchant are connected via partner's app,
            // this connect is used for multiple validity checks, web-hooks, etc
            $this->mapSubMerchantPartnerAppIfApplicable($merchant, $subMerchant);
        }

        // Users will be created and given access to the account in partners flow, irrespective of enable
        // dashboard access. users will be created and given access in linked accounts case only when enable
        // dashboard access is true.
        if ((($enableDashboardAccess === true) and ($isLinkedAccount === true)) or ($isLinkedAccount === false))
        {
            list($newUser, $createdNewUser) = $this->createAdditionalUserOrFetchIfApplicable($subMerchant, $merchant, $product);
        }

        $this->repo->saveOrFail($subMerchant);

        if (($allowReversals === true) and ($isLinkedAccount === true))
        {
            $featureParams = [
                Feature\Entity::ENTITY_ID    => $subMerchant->getId(),
                Feature\Entity::ENTITY_TYPE  => CE::MERCHANT,
                Feature\Entity::NAME         => Feature\Constants::ALLOW_REVERSALS_FROM_LA
            ];

            (new Feature\Core)->create($featureParams, true);
        }

        $subMerchantAdditionType = ($isLinkedAccount === true) ? Metric::MARKETPLACE : Metric::PARTNER;

        $dimensions = [Metric::SUB_MERCHANT_ADD_TYPE => $subMerchantAdditionType];

        $this->trace->count(Metric::ADD_SUB_MERCHANT, $dimensions);

        $this->trace->count(PartnerMetric::SUBMERCHANT_PRICING_PLAN_ASSIGN_TOTAL, ['partner_type' => $merchant->getPartnerType()]);

        $this->trace->count(PartnerMetric::SUBMERCHANT_USER_CREATE_TOTAL, ['submerchant_user_created' => $createdNewUser]);

        return [$subMerchant, $newUser, $createdNewUser];
    }

    protected function createSubMerchantAndSetRelations(Entity $merchant, bool $isLinkedAccount, array $input, bool $optimizeCreationFlow = false)
    {
        $ownerId = $merchant->primaryOwner()->getId();

        // TODO: Remove when dashboard stops sending
        unset($input['user_id']);

        unset($input['account']);

        $product = $input[Entity::PRODUCT] ?? Product::PRIMARY;

        if($optimizeCreationFlow === false)
        {
            list($subMerchant, $newUser, $createdNew) = $this->repo->transactionOnLiveAndTest(function() use (
                $input,
                $merchant,
                $isLinkedAccount,
                $ownerId,
                $product
            ) {
                return $this->createSubMerchantAndSetRelationsInternal($input, $merchant, $isLinkedAccount, $ownerId, $product, false);
            });
        }
        else
        {
            list($subMerchant, $newUser, $createdNew) = $this->createSubMerchantAndSetRelationsInternal($input, $merchant, $isLinkedAccount, $ownerId, $product, true);
        }

        if ($merchant->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM) === true)
        {
            $this->app->hubspot->skipMerchantOnboardingComm($subMerchant->getEmail());
        }

        // Sends email to marketplace LA dashboard enabled users.
        if ((empty($newUser) === false) and (($merchant->isMarketplace() and $isLinkedAccount) === true))
        {
            (new User\Service)->sendAccountLinkedCommunicationEmail($newUser, $subMerchant, $createdNew);
        }
        else if (((($merchant->isMarketplace() === true) and ($isLinkedAccount === true)) === false) and
                 ($merchant->canCommunicateWithSubmerchant() === true))
        {
            $this->sendSubMerchantCreationMail($subMerchant, $merchant, $product, $newUser, $createdNew);
        }

        return $this->getSubMerchantResponseArray($merchant, $subMerchant, $product);
    }

    /**
     * This returns subMerchant entity as it is in case of old-aggregator/marketplace
     * flow and subMerchant with additional partner dashboard details in case of
     * partner flow.
     *
     * @param Entity $merchant
     * @param Entity $subMerchant
     * @param string|null $product
     *
     * @return array
     */
    protected function getSubMerchantResponseArray(Entity $merchant, Entity $subMerchant, string $product = null): array
    {
        if (($merchant->isPartner() === true) and ($subMerchant->isLinkedAccount() === false))
        {
            //
            // This gets submerchant for a partner, with extra details required by partner dashboard.
            // This does not get called for pure platform partners.
            //
            $subMerchant = $this->core()->getSubmerchant($merchant, $subMerchant->getId(), [Entity::PRODUCT => $product]);

            $subMerchant = $subMerchant->toArrayPartner();
        }
        else
        {
            $subMerchant = $subMerchant->toArrayPublic();
        }

        return $subMerchant;
    }

    protected function createAdditionalUserOrFetchIfApplicable(Entity $subMerchant, Entity $merchant, string $product = null)
    {
        $subMerchantUser = null;
        $createdNew      = false;

        if ((($merchant->isPartner() === true) or ($merchant->isMarketplace() === true)) and
            ($subMerchant->getEmail() !== $merchant->getEmail()))
        {
            list($subMerchantUser, $createdNew) =
                $this->createOrFetchUserAndAttachMerchant($subMerchant, $subMerchant->getEmail(), $product);
        }

        return [$subMerchantUser, $createdNew];
    }

    protected function mapSubMerchantPartnerAppIfApplicable(Entity $merchant, Entity $subMerchant)
    {
        $this->trace->info(TraceCode::MAP_PARTNER_SUBMERCHANT_ENTITY);

        if ($merchant->isPartner() === false)
        {
            return;
        }

        $app = $this->core()->fetchPartnerApplication($merchant);

        $appId = $app->getId();

        (new AccessMap\Service)->mapOAuthApplication(
                                                $subMerchant->getId(),
                                                ['application_id' => $appId, 'partner_id' => $merchant->getId()]);
    }

    /**
     * @param  Entity $subMerchant
     * @param  Entity $aggregatorMerchant
     *
     * @throws Exception\BadRequestException
     */
    protected function validateAggregatorSubMerchantRelation(Entity $subMerchant, Entity $aggregatorMerchant)
    {
        if ($subMerchant->isLinkedAccount() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $referrer = $subMerchant->getReferrer();

        $referrerNotEmptyAndSame = (empty($referrer) === false) and ($referrer === $aggregatorMerchant->getId());

        if ($referrerNotEmptyAndSame === true)
        {
            return;
        }

        $isNonPurePlatformAggregator = $aggregatorMerchant->isNonPurePlatformPartner();

        $isMapped = $this->core()->isMerchantManagedByPartner($subMerchant->getId(), $aggregatorMerchant->getId());

        if (($isNonPurePlatformAggregator === true) and ($isMapped === true))
        {
            return;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
    }

    /**
     * This used to map submerchants to the partner in merchant_access_map entity
     * If the given partnerId is not a partner then it will mark him as a partner then proceed
     *
     * @param array $input
     *
     * @return array
     */
    public function createPartnerSubmerchantMap(array $input)
    {
        (new Validator)->validateInput('partner_submerchant_map', $input);

        $partnerType   = $input[ENTITY::PARTNER_TYPE];
        $submerchantId = $input['submerchant_id'];
        $partnerId     = $input['partner_merchant_id'];

        $partner = $this->markAsPartner($partnerId, $partnerType);

        return $this->mapSubmerchant($partner, $submerchantId);
    }

    public function fetchPartnerIntent(): array
    {
        $response = (new Settings\Service)->get(
            Constants::PARTNER,
            Constants::PARTNER_INTENT);

        $partnerIntent = $response['settings'];

        if ($partnerIntent instanceof Dictionary)
        {
            $partnerIntent = null;
        }
        else
        {
            $partnerIntent = boolVal($partnerIntent);
        }

        return [
            Constants::PARTNER_INTENT       => $partnerIntent,
        ];
    }

    /**
     * Updates partner_intent key in settings table
     * @param array $input
     *
     * @return array
     */
    public function updatePartnerIntent(array $input): array
    {
        (new Validator)->validateInput('update_partner_intent', $input);

        (new Settings\Service)->upsert(
            Constants::PARTNER,
            $input);

        // since Settings/Service->upsert does not return anything hence,
        // returning whatever was passed in input
        return [
            Constants::PARTNER_INTENT   => $input[Constants::PARTNER_INTENT],
        ];
    }

    /**
     * @param string $merchantId
     *
     * @return array
     */
    public function createPartnerAccessMap(string $merchantId): array
    {
        $partner = $this->fetchPartner();

        $submerchant = $this->fetchSubmerchant($merchantId);

        $accessMap = $this->core()->createPartnerSubmerchantAccessMap($partner, $submerchant);

        $data = [
            'status'       => 'success',
            'merchant_id'  => $merchantId,
            'partner_id'   => $partner->getId(),
            'source'       => PartnerConstants::LINKING_ADMIN
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP,
            $partner, null,
            $data);

        if ($partner->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM) === true)
        {
            $this->app->hubspot->skipMerchantOnboardingComm($submerchant->getEmail());
        }

        $this->app->hubspot->trackSubmerchantSignUp($partner->getEmail());

        $dimension = [
            'partner_type' => $partner->getPartnerType(),
            'source'       => PartnerConstants::LINKING_ADMIN
        ];

        $this->trace->count(PartnerMetric::SUBMERCHANT_CREATE_TOTAL, $dimension);

        return $accessMap;
    }

    /**
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws \Throwable
     */
    public function updatePartnerAccessMap(string $merchantId, array $input)
    {
        $partner = $this->fetchPartner();

        $submerchant = $this->fetchSubmerchant($merchantId);

        $accessMap = $this->core()->updatePartnerAccessMap($input, $partner, $submerchant);

        return $accessMap;
    }

    /**
     * @param Merchant\Entity $partner
     * @param                 $submerchantId
     *
     * @throws BadRequestException
     */
    protected function mapSubmerchant(Merchant\Entity $partner, $submerchantId): array
    {
        // Using findOrFail here will not give a proper error code in the batch output.
        $submerchant = $this->repo->merchant->find($submerchantId);

        if ($submerchant === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                Merchant\Entity::ID,
                [
                    Merchant\Entity::ID => $submerchantId
                ]);
        }

        return $this->core()->createPartnerSubmerchantAccessMap($partner, $submerchant);
    }

    /**
     * @param       $merchantId
     * @param       $partnerType
     *
     * @return Merchant\Entity
     * @throws BadRequestException
     */
    protected function markAsPartner($merchantId, $partnerType): Merchant\Entity
    {
        $partner = $this->repo->merchant->find($merchantId);

        if ($partner === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                Merchant\Entity::ID,
                [
                    Merchant\Entity::ID => $merchantId
                ]);
        }

        // Mark as partner only if the merchant is not a partner
        if ($partner->isPartner() === true)
        {
            return $partner;
        }

        if (empty($partnerType) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER);
        }

        $partner = $this->core()->markAsPartner($partner, $partnerType);

        return $partner;
    }

    public function updatePartnerType(array $input): array
    {
        (new Validator)->validateInput('update_partner_type', $input);

        $partnerType = $input[Entity::PARTNER_TYPE];

        return $this->core()->updatePartnerType($this->merchant, $partnerType);
    }

    public function backFillMerchantApplications(array $input)
    {
        $limit = $input['limit'];

        $merchantIds = $input['merchant_ids'];

        $afterId = $input['afterId'];

        return CallBackFillMerchantApps::dispatch($this->mode, $merchantIds, $limit, $afterId);
    }

    public function backFillReferredApplication(array $input)
    {
        $limit = $input['limit'];

        $merchantIds = $input['merchant_ids'];

        $afterId = $input['afterId'];

        return CallBackFillReferredApp::dispatch($this->mode, $merchantIds, $limit, $afterId);
    }

    public function getSubmerchant(string $submerchantId, array $input): array
    {
        Account\Entity::verifyIdAndSilentlyStripSign($submerchantId);

        $partner = $this->fetchPartner();

        $submerchant = $this->core()->getSubmerchant($partner, $submerchantId, $input);

        return $submerchant->toArrayPartner();
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function listSubmerchants(array $input): array
    {
        $partner = $this->fetchPartner();

        (new Validator)->validateInput('list_submerchants', $input);

        // add default params
        $input['skip'] = $input['skip'] ?? 0;
        $input['count'] = $input['count'] ?? self::DEFAULT_SUBMERCHANT_FETCH_LIMIT;

        $startTime = millitime();

        $result = $this->core()->listSubmerchants($partner, $input);

        $this->trace->histogram(Metric::FETCH_ALL_SUBMERCHANTS_LATENCY, millitime()-$startTime);

        $response = $result[0]->toArrayPartner();

        if (array_key_exists(STATIC::OFFSET, $result) === true)
        {
            $response[static::OFFSET] = $result[STATIC::OFFSET];
        }

        return $response;
    }

    /**
     * @param string $merchantId
     *
     * @throws Exception\BadRequestValidationFailureException
     * @throws \Throwable
     */
    public function deletePartnerAccessMap(string $merchantId)
    {
        $partner = $this->fetchPartner();

        $submerchant = $this->fetchSubmerchant($merchantId);

        $this->core()->deletePartnerAccessMap($partner, $submerchant);
    }

    /**
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function fetchPartner(): Entity
    {
        //
        // In the context of partners and submerchants -
        //
        // $merchant_id here corresponds to the submerchant's id. This is because the merchant_access_map entity maps
        // the submerchant id to the application entity (entity_type = application and entity_id = application_id),
        // which makes the submerchant as the primary entity in the merchant_access_map
        //
        $partner = $this->merchant;

        if ($partner === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_CONTEXT_NOT_SET,
                Entity::PARTNER_TYPE);
        }

        return $partner;
    }

    /**
     * @param string $submerchantId
     *
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function fetchSubmerchant(string $submerchantId): Entity
    {
        // The submerchant should belong to the same org as of the admin
        /** @var Entity $submerchant */
        $submerchant = $this->repo->merchant->findByIdAndOrgId($submerchantId, $this->auth->getOrgId());

        /** @var Admin\Entity $admin */
        $admin = $this->auth->getAdmin();

        // The current admin should have access to the submerchant before the mapping can be created/deleted
        $hasSubmerchantAccess = (new Group\Core)->groupCheck($admin, $submerchant);

        if ($hasSubmerchantAccess === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                Entity::MERCHANT_ID,
                [
                    'admin_id'       => $admin->getId(),
                    'partner_id'     => $submerchantId,
                    'submerchant_id' => $submerchant->getId(),
                ]);
        }

        return $submerchant;
    }

    /**
     * Edits linked account email.
     *
     * @param array $input
     *
     * @return array
     */
    public function editLinkedAccountEmail(array $input): array
    {
        $merchant = $this->merchant;

        (new Validator)->validateLinkedAccount($merchant);

        $merchant = $this->core()->editEmail($merchant, $input);

        $product = $this->auth->getRequestOriginProduct();

        $this->core()->handleLinkedAccountMerchantsUsers($merchant, $product);

        return $merchant->toArrayPublic();
    }

    public function registerBeneficiariesThroughApi(array $input, string $channel): array
    {
        $response = (new BankAccount\Beneficiary)->registerBeneficiariesThroughApi($input, $channel);

        return $response;
    }

    /**
     * Function to provide dashboard access and allow refunds access to linked accounts.
     * @param array $input
     *
     * @return array
     *
     */
    public function updateLinkedAccountConfig(array $input): array
    {
        $merchant = $this->auth->getMerchant();

        (new Validator)->validateLinkedAccount($merchant);

        if (isset($input['dashboard_access']) === true)
        {
            $this->updateLinkedAccountDashboardAccess($input, $merchant);
        }

        if (isset($input['allow_reversals']) === true)
        {
            $this->updateLinkedAccountAllowReversals($input, $merchant);
        }

        return ['success' => true];
    }

    protected function updateLinkedAccountDashboardAccess(array &$input, Merchant\Entity $merchant)
    {
        $dashboardAccess = (bool) ($input['dashboard_access'] ?? false);

        (new Validator)->validateLinkedAccountDashboardAccess($dashboardAccess, $merchant);

        $parentMerchant = $merchant->parent;

        if (($dashboardAccess === true) and ($parentMerchant->isMarketplace() === true))
        {
            if ($parentMerchant->getEmail() === $merchant->getEmail())
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_NO_EMAIL_LINKED_ACCOUNT_DASHBOARD_ACCESS);
            }

            list($newUser, $createdNew) = $this->createAdditionalUserOrFetchIfApplicable($merchant, $parentMerchant);

            if (empty($newUser) === false)
            {
                (new User\Service)->sendAccountLinkedCommunicationEmail($newUser, $merchant, $createdNew);
            }
        }
        else
        {
            // Remove allow reversals capability as well if dashboard access is revoked
            $allowReversals = $merchant->isFeatureEnabled(Feature\Constants::ALLOW_REVERSALS_FROM_LA);

            if ($allowReversals === true)
            {
                $input['allow_reversals'] = false;

                $this->updateLinkedAccountAllowReversals($input, $merchant);

                unset($input['allow_reversals']);
            }

            $this->repo->sync($merchant,  'users', []);
        }
    }

    public function updateLinkedAccountAllowReversals(array $input, Merchant\Entity $merchant)
    {
        $allowReversals = (bool) ($input['allow_reversals'] ?? false);

        (new Validator)->validateLinkedAccountReversals($allowReversals, $merchant);

        $feature = [Feature\Constants::ALLOW_REVERSALS_FROM_LA];

        if ($allowReversals === true)
        {
            $this->addFeatures($feature, true);
        }
        else
        {
            $this->removeFeatures($feature, true);
        }
    }

    /**
     * Fetches submerchant / linked / referred accounts for parent account.
     */
    public function fetchAssociatedAccounts(string $merchantId)
    {
        $associatedAccounts = [];

        $merchant = $this->repo->merchant->findorFailPublic($merchantId);

        if ($merchant->isMarketplace() === true)
        {
            // linked accounts
            $associatedAccounts = $merchant->accounts()->get()->getIds();
        }
        else if ($merchant->isPartner() === true)
        {
            // submerchant accounts
            $submerchants = ($this->core()->listSubmerchants($merchant, []))[0];
            $associatedAccounts = $submerchants->getIds();
        }
        else if ($merchant->hasAggregatorFeature() === true)
        {
            // referred accounts
            $associatedAccounts = $this->repo->merchant->fetchReferredMerchants($merchantId)->getIds();
        }

        return ['associated_accounts' => array_unique($associatedAccounts)];
    }

    /**
     * Fetch the list of all merchants the submerchant is associated with
     *
     * @param string $merchantId
     *
     * @return array
     */
    public function fetchAffiliatedPartners(string $merchantId): array
    {
        $startTime = millitime();

        $partners = $this->core()->fetchAffiliatedPartners($merchantId);

        $this->trace->histogram(Metric::AFFILIATED_PARTNERS_FETCH_LATENCY,millitime()-$startTime);

        return $partners->toArrayPublic();
    }

    /**
     * Takes Merchant from auth context and sends it to razorx.
     *
     * @param string $featureFlag
     *
     * @return array
     */
    public function getRazorxTreatment(string $featureFlag)
    {
        $merchantId = $this->merchant->getId();

        $mode = $this->mode ?? 'live';

        $result = $this->app['razorx']->getTreatment($merchantId, $featureFlag, $mode);

        $response = ['result' => $result];

        return $response;
    }

    /**
     * Takes Merchant from auth context and sends it to razorx in Bulk
     *
     * @param array $featureFlag
     *
     * @return array
     */
    public function getRazorxTreatmentUsingBulkEvaluate(array $featureFlag)
    {
        $merchantId = $this->merchant->getId();

        $mode = $this->mode ?? 'live';

        $result = $this->app['razorx']->getTreatmentBulk($merchantId, $featureFlag, $mode);

        return $result;
    }

    public function getRazorxTreatmentInBulk(array $input)
    {
        $response = [];

        $featureFlags = $input['features'] ?? "";

        if (empty($featureFlags) === false)
        {
            $timeStarted = microtime(true);

            $featureFlagArray = explode(',', $featureFlags);

            $chunkFeatureArray = array_chunk($featureFlagArray, 10);

            $resultArray = [];

            foreach ($chunkFeatureArray as $batchFeatureArray)
            {
                $trimmed_array = array_map('trim', $batchFeatureArray);

                $result = $this->getRazorxTreatmentUsingBulkEvaluate($trimmed_array);

                $resultArray = array_merge($resultArray, $result);
            }

            foreach ($resultArray as $resultValue)
            {
                $response[$resultValue['feature_flag']] = ['result' => $resultValue['result']];
            }

            $timeTaken = get_diff_in_millisecond($timeStarted);

            $this->trace->histogram(Merchant\Metric::RAZORX_BULK_EVALUATE_TIME_MS, $timeTaken);
        }

        return $response;
    }

    public function submitSupportCallRequest(array $input): array
    {
        $validator = new Validator;
        $validator->validateNowIsWorkingHour();
        $validator->validateInput(__FUNCTION__, $input);

        // Dashboard also does treatment check hence happening this is a invalid request.
        if ($this->canSubmitSupportCallRequest() === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid request.');
        }

        return $this->app->myoperator->submitSupportCallRequest($input);
    }

    public function canSubmitSupportCallRequest() : bool
    {
        $allowCallRequest = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::SUPPORT_CALL,
            $this->mode ?? 'live');

        if ($allowCallRequest !== 'on')
        {
            return false;
        }

        $isActivated = $this->merchant->isActivated();

        $product = $this->app['basicauth']->getProduct();

        $this->trace->info(
        TraceCode::SUBMIT_SUPPORT_CALL_REQUEST,
            compact('input', 'allowCallRequest', 'isActivated'));

        if ($product === Product::BANKING)
        {
            return ($isActivated === true);
        }

        if ($product === Product::PRIMARY)
        {
            return (($isActivated === false) or
                    ($this->merchant->isFundsOnHold() === true));
        }

        return false;
    }

    public function syncMerchantsToEs(array $input)
    {
        return $this->core()->syncMerchantsToEs($input);
    }

    public function bulkRegenerateBalanceIds(array $input)
    {
        $limit = (int) ($input['limit'] ?? 1000);

        $balances = $this->repo->balance->getBalances($limit);

        $failed = 0;
        $failedIds = [];
        $success = 0;
        $total = count($balances);

        $this->trace->info(
            TraceCode::MERCHANT_BALANCE_BACKFILL_REQUEST,
            [
                'merchant_ids' => $balances->pluck(Entity::MERCHANT_ID)->toArray(),
                'total'        => $total,
            ]);

        foreach ($balances as $balance)
        {
            try
            {
                $id = $balance->generateUniqueIdFromTimestamp($balance->getCreatedAt());

                $balance->setAttribute(Entity::ID, $id);

                $balance->saveOrFail();

                $success++;
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::MERCHANT_BALANCE_BACKFILL_ERROR,
                    [
                        'id' => $balance->getMerchantId(),
                    ]);

                $failed++;

                $failedIds[] = $balance->getMerchantId();
            }
        }

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'failed_ids' => $failedIds,
        ];
    }

    /**
     * Checks if sbi emi is enabled on checkout for a merchant.
     *
     * Fetches the terminal for a merchant with gateway:`emi_sbi`
     * If null is returned
     *      There is no SBI MID stored for this merchant.
     *      This merchant has not been onboarded yet. return false
     *
     * Else if there's a emi_sbi terminal which is enabled. return true.
     *
     * @return bool
     */
    public function isSbiEmiEnabled()
    {
        $merchantId = $this->merchant->getId();

        $terminal = $this->repo->terminal->getByMerchantIdAndGateway($merchantId, Payment\Gateway::EMI_SBI);

        if ((empty($terminal) === false) and
            ($terminal->isEnabled() === true))
        {
            return true;
        }
        return false;
    }

    public function isAllowedForBusinessBanking(string $merchantId)
    {
        $org = $this->repo->merchant->getMerchantOrg($merchantId);

        $businessType = $this->repo->merchant_detail->getByMerchantId($merchantId)->getBusinessType();

        return (in_array($org, \RZP\Models\Admin\Org\Constants:: ALLOW_TO_BUSINESS_BANKING, true) and
            (Merchant\Detail\BusinessType::isUnregisteredBusiness($businessType) === false));
    }

    public function switchProductMerchant($product = null)
    {
        // TODO: remove this once Yesbank issue is resolved
        $merchant = $this->auth->getMerchant();

        $isProductBanking = (($product === Product::BANKING) or
                             ($this->auth->isProductBanking()));

        $isMerchantBankingEnabled = $merchant->isBusinessBankingEnabled();

        $merchantId = $merchant->getMerchantId();

        if (($isMerchantBankingEnabled === false) and
            ($isProductBanking === true))
        {
            // Commenting this call since YesBank Moratorium is done.
            // $this->isXRegistrationBlocked(false, true);

            if ($this->isAllowedForBusinessBanking($merchantId) === false)
            {
                return;
            }
        }

        $wasBankingEnabledNow = false;
        $wasSwitchToPG = false;

        $this->repo->transactionOnLiveAndTest(function() use ($product, &$wasBankingEnabledNow, &$wasSwitchToPG)
        {

            // Add Banking Role for the current merchant User.
            Tracer::inSpan(['name' => 'product_switch.addProductSwitchRole'], function() use($product) {
                (new User\Service)->addProductSwitchRole($product);
            });


            $wasSwitchToPG = $this->auth->getRequestOriginProduct() === Product::PRIMARY;

            $merchant = $this->auth->getMerchant();

            $currentlyEnabled = $merchant->isBusinessBankingEnabled();

            $wasBankingEnabledNow = $this->enableBusinessBankingIfApplicable($merchant);

            $this->repo->saveOrFail($merchant);

            if ($wasBankingEnabledNow === true)
            {
                Tracer::inSpan(['name' => 'product_switch.captureEventOfInterestOfPrimaryMerchantInBanking'], function() use($merchant) {
                    $this->captureEventOfInterestOfPrimaryMerchantInBanking($merchant);
                });

                Tracer::inSpan(['name' => 'product_switch.addNewBankingErrorFeature'], function() use($merchant) {
                    $this->addNewBankingErrorFeature($merchant);
                });
            }

            // Commenting this call since YesBank Moratorium is done.

            // $isXRegistrationBlocked = $this->isXRegistrationBlocked($currentlyEnabled, false);

            // if ($isXRegistrationBlocked === true)
            // {
            //     return;
            // }

            Tracer::inSpan(['name' => 'product_switch.activateBusinessBankingIfApplicable'] , function() use($merchant, $wasBankingEnabledNow) {
                (new Activate)->activateBusinessBankingIfApplicable($merchant, $wasBankingEnabledNow);
            });


            // creating a user mapping for a merchant on X is equivalent to him signing up on X
            // platform, so we will check if sign up has any promotion running and will assign rewards
            Tracer::inSpan(['name' => 'product_switch.applyPromotion'], function() use($merchant, $product) {
                (new Promotion\Core)->applyPromotion($merchant, $product, Promotion\Event\Constants::SIGN_UP);
            });
        });

        // At this point the product switch has happened, and if there were exceptions it
        // wouldn't have come till here


        $this->trace->info(TraceCode::PRODUCT_SWITCH, [
            'merchant' => $merchant,
            'wasSwitchToPg' => $wasSwitchToPG,
            'wasBankingEnabledNow' => $wasBankingEnabledNow
        ]);

        if ($wasBankingEnabledNow or $wasSwitchToPG) {
            Tracer::inSpan(['name' => 'product_switch.postProductSwitchActions'] , function() use($merchant, $wasSwitchToPG, $wasBankingEnabledNow) {
                $this->postProductSwitchActions($wasSwitchToPG, $wasBankingEnabledNow, $merchant);
            });
        }

    }

    /**
     * @param Entity $merchant
     * @param array  $utmParams
     */
    protected function storeIfVisitedCaStaticPage(Entity $merchant, array $utmParams): void
    {
        $attributeCore = new Attribute\Core;

        $product = Product::BANKING;
        $group   = Attribute\Group::X_SIGNUP;
        $type    = Attribute\Type::CA_PAGE_VISITED;

        try
        {
            $caPageVisitedAttr = $attributeCore->fetch($merchant, $product, $group, $type);
        }
        catch (\Throwable $e)
        {
            $caPageVisitedAttr = null;
        }

        // don't want to rewrite in case product switch happens again.
        if ($caPageVisitedAttr === null)
        {
            $caPageVisited = ((isset($utmParams['first_page']) and ($utmParams['first_page'] === User\Constants::CA_STATIC_PAGE))
                              or (isset($utmParams['final_page']) and ($utmParams['final_page'] === User\Constants::CA_STATIC_PAGE))
                              or (isset($utmParams['website']) and ($utmParams['website'] === User\Constants::CA_STATIC_PAGE)));
            $attributeCore->create(
                [
                    Attribute\Entity::PRODUCT => $product,
                    Attribute\Entity::GROUP   => $group,
                    Attribute\Entity::TYPE    => $type,
                    Attribute\Entity::VALUE   => strval((int) ($caPageVisited)) // saving as 1/0
                ],
                $merchant
            );
        }
    }


    private function storeCampaignType(Entity $merchant, array $utmParams)
    {
        $attributeCore = new Attribute\Core;

        $product = Product::BANKING;
        $group   = Attribute\Group::X_SIGNUP;
        $type    = Attribute\Type::CAMPAIGN_TYPE;

        try
        {
            $campaignTypeAttr = $attributeCore->fetch($merchant, $product, $group, $type);
        }
        catch (\Throwable $e)
        {
            $campaignTypeAttr = null;
        }

        // don't want to rewrite in case product switch happens again.
        if ($campaignTypeAttr === null)
        {
            $campaignType = $this->findCampaignType($utmParams);

            if ($campaignType === null)
            {
                return;
            }

            $attributeCore->create(
                [
                    Attribute\Entity::PRODUCT => $product,
                    Attribute\Entity::GROUP   => $group,
                    Attribute\Entity::TYPE    => $type,
                    Attribute\Entity::VALUE   => $campaignType
                ],
                $merchant
            );
        }
    }

    private function isXRegistrationBlocked(bool $currentlyEnabled = false, bool $trace = false) :bool
    {
        $config = (new MainAdmin\Service)->getConfigKey(['key' => MainAdmin\ConfigKey::BLOCK_X_REGISTRATION]) ?? false;


        if (boolval($config) === true)
        {
            if ($trace === true)
            {
                $this->trace->info(
                    TraceCode::BLOCKING_RX_PRODUCT_SWITCH_TEMPORARILY,
                    [
                        'product'           => Product::BANKING,
                        'business_banking'  => false,
                        'config'            => $config
                    ]);
            }
            return $currentlyEnabled;
        }

        return false;
    }

    private function postProductSwitchActions(bool $wasSwitchtoPG, bool  $wasBankingEnabledNow, Merchant\Entity $merchant){

        // Keeping this for only PG -> X for now, since the current event dashboard are built with that assumption
        // need to change this once the expectation is clear.

        if ($wasBankingEnabledNow) {
            //1. Capture this Product Switch Event in the Datalake
            /** @var $diagClient DiagClient */
            $diagClient = $this->app['diag'];
            $utmParams = [];
            (new User\Service)->addUtmParameters($utmParams);

            $diagClient->trackOnboardingEvent(EventCode::PRODUCT_SWITCH, $merchant, null, $utmParams);

            // 2. store signup source information
            $this->storeRelevantPreSignUpSourceInfoForBanking($utmParams, $merchant);
        }

        //3. Send this Event to Hubspot
        /** @var HubspotClient $hubspotClient */
        $hubspotClient = $this->app->hubspot;
        $hubspotClient->trackHubspotEvent($merchant->getEmail(), [
            'product_switch' => true
        ]);
    }

    public function storeRelevantPreSignUpSourceInfoForBanking(array $utmParams, Merchant\Entity $merchant)
    {
        $this->trace->info(TraceCode::UTM_PARAMS, [
            'merchant'   => $merchant->getId(),
            'utm_params' => $utmParams
        ]);

        // if the merchant visited the CA static page (first or last) (razorpay.com/x/current-accounts/)
        // we want to show the new CA self-serve flow on dashboard. Hence, saving this information
        $this->storeIfVisitedCaStaticPage($merchant, $utmParams);

        $this->storeCampaignType($merchant, $utmParams);
    }

    public function migrationBankingVAs(array $input)
    {
        $merchantIds = $input['merchant_ids'] ?? [];
        $mode        = $input['mode'] ?? Mode::LIVE;

        $processedCount = 0;

        $illegal = [];

        $failed = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                /** @var Merchant\Entity $merchant */
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $this->trace->info(
                    TraceCode::MERCHANT_RAZORPAYX_VA_MIGRATION,
                    [
                        'merchant_id'        => $merchantId,
                        'category'           => $merchant->getCategory(),
                        'category2'          => $merchant->getCategory2(),
                        'billing_label'      => $merchant->getBillingLabel(),
                    ]);

                if ($merchant->isBusinessBankingEnabled() === false)
                {
                    $illegal[] = $merchantId;

                    continue;
                }

                (new Activate)->createBankingEntitiesForMode($merchant, $mode);

                $processedCount++;
            }
            catch (\Throwable $e)
            {
                $failed[] = $merchantId;

                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::MERCHANT_RAZORPAYX_VA_MIGRATION_FAILED);
            }
        }

        return [
            'total'     => count($merchantIds),
            'processed' => $processedCount,
            'illegal'   => $illegal,
            'failed'    => $failed
        ];
    }

    /**
     * Checks if a merchant exists with the input email
     * and if it is marked as a partner
     *
     * @param  array $input
     * @return array
     */
    public function fetchMerchantPartnerStatus(array $input)
    {
        $partnerExists = $merchantExists = false;

        (new Validator)->validateInput('merchant_partner_status', $input);

        $this->auth->setModeAndDbConnection(Mode::LIVE);

        /** @var Base\PublicCollection $merchants */
        $merchants = $this->repo->merchant->fetchByEmailAndOrgId($input[Entity::EMAIL]);

        if ($merchants->count() > 0)
        {
            $merchantExists = true;

            //
            // First entry should be partner if there is a partner
            // as we order by created_at asc.
            //

            /** @var Entity $first */
            $first = $merchants->first();

            if ($first->getPartnerType() !== null)
            {
                $partnerExists = true;
            }
        }

        $result = [CE::MERCHANT => $merchantExists, Constants::PARTNER => $partnerExists];

        $this->trace->info(
            TraceCode::MERCHANT_PARTNER_STATUS_RESPONSE,
            [
                'input'  => $input,
                'result' => $result
            ]
        );

        return $result;
    }

    protected function captureEventOfInterestOfPrimaryMerchantInBanking($merchant)
    {
        // Merchant has switched from primary product to banking product for the first time,
        // so, sending details to salesforce.

        // Putting in a try catch block so that any error here does not disrupt
        // the main flow.
        try
        {
            /** @var  $salesforceClient SalesForceClient */
            $salesforceClient = $this->app->salesforce;

            $salesforceClient->captureInterestOfPrimaryMerchantInBanking($merchant);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SALESFORCE_FAILED_TO_DISPATCH_JOB);
        }
    }

    protected function enableBusinessBankingIfApplicable(Entity $merchant)
    {
        $isBanking = $this->auth->isProductBanking();

        if (($isBanking === true) and
            ($merchant->isBusinessBankingEnabled() === false))
        {
            $this->trace->info(
                TraceCode::MERCHANT_EDIT,
                [
                    'business_banking' => $isBanking,
                ]
            );

            $merchant->setBusinessBanking(true);

            return true;
        }
        return false;
    }

    protected function addNewBankingErrorFeature(Entity $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::NEW_BANKING_ERROR) === true)
        {
            return;
        }

        $featureParams = [
            Feature\Entity::ENTITY_ID   => $merchant->getId(),
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAMES       => [Feature\Constants::NEW_BANKING_ERROR],
            Feature\Entity::SHOULD_SYNC => true
        ];

        (new Feature\Service)->addFeatures($featureParams);
    }

    /**
     * Used when partner sends a reminder mail to sub merchant for creation of password.
     * Mail is sent only to sub merchant and partner does not get any mail.
     *
     * @param string $id submerchant id.
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function sendSubmerchantPasswordResetLink(string $id)
    {
        $merchant = $this->auth->getMerchant();

        (new Validator)->validateIsPartner($merchant);

        /** @var Entity $subMerchant */
        $subMerchant = $this->repo->merchant->findOrFailPublic($id);

        $isMapped = $this->core()->isMerchantMappedToNonPurePlatformPartner($subMerchant->getId(), $merchant->getId());

        if ($isMapped === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER);
        }

        if (strtolower($subMerchant->getEmail()) === strtolower($merchant->getEmail()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL,
                Merchant\Entity::EMAIL,
                $merchant->getEmail()
            );
        }

        $subMerchantUser = $this->repo->user->getUserFromEmail($subMerchant->getEmail());

        $product = $this->auth->getRequestOriginProduct();

        $mapping = null;

        if (empty($subMerchantUser) === false)
        {
            $mapping = $this->repo->merchant->getMerchantUserMapping($subMerchant->getId(),
                                                                     $subMerchantUser->getId());
        }

        if ((empty($subMerchantUser) === true) or (empty($mapping) === true))
        {
            list($subMerchantUser, $createdNew) = $this->createAdditionalUserOrFetchIfApplicable($subMerchant,
                                                                                                 $merchant);
        }

        //
        // If user already exists, we do not send mail to the user and createNewUser (4th param in following function)
        // is false in that case. Here, for resending the mail to the user, we are passing createdNewUser as true always
        // so that user always get a mail.
        //
        $this->sendSubMerchantCreationMail($subMerchant, $merchant, $product, $subMerchantUser, true, true);

        return ['success' => true];
    }

    public function onboardMerchant(string $id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->trace->info(
            TraceCode::INITIATE_TERMINAL_ONBOARDING_REQUEST_ADMIN_ROUTE,
            [
                'merchant_id'    => $id,
                'input'          => $input,
            ]);

        (new Validator)->validateInput('onboard_merchant_input', $input);

        (new Validator)->validateInput('onboard_merchant_input_' . $input['gateway'], $input); //validate input based on gateway

        if ($input['gateway'] === Payment\Gateway::HITACHI)
        {
            return (new TerminalService)->onboardMerchant($merchant, $input, false)
            ->toArrayAdmin();
        }

        $currency = isset($input['currency_code']) ? [$input['currency_code']] : [];

        $identifiers = isset($input['identifiers']) ? $input['identifiers'] : null;

        $response = $this->app['terminals_service']->initiateOnboarding($id, $input['gateway'], $identifiers, null, $currency, $input);

        return $response;
    }

    public function applyRestrictedSettings(array $input): array
    {
        (new Validator)->validateInput('restrict_settings_merchant', $input);

        $merchantId = $input[Entity::MERCHANT_ID];

        $action = $input[Entity::ACTION];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        return $this->core()->applyRestrictedSettings($merchant, $action);
    }

    public function removeSuspendedMerchantsFromMailingList(array $input)
    {
        (new Validator)->validateInput('suspended_merchant_remove', $input);

        $merchants = $this->repo->merchant
                                ->fetchAllSuspendedMerchants($input);

        $i = 0;

        foreach ($merchants as $merchant)
        {
            $this->core()->removeMerchantEmailToMailingList($merchant, [], [], $i);

            $i++;
        }
    }

    /**
     * @param Entity $merchant
     * @param Plan $plan
     * @throws Exception\BadRequestValidationFailureException
     *
     * Ensures that all pricing rules in plan have the same feeBearer value as the merchant
     * the plan is being assigned to.
     *
     * This is not applicable in case of dynamic fee bearer.
     */
    public function validatePricingPlanForFeeBearer(Merchant\Entity $merchant, Plan $plan)
    {
        if ($merchant->isFeeBearerDynamic() === true)
        {
            return;
        }

        $merchantFeeBearer = $merchant->getFeeBearer();

        foreach ($plan as $pricing)
        {
            $pricingFeeBearer = $pricing->getFeeBearer();

            if ($pricingFeeBearer !== $merchantFeeBearer)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_PRICING_RULE_FEE_BEARER_MISMATCH,
                    'fee_bearer',
                    'The merchant is ' . $merchantFeeBearer . ' fee bearer. Cannot assign ' . $pricingFeeBearer . ' fee bearer pricing rule to merchant'
                );
            }
        }
    }

    /**
     * @return array
     * @throws Exception\BadRequestException
     */
    public function fetchReferral(): array
    {
        $merchant = $this->auth->getMerchant();

        $referrals = (new Referral\Core)->fetchMerchantReferral($merchant);

        return $this->formatReferralResponse($referrals);
    }

    /**
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function createReferral()
    {
        $merchant = $this->auth->getMerchant();

        $partner = $this->fetchPartner();

        (new Referral\Validator)->validateForReferral($partner);

        $referrals = (new Referral\Core)->createOrFetch($merchant);

        $result = $referrals[Product::PRIMARY];

        $result['referrals'] = $referrals;

        return $result;
    }

    /**
     * @param array $record
     */
    public function actionPerform(array $record)
    {
        $attribute = [];
        $settings  = [];

        $this->segregateInputFieldsAndSettings($record, $attribute, $settings);

        (new Validator)->validateInput('entity_batch_action', $settings);

        $core = CE::getEntityCoreClass($settings[Constants::ENTITY]);

        $batch_action = $settings[Constants::BATCH_ACTION];

        $function = camel_case($batch_action);

        $core->$function($settings[Entity::ID], $attribute);
    }


    /**
     * @param array $input
     *
     * @return Base\PublicCollection
     */
    public function merchantsBulkUpdate(array $input)
    {
        $response = new Base\PublicCollection();

        foreach ($input as $record)
        {
            try
            {
                $this->actionPerform($record);

                $response->push($record);
            }
            catch (Exception\BaseException $exception)
            {
                $this->setErrorAttributesToResponse($record, $exception, $response);
            }
        }

        return $response->toArrayWithItems();
    }

    /**
     * @param array                   $record
     * @param Exception\BaseException $exception
     * @param Base\PublicCollection   $response
     */
    public function setErrorAttributesToResponse(array $record, Exception\BaseException $exception, Base\PublicCollection $response)
    {
        $this->trace->traceException($exception,
                                     Trace::INFO,
                                     TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST);
        $exceptionData = [
            'error'                 => [
                Error::DESCRIPTION       => $exception->getError()->getDescription(),
                Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
            ],
            Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
        ];

        $response->push(array_merge($record, $exceptionData));
    }

    /**
     * @param array $record
     * @param array $attribute
     * @param array $settings
     */
    public function segregateInputFieldsAndSettings(array $record, array & $attribute, array & $settings)
    {
        $settings = array_only($record, Constants::$EntityBatchActionSettingParams);

        $attribute = array_diff($record, $settings);
    }

    /**
     * @return array
     */
    public function getBatchActionEntities(): array
    {
        $batchAction = (new Core())->getBatchActionEntities();

        return $batchAction;
    }

    /**
     * @return array
     */
    public function getBatchActions(): array
    {
        $batchAction = (new Core())->getBatchActions();

        return $batchAction;
    }

    public function requestInternationalProduct(array $input, bool $draft = false): array
    {
        $validator = (new Validator);

        $validator->validateInput('request_international_product', $input);

        $validator->validateMerchantForProductInternational($this->merchant);

        if ($draft === true)
        {
            return [];
        }

        $merchant = $this->core()->requestInternationalProduct($input);

        return $merchant->toArrayPublic();
    }

    /**
     * getGlobalMerchantConfigs: used to return global configs to the settlement service which are not stored in the
     * settlement service e.g. ActivationStatus, PartnerSettlementConfig, ParentDetails(for Aggregate settlement)
     *
     * @param string $mid
     * @return array
     */
    public function getGlobalMerchantConfigs(string $mid)
    {
        $merchant = $this->repo->merchant->findOrFail($mid);

        $merchantSettleToPartner = $this->core()->getPartnerBankAccountIdsForSubmerchants([$mid]);

        return [
            "active"               => $merchant->isActivated(),
            "parent"               => $this->settlementToPartner($mid),
            "partner_bank_account" => isset($merchantSettleToPartner[$mid]) ? $merchantSettleToPartner[$mid] : null,
            "pan_details"          => ($merchant->merchantDetail !== null) ? $merchant->merchantDetail->getPan() : null,
            "purpose_code"         => $merchant->getPurposeCode(),
            "business_address"     => ($merchant->merchantDetail !== null) ? $merchant->merchantDetail->getBusinessRegisteredAddressAsText(', ') : null,
        ];
    }

    public function getPersonalisedMethods($input)
    {
        $merchant = $this->merchant;

        (new Validator)->setStrictFalse()->validateInput(Validator::PERSONALISATION, $input);

        $preferredMethods = [];

        $data = (new Checkout)->getPersonalisedMethods($merchant, $this->mode, $input);

        if (isset($data['preferred_methods']) === true) {
            $preferredMethods['preferred_methods'] = $data['preferred_methods'];
        }

        return $preferredMethods;

    }

    /**
     * Fix merchant data with leading and trailing spaces
     *
     * @param PaginationEntity $paginationEntity
     * @return array
     */
    public function fixData(PaginationEntity $paginationEntity): array
    {
        (new Payout\Core)->trimPayoutPurpose($paginationEntity);

        (new FundAccount\Core)->trimBeneficiaryName($paginationEntity);

        (new FundAccount\Core)->trimAccountNumber($paginationEntity);

        (new Contact\Core)->trimContactType($paginationEntity);

        (new Contact\Core)->trimContactName($paginationEntity);

        return [
            'status'        => 'updated'
        ];
    }

    public function setDefaultLateAuthConfigForMerchant($merchant)
    {
        $defaultConfig = array(
            'capture' => "automatic",
            'capture_options' => [
                'automatic_expiry_period' => 7200,
                'refund_speed' => 'normal'
            ]
        );

        $configInput = array(
            'config' => $defaultConfig,
            'is_default' => true,
            'name' => 'late_auth_' . $merchant->getId(),
            'type' => Payment\Config\Type::LATE_AUTH,
        );

        //
        // Reset the connection to the requests original mode
        //
        $originalMode = $this->app['basicauth']->getMode();

        $this->repo->transactionOnLiveAndTest(function () use($configInput, $merchant)
        {
            try
            {
                $this->createConfigWithMode(Mode::LIVE, $configInput, $merchant);
                $this->createConfigWithMode(Mode::TEST, $configInput, $merchant);
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException($t);

                throw $t;
            }
        });

        $this->app['basicauth']->setModeAndDbConnection($originalMode);
    }

    private function createConfigWithMode(string $mode, $configInput, $merchant)
    {
        $config = (new Payment\Config\Entity())->build($configInput, 'create');

        $config['merchant_id'] = $merchant->getId();;

        (new PaymentConfig\Core())->withMerchant($merchant)->trackLateAuthConfigEvent(EventCode::PAYMENT_CONFIG_CREATION_INITIATED, $configInput, 'default');

        $this->app['basicauth']->setModeAndDbConnection($mode);

        $config->setConnection($mode);

        $config->refresh();

        $this->repo->config->save($config);
    }

    /**
     * Bootstrap stork's mid<>oauth-app-ids cache using api's access map table as source.
     *
     * @param  array $input Holds opts for source and target.
     * @return array
     */
    public function bootstrapAccessMapsCacheOfStork(array $input): array
    {
        $this->trace->info(TraceCode::BOOTSTRAP_ACCESS_MAPS_CACHE_REQUEST, $input);

        (new JitValidator)->rules(self::BOOTSTRAP_ACCESS_MAPS_CACHE_REQUEST_RULES)
            ->caller($this)->input($input)->validate();

        $source  = new AccessMap\MigrateSource;
        $target  = new AccessMap\MigrateStorkTarget;
        $migrate = new Migrate($source, $target);

        $sourceOpts = $input['source'] ?? [];
        $targetOpts = $input['target'] ?? [];

        return $migrate->migrateAsync($sourceOpts, $targetOpts, false);
    }

    public function migrateImpersonationGrants(array $input): array
    {
        $this->trace->info(TraceCode::IMPERSONATION_MIGRATE_REQUEST, $input);

        (new JitValidator)->rules(self::IMPERSONATION_ACCESS_MAPS_REQUEST_RULES)
            ->caller($this)->input($input)->validate();

        $source  = new AccessMap\MigrateImpersonationSource;
        $target  = new AccessMap\MigrateKongTarget;
        $migrate = new Migrate($source, $target);

        $sourceOpts = $input['source'] ?? [];
        $targetOpts = $input['target'] ?? [];

        return $migrate->migrateAsync($sourceOpts, $targetOpts, false);
    }

    public function partnerAccessMapBulkUpsert(array $input)
    {
        $response = new Base\PublicCollection();

        foreach ($input as $record)
        {
            $attribute = [];
            $settings = [];
            
            $this->segregateInputFieldsAndSettings($record, $attribute, $settings);

            (new Validator)->validateInput('access_map_batch', $settings);

            $batch_action = camel_case($settings[Constants::BATCH_ACTION]);

            try
            {
                $this->actionOnAccessMap($record);

                $response->push($record);

                if(Constants::BATCH_ACTION == 'submerchantLink') {
                    $this->trace->count(Metric::SUBMERCHANT_LINKING_SUCCESS_TOTAL);
                }
                else {
                    $this->trace->count(Metric::SUBMERCHANT_DELINKING_SUCCESS_TOTAL);
                }
            }
            catch (BaseException $exception)
            {
                if(Constants::BATCH_ACTION == 'submerchantLink') {
                    $this->trace->count(Metric::SUBMERCHANT_LINKING_FAILURE_TOTAL);
                }
                else {
                    $this->trace->count(Metric::SUBMERCHANT_DELINKING_FAILURE_TOTAL);
                }

                $this->setErrorAttributesToResponse($record, $exception, $response);
            }
        }

        return $response->toArrayWithItems();
    }

    /**
     * @param array $record
     *
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public function actionOnAccessMap(array $record)
    {
        $attribute = [];

        $settings = [];

        $this->segregateInputFieldsAndSettings($record, $attribute, $settings);

        (new Validator)->validateInput('access_map_batch', $settings);

        $core = CE::getEntityCoreClass($settings[Constants::ENTITY]);

        $batch_action = $settings[Constants::BATCH_ACTION];

        $function = camel_case($batch_action);

        $partner = $this->repo->merchant->find($attribute[Constants::PARTNER_ID]);

        if (empty($partner) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_ID_DOES_NOT_EXIST);
        }

        $subMerchant = $this->repo->merchant->find($attribute[Constants::MERCHANT_ID]);

        if (empty($subMerchant) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_DOES_NOT_EXIST);
        }

        return $core->$function($partner, $subMerchant);
    }

    protected function getBankAccountChangeViaWorkflowStatus($id): bool
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $oldBankAccount = $this->repo->bank_account->getBankAccount($merchant);

        if (empty($oldBankAccount) === true) {
            return false;
        }


        $actions = (new Action\Core())->fetchOpenActionOnEntityOperation(
            $oldBankAccount->getId(), $oldBankAccount->getEntity(), Permission::EDIT_MERCHANT_BANK_DETAIL);

        $actions = $actions->toArray();

        // If there are any action in progress
        if (empty($actions) === false) {
            return true;
        }

        return false;
    }

    protected function getBankAccountChangeViaPennyTestingStatus($id)
    {
        $bankAccountCore = (new BankAccount\Core);

        $merchant = $this->repo->merchant->findOrFail($id);

        return $bankAccountCore->isBankAccountUpdatePennyTestingInProgress($merchant);
    }

    public function triggerMerchantBankingAccountsWebhook($id)
    {
        $merchant = $this->repo->merchant->findOrFail($id);

        return $this->core()->triggerMerchantBankingAccountsWebhook($merchant);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(300);
    }

    public function handleSoftLimitBreachOnAutoKYC()
    {
        return (new Escalations\Core())->handleSoftLimitBreach();
    }

    public function handleHardLimitBreachOnAutoKYC()
    {
        return (new Escalations\Core())->handleHardLimitBreach();
    }

    public function handleAutoKycEscalationCron()
    {
        return (new Escalations\Core())->handleEscalationsCron();
    }

    public function updateMerchantStore(array $input)
    {
        return (new Store\Core())->updateMerchantStore($this->merchant->getId(), $input);
    }

    public function fetchMerchantStore(array $input)
    {
        return (new Store\Core())->fetchMerchantStore($this->merchant->getId(), $input);
    }

    public function handleReport(array $input)
    {
        return (new Detail\Report\Core)->sendReport($input);
    }

    public function installAppOnAppStoreForMerchant(array $input)
    {
        //Validate input
        $merchant = $this->app['basicauth']->getMerchant();

        return (new \RZP\Models\AppStore\Core())->installAppOnAppStoreForMerchant($input, $merchant);
    }

    public function getInstalledAppsOnAppStore(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        return (new \RZP\Models\AppStore\Core())->getInstallAppsForMerchant($merchant);
    }

    protected function extractSubmerchantInput(array $input)
    {
        return [
            Entity::NAME                    => $input[BatchHeader::ACCOUNT_NAME],
            Entity::EMAIL                   => $input[BatchHeader::ACCOUNT_EMAIL],
            Entity::DASHBOARD_ACCESS        => (bool) $input[BatchHeader::DASHBOARD_ACCESS],
            Entity::ALLOW_REVERSALS         => (bool) $input[BatchHeader::CUSTOMER_REFUNDS],
        ];
    }

    protected function extractBankAccountDetails(array $input)
    {
        return [
            MerchantDetail::BANK_ACCOUNT_NAME       => $input[BatchHeader::BENEFICIARY_NAME],
            MerchantDetail::BANK_ACCOUNT_NUMBER     => $input[BatchHeader::ACCOUNT_NUMBER],
            MerchantDetail::BANK_BRANCH_IFSC        => $input[BatchHeader::IFSC_CODE],
            MerchantDetail::BUSINESS_NAME           => $input[BatchHeader::BUSINESS_NAME],
            MerchantDetail::BUSINESS_TYPE           => Detail\BusinessType::getIndexFromKey($input[BatchHeader::BUSINESS_TYPE]),
            MerchantDetail::SUBMIT                  => '1',
        ];
    }

    protected function checkDashboardAccessForAllowReversals(bool $dashboardAccess, bool $allowReversals)
    {
        if (($allowReversals === true) and
            ($dashboardAccess === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_DASHBOARD_ACCESS_REQUIRED_TO_ALLOW_REVERSALS,
                null,
                null,
                PublicErrorDescription::BAD_REQUEST_DASHBOARD_ACCESS_REQUIRED_TO_ALLOW_REVERSALS
            );
        }
    }

    public function getRewardsForCheckout()
    {
        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::M2M_REWARDS_AB_TESTING,
            $this->mode
        );

        $rewards = $this->repo->merchant_reward->fetchLiveRewardByMerchantId($this->merchant->getId());

        if(empty($rewards) === true)
        {
            $response[] = ['variant' => false];

            return $response;
        }

        $response[] = ['variant' => true];

        if($variant === 'on' || $variant === 'off')
        {
            if($variant === 'off')
            {
                $response[0]['variant'] = false;
            }

            $rewardKey = array_rand($rewards);

            if(isset($rewardKey) === true)
            {
                $reward = $rewards[$rewardKey];

                $response[0]['reward_id'] = "reward_".$reward['id'];
                $response[0]['logo'] = $reward['logo'];
                $response[0]['name'] = $reward['name'];
                $response[0]['brand_name'] = $reward['brand_name'];
            }

        }

        return $response;
    }

    public function getMerchantDetailsForAccountService(string $accountId): array
    {
        $this->trace->info(TraceCode::ACS_FETCH_ACCOUNT_DETAILS, ['id' => $accountId]);

        $data = [];
        $merchant = $this->repo->merchant->findOrFailPublic($accountId);
        $merchantDetails = $this->repo->merchant_detail->findOrFailPublic($accountId);
        $stakeholders = $this->repo->stakeholder->findManyByMerchantIds([$accountId]);
        $documents = $this->repo->merchant_document->findManyByMerchantIds([$accountId]);
        $merchantEmails = $this->repo->merchant_email->getEmailByMerchantId($accountId);

        $merchantDocs = new Base\PublicCollection;
        $stakeholderDocs = new Base\PublicCollection;
        foreach ($documents as $document)
        {
            if ($document->getEntityType() === EntityConstants::STAKEHOLDER)
            {
                $stakeholderDocs->add($document);
            }
            else
            {
                $docType = $document->getDocumentType();
                $proofType = Document\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING[$docType];

                if (Document\Type::PROOF_TYPE_ENTITY_MAPPING[$proofType] === EntityConstants::STAKEHOLDER)
                {
                    $stakeholderDocs->add($document);
                }
                else
                {
                    $merchantDocs->add($document);
                }
            }
        }

        $data['merchant'] = $merchant->toArray();
        $data['merchant_details'] = $merchantDetails->toArray();
        $data['stakeholders'] = $stakeholders->toArray();

        foreach ($stakeholders as $index => $stakeholder)
        {
            $address = $this->repo->address->fetchPrimaryAddressOfEntityOfType($stakeholder, Address\Type::RESIDENTIAL);
            if (empty($address) === false)
            {
                $data['stakeholders'][$index]['addresses']['residential'] = $address->toArray();
            }
        }

        $data['stakeholder_documents'] = $stakeholderDocs->toArray();
        $data['merchant_documents'] = $merchantDocs->toArray();
        $data['merchant_emails'] = $merchantEmails->toArray();

        return $data;
    }

    /**
     * validate the product name received from input and
     * fetches the product used by a merchant for given merchant ids and product
     *
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function fetchProductUsedByMerchants(array $input)
    {
        $merchantIds = $input['merchant_ids'];

        $product = $input['product'] ?? null;

        $limit = $input['limit'] ?? null;

        // validate the product name
        (new Validator())->validateMerchantProduct($product);

        $merchantProducts = $this->core()->fetchProductUsedByMerchants($merchantIds, $product, $limit);

        return $merchantProducts;
    }

    public function getMerchantSupportOptionFlags() : array
    {
        $isActivated = $this->merchant->isActivated();

        $showCreateTicketPopup = false;

        if ($isActivated === false)
        {
            $showCreateTicketPopup = true;
        }

        $response = [
            'show_chat'                                 =>      $this->canChatOnDashboard($isActivated),
            "show_create_ticket_popup"                  =>      $showCreateTicketPopup,
        ];

        $variant  = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::SHOW_CREATE_TICKET_POPUP,
            $this->app['rzp.mode'] ?? Mode::LIVE);

        if ($variant === 'control')
        {
            return $response;
        }

        $createTicketPopup = $this->getCreateTicketPopupOptions();

        $response = array_merge($response, $createTicketPopup);

        return $response;
    }

    /**
     * @return bool
     * @throws Exception\BadRequestException
     */
    protected function canChatOnDashboard($isActivated) : bool
    {
        if ((new Freshchat\Service)->isChatEnabledNow() === false)
        {
            return false;
        }

        $merchantDetailsCore = new MerchantDetailCore;

        $merchantDetail = $merchantDetailsCore->getMerchantDetails($this->merchant);

        $isUnregistered = $merchantDetail->isUnregisteredBusiness();

        $activationFlow = null;

        if($merchantDetail->canDetermineActivationFlow())
        {
            if($isUnregistered === false)
            {
                // get partners if any
                $partners = (new Merchant\Core())->fetchAffiliatedPartners($this->merchant->getId());

                $partner = $partners->filter(function(Merchant\Entity $partner) {
                    return (($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true));
                })->first();

                $activationFlow = $merchantDetailsCore->getActivationFlow($this->merchant, $merchantDetail, $partner, false);
            }
        }

        $isWhitelisted = $activationFlow === ActivationFlow::WHITELIST;

        if ($isActivated === true)
        {
            return true;
        }
        else if ($isUnregistered === false && $isWhitelisted === true)
        {
            return true;
        }

        return  false;
    }

    protected function getCreateTicketPopupOptions() : array
    {
        $merchantDetails = $this->merchant->merchantDetail;

        if ($this->merchant->isActivated() === true)
        {
            return [
                "show_create_ticket_popup" => false,
                "cta_list"                 => [],
                "message_body"             => "",
            ];
        }

        $activationStatus = $merchantDetails->getActivationStatus();

        $activationProgress = $merchantDetails->getActivationProgress();

        $formSubmissionDate = $merchantDetails->getSubmittedAt();

        $isSubmitted = $this->merchant->merchantDetail->isSubmitted();

        $dataForPopup = $this->getDataForCreateTicketPopup($activationStatus, $activationProgress, $isSubmitted);

        $message    = ($dataForPopup[Constants::MESSAGE]) ?
            __($dataForPopup[Constants::MESSAGE],['submission_at' => date("F j, Y",$formSubmissionDate)])
            : "";

        $response = [
            "show_create_ticket_popup"  => $dataForPopup[Constants::SHOW_POPUP] ? $dataForPopup[Constants::SHOW_POPUP] : false,
            "cta_list"                  => $dataForPopup[Constants::CTA_LIST] ? $dataForPopup[Constants::CTA_LIST] : [],
            "message_body"              => $message
        ];

        return $response;
    }

    /**
     * @param array $referrals
     * @return array|mixed
     */
    public function formatReferralResponse(array $referrals)
    {
        // In the current format, a single referral for the pg product
        // is returned in response, going forward, we will be returning all
        // the referrals for a merchant (pg, banking, etc.).
        // To support backward compatibility, we are stuffing referral details of
        // the pg at root level

        if (array_key_exists(Product::PRIMARY, $referrals) === true) {
            $result = $referrals[Product::PRIMARY];

            $result['referrals'] = $referrals;

            return $result;
        }

        return $referrals;
    }

    public function getRZPTrustedBadgeDetails()
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::RZP_TRUSTED_BADGE)) {

            return [
                'rtb_details' => true,
            ];
        }
        else
        {
            return [
                'rtb_details' => false,
            ];
        }
    }

    protected function getDataForCreateTicketPopup($activationStatus, $activationProgress, bool $isSubmitted= false): array
    {
        $dataForPopup = [];

        if (in_array($activationStatus,[MerchantStatus::UNDER_REVIEW, MerchantStatus::NEEDS_CLARIFICATION, MerchantStatus::REJECTED]) === true )
        {
            $functionName = "getPopupDataFor".studly_case($activationStatus);

            $dataForPopup = $this->$functionName();
        }
        else if ($isSubmitted === false)
        {
            $activationProgressRanges = Constants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_PROGRESS_RANGES;

            foreach ($activationProgressRanges as $activationProgressRange)
            {
                $minPercent = is_numeric($activationProgressRange[Constants::MIN_ACTIVATION_PROGRESS]) === true ? $activationProgressRange[Constants::MIN_ACTIVATION_PROGRESS] :
                    ($this->getActivationProgressRequired($activationProgressRange[Constants::MIN_ACTIVATION_PROGRESS]) + 1);

                $maxPercent = is_numeric($activationProgressRange[Constants::MAX_ACTIVATION_PROGRESS]) === true ? $activationProgressRange[Constants::MAX_ACTIVATION_PROGRESS] :
                    $this->getActivationProgressRequired($activationProgressRange[Constants::MAX_ACTIVATION_PROGRESS]);

                $this->trace->info(
                    TraceCode::SHOW_CREATE_TICKET_POPUP_DEBUG,
                    [
                        'minimum Percent' => $minPercent,
                        'maximum Percent' => $maxPercent,
                        'activation Progress'   => $activationProgress,
                    ]);

                if ($activationProgress >= $minPercent &&
                    $activationProgress <= $maxPercent)
                {
                    $dataForPopup = $activationProgressRange;
                }
            }
        }

        return $dataForPopup;
    }

    protected function getActivationProgressRequired($key) : int
    {
        $maxActivationProgressForFirstRange = (int)((new AdminService)->getConfigKey(['key' => $key]));

        if (empty($maxActivationProgressForFirstRange) === true)
        {
            return 0;
        }

        return $maxActivationProgressForFirstRange;
    }

    /**
     * @return int
     */
    protected function getMinTimeDiffToAllowCreateTicket(): int
    {
        $minTimeDiff = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::MIN_HOURS_TO_START_TICKET_CREATION_AFTER_ACTIVATION_FORM_SUBMISSION]);

        if (empty($minTimeDiff) === true)
        {
            $minTimeDiff = self::DEFAULT_MIN_HOURS_TO_START_TICKET_CREATION_AFTER_ACTIVATION_FORM_SUBMISSION;
        }

        return $minTimeDiff;
    }

    protected function getPopupDataForUnderReview(): array
    {
        $l2FirstSubmissionDateTime = (new Detail\Service())->getFirstL2SubmissionDate();

        $dataForUnderReview = Constants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_STATUS[MerchantStatus::UNDER_REVIEW];

        $minTimeDiff = $this->getMinTimeDiffToAllowCreateTicket();

        $differenceInSeconds = self::HOUR * $minTimeDiff;

        $currentTimestamp = Carbon::now()->getTimestamp();

        $this->trace->info(
            TraceCode::SHOW_CREATE_TICKET_POPUP_DEBUG,
            [
                'minimum_difference_in_seconds'              => $differenceInSeconds,
                'current_time_difference_in_seconds'         => $currentTimestamp,
            ]);

        if ($differenceInSeconds < $currentTimestamp - $l2FirstSubmissionDateTime)
        {
            $dataForPopup = $dataForUnderReview[Constants::X_HOURS_AFTER_ACTIVATION_FORM_SUBMISSION];
        }
        else
        {
            $dataForPopup = $dataForUnderReview[Constants::X_HOURS_WITHIN_ACTIVATION_FORM_SUBMISSION];
        }

        return $dataForPopup;
    }

    protected function getPopupDataForNeedsClarification(): array
    {
        $isDedupe = (new Detail\DeDupe\Core)->isMerchantImpersonated($this->merchant);

        $this->trace->info(
            TraceCode::SHOW_CREATE_TICKET_POPUP_DEBUG,
            [
                'is Dedupe ' => $isDedupe,
            ]);

        $dataForNeedsClarification = Constants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_STATUS[MerchantStatus::NEEDS_CLARIFICATION];

        if ($isDedupe === true)
        {
            $dataForPopup = $dataForNeedsClarification[Constants::DEDUPE_MERCHANT];
        }
        else
        {
            $dataForPopup = $dataForNeedsClarification[Constants::NON_DEDUPE_MERCHANT];
        }

        return $dataForPopup;
    }

    protected function getPopupDataForRejected(): array
    {
        return Constants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_STATUS[MerchantStatus::REJECTED][Constants::DEFAULT];
    }

    public function getMerchantRiskData(string $merchantId): array
    {
        return $this->core()->getMerchantRiskData($merchantId);
    }

    public function fireHubspotEventFromDashboard(array $input): array
    {
        $merchantEmail = array_pull($input, 'merchant_email');

        $this->app->hubspot->trackHubspotEvent($merchantEmail, $input);

        $this->trace->info(
            TraceCode::PUSHED_EVENT_TO_HUBSPOT,
            [
                'merchant_email' => $merchantEmail,
                'payload'        => $input
            ]);

        return ['success' => true];
    }

    public function handleMerchantActionNotificationCron(): array
    {
        (new MerchantActionNotification())->handleMerchantActionNotificationCron();

        return ['success' => true];
    }

    public function completeSubmerchantOnboarding($submerchantId, $input)
    {
        $input['submerchant_id'] = $submerchantId;

        (new Validator)->validateInput('complete_submerchant_onboarding', $input);

        $partnerId     = $input['partner_merchant_id'];

        $submerchant = $this->repo->merchant->findOrFailPublic($submerchantId);
        $partner = $this->repo->merchant->findOrFailPublic($partnerId);

        $this->validateAggregatorSubMerchantRelation($submerchant, $partner);

        $this->core()->addMerchantSupportingEntitiesAsync($submerchant, $partner);

        return ['success' => true];
    }

    public function createSalesforceLeadFromDashboard(array $input): array
    {
        try
        {
            $this->app->salesforce->sendNeostoneFlag($input);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SALESFORCE_FAILED_TO_DISPATCH_JOB);

            return ['success' => false];
        }

        $this->trace->info(
            TraceCode::CREATED_LEAD_ON_SALESFORCE,
            [
                'payload'        => $input
            ]);

        return ['success' => true];
    }

    public function getPurposeCodeDetails(): array
    {
        $data = [];

        $data = PurposeCodeList::getPurposeCode();

        return $data;
    }

    public function patchMerchantPurposeCode(array $input)
    {
        $dba[Merchant\Entity::PURPOSE_CODE] = $input['purpose_code'];

        $this->merchant->edit($dba);

        $this->repo->merchant->saveOrFail($this->merchant);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT, [
            Entity::ID => $this->merchant->getId(),
            Entity::PURPOSE_CODE => $this->merchant->getPurposeCode(),
        ]);
        return ['success' => true];
    }

    private function getWebsiteSelfServeWorkflowAction()
    {
        $merchantCore = new Merchant\Core;

        $merchant = $this->merchant;

        [$entityId, $entity] = $merchantCore->fetchWorkflowData(Constants::ADDITIONAL_WEBSITE, $merchant);

        $this->trace->info(
            TraceCode::GET_WEBSITE_SELF_SERVE_WORKFLOW_ACTION,
            [
                'entity_id'  => $entityId,
                'entity'     => $entity
            ]);

        $action = (new Action\Core())->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Constants::MERCHANT_WORKFLOWS[Constants::ADDITIONAL_WEBSITE][Constants::PERMISSION],
             Constants::MERCHANT_WORKFLOWS[Constants::UPDATE_BUSINESS_WEBSITE][Constants::PERMISSION]]);

        return $action;
    }

    public function getWebsiteSelfServeWorkflowDetails()
    {
        $action = $this->getWebsiteSelfServeWorkflowAction();

        return (new WorkflowService)->getWorkflowDetailsWithRejectionMessage($action);
    }

    private function findCampaignType(array $utmParams): ?string
    {
        $isNeostoneCampaign = false;

        foreach (self::NEOSTONE_UTM_RULES as $neostoneUtmRule)
        {
            $ruleSatisfied = true;

            foreach (User\Constants::$utmDecider as $utmType)
            {
                $ruleSatisfied = ($ruleSatisfied and array_key_exists(('final_' . $utmType), $utmParams));

                if (array_key_exists(('final_' . $utmType), $utmParams)) // We check only last click utm parameters
                {
                    $ruleSatisfied = ($ruleSatisfied and (strtolower($neostoneUtmRule[$utmType]) === strtolower($utmParams['final_' . $utmType])));
                }
            }

            if ($ruleSatisfied === true)
            {
                $isNeostoneCampaign = true;

                break;
            }
        }

        return $isNeostoneCampaign ? 'ca_neostone' : null;
    }

    public function toggleFeeBearer(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_TOGGLE_FEE_BEARER,
        [
            Constants::INPUT   => $input
        ]);
        $merchant = $this->merchant;

        $merchant->validateInput('toggle_fee_bearer', $input);

        (new Validator())->validateIsActivated($merchant);

        $planId = $this->repo->transactionOnLiveAndTest(function () use($merchant, $input)
        {
            $oldPlanId = $merchant->getPricingPlanId();

            $merchant->setFeeBearer($input[Entity::FEE_BEARER]);

            $plan = $this->repo->pricing->getPlanByIdOrFailPublic($merchant->getPricingPlanId());

            $newPlanId = $this->createNewPricingPlan($merchant, $plan, $input);

            $merchant->setPricingPlan($newPlanId);

            $this->repo->merchant->saveOrFail($merchant);

            $this->trace->info(TraceCode::PRICING_PLAN_ASSIGN_SUCCESS,
                [
                    Entity::FEE_BEARER              => $merchant->getFeeBearer(),
                    Constants::OLD_FEE_BEARER       => $oldPlanId,
                    Constants::NEW_FEE_BEARER       => $newPlanId
                ]);

            return $newPlanId;
        });

        return ['plan id' => $planId];
    }

    public function createNewPricingPlan($merchant, $plan, $input)
    {
        $merchantId = $merchant->getMerchantId();

        $ruleOrgId = $plan->getOrgId();

        $oldRules = $plan->toArray();

        $planName = Constants::SELF_SERVE_FOR_FEE_BEARER . $merchantId . time();

        $newRules = [];

        foreach ($oldRules as $rule)
        {
            $rule = array_except ($rule,
                [
                    PricingEntity::ID,
                    PricingEntity::PLAN_ID,
                    PricingEntity::ORG_ID,
                    PricingEntity::CREATED_AT,
                    PricingEntity::UPDATED_AT,
                    PricingEntity::DELETED_AT,
                    PricingEntity::EXPIRED_AT
                ]);

            if ($rule[PricingEntity::FEATURE] === PricingFeature::REFUND)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'This action cannot be performed for your account. Please reach out to support team to perform this action'
                );
            }
            $rule[PricingEntity::FEE_BEARER] = $input[PricingEntity::FEE_BEARER];

            $rule[PricingEntity::INTERNATIONAL] = $rule[PricingEntity::INTERNATIONAL] === true ? '1' : '0';

            if ($rule[PricingEntity::PRODUCT] !== Product::BANKING)
            {
                unset($rule[PricingEntity::ACCOUNT_TYPE]);
            }

            if ((isset($rule[PricingEntity::ACCOUNT_TYPE]) === false) or
                ($rule[PricingEntity::ACCOUNT_TYPE] !== Merchant\Balance\AccountType::DIRECT))
            {
                unset($rule[PricingEntity::CHANNEL]);
            }

            array_push($newRules, $rule);
        }

        $newPlan = (new Pricing\Core)->create([PricingEntity::PLAN_NAME => $planName, PricingEntity::RULES => $newRules], $ruleOrgId);

        return $newPlan[0][PricingEntity::PLAN_ID];
    }

    public function postIncreaseTransactionLimitSelfServe(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_INCREASE_TRANSACTION_LIMIT_INPUT,[
           Constants::INPUT => $input
        ]);

        $merchant = $this->merchant;

        $merchantId = $merchant->getMerchantId();

        $merchantDetails = $merchant->merchantDetail;

        $businessType = $merchantDetails->getBusinessType();

        $isBusinessRegistered = in_array($businessType, [Merchant\Detail\BusinessType::INDIVIDUAL, Merchant\Detail\BusinessType::NOT_YET_REGISTERED]) ? false : true;

        $merchantInfo = $this->app['datalake.presto']->getDataFromDataLake(sprintf(Constants::PRESTO_QUERY_FIND_MERCHANT_TYPE, $merchant->getId()));

        $isMerchantKamOrDirectSales = $this->isMerchantKamOrDirectSales($merchantInfo);

        (new Validator)->validateIncreaseTransactionLimitConditions($merchant, $input, $isBusinessRegistered, $isMerchantKamOrDirectSales);

        if ((isset($input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]) === true) and
            (is_object($input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]) === true))
        {
            $input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL] = (new Core())->uploadInvoiceForIncreaseTransactionLimit($merchantDetails, $input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]);
        }

        $oldMerchantData = $merchant;

        $newMerchantData = clone $oldMerchantData;

        $newMerchantData->setMaxPaymentAmount($input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT]);

        $this->app['workflow']
            ->setPermission(Permission::INCREASE_TRANSACTION_LIMIT)
            ->setEntityAndId($merchant->getEntity(), $merchant->getId())
            ->setInput([
                Entity::MERCHANT_ID         => $merchantId,
                Entity::MAX_PAYMENT_AMOUNT  => $input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT]
            ])
            ->setController(Constants::INCREASE_TRANSACTION_LIMIT_POST_WORKFLOW_APPROVE)
            ->handle($oldMerchantData, $newMerchantData, true);

        $this->addCommentForIncreaseTransactionLimitPostWorkflowCreation($merchant, $input);

        return [Entity::MAX_PAYMENT_AMOUNT => $merchant->getMaxPaymentAmount()];
    }

    protected function addCommentForIncreaseTransactionLimitPostWorkflowCreation(Entity $merchant, array $input)
    {
        $workFlowAction = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperation($merchant->getMerchantId(),
            $merchant->getEntity(),
            Permission::INCREASE_TRANSACTION_LIMIT,
            $merchant->getOrgId()
        )->first();

        if (is_null($workFlowAction) === true)
        {
            throw new Exception\ServerErrorException('Workflow Action Not Found',
                ErrorCode::SERVER_ERROR_WORKFLOW_ACTION_CREATE_FAILED);
        }

        $this->addCommentForTransactionLimitIncreaseReason($workFlowAction, $input);

        if (empty($input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]) === false)
        {
            $this->addCommentForTransactionLimitInvoiceUrl($workFlowAction, $input);
        }
    }

    protected function addCommentForTransactionLimitIncreaseReason(WorkFlowActionEntity $workFlowAction, array $input)
    {
        $comment = sprintf(Constants::TRANSACTION_LIMIT_INCREASE_REASON_COMMENT,
            $input[Constants::TRANSACTION_LIMIT_INCREASE_REASON]
        );

        $commentEntity = (new CommentCore())->create([
            Constants::COMMENT => $comment,
        ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    protected function addCommentForTransactionLimitInvoiceUrl(WorkFlowActionEntity $workFlowAction, array $input)
    {
        $comment = sprintf(Constants::TRANSACTION_LIMIT_INCREASE_SUPPORT_DOCUMENT_URL_COMMENT,
            $this->app->config->get('applications.dashboard.url'),
            $input[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]
        );

        $commentEntity = (new CommentCore())->create([
            Constants::COMMENT => $comment,
        ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    public function postTransactionLimitWorkflowApprove(array $input)
    {
        $merchantId = $input[Constants::MERCHANT_ID];

        $merchant = $this->repo->merchant->findorFailPublic($merchantId);

        $newTransactionLimit = (new Merchant\Detail\Service())->getAgentApprovedTransactionLimit($merchant);

        $this->merchant->setMaxPaymentAmount($newTransactionLimit);

        $this->repo->merchant->saveOrFail($this->merchant);

        $this->trace->info(TraceCode::MERCHANT_TRANSACTION_LIMIT_UPDATE_SUCCESS,[
            Constants::UPDATED_TRANSACTION_LIMIT => $newTransactionLimit
        ]);

        $merchantPrimaryOwner = $this->merchant->primaryOwner()->toArrayPublic();

        $mailInstance = new TransactionLimitMerchantMail($this->merchant->toArray(), $newTransactionLimit, $merchantPrimaryOwner);

        Mail::queue($mailInstance);
    }

    public function getMerchantWorkflowDetails(string $workflowType)
    {
        $merchant = $this->merchant;

        $merchantCore = new Merchant\Core;

        [$entityId, $entity] = $merchantCore->fetchWorkflowData($workflowType, $merchant);

        $this->trace->info(
            TraceCode::GET_MERCHANT_WORKFLOW_DETAILS,
            [
                'entity_id'  => $entityId,
                'entity'     => $entity
            ]);

        $action = (new Action\Core())->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::PERMISSION]]);

        return (new WorkflowService)->getWorkflowDetailsWithRejectionMessage($action);
    }

    private function isMerchantKamOrDirectSales($merchantInfo): bool
    {
        if ((isset($merchantInfo) === true) and
            (isset($merchantInfo[0]['owner_role__c']) === true) and
            (in_array($merchantInfo[0]['owner_role__c'], [Constants::MERCHANT_TYPE_KAM , Constants::MERCHANT_TYPE_DIRECT_SALES])) === true)
        {
            return true;
        }

        return false;
    }
}
