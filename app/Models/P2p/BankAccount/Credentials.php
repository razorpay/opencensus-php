<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Models\P2p\Base\Libraries\Card;
use RZP\Models\P2p\Base\Libraries\Rules;
use RZP\Models\P2p\Base\Upi\ClientLibrary;

class Credentials
{
    const CREDS         = 'creds';
    const TYPE          = 'type';
    const SUB_TYPE      = 'sub_type';
    const ACTION        = 'action';
    const SET           = 'set';
    const RESET         = 'reset';
    const CHANGE        = 'change';
    const LENGTH        = 'length';
    const FORMAT        = 'format';

    // Valid Types
    const PIN           = 'pin';
    const OTP           = 'otp';

    // Valid SubTypes
    const SMS           = 'sms';
    const UPI_PIN       = 'upipin';
    const ATM_PIN       = 'atmpin';

    protected $credDefault = [
        self::FORMAT    => 'ALPHANUM',
        self::LENGTH    => 0,
        self::SET       => false,
    ];

    private $clFormatMap = [
        'NUM'       => 'Numeric',
        'ALPHANUM'  => 'Alphanumeric'
    ];

    protected $creds = [];

    public function __construct(array $creds)
    {
        foreach ($creds as $cred)
        {
            $this->setCred($cred);
        }
    }

    public static function rules(): Rules
    {
        return new Rules([
            self::TYPE              => 'string',
            self::SUB_TYPE          => 'string',
            self::FORMAT            => 'string',
            self::LENGTH            => 'integer',
        ]);
    }

    public static function actionRules()
    {
        $actions = implode(',', self::allowedActions());

        $base = new Rules([
            self::ACTION => 'required|string|in:' . $actions,
            Card::CARD   => 'required_unless:action,change|array'
        ]);

        $rules = Card::rules()->with([
            Card::EXPIRY_MONTH  => 'required_unless:action,change',
            Card::EXPIRY_YEAR   => 'required_unless:action,change',
            Card::LAST6         => 'required_unless:action,change',
        ]);

        $rules->wrapRules(Card::CARD);

        $rules->merge($base);

        return $rules;
    }

    public static function allowedActions(): array
    {
        return [self::SET, self::RESET, self::CHANGE];
    }

    public function setCred(array $cred): self
    {
        $unique = $cred[self::SUB_TYPE];

        $this->creds[$unique] = array_merge($this->credDefault, $cred);

        return $this;
    }

    public function mergeCred(string $unique, array $cred)
    {
        $this->creds[$unique] = array_merge($this->credDefault,
                                            $this->creds[$unique],
                                            $cred);

        return $this;
    }

    public function toArray(): array
    {
        return $this->creds;
    }
}
