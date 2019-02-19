<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Models\P2p\Base\Libraries\Rules;
use RZP\Models\P2p\Base\Upi\ClientLibrary;

class Credentials
{
    const CREDS         = 'creds';
    const TYPE          = 'type';
    const SUB_TYPE      = 'sub_type';
    const SET           = 'set';
    const LENGTH        = 'length';
    const FORMAT        = 'format';
    const CODE          = 'code';
    const STRING        = 'string';
    const KI            = 'ki';

    protected $credDefault = [
        self::TYPE      => null,
        self::SUB_TYPE  => null,
        self::FORMAT    => null,
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
            self::CODE              => 'string',
            self::STRING            => 'string',
            self::KI                => 'string',
        ]);
    }

    public function setCred(array $cred): self
    {
        $merged = array_merge($this->credDefault, $cred);

        $unique = $merged[self::TYPE].$merged[self::SUB_TYPE];

        $merged[ClientLibrary::CL] = $this->getClData($merged);

        $this->creds[$unique] = $merged;

        return $this;
    }

    public function toArray(): array
    {
        return array_values($this->creds);
    }

    private function getClData(array $input): array
    {
        return [
            self::FORMAT    => array_get($this->clFormatMap, $input[self::FORMAT])
        ];
    }
}
