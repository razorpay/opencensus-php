<?php

// Borrowed from https://github.com/VentureCraft/revisionable
namespace RZP\Models\Base\Traits;

use App;
use RZP\Events\AuditLogEntry;
use RZP\Trace\TraceCode;

trait RevisionableTrait
{
    /**
     * @var array
     */
    private $originalData = [];

    /**
     * @var array
     */
    private $updatedData = [];

    /**
     * @var boolean
     */
    private $updating = false;

    /**
     * @var array
     */
    private $dontKeep = [];

    /**
     * @var array
     */
    private $doKeep = [];

    /**
     * Keeps the list of values that have been updated
     *
     * @var array
     */
    protected $dirtyData = [];

    /**
     * Ensure that the bootRevisionableTrait is called only
     * if the current installation is a laravel 4 installation
     * Laravel 5 will call bootRevisionableTrait() automatically
     */
    public static function boot()
    {
        parent::boot();

        if (!method_exists(get_called_class(), 'bootTraits'))
        {
            static::bootRevisionableTrait();
        }
    }

    /**
     * Create the event listeners for the saving and saved events
     * This lets us save revisions whenever a save is made, no matter the
     * http method.
     *
     */
    public static function bootRevisionableTrait()
    {
        static::saving(function ($model)
        {
            $model->preSave();
        });

        static::saved(function ($model)
        {
            $model->postSave();
        });

        static::created(function($model)
        {
            $model->postCreate();
        });

        static::deleting(function($model)
        {
            $model->preDelete();
        });

        static::deleted(function ($model)
        {
            //$model->preSave();

            $model->postDelete();
        });
    }

    /**
     * Invoked before a model is saved. Return false to abort the operation.
     *
     * @return bool
     */
    public function preSave()
    {
        if (!isset($this->revisionEnabled) or $this->revisionEnabled)
        {
            // if there's no revisionEnabled. Or if there is, if it's true

            $this->originalData = $this->original;

            $this->updatedData = $this->attributes;

            // we can only safely compare basic items,
            // so for now we drop any object based items, like DateTime
            foreach ($this->updatedData as $key => $val)
            {
                if ((gettype($val) === 'object') and
                    (method_exists($val, '__toString') === false))
                {
                    unset($this->originalData[$key]);

                    unset($this->updatedData[$key]);

                    array_push($this->dontKeep, $key);
                }
            }

            // the below is ugly, for sure, but it's required so we can save the standard model
            // then use the keep / dontkeep values for later, in the isRevisionable method
            $this->dontKeep = isset($this->dontKeepRevisionOf) ?
                array_merge($this->dontKeepRevisionOf, $this->dontKeep)
                : $this->dontKeep;

            $this->doKeep = isset($this->keepRevisionOf) ?
                array_merge($this->keepRevisionOf, $this->doKeep)
                : $this->doKeep;

            unset($this->attributes['dontKeepRevisionOf']);

            unset($this->attributes['keepRevisionOf']);

            $this->dirtyData = $this->getDirty();

            $this->updating = $this->exists;
        }
    }

    /**
     * Called before the model is deleted
     */

    public function preDelete()
    {
        $app = App::getFacadeRoot();

        if (!isset($this->revisionEnabled) or $this->revisionEnabled)
        {
            // if there's no revisionEnabled. Or if there is, if it's true

            $this->originalData = $this->original;
        }

        unset($this->originalData['dontKeepRevisionOf']);

        unset($this->originalData['keepRevisionOf']);

        //$removeFields = ['created_at', 'updated_at', 'deleted_at'];

        //unset($this->originalData[$removeFields]);
    }


    /**
     * Called after a model is successfully saved.
     *
     * @return void
     */
    public function postSave()
    {
        // check if the model already exists
        if ((!isset($this->revisionEnabled) or ($this->revisionEnabled)) and ($this->updating))
        {
            // if it does, it means we're updating

            $formattedRevisons = $this->getFormattedRevisionsForPostSave();

            if ((empty($formattedRevisons) === false) and (empty($this->getAuditAction()) === false))
            {
                $trace = $this->getTrace();

                $admin = $this->getAdmin();

                if ($admin === null)
                {
                    return;
                }

                $action = $this->getAuditAction();

                event(new AuditLogEntry(
                        $admin->toArrayPublic(),
                        $action,
                        $formattedRevisons)
                );

                $this->resetAuditAction();
            }
        }
    }

    /**
     * Called after record successfully created
     */
    public function postCreate()
    {
        $app = App::getFacadeRoot();
        // Check if we should store creations in our revision history
        // Set this value to true in your model if you want to
        if(empty($this->revisionCreationsEnabled))
        {
            // We should not store creations.
            return false;
        }

        if ((!isset($this->revisionEnabled) or $this->revisionEnabled))
        {
            $formatted = $this->getFormattedRevisionsForPostCreate();

            $trace = $this->getTrace();

            if (empty($this->getAuditAction()) === false)
            {
                $admin = $this->getAdmin();

                if ($admin === null)
                {
                    return;
                }

                $action = $this->getAuditAction();

                $trace->info(TraceCode::HEIMDALL_AUDIT_LOG, ["admin" => $admin, "action" => $action]);

                event(new AuditLogEntry(
                        $admin->toArrayPublic(),
                        $action,
                        $formatted)
                );

            }
        }
    }

    /**
     * If softdeletes are enabled, store the deleted time
     */
    public function postDelete()
    {
        $app = App::getFacadeRoot();

        if (((isset($this->revisionEnabled) === false) or ($this->revisionEnabled)) and
             ($this->isSoftDelete() === true) and
             ($this->isRevisionable($this->getDeletedAtColumn()) === true))
        {
            $revisions = [
                'revisionable_type' => $this->getMorphClass(),
                'revisionable_id' => $this->getKey(),
                'deleted' => $this->originalData
            ];

            $formatted = $this->formatRevisions($revisions, 'DELETE');

            if (empty($this->getAuditAction()) === false)
            {
                $trace = $this->getTrace();

                $admin = $this->getAdmin();

                $action = $this->getAuditAction();

                $trace->info(TraceCode::HEIMDALL_AUDIT_LOG, ["admin" => $admin, "action" => $action]);

                event(new AuditLogEntry(
                        $admin->toArrayPublic(),
                        $action,
                        $formatted)
                );

                $this->resetAuditAction();
            }
        }
    }

    /**
     * Get all of the changes that have been made, that are also supposed
     * to have their changes recorded
     *
     * @return array fields with new data, that should be recorded
     */
    private function changedRevisionableFields()
    {
        $changes_to_record = array();

        foreach ($this->dirtyData as $key => $value)
        {
            // check that the field is revisionable, and double check
            // that it's actually new data in case dirty is, well, clean
            if (($this->isRevisionable($key) === true) and
                (is_array($value) === false))
            {
                if ((isset($this->originalData[$key]) === false) or
                    ($this->originalData[$key] != $this->updatedData[$key]))
                {
                    $changes_to_record[$key] = $value;
                }
            }
            else
            {
                // we don't need these any more, and they could
                // contain a lot of data, so lets trash them.
                unset($this->updatedData[$key]);

                unset($this->originalData[$key]);
            }
        }

        return $changes_to_record;
    }

    /**
     * Check if this field should have a revision kept
     *
     * @param string $key
     *
     * @return bool
     */
    private function isRevisionable($key)
    {

        // If the field is explicitly revisionable, then return true.
        // If it's explicitly not revisionable, return false.
        // Otherwise, if neither condition is met, only return true if
        // we aren't specifying revisionable fields.
        if ((isset($this->doKeep) === true) and
            (in_array($key, $this->doKeep) === true))
        {
            return true;
        }
        if ((isset($this->dontKeep) === true) and
            (in_array($key, $this->dontKeep) === true))
        {
            return false;
        }

        return empty($this->doKeep);
    }

    /**
     * Check if soft deletes are currently enabled on this model
     *
     * @return bool
     */
    private function isSoftDelete()
    {
        // check flag variable used in laravel 4.2+
        if (isset($this->forceDeleting))
        {
            return !$this->forceDeleting;
        }

        // otherwise, look for flag used in older versions
        if (isset($this->softDelete))
        {
            return $this->softDelete;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getRevisionFormattedFields()
    {
        return $this->revisionFormattedFields;
    }

    /**
     * @return mixed
     */
    public function getRevisionFormattedFieldNames()
    {
        return $this->revisionFormattedFieldNames;
    }

    /**
     * Identifiable Name
     * When displaying revision history, when a foreign key is updated
     * instead of displaying the ID, you can choose to display a string
     * of your choice, just override this method in your model
     * By default, it will fall back to the models ID.
     *
     * @return string an identifying name for the model
     */
    public function identifiableName()
    {
        return $this->getKey();
    }

    /**
     * Revision Unknown String
     * When displaying revision history, when a foreign key is updated
     * instead of displaying the ID, you can choose to display a string
     * of your choice, just override this method in your model
     * By default, it will fall back to the models ID.
     *
     * @return string an identifying name for the model
     */
    public function getRevisionNullString()
    {
        return isset($this->revisionNullString) ? $this->revisionNullString : 'nothing';
    }

    /**
     * No revision string
     * When displaying revision history, if the revisions value
     * cant be figured out, this is used instead.
     * It can be overridden.
     *
     * @return string an identifying name for the model
     */
    public function getRevisionUnknownString()
    {
        return isset($this->revisionUnknownString) ? $this->revisionUnknownString : 'unknown';
    }

    /**
     * Disable a revisionable field temporarily
     * Need to do the adding to array longhanded, as there's a
     * PHP bug https://bugs.php.net/bug.php?id=42030
     *
     * @param mixed $field
     *
     * @return void
     */
    public function disableRevisionField(mixed $field)
    {
        if (!isset($this->dontKeepRevisionOf))
        {
            $this->dontKeepRevisionOf = array();
        }
        if (is_array($field))
        {
            foreach ($field as $one_field)
            {
                $this->disableRevisionField($one_field);
            }
        }
        else
        {
            $donts = $this->dontKeepRevisionOf;

            $donts[] = $field;

            $this->dontKeepRevisionOf = $donts;

            unset($donts);
        }
    }

    protected function getAdmin()
    {
        try
        {
            $app = App::getFacadeRoot();

            return $app['basicauth']->getAdmin();
        }
        catch(\Exception $e)
        {
            return null;
        }
    }

    protected function getTrace()
    {
        $app = App::getFacadeRoot();

        return $app['trace'];
    }

    protected function getMode()
    {
        $app = App::getFacadeRoot();

        return $app['rzp.mode'];
    }

    protected function formatRevisions(array $revisions, $mode)
    {
        if ((empty($revisions) === true) or (is_array($revisions) === false))
        {
            return null;
        }

        $formatted = [];

        $changes = [
            'old' => [],
            'new' => []
        ];

        $entityName = null;

        $entityId = null;

        if ($mode === 'EDIT')
        {
            list($entityName, $entityId) = $this->getPrefix($revisions[0]);

            foreach($revisions as $revision)
            {
                $key = $revision['key'];

                $changes['old'][$key] = $revision['old_value'];

                $changes['new'][$key] = $revision['new_value'];
            }
        }
        if ($mode === 'CREATE')
        {
            list($entityName, $entityId) = $this->getPrefix($revisions);

            unset($changes['old']);

            $changes['new'] = $revisions['created'];
        }
        if ($mode === 'DELETE')
        {
            list($entityName, $entityId) = $this->getPrefix($revisions);

            unset($changes['new']);

            $changes['old'] = $revisions['deleted'];
        }

        $formatted['entity'] = [
            'id' => $entityId,
            'name' => $entityName,
            'change' => $changes
        ];

        return $formatted;
    }

    protected function getPrefix($revision)
    {
        $entityName = null;

        $entityId = null;

        try
        {
            if ((empty($revision) === false) and (is_array($revision) === true))
            {
                $entityName = $revision['revisionable_type'];

                $entityId = $revision['revisionable_id'];
            }

        }
        catch(\Exception $e)
        {
            return null;
        }

        return [$entityName, $entityId];
    }

    protected function getFormattedRevisionsForPostSave(): ?array
    {
        $changes_to_record = $this->changedRevisionableFields();

        $revisions = [];

        foreach ($changes_to_record as $key => $change)
        {
            $revisions[] = [
                'revisionable_type' => $this->getMorphClass(),
                'revisionable_id'   => $this->getKey(),
                'key'               => $key,
                'old_value'         => array_get($this->originalData, $key),
                'new_value'         => $this->updatedData[$key]
            ];
        }

        return $this->formatRevisions($revisions, 'EDIT');
    }

    protected function getFormattedRevisionsForPostCreate(): ?array
    {
        $created = $this->toArrayPublic();

        //$removeFields = ['created_at','updated_at','deleted_at'];

        //unset($this->originalData[$removeFields]);

        $revisions = [
            'revisionable_type' => $this->getMorphClass(),
            'revisionable_id'   => $this->getKey(),
            'created'           => $created
        ];

        return $this->formatRevisions($revisions, 'CREATE');
    }
}
