<?php

namespace RZP\Models\Vpa;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Payment\Processor\TerminalProcessor;
use RZP\Models\QrCode\NonVirtualAccountQrCode as QrV2;

class Generator extends Base\Core
{
    const DYNAMIC_VPA_LENGTH = 20;

    const DESCRIPTOR = 'descriptor';

    const MAX_VPA_GENERATION_ATTEMPTS = 10;

    const VPA_NUM_CHAR_SPACE = '0123456789';

    const VA_VPA_GENERATION = 'VA_VPA_GENERATION';

    const VPA_QR_PREFIX = 'qr';

    const VPA_QR_BILLING_LABEL_LENGTH = 10;

    protected $mutex;

    protected $root;

    protected $merchantIdentifier;

    protected $handle;

    protected $isDescriptorEnabled;

    protected $options = [
        self::DESCRIPTOR => null,
    ];

    public function __construct(Merchant\Entity $merchant, array $input)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->setOptions($input);

        $this->mutex = $this->app['api.mutex'];
    }

    protected function setOptions(array $input)
    {
        $this->options = array_merge($this->options, $input);
    }

    protected function setConfigForVpa(
        Terminal\Entity $terminal,
        $merchantPrefix = null,
        Base\PublicEntity $entity
    )
    {
        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_GENERATE_VPA_TERMINAL,
            [
                'terminalId' => $terminal->getId(),
            ]);

        $this->root                = $terminal->getVirtualUpiRoot();

        $this->handle              = $terminal->getVirtualUpiHandle();

        if ($entity instanceof QrV2\Entity)
        {
            $merchantIdentifier = preg_replace('/[^A-Za-z0-9]/', '', $this->merchant->getBillingLabel());

            $this->merchantIdentifier = self::VPA_QR_PREFIX . substr($merchantIdentifier, 0, self::VPA_QR_BILLING_LABEL_LENGTH);

            return;
        }

        if ($entity->getSourceType() === VirtualAccount\SourceType::PAYMENT_LINKS_V2)
        {
            $this->merchantIdentifier = Entity::PAYMENT_LINK_VPA_PREFIX;
        }
        else
        {
            $this->merchantIdentifier = $merchantPrefix ?: $terminal->getVirtualUpiMerchantPrefix();
        }

        //Custom descriptor was allowed only to merchants who have registered for custom prefix.
        //Going forward all the merchants will be able to add custom descriptor.
        $this->isDescriptorEnabled = true;
    }

    public function generate(Base\PublicEntity $entity): Entity
    {
        $vpa = $this->buildVpaEntity($entity);

        $this->setTerminalConfigsForVpa($vpa, $entity);

        $attempts = 0;

        while ($attempts <= self::MAX_VPA_GENERATION_ATTEMPTS)
        {
            $vpa = $this->createAndSetVpaAddress($vpa);

            $savedVpa = $this->lockAndSaveVpa($vpa);

            if ($savedVpa !== null)
            {
                return $savedVpa;
            }
            else
            {
                if ($this->options[self::DESCRIPTOR] !== null)
                {

                    // VPA is null when same vpa already exist
                    // for any other virtual account in our system.
                    //
                    // But If Descriptor was passed by merchant then we throw
                    // bad request identical descriptor .

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_IDENTICAL_DESCRIPTOR,
                        'descriptor',
                        [
                            'descriptor' => $this->options[Generator::DESCRIPTOR],
                        ]);
                }
            }

            $attempts++;
        }

        $this->trace->critical(
            TraceCode::VIRTUAL_ACCOUNT_UNAVAILABLE,
            [
                'method'      => Method::UPI,
                'options'     => $this->options,
                'merchant_id' => $this->merchant->getId(),
            ]);

        // This should never happen
        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_UNAVAILABLE);
    }

    /**
     * Grabs a lock on the vpa address, then checks if it is a
     * valid one, and if so, saves it to DB in the vpa table.
     *
     * This function is called in a loop, and is expected to return a vpa
     * entity. If an entity is not returned, and null is returned
     * instead, the calling function assumes that vpa was not created
     * for some reason (usually because the lock on that vpa address was
     * already taken by a different process), and creates a new vpa address
     * for the next attempt.
     *
     * This allows us to 'fail' an attempt at account generation by simply returning null.
     *
     * @param Entity $vpa VPA entity to be saved.
     *
     * @return Entity|null
     * Saved vpa, or null if no address was saved.
     */
    protected function lockAndSaveVpa(Entity $vpa)
    {
        $vpa = $this->mutex->acquireAndRelease(
            self::VA_VPA_GENERATION . $vpa->getAddress(),
            function() use ($vpa) {
                $existingAccount = $this->repo->vpa
                    ->findByAddress($vpa->getAddress(), true);

                if ($existingAccount !== null)
                {
                    // Account with this VPA already exists, fail this attempt
                    return;
                }

                $this->repo->saveOrFail($vpa);

                return $vpa;
            },
            60,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);

        return $vpa;
    }

    protected function validateDescriptor()
    {
        $descriptor = $this->options[self::DESCRIPTOR];

        if (strlen($this->merchantIdentifier . $descriptor) !== self::DYNAMIC_VPA_LENGTH)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_DESCRIPTOR_LENGTH,
                'descriptor',
                [
                    'merchant_prefix' => $this->merchantIdentifier,
                    'descriptor'      => $descriptor,
                ]);
        }
    }

    protected function buildVpaEntity(Base\PublicEntity $entity): Entity
    {
        $vpa = new Entity();

        $vpa->merchant()->associate($this->merchant);

        $vpa->source()->associate($entity);

        return $vpa;
    }

    protected function setTerminalConfigsForVpa(Entity $vpa, Base\PublicEntity $entity): Terminal\Entity
    {
        $virtualVpaPrefix = $this->repo
                                 ->virtual_vpa_prefix
                                 ->fetchEntityByMerchantId($this->merchant->getId());

        if ($virtualVpaPrefix !== null)
        {
            $terminal = $this->repo
                             ->terminal
                             ->getById($virtualVpaPrefix->getTerminalId());

            $this->setConfigForVpa($terminal, $virtualVpaPrefix->getPrefix(), $entity);

            return $terminal;
        }

        $gateway = Gateway::UPI_ICICI;

        $terminal = (new TerminalProcessor())->getTerminalForUpiTransfer(null, $gateway);

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'No Terminal applicable.',
                null,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'method'      => Method::UPI,
                    'options'     => $this->options,
                ]);
        }

        $this->setConfigForVpa($terminal, null, $entity);

        return $terminal;
    }

    protected function createAndSetVpaAddress(Entity $vpa): Entity
    {
        $vpaAddress = $this->generateVpa();

        $vpa->build([Entity::ADDRESS => $vpaAddress], "createVirtualVpa");

        return $vpa;
    }

    protected function generateVpa(): string
    {
        if ($this->options[Generator::DESCRIPTOR] !== null)
        {
            $this->validateDescriptor();
        }

        $descriptor = $this->getDescriptor($this->merchantIdentifier);

        $prefix = $this->root . $this->merchantIdentifier;

        $vpa = strtolower($prefix . $descriptor . Entity::AROBASE . $this->handle);

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_NUMBER_GENERATED,
            [
                'root'               => $this->root,
                'merchantIdentifier' => $this->merchantIdentifier,
                'handle'             => $this->handle,
                'descriptor'         => $descriptor,
                'vpa'                => $vpa,
            ]
        );

        if (strlen($vpa) > (self::DYNAMIC_VPA_LENGTH + strlen($prefix) + strlen($this->handle) + 1))
        {
            throw new Exception\LogicException(
                'Error in VPA generation.',
                null,
                [
                    'VPA'        => $vpa,
                    'max_length' => self::DYNAMIC_VPA_LENGTH,
                ]);
        }

        return $vpa;
    }

    protected function getDescriptor(string $merchantIdentifier = null): string
    {
        $descriptor = $this->options[Generator::DESCRIPTOR];

        if ($descriptor !== null)
        {
            return $descriptor;
        }

        $totalLength = self::DYNAMIC_VPA_LENGTH;

        $availableLength = $totalLength - strlen($merchantIdentifier);

        $descriptor = $this->generateDescriptor($availableLength);

        return $descriptor;
    }

    protected function generateDescriptor(int $desiredLength): string
    {
        $pad = '';

        $charSpace = $this->getCharSpace();

        while (strlen($pad) < $desiredLength)
        {
            $pad .= $charSpace[array_rand($charSpace)];
        }

        return $pad;
    }

    protected function getCharSpace(): array
    {
        return str_split(self::VPA_NUM_CHAR_SPACE);
    }

    public function getConfigs(VirtualAccount\Entity $virtualAccount)
    {
        $vpa = $this->buildVpaEntity($virtualAccount);

        $this->setTerminalConfigsForVpa($vpa, $virtualAccount);

        return [
            'prefix'              => $this->root . $this->merchantIdentifier,
            'handle'              => $this->handle,
            'isDescriptorEnabled' => $this->isDescriptorEnabled,
        ];
    }
}
