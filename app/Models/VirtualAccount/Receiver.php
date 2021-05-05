<?php

namespace RZP\Models\VirtualAccount;

use App;

use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\QrCode;
use RZP\Models\BankAccount\Generator;
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

    /**
     * If the QR options are provided and with specific flags which are
     * card = false and upi = true, we consider this to be UPI QR
     *
     * @param array $options
     * @return bool
     */
    public static function isOnlyUpiQrCode(array $options): bool
    {
        $isCard = filter_var($options['method']['card'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isUpi  = filter_var($options['method']['upi'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return ($isCard === false and $isUpi === true);
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

        $qrCode = (new QrCode\Service())->create($input, $virtualAccount);

        return $qrCode;
    }

    public function buildVPA(Entity $virtualAccount, array $options): Vpa\Entity
    {
        $validator = $virtualAccount->getValidator();

        $validator->validateInput('vpaReceiverOption', $options);

        return (new Vpa\Generator($this->merchant, $options))->generate($virtualAccount);
    }

    protected function getQrCodeEntityParams(Entity $virtualAccount, array $options): array
    {
        $provider = self::isOnlyUpiQrCode($options) ? Provider::UPI_QR : Provider::BHARAT_QR;

        $input = [
            // For now it is set bharat qr as default
            QrCode\Entity::PROVIDER  => $provider,
            QrCode\Entity::AMOUNT    => $virtualAccount->getAmountExpected(),
        ];

        if (isset($options[QrCode\Entity::REFERENCE]) === true)
        {
            $input[QrCode\Entity::REFERENCE] = $options[QrCode\Entity::REFERENCE];
        }

        return $input;
    }

    public function getVpaConfigs(Entity $virtualAccount)
    {
        return (new Vpa\Generator($this->merchant, []))->getConfigs($virtualAccount);
    }

    public function getBankAccountConfigs(Entity $virtualAccount)
    {
        return (new Generator($this->merchant, []))->getConfigs($virtualAccount);
    }
}
