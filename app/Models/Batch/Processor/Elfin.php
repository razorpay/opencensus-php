<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;

class Elfin extends Base
{
    /**
     * @var \RZP\Services\Elfin\Service
     */
    protected $elfin;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->elfin = $this->app['elfin'];
    }

    /**
     * {@inheritDoc}
     */
    protected function processEntry(array & $entry)
    {
        $longUrl  = $entry[Header::ELFIN_LONG_URL];
        $ptype    = $this->settingsAccessor->get('ptype');
        $shortUrl = $this->elfin->shorten($longUrl, ['ptype' => $ptype], true);

        $entry[Header::STATUS]          = Status::SUCCESS;
        $entry[Header::ELFIN_SHORT_URL] = $shortUrl;
    }

    /**
     * {@inheritDoc}
     */
    protected function updateBatchPostValidation(array $entries, array $input)
    {
        $totalCount  = count($entries);

        $this->batch->setTotalCount($totalCount);
    }

    /**
     * {@inheritDoc}
     */
    protected function sendProcessedMail()
    {
        return;
    }
}
