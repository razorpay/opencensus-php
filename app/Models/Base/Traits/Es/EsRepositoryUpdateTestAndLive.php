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
    public static $syncEnabled = true;

    public function disableSync()
    {
        self::$syncEnabled = false;
    }

    public function bulkUpdate(array $documents): array
    {
        $result = parent::bulkUpdate($documents);

        if (self::$syncEnabled === true)
        {
            $this->setIndexNameForAlternateMode();

            parent::bulkUpdate($documents);
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
        }
    }
}
