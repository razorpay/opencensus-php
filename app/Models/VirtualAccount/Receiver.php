<?php

namespace RZP\Models\VirtualAccount;

use App;

use RZP\Error\ErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\QrCode;
use RZP\Models\OfflineChallan;
use RZP\Models\BankAccount\Generator;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\VirtualAccount\Entity as VAEntity;
Use RZP\Models\Order;
use RZP\Exception;

class Receiver extends Base\Core
{
    const BANK_ACCOUNT      = 'bank_account';
    const VPA               = 'vpa';
    const QR_CODE           = 'qr_code';
    const OFFLINE_CHALLAN   = 'offline_challan';
    const POS               = 'pos';

    const TYPES = [
        self::BANK_ACCOUNT,
        self::VPA,
        self::QR_CODE,
        self::OFFLINE_CHALLAN,
        self::POS,
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

    public function buildOfflineChallan(Entity $virtualAccount, array $options): OfflineChallan\Entity
    {

        return (new OfflineChallan\Generator())->generate($virtualAccount);
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

        if ($virtualAccount->merchant->isFeatureEnabled(Constants::UPIQR_V1_HDFC) === true)
        {
            if(isset($options[QrCode\Entity::STATUS]) === true)
            {
                $input[QrCode\Entity::STATUS] = $options[QrCode\Entity::STATUS];
            }

            if (isset($options[QrCode\Entity::REFERENCE]) === true) {
                $input[QrCode\Entity::REFERENCE] = $options[QrCode\Entity::REFERENCE];
            }

            if (isset($options['usage']) === true) {
                $input[QrCode\Entity::REQ_USAGE_TYPE] = $options['usage'];
            }

            if (isset($options[QrCode\Entity::DESCRIPTION]) === true) {
                $input[QrCode\Entity::DESCRIPTION] = $options[QrCode\Entity::DESCRIPTION];
            }

            if (isset($options[QrCode\Entity::NAME]) === true) {
                $input[QrCode\Entity::NAME] = $options[QrCode\Entity::NAME];
            }

            if (isset($options[QrCode\Entity::CLOSE_BY]) === true) {
                $input[QrCode\Entity::CLOSE_BY] = $options[QrCode\Entity::CLOSE_BY];
            }

            if (isset($options[QrCode\Entity::NOTES]) === true) {
                $input[QrCode\Entity::NOTES] = $options[QrCode\Entity::NOTES];
            }

            if (isset($options[QrCode\Entity::CUSTOMER_ID]) === true) {
                $input[QrCode\Entity::CUSTOMER_ID] = $options[QrCode\Entity::CUSTOMER_ID];
            }
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


    public function checkReceiverIsOfflineChallan(array $input)
    {
        return isset($input[VAEntity::RECEIVERS]) and ($input[VAEntity::RECEIVERS][0] === self::OFFLINE_CHALLAN);
    }

    public function getOfflineChallanInfo($order)
    {
        $offlineInfo = null;

        if ($this->mode === Mode::LIVE)
        {
            //Fetched from PG router
            if (isset($order['order_metas']) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_ADDITIONAL_INFO_NOT_PROVIDED);
            }

            $offlineInfo = $this->repo->order_meta->getOrderMetaByTypeFromPGOrder($order, Order\OrderMeta\Type::CUSTOMER_ADDITIONAL_INFO);
        }
        else
        {
            //Fetched from API
            $offlineInfo = (new Order\OrderMeta\Repository())->findByOrderIdAndType($order['id'],
                (new Order\OrderMeta\Type)::CUSTOMER_ADDITIONAL_INFO);
        }

        if($offlineInfo === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_ADDITIONAL_INFO_NOT_PROVIDED);
        }

        return $offlineInfo;
    }
}
