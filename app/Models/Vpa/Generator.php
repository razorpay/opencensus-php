<?php

namespace RZP\Models\Vpa;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Models\VirtualAccount\Provider;

class Generator extends Base\Core
{
    const DYNAMIC_VPA_LENGTH = 20;

    const DESCRIPTOR = 'descriptor';

    const MAX_VPA_GENERATION_ATTEMPTS = 10;

    const VPA_NUM_CHAR_SPACE = '0123456789';

    const VA_VPA_GENERATION = 'VA_VPA_GENERATION';

    protected $mutex;

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

    public function generate(VirtualAccount\Entity $virtualAccount): Entity
    {
        $vpa = $this->buildVpaEntity($virtualAccount);

        $terminal = $this->getTerminalForVpa($vpa);

        $attempts = 0;

        while ($attempts <= self::MAX_VPA_GENERATION_ATTEMPTS)
        {
            $vpa = $this->createAndSetVpaAddress($vpa, $terminal);

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
                    ->findByAddress($vpa->getAddress());

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

    protected function validateDescriptor(Terminal\Entity $terminal)
    {
        $descriptor = $this->options[self::DESCRIPTOR];

        if (strlen($terminal->getVirtualUpiMerchantPrefix() . $descriptor) !== self::DYNAMIC_VPA_LENGTH)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_DESCRIPTOR_LENGTH,
                'descriptor',
                [
                    'merchant_prefix' => $terminal->getVirtualUpiMerchantPrefix(),
                    'descriptor'      => $descriptor,
                ]);
        }

        if ($terminal->isShared() === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Descriptor cannot be used with your account.',
                null,
                [
                    'options'       => $this->options,
                    'merchant_id'   => $this->merchant->getId(),
                ]);
        }
    }

    protected function buildVpaEntity(VirtualAccount\Entity $virtualAccount): Entity
    {
        $vpa = new Entity();

        $vpa->merchant()->associate($this->merchant);

        $vpa->source()->associate($virtualAccount);

        return $vpa;
    }

    protected function getTerminalForVpa(Entity $vpa): Terminal\Entity
    {
        $terminal = (new Provider())->getTerminalForMethod(Method::UPI, $vpa, null, $this->options);

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

        return $terminal;
    }

    protected function createAndSetVpaAddress(Entity $vpa, Terminal\Entity $terminal): Entity
    {
        $vpaAddress = $this->generateVpa($terminal);

        $vpa->build([Entity::ADDRESS => $vpaAddress], "createVirtualVpa");

        return $vpa;
    }

    protected function generateVpa(Terminal\Entity $terminal): string
    {
        $root               = $terminal->getVirtualUpiRoot();
        $merchantIdentifier = $terminal->getVirtualUpiMerchantPrefix();
        $handle             = $terminal->getVirtualUpiHandle();
        $prefix             = $root . $merchantIdentifier;

        if ($this->options[Generator::DESCRIPTOR] !== null)
        {
            $this->validateDescriptor($terminal);
        }

        $descriptor = $this->getDescriptor($merchantIdentifier);

        $vpa = strtolower($prefix . $descriptor . Entity::AROBASE . $handle);

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_NUMBER_GENERATED,
            [
                'root'               => $root,
                'merchantIdentifier' => $merchantIdentifier,
                'handle'             => $handle,
                'descriptor'         => $descriptor,
                'vpa'                => $vpa,
                'terminalId'         => $terminal->getId(),
            ]
        );

        if (strlen($vpa) > (self::DYNAMIC_VPA_LENGTH + strlen($prefix) + strlen($handle) + 1))
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
}
