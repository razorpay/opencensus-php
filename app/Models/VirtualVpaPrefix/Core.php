<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
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

        // We are taking UPI ICICI for VPA live with razorx experiment.
        //This is to ensure correct terminal config is used to vpa custom prefix.
        // Once everything is moved to ICICI and mindgate terminals are disabled, this can be removed.
        $gateway = Gateway::UPI_ICICI;

        // ToDo: Pass merchant_id after terminals are migrated.
        $terminal = (new TerminalProcessor())->getTerminalForUpiTransfer(null, $gateway);

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
            return $this->update($input, $terminal);
        }
    }

    public function create(array $input, Terminal\Entity $terminal) : Entity
    {
        $this->trace->info(
            TraceCode::VIRTUAL_VPA_PREFIX_CREATING,
            $input
        );

        try
        {
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

            (new Metric())->pushSuccessMetrics('create');

            return $virtualVpaPrefix;
        }
        catch(\Exception $ex)
        {
            (new Metric())->pushFailedMetrics('create', $ex);

            throw $ex;
        }
    }

    public function update(array $input, Terminal\Entity $terminal) : Entity
    {
        $this->trace->info(
            TraceCode::VIRTUAL_VPA_PREFIX_UPDATING,
            $input
        );

        try
        {
            $virtualVpaPrefix = $this->transaction(function () use ($input, $terminal)
            {
                $merchantId = $this->merchant->getId();

                $virtualVpaPrefix = $this->repo->virtual_vpa_prefix->fetchEntityByMerchantId($merchantId);

                (new PrefixHistory\Core())->deactivatePreviousPrefix($merchantId, $virtualVpaPrefix->getId());

                $previousPrefix = $virtualVpaPrefix->getPrefix();

                $virtualVpaPrefix->terminal()->associate($terminal);

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

            (new Metric())->pushSuccessMetrics('update');

            return $virtualVpaPrefix;
        }
        catch(\Exception $ex)
        {
            (new Metric())->pushFailedMetrics('update', $ex);

            throw $ex;
        }
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
