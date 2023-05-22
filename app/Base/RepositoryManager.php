<?php

namespace RZP\Base;

use Closure;
use Illuminate;
use Database\Connection;

use RZP\Models;
use RZP\Gateway;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Base\Database\MySqlConnection;
use RZP\Base\Database\Connectors\MySqlConnector;

/**
 * @property Models\Plan\Subscription\Repository                 $subscription
 * @property Models\SubscriptionRegistration\Repository          $subscription_registration
 * @property Models\Customer\Repository                          $customer
 * @property Models\Customer\Token\Repository                    $token
 * @property Models\Key\Repository                               $key
 * @property Models\Merchant\Methods\Repository                  $methods
 * @property Models\Terminal\Repository                          $terminal
 * @property Models\Invoice\Repository                           $invoice
 * @property Models\Tax\Repository                               $tax
 * @property Models\Payment\Repository                           $payment
 * @property Models\Settings\Repository                          $settings
 * @property Models\Payment\Refund\Repository                    $refund
 * @property Models\Merchant\Repository                          $merchant
 * @property Models\Batch\Repository                             $batch
 * @property Models\BankAccount\Repository                       $bank_account
 * @property Models\OfflineChallan\Repository                    $offline_challan
 * @property Models\OfflinePayment\Repository                    $offline_payment
 * @property Models\Adjustment\Repository                        $adjustment
 * @property Models\BankTransfer\Repository                      $bank_transfer
 * @property Models\CreditTransfer\Repository                    $credit_transfer
 * @property Models\External\Repository                          $external
 * @property Models\Merchant\Account\Repository                  $account
 * @property Models\PaymentLink\Repository                       $payment_link
 * @property Models\PayoutLink\Repository                        $payout_link
 * @property Models\Feature\Repository                           $feature
 * @property Models\Order\Repository                             $order
 * @property Models\OrderOutbox\Repository                       $order_outbox
 * @property Models\Order\Product\Repository                     $product
 * @property Models\Order\OrderMeta\Repository                   $order_meta
 * @property Models\Payment\Analytics\Repository                 $payment_analytics
 * @property Models\PayoutOutbox\Repository                      $payout_outbox
 * @property Models\LedgerOutbox\Repository                      $ledger_outbox
 * @property Models\Transaction\Repository                       $transaction
 * @property Models\Vpa\Repository                               $vpa
 * @property Models\Contact\Repository                           $contact
 * @property Models\FundAccount\Repository                       $fund_account
 * @property Models\Merchant\Balance\Repository                  $balance
 * @property Models\Merchant\Balance\BalanceConfig\Repository    $balance_config
 * @property Models\Merchant\AccessMap\Repository                $merchant_access_map
 * @property Models\Partner\Config\Repository                    $partner_config
 * @property Models\Transaction\Statement\Repository             $statement
 * @property Models\FundAccount\Repository                       $customer_balance
 * @property Models\FundAccount\Validation\Repository            $fund_account_validation
 * @property Models\FundTransfer\Attempt\Repository              $fund_transfer_attempt
 * @property Models\Reversal\Repository                          $reversal
 * @property Models\Payout\Repository                            $payout
 * @property Models\Merchant\Credits\Repository                  $credits
 * @property Models\Merchant\Detail\Repository                   $merchant_detail
 * @property Models\Merchant\Stakeholder\Repository              $stakeholder
 * @property Models\Merchant\AvgOrderValue\Repository            $merchant_avg_order_value
 * @property Models\Merchant\VerificationDetail\Repository       $merchant_verification_detail
 * @property Models\ClarificationDetail\Repository               $clarification_detail
 * @property Models\Base\Audit\Repository                        $audit_info
 * @property Models\Merchant\CheckoutDetail\Repository           $merchant_checkout_detail
 * @property Models\Merchant\BusinessDetail\Repository           $merchant_business_detail
 * @property Models\Merchant\Escalations\Repository              $merchant_onboarding_escalations
 * @property Models\Merchant\Escalations\Actions\Repository      $onboarding_escalation_actions
 * @property Models\Merchant\Promotion\Repository                $merchant_promotion
 * @property Models\Merchant\Attribute\Repository                $merchant_attribute
 * @property Models\BankingAccount\Repository                    $banking_account
 * @property Models\BankingAccount\Detail\Repository             $banking_account_detail
 * @property Models\BankingAccount\Activation\Detail\Repository  $banking_account_activation_detail
 * @property Models\PayoutsDetails\Repository                    $payouts_details
 * @property Models\BankingAccount\Activation\Comment\Repository $banking_account_comment
 * @property Models\BankingAccount\Activation\CallLog\Repository $banking_account_call_log
 * @property Models\BankingAccount\State\Repository              $banking_account_state
 * @property Models\Item\Repository                              $item
 * @property Models\PaymentLink\PaymentPageItem\Repository       $payment_page_item
 * @property Models\PaymentLink\NocodeCustomUrl\Repository       $nocode_custom_url
 * @property Models\PaymentLink\PaymentPageRecord\Repository     $payment_page_record
 * @property Models\BankingAccountStatement\Repository           $banking_account_statement
 * @property Models\BankingAccountStatement\Pool\Rbl\Repository  $banking_account_statement_pool_rbl
 * @property Models\BankingAccountStatement\Pool\Icici\Repository $banking_account_statement_pool_icici
 * @property Models\BankingAccountStatement\Details\Repository   $banking_account_statement_details
 * @property Models\Admin\Role\Repository                        $role
 * @property Models\Admin\Permission\Repository                  $permission
 * @property Models\Partner\Commission\Invoice\Repository        $commission_invoice
 * @property Models\Partner\Commission\Repository                $commission
 * @property Models\Workflow\Action\Repository                   $workflow_action
 * @property Models\Workflow\Step\Repository                     $workflow_step
 * @property Models\Workflow\Repository                          $workflow
 * @property Models\Workflow\Action\Checker\Repository           $action_checker
 * @property Models\Comment\Repository                           $comment
 * @property Models\State\Repository                             $state
 * @property Models\Workflow\Action\State\Repository             $action_state
 * @property Models\Merchant\AutoKyc\Escalations\Repository      $merchant_auto_kyc_escalations
 * @property Models\Workflow\PayoutAmountRules\Repository        $workflow_payout_amount_rules
 * @property Models\Mpan\Repository                              $mpan
 * @property Models\Merchant\Document\Repository                 $merchant_document
 * @property Models\Card\Repository                              $card
 * @property Models\CorporateCard\Repository                     $corporate_card
 * @property Models\CardMandate\Repository                       $card_mandate
 * @property Models\CardMandate\CardMandateNotification\Repository $card_mandate_notification
 * @property Models\Settlement\Bucket\Repository                 $settlement_bucket
 * @property Models\Settlement\Destination\Repository            $settlement_destination
 * @property Models\D2cBureauDetail\Repository                   $d2c_bureau_detail
 * @property Models\D2cBureauReport\Repository                   $d2c_bureau_report
 * @property Models\Merchant\MerchantUser\Repository             $merchant_user
 * @property Models\Merchant\Invoice\Repository                  $merchant_invoice
 * @property Models\Merchant\Invoice\EInvoice\Repository         $merchant_e_invoice
 * @property Models\Address\Repository                           $address
 * @property Models\RawAddress\Repository                        $raw_address
 * @property Models\Options\Repository                           $options
 * @property Models\Merchant\FreshdeskTicket\Repository          $merchant_freshdesk_tickets
 * @property Models\VirtualAccount\Repository                    $virtual_account
 * @property Models\Payment\PaymentMeta\Repository               $payment_meta
 * @property Models\Payment\Config\Repository                    $config
 * @property Models\IdempotencyKey\Repository                    $idempotency_key
 * @property Models\FeeRecovery\Repository                       $fee_recovery
 * @property Models\VirtualVpaPrefix\Repository                  $virtual_vpa_prefix
 * @property Models\VirtualVpaPrefixHistory\Repository           $virtual_vpa_prefix_history
 * @property Models\Merchant\Reminders\Repository                $merchant_reminders
 * @property Models\PayoutDowntime\Repository                    $payout_downtimes
 * @property Models\FundLoadingDowntime\Repository               $fund_loading_downtimes
 * @property Models\User\Repository                              $user
 * @property Models\Transfer\Repository                          $transfer
 * @property Models\Settlement\Ondemand\Repository               $settlement_ondemand
 * @property Models\Settlement\OndemandPayout\Repository         $settlement_ondemand_payout
 * @property Models\Settlement\OndemandFundAccount\Repository    $settlement_ondemand_fund_account
 * @property Models\BankTransferRequest\Repository               $bank_transfer_request
 * @property Models\Merchant\Balance\LowBalanceConfig\Repository $low_balance_config
 * @property Models\PaperMandate\PaperMandateUpload\Repository   $paper_mandate_upload
 * @property Models\PaperMandate\Repository                      $paper_mandate
 * @property Models\VirtualAccountTpv\Repository                 $virtual_account_tpv
 * @property Models\Counter\Repository                           $counter
 * @property Models\Workflow\Service\Config\Repository           $workflow_config
 * @property Models\Workflow\Service\EntityMap\Repository        $workflow_entity_map
 * @property Models\Application\Repository                               $application
 * @property Models\Application\ApplicationTags\Repository               $application_mapping
 * @property Models\Application\ApplicationMerchantMaps\Repository       $application_merchant_mapping
 * @property Models\Application\ApplicationMerchantTags\Repository       $application_merchant_tag
 * @property Models\Workflow\Service\StateMap\Repository         $workflow_state_map
 * @property Models\Merchant\BvsValidation\Repository            $bvs_validation
 * @property Models\UpiTransfer\Repository                       $upi_transfer
 * @property Models\UpiTransferRequest\Repository                $upi_transfer_request
 * @property Models\PayoutSource\Repository                      $payout_source
 * @property Models\PayoutMeta\Repository                        $payouts_meta
 * @property Models\Promotion\Repository                         $promotion
 * @property Models\RequestLog\Repository                        $request_log
 * @property Models\Merchant\MerchantApplications\Repository     $merchant_application
 * @property Models\Offer\SubscriptionOffer\Repository           $subscription_offers_master
 * @property Models\Merchant\MerchantNotificationConfig\Repository $merchant_notification_config
 * @property Models\Reward\Repository                              $reward;
 * @property Models\Reward\MerchantReward\Repository               $merchant_reward;
 * @property Models\Reward\RewardCoupon\Repository                 $reward_coupon;
 * @property Models\TrustedBadge\Repository                        $trusted_badge;
 * @property Models\TrustedBadge\TrustedBadgeHistory\Repository    $trusted_badge_history;
 * @property Models\AppStore\Repository                            $app_store;
 * @property Models\Store\Repository                               $store;
 * @property Models\Survey\Tracker\Repository                      $survey_tracker;
 * @property Models\Survey\Repository                              $survey;
 * @property Models\Survey\Response\Repository                     $survey_response;
 * @property Models\Pricing\Repository                             $pricing;
 * @property Models\Partner\Commission\Component\Repository        $commission_component;
 * @property Models\BankingAccountTpv\Repository                   $banking_account_tpv;
 * @property Models\Partner\Activation\Repository                  $partner_activation;
 * @property Models\Partner\KycAccessState\Repository              $partner_kyc_access_state;
 * @property Models\Merchant\Product\Repository                    $merchant_product;
 * @property Models\Merchant\Product\Request\Repository            $merchant_product_request;
 * @property Models\Merchant\Product\TncMap\Repository                $tnc_map;
 * @property Models\Merchant\Product\TncMap\Acceptance\Repository     $merchant_tnc_acceptance;
 * @property Models\Settlement\Repository                             $settlement
 * @property Models\Settlement\InternationalRepatriation\Repository   $settlement_international_repatriation
 * @property Gateway\Enach\Base\Repository                            $enach
 * @property Gateway\Netbanking\Base\Repository                       $netbanking
 * @property Models\SubVirtualAccount\Repository                      $sub_virtual_account
 * @property Models\Settlement\Transfer\Repository                    $settlement_transfer;
 * @property Models\Dispute\Evidence\Repository                       $dispute_evidence;
 * @property Models\Dispute\Evidence\Document\Repository              $dispute_evidence_document;
 * @property Models\Merchant\RiskNotes\Repository                     $merchant_risk_note;
 * @property Models\Payout\Batch\Repository                           $payouts_batch;
 * @property Models\UpiMandate\Repository                             $upi_mandate;
 * @property Models\Merchant\M2MReferral\Repository                   $m2m_referral;
 * @property Models\AMPEmail\Repository                               $amp_email;
 * @property Models\Dispute\Repository                                $dispute;
 * @property Models\Dispute\DebitNote\Repository                      $debit_note;
 * @property Models\Dispute\DebitNote\Detail\Repository               $debitNoteDetail;
 * @property Models\Payment\Fraud\Repository                       $payment_fraud;
 * @property Models\DeviceDetail\Repository                        $user_device_detail
 * @property Models\DeviceDetail\Attribution\Repository            $app_attribution_detail
 * @property Models\Coupon\Repository                              $coupon
 * @property Models\Payout\PayoutsIntermediateTransactions\Repository $payouts_intermediate_transactions;
 * @property Models\Emi\Repository                                    $emi_plan
 * @property Models\Merchant\Balance\SubBalanceMap\Repository         $sub_balance_map
 * @property Models\Merchant\Slab\Repository                          $merchant_slabs
 * @property Models\Merchant\Merchant1ccConfig\Repository             $merchant_1cc_configs
 * @property Models\Merchant\Merchant1ccComments\Repository           $merchant_1cc_comments
 * @property Models\PayoutsStatusDetails\Repository                   $payouts_status_details
 * @property Models\VirtualAccountProducts\Repository                 $virtual_account_products
 * @property Models\Merchant\OneClickCheckout\AuthConfig\Repository   $merchant_1cc_auth_configs
 * @property Models\Offer\Repository                                  $offer
 * @property Models\Card\TokenisedIIN\Repository                      $tokenised_iin;
 * @property Models\Offer\EntityOffer\Repository                      $entity_offer
 * @property Models\Transaction\Statement\Ledger\Journal\Repository         $journal
 * @property Models\Transaction\Statement\Ledger\Account\Repository         $ledger_account
 * @property Models\Transaction\Statement\Ledger\AccountDetail\Repository   $account_detail
 * @property Models\Transaction\Statement\Ledger\Statement\Repository       $ledger_statement
 * @property Models\Internal\Repository                               $internal
 * @property Models\Address\AddressConsent1ccAudits\Repository               $address_consent_1cc_audits
 * @property Models\Address\AddressConsent1cc\Repository                     $address_consent_1cc
 * @property Models\Pincode\ZipcodeDirectory\Repository                      $zipcode_directory
 *
 * @property Models\Transaction\Statement\DirectAccount\Statement\Repository $direct_account_statement
 * @property Models\Roles\Repository                                         $roles
 * @property Models\Merchant\InternationalIntegration\Repository             $merchant_international_integrations
 * @property Models\Merchant\OwnerDetail\Repository                          $merchant_owner_details
 * @property Models\PartnerBankHealth\Repository                             $partner_bank_health
 * @property Models\Merchant\Product\Otp\Repository                          $merchant_otp_verification_logs
 * @property Models\FileStore\Repository                                     $file_store
 * @property Models\Merchant\Website\Repository                              $merchant_website
 * @property Models\Merchant\Consent\Details\Repository                      $merchant_consent_details
 * @property Models\Merchant\Consent\Repository                              $merchant_consents
 * @property Models\Merchant\LinkedAccountReferenceData\Repository           $linked_account_reference_data
 * @property Models\Checkout\Order\Repository                                $checkout_order
 * @property Models\QrPayment\Repository                                     $qr_payment
 * @property Models\Customer\CustomerConsent1cc\Repository                   $customer_consent_1cc
 * @property Models\Merchant\Referral\Repository                             $referrals
 */

class RepositoryManager extends Illuminate\Support\Manager
{
    protected $app;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->db = $app['db'];

        $this->app = $app;
    }

    public function __get($entity)
    {
        return $this->driver($entity);
    }

    /**
     * Duplicate of above driver initialization, meant for use where entity name is complex
     */
    public function getCustomDriver($entity)
    {
        return $this->driver($entity);
    }

    public function getDefaultDriver()
    {
        throw new Exception\LogicException('No default repository driver');
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function createDriver($driver)
    {
        $repo = Entity::getEntityRepository($driver);

        return new $repo;
    }

    public function saveOrFail($entity, array $options = array())
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->saveOrFail($entity, $options);
    }

    public function saveOrFailWithoutEsSync($entity, array $options = array())
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->saveOrFailWithoutEsSync($entity, $options);
    }

    public function save($entity, array $options = array())
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->save($entity, $options);
    }

    public function sync($entity, $relation, $ids = [], bool $detaching = true)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->sync($entity, $relation, $ids, $detaching);
    }

    public function detach($entity, $relation, $ids = [])
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->detach($entity, $relation, $ids);
    }

    public function attach($entity, $relation, $id, array $attributes = [], $touch = true)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->attach($entity, $relation, $id, $attributes, $touch);
    }

    public function delete($entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->delete($entity);
    }

    public function deleteOrFail($entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->deleteOrFail($entity);
    }

    public function pushOrFail($entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        $repo->pushOrFail($entity);
    }

    public function saveOrFailCollection($collection)
    {
        foreach ($collection->all() as $entity)
        {
            $this->saveOrFail($entity);
        }
    }

    public function reload(&$entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->reload($entity);
    }

    public function loadRelations(Models\Base\PublicEntity $entity): Models\Base\PublicEntity
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->loadRelations($entity);
    }

    public function determineLiveOrTestModeForEntity($id, $entity)
    {
        $repo = $this->driver($entity);

        $obj = $repo->connection(Mode::LIVE)->find($id);

        if ($obj !== null)
        {
            return Mode::LIVE;
        }

        $obj = $repo->connection(Mode::TEST)->find($id);

        if ($obj !== null)
        {
            return Mode::TEST;
        }

        // Check id in archived data replica as the entity might be archived
        // Note : Add _record_source = 'api' filter if moving to aggregated warm storage (tidb)
        $obj = $repo->connection(Connection::ARCHIVED_DATA_REPLICA_LIVE)->find($id);

        if ($obj !== null)
        {
            return Mode::LIVE;
        }

        $obj = $repo->connection(Connection::ARCHIVED_DATA_REPLICA_TEST)->find($id);

        if ($obj !== null)
        {
            return Mode::TEST;
        }

        //
        // We need to set connection to null
        // because it will be set to test if the
        // id is not found in any of the database.
        // So even if the db connection is later set
        // to live, query connection will be set to
        // test.
        //
        $repo->connection(null);

        return null;
    }

    protected function getRepositoryClassFromObject($entityObject)
    {
        $entity = $entityObject->getEntityName();

        return $this->driver($entity);
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();

        return $this;
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollback()
    {
        $this->db->rollback();
    }

    public function beginTransactionAndRollback(Closure $callback)
    {
        $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_TRANSACTION_WAIT_TIMEOUT);

        try
        {
            $this->db->beginTransaction();

            $result = $callback($this);
        }
        finally
        {
            $this->db->rollback();
        }

        $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_WAIT_TIMEOUT);

        return $result;
    }

    /**
     * Execute a callable within a transaction.
     * $callback is not type-hinted as callable to support arrays.
     *
     * @param callable $callback
     * @param array    $params
     *
     * @return mixed
     */
    public function transaction($callback, ...$params)
    {
        $start = millitime();

        $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_TRANSACTION_WAIT_TIMEOUT);

        if ((is_object($callback) === false) or
            ($callback instanceof Closure === false))
        {
            //
            // It's a callable not closure. Wrap it in closure because
            // transaction function in db only accepts closures.
            //
            $result = $this->db->transaction(function() use ($callback, $params)
            {
                return call_user_func($callback, ...$params);
            });
        }
        else
        {
            $result = $this->db->transaction($callback, ...$params);
        }

        $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_WAIT_TIMEOUT);

        return $result;
    }

    public function transactionOnConnection($callback, string $connection)
    {
        $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_TRANSACTION_WAIT_TIMEOUT, $connection);

        if ((is_object($callback) === false) or
            ($callback instanceof Closure === false))
        {
            //
            // It's a callable not closure. Wrap it in closure because
            // transaction function in db only accepts closures.
            //
            $result = $this->db->connection($connection)->transaction(function() use ($callback)
            {
                return call_user_func($callback);
            });

        }
        else
        {
            $result = $this->db->connection($connection)->transaction($callback);
        }

        $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_WAIT_TIMEOUT, $connection);

        return $result;
    }

    public function transactionOnLiveAndTest(callable $callback)
    {
        //
        // We need to grab and assign the default connection here
        // because in the callback code, the functions try to change
        // the default connection. This is again required because of
        // lack of eloquent's support for taking specific connection
        // instance on relationship based queries.
        //
        $currentConnection = $this->getDefaultDbConn();

        $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_TRANSACTION_WAIT_TIMEOUT, Mode::LIVE);
        $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_TRANSACTION_WAIT_TIMEOUT, Mode::TEST);

        $this->db->connection(Mode::TEST)->beginTransaction();
        $this->db->connection(Mode::LIVE)->beginTransaction();

        // We'll simply execute the given callback within a try / catch block
        // and if we catch any exception we can rollback the transaction
        // so that none of the changes are persisted to the database.
        try
        {
            $result = $callback($this);

            $this->db->connection(Mode::LIVE)->commit();
            $this->db->connection(Mode::TEST)->commit();
        }

            // If we catch an exception, we will roll back so nothing gets messed
            // up in the database. Then we'll re-throw the exception so it can
            // be handled how the developer sees fit for their applications.
        catch (\Throwable $e)
        {
            $this->db->connection(Mode::LIVE)->rollBack();
            $this->db->connection(Mode::TEST)->rollBack();

            throw $e;
        }
        finally
        {
            $this->setDefaultDbConn($currentConnection);
            $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_WAIT_TIMEOUT, Mode::LIVE);
            $this->app['db.connector.mysql']->setWaitTimeout(MySqlConnector::TYPE_WAIT_TIMEOUT, Mode::TEST);
        }

        return $result;
    }

    public function useSlave(callable $callback)
    {
        $this->db->connection()->forceReadPdo = true;

        $result = $callback($this);

        $this->db->connection()->forceReadPdo = false;

        return $result;
    }

    protected function getDefaultDbConn()
    {
        return $this->app['config']->get('database.default');
    }

    protected function setDefaultDbConn($conn)
    {
        $this->app['config']->set('database.default', $conn);
    }

    public function getTransactionLevel()
    {
        return $this->db->transactionLevel();
    }

    public function isTransactionActive()
    {
        $env = $this->app->environment();

        if ($env === 'testing')
        {
            return ($this->db->transactionLevel() > 1);
        }

        return ($this->db->transactionLevel() > 0);
    }

    public function assertTransactionActive()
    {
        assertTrue ($this->isTransactionActive());
    }

    public function resetConnectionAttributes()
    {
        $dbConnection = $this->db->connection();

        //
        // Only if the connection being used is the overridden one
        // we need to reset some connection attributes.
        //
        if ($dbConnection instanceof MySqlConnection)
        {
            $dbConnection->resetConnectionAttributes();
        }
    }

    /**
     * Run a dummy select and add a comment. Useful for adding markers in query logs.
     *
     * => $this->repo->addComment('My comment');
     *
     * @param string $comment
     */
    public function addComment(string $comment = 'default')
    {
        $this->db
            ->select('SELECT /* comment: ' . $comment . ' */ 1;' );
    }
}
