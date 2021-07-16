<?php


namespace RZP\Models\Dispute\Evidence\Document;

use RZP\Constants\Table;
use Elasticsearch\Endpoints\Count;
use RZP\Models\Base\Repository as BaseRepository;


class Repository extends BaseRepository
{
    public $entity = Table::DISPUTE_EVIDENCE_DOCUMENT;

    protected $merchantIdRequiredForMultipleFetch = false;

    public function getDocumentsForDispute(string $disputeId)
    {
        return $this->repo->dispute_evidence_document->fetch([
            Entity::DISPUTE_ID => $disputeId,
            Repository::COUNT  => 100,
        ]);
    }

    public function deleteDocumentsForDispute(string $disputeId)
    {
        $this->repo
            ->dispute_evidence_document
            ->newQuery()
            ->where(Entity::DISPUTE_ID, '=', $disputeId)
            ->delete();

    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::ID, 'desc');
    }
}