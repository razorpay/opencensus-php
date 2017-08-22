<?php

namespace RZP\Models\Base\Traits\Es;

trait EsRepositoryUpdateTestAndLive
{
    /**
     * By default all EsRepository which usage this trait
     * will have write operation done on both _live and _test suffixed
     * indices if either of it gets writes.
     *
     * But there is one case (rzp:index) where we don't want this to happen.
     *
     * @var boolean
     */
    protected static $syncEnabled = true;

    public function disableSync()
    {
        self::$syncEnabled = false;
    }

    //
    // Following 2 methods work as following:
    // - We run the parent's method. This updates the index
    //   in current mode.
    // - We set index name for alternate mode.
    // - We run the parent's method again.
    // - We reset index name.
    //

    public function bulkUpdate(array $documents): array
    {
        $result = parent::bulkUpdate($documents);

        if (self::$syncEnabled === true)
        {
            $this->setIndexNameForAlternateMode();

            parent::bulkUpdate($documents);

            $this->setIndexName();
        }

        return $result;
    }

    public function deleteDocument(string $id)
    {
        parent::deleteDocument($id);

        if (self::$syncEnabled === true)
        {
            $this->setIndexNameForAlternateMode();

            parent::deleteDocument($id);

            $this->setIndexName();
        }
    }
}
