<?php

namespace RZP\Models\CardMandate;

use App;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Customer\Token;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;

/**
 * @property Merchant\Entity $merchant
 * @property Terminal\Entity $terminal
 * @property Token\Entity    $token
 */
class Entity extends Base\PublicEntity
{
    const MANDATE_ID                 = 'mandate_id';
    const MANDATE_CARD_ID            = 'mandate_card_id';
    const MANDATE_CARD_NAME          = 'mandate_card_name';
    const MANDATE_CARD_LAST4         = 'mandate_card_last4';
    const MANDATE_CARD_NETWORK       = 'mandate_card_network';
    const MANDATE_CARD_TYPE          = 'mandate_card_type';
    const MANDATE_CARD_ISSUER        = 'mandate_card_issuer';
    const MANDATE_CARD_INTERNATIONAL = 'mandate_card_international';
    const MANDATE_SUMMARY_URL        = 'mandate_summary_url';
    const STATUS                     = 'status';
    const DEBIT_TYPE                 = 'debit_type';
    const CURRENCY                   = 'currency';
    const MAX_AMOUNT                 = 'max_amount';
    const AMOUNT                     = 'amount';
    const START_AT                   = 'start_at';
    const END_AT                     = 'end_at';
    const TOTAL_CYCLES               = 'total_cycles';
    const MANDATE_INTERVAL           = 'mandate_interval';
    const FREQUENCY                  = 'frequency';
    const PAUSED_BY                  = 'paused_by';
    const CANCELLED_BY               = 'cancelled_by';
    const MANDATE_HUB                = 'mandate_hub';
    const TERMINAL_ID                = 'terminal_id';
    const NETWORK_TRANSACTION_ID     = 'network_transaction_id';
    const HAS_INITIAL_TRANSACTION_ID = 'has_initial_transaction_id';
    const VAULT_TOKEN_PAN            = 'vault_token_pan';

    const SKIP_SUMMARY_PAGE          = 'skip_summary_page';

    protected $entity = 'card_mandate';

    protected $generateIdOnCreate = true;

    protected $fillable = [
    ];

    protected $public = [
        self::ID,
        self::MANDATE_SUMMARY_URL,
        self::STATUS,
        self::CREATED_AT,
    ];

    protected $visible = [
        self::ID,
        self::MANDATE_ID,
        self::MERCHANT_ID,
        self::MANDATE_ID,
        self::MANDATE_HUB,
        self::MANDATE_CARD_ISSUER,
        self::STATUS,
        self::DEBIT_TYPE,
        self::MAX_AMOUNT,
        self::AMOUNT,
        self::START_AT,
        self::END_AT,
        self::TOTAL_CYCLES,
        self::MANDATE_INTERVAL,
        self::FREQUENCY,
        self::MANDATE_SUMMARY_URL,
        self::STATUS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::NETWORK_TRANSACTION_ID,
        self::HAS_INITIAL_TRANSACTION_ID,
        self::VAULT_TOKEN_PAN
    ];

    protected $defaults = [
        self::STATUS => Status::CREATED,
    ];

    public function setMandateId($url)
    {
        $this->setAttribute(self::MANDATE_ID, $url);
    }

    public function setNetworkTransactionId($network_transaction_id)
    {
        $this->setAttribute(self::NETWORK_TRANSACTION_ID, $network_transaction_id);
    }

    public function setHasInitialTransactionId($has_initial_transaction_id)
    {
        $this->setAttribute(self::HAS_INITIAL_TRANSACTION_ID, $has_initial_transaction_id);
    }

    public function setStatus($status)
    {
        Status::checkStatus($status);

        $previousState = $this->getStatus();

        Status::checkStatusChange($previousState, $status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setMandateCardId($value)
    {
        $this->setAttribute(self::MANDATE_CARD_ID, $value);
    }

    public function setMandateCardName($value)
    {
        $this->setAttribute(self::MANDATE_CARD_NAME, $value);
    }

    public function setMandateCardLast4($value)
    {
        $this->setAttribute(self::MANDATE_CARD_LAST4, $value);
    }

    public function setMandateCardNetwork($value)
    {
        $this->setAttribute(self::MANDATE_CARD_NETWORK, $value);
    }

    public function setMandateCardType($value)
    {
        $this->setAttribute(self::MANDATE_CARD_TYPE, $value);
    }

    public function setMandateCardIssuer($value)
    {
        $this->setAttribute(self::MANDATE_CARD_ISSUER, $value);
    }

    public function setMandateCardInternational($value)
    {
        $this->setAttribute(self::MANDATE_CARD_INTERNATIONAL, $value);
    }

    public function setMandateSummaryUrl($value)
    {
        $this->setAttribute(self::MANDATE_SUMMARY_URL, $value);
    }

    public function setDebitType($value)
    {
        $this->setAttribute(self::DEBIT_TYPE, $value);
    }

    public function setCurrency($value)
    {
        $this->setAttribute(self::CURRENCY, $value);
    }

    public function setMaxAmount($value)
    {
        $this->setAttribute(self::MAX_AMOUNT, $value);
    }

    public function setAmount($value)
    {
        $this->setAttribute(self::AMOUNT, $value);
    }

    public function setStartAt($value)
    {
        $this->setAttribute(self::START_AT, $value);
    }

    public function setEndAt($value)
    {
        $this->setAttribute(self::END_AT, $value);
    }

    public function setTotalCycles($value)
    {
        $this->setAttribute(self::TOTAL_CYCLES, $value);
    }

    public function setMandateInterval($value)
    {
        $this->setAttribute(self::MANDATE_INTERVAL, $value);
    }

    public function setFrequency($value)
    {
        $this->setAttribute(self::FREQUENCY, $value);
    }

    public function setMandateHub($value)
    {
        $this->setAttribute(self::MANDATE_HUB, $value);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function setVaultTokenPan($value)
    {
        $this->setAttribute(self::VAULT_TOKEN_PAN, $value);
    }

    public function getMaxAmount()
    {
        return $this->getAttribute(self::MAX_AMOUNT);
    }

    public function getMandateSummaryUrl()
    {
        return $this->getAttribute(self::MANDATE_SUMMARY_URL);
    }

    public function getMandateId()
    {
        return $this->getAttribute(self::MANDATE_ID);
    }

    public function getVaultTokenPan()
    {
        return $this->getAttribute(self::VAULT_TOKEN_PAN);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getMandateHub()
    {
        return $this->getAttribute(self::MANDATE_HUB);
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    public function getFrequency()
    {
        return $this->getAttribute(self::FREQUENCY);
    }

    public function getDebitType()
    {
        return $this->getAttribute(self::DEBIT_TYPE);
    }

    public function getPayType()
    {
        return null;
    }

    public function isMandateValidated()
    {
        return null;
    }

    public function getNetworkTransactionId()
    {
        return $this->getAttribute(self::NETWORK_TRANSACTION_ID);
    }

    public function getHasInitialTransactionId()
    {
        return $this->getAttribute(self::HAS_INITIAL_TRANSACTION_ID);
    }

    public function getRecurringCount()
    {
        return $this->getAttribute(self::TOTAL_CYCLES);
    }

    public function getStartAt()
    {
        return $this->getAttribute(self::START_AT);
    }

    public function getEndAt()
    {
        return $this->getAttribute(self::END_AT);

    }
    public function isActive(): bool
    {
        return $this->getAttribute(self::STATUS) === Status::ACTIVE;
    }

    public function shouldSaveAInputDetailsToCache()
    {
        if (($this->getMandateHub() === MandateHubs::MANDATE_HQ) and ($this->isCustomerConsentRequired() === true))
        {
            return true;
        }

        return false;
    }

    public function isMandateApproved(): bool
    {
        return $this->getAttribute(self::STATUS) === Status::MANDATE_APPROVED;
    }

    public function isCustomerConsentRequired(): bool
    {
        $app = App::getFacadeRoot();

        return $this->getStatus() === Status::CREATED and $app->mandateHQ->shouldSkipSummaryPage() === false;
    }

    // Relations
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function terminal()
    {
        return $this->belongsTo(Terminal\Entity::class);
    }

    public function token()
    {
        return $this->hasOne(Token\Entity::class);
    }
}
