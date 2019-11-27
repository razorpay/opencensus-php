<?php

namespace RZP\Models\VirtualAccount;

use App;

use RZP\Exception;
use RZP\Models\BankAccount\Generator;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\BankAccount\Entity as BankAccount;

class Receiver extends Base\Core
{
    const BANK_ACCOUNT      = 'bank_account';
    const VPA               = 'vpa';
    const QR_CODE           = 'qr_code';

    const TYPES = [
        self::BANK_ACCOUNT,
        self::VPA,
        self::QR_CODE,
    ];

    protected $app;
    protected $merchant;
    protected $descriptor;
    protected $trace;
    protected $repo;
    protected $mode;
    protected $numeric;
    protected $provider;

    public function __construct(Entity $virtualAccount)
    {
        parent::__construct();

        $this->merchant = $virtualAccount->merchant;

        $this->virtualAccount = $virtualAccount;

        $this->mutex = $this->app['api.mutex'];
    }

    public static function areTypesValid(array $receiverTypes): bool
    {
        $invalidTypes = array_diff($receiverTypes, self::TYPES);

        return (empty($invalidTypes) === true);
    }

    public function buildBankAccount(Entity $virtualAccount, array $options): BankAccount
    {
        $validator = $virtualAccount->getValidator();

        $validator->validateInput('bankAccountReceiverOption', $options);

        return (new Generator($this->merchant, $options))->generate($virtualAccount);
    }

    public function buildQrCode(Entity $virtualAccount, array $options): QrCode\Entity
    {
        $input = $this->getQrCodeEntityParams($virtualAccount, $options);

        $qrCode = (new QrCode\Generator($this->merchant))->generate($input, $virtualAccount);

        return $qrCode;
    }

    protected function getQrCodeEntityParams(Entity $virtualAccount, array $options): array
    {
        $input = [
            // For now it is set bharat qr as default
            QrCode\Entity::PROVIDER  => Provider::BHARAT_QR,
            QrCode\Entity::AMOUNT    => $virtualAccount->getAmountExpected(),
        ];

        if (isset($options[QrCode\Entity::REFERENCE]) === true)
        {
            $input[QrCode\Entity::REFERENCE] = $options[QrCode\Entity::REFERENCE];
        }

        return $input;
    }
}
