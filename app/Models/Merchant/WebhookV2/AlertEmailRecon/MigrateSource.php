<?php


namespace RZP\Models\Merchant\Webhook\AlertEmailRecon;


use Generator;
use RZP\Modules\Migrate\Record;
use RZP\Modules\Migrate\Source;
use RZP\Models\Merchant\Entity as MerchantEntity;

class MigrateSource implements Source
{
    const CHUNK_SIZE_IDS = 1000;

    public function getParallelOpts(array $opts): Generator
    {
        $allMids = $opts['mids'] ?? null;

        if (($allMids === null) || (is_array($allMids) === false))
        {
            return;
        }

        foreach (array_chunk($allMids, self::CHUNK_SIZE_IDS) as $chunkedMids)
        {
            yield ['mids' => $chunkedMids];
        }
    }

    public function iterate(array $opts): Generator
    {
        /** @var \RZP\Base\RepositoryManager */
        $repo = app('repo');

        $mids = $opts['mids'] ?? null;
        if ($mids === null)
        {
            return;
        }

        $merchants = $repo->useSlave(function() use ($repo, $mids)
        {
            return $repo->merchant->findManyByPublicIds($mids);
        });

        foreach ($merchants as $merchant)
        {
            $email = empty($merchant[MerchantEntity::TRANSACTION_REPORT_EMAIL][0]) === true ?
                                $merchant[MerchantEntity::EMAIL] : $merchant[MerchantEntity::TRANSACTION_REPORT_EMAIL][0];
            yield new Record($merchant->getId(), $email);
        }
    }

    public function find(Record $targetRecord)
    {
        // TODO: Implement find() method.
    }
}
