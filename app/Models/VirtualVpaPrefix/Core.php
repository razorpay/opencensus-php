<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Processor\TerminalProcessor;
use RZP\Models\VirtualVpaPrefixHistory as PrefixHistory;

class Core extends Base\Core
{
    public function savePrefix(array $input) : Entity
    {
        $prefix = $input[Entity::PREFIX];

        $merchantId = $this->merchant->getId();

        if ($this->validatePrefixAvailability($prefix) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_VPA_PREFIX_UNAVAILABLE,
                Entity::PREFIX,
                $prefix
            );
        }

        // ToDo: Pass merchant_id after terminals are migrated.
        $terminal = (new TerminalProcessor())->getTerminalForUpiTransfer();

        if ($terminal->getMerchantId() === $merchantId)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_VPA_PREFIX_NOT_ALLOWED
            );
        }

        $count = $this->repo->virtual_vpa_prefix->getMerchantIdCount($merchantId);

        if ($count === 0)
        {
            return $this->create($input, $terminal);
        }
        else
        {
            return $this->update($input);
        }
    }

    public function create(array $input, Terminal\Entity $terminal) : Entity
    {
        $this->trace->info(
            TraceCode::VIRTUAL_VPA_PREFIX_CREATING,
            $input
        );

        $virtualVpaPrefix = $this->transaction(function () use ($input, $terminal)
        {
            $virtualVpaPrefix = (new Entity())->build($input);

            $virtualVpaPrefix->merchant()->associate($this->merchant);

            $virtualVpaPrefix->terminal()->associate($terminal);

            $this->repo->saveOrFail($virtualVpaPrefix);

            $virtualVpaPrefixHistory = (new PrefixHistory\Entity())->buildEntity($virtualVpaPrefix);

            $this->repo->saveOrFail($virtualVpaPrefixHistory);

            return $virtualVpaPrefix;
        });

        $this->trace->info(
            TraceCode::VIRTUAL_VPA_PREFIX_CREATED,
            $virtualVpaPrefix->toArray()
        );

        return $virtualVpaPrefix;
    }

    public function update(array $input) : Entity
    {
        $this->trace->info(
            TraceCode::VIRTUAL_VPA_PREFIX_UPDATING,
            $input
        );

        $virtualVpaPrefix = $this->transaction(function () use ($input)
        {
            $merchantId = $this->merchant->getId();

            $virtualVpaPrefix = $this->repo->virtual_vpa_prefix->fetchEntityByMerchantId($merchantId);

            (new PrefixHistory\Core())->deactivatePreviousPrefix($merchantId, $virtualVpaPrefix->getId());

            $previousPrefix = $virtualVpaPrefix->getPrefix();

            $virtualVpaPrefix->edit($input, 'edit');

            $this->repo->saveOrFail($virtualVpaPrefix);

            $virtualVpaPrefixHistory = (new PrefixHistory\Entity())->buildEntity($virtualVpaPrefix, [PrefixHistory\Entity::PREVIOUS_PREFIX => $previousPrefix]);

            $this->repo->saveOrFail($virtualVpaPrefixHistory);

            return $virtualVpaPrefix;
        });

        $this->trace->info(
            TraceCode::VIRTUAL_VPA_PREFIX_UPDATED,
            $virtualVpaPrefix->toArray()
        );

        return $virtualVpaPrefix;
    }

    public function validatePrefixAvailability(string $prefix) : bool
    {
        $count = $this->repo->virtual_vpa_prefix->getPrefixCount($prefix);

        if ($count !== 0)
        {
            $this->trace->error(
                TraceCode::VIRTUAL_VPA_PREFIX_UNAVAILABLE,
                [
                    Entity::PREFIX          => $prefix,
                ]
            );

            return false;
        }

        return true;
    }
}
