<?php

namespace RZP\Models\Base\Traits;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use RZP\Models\Base\Notes;

trait NotesTrait
{
    // -------------------------------------- Setters --------------------------------------

    protected function setNotesAttribute($notes)
    {
        if ($notes === '')
        {
            $code = ErrorCode::BAD_REQUEST_NOTES_SHOULD_BE_ARRAY;

            throw new Exception\BadRequestException($code, self::NOTES);
        }

        if ($notes === null)
        {
            $notes = [];
        }

        $notesObj = new Notes($notes);
        $this->attributes[self::NOTES] = $notesObj->toJson();
    }

    public function setNotes(array $notes)
    {
        $this->setAttribute(self::NOTES, $notes);
    }

    public function appendNotes(array $newNotes)
    {
        if (empty($newNotes) === true)
        {
            return;
        }

        $oldNotes = $this->getNotes()->toArray();

        // If the key is present in both $oldNotes and $newNotes, it will be overwritten with the new value.
        $notes = array_merge($oldNotes, $newNotes);

        // Performs validations over the new notes array generated
        (new JitValidator)->rules(['notes' => 'sometimes|notes'])
                          ->input(compact('notes'))
                          ->validate();

        $this->setAttribute(self::NOTES, $notes);
    }

    // -------------------------------------- End Setters --------------------------------------

    // -------------------------------------- Getters --------------------------------------

    /**
     * Makes sure that getNotes always returns an object
     */
    protected function getNotesAttribute($notes)
    {
        $notesArray = json_decode($notes, true);

        if (empty($notesArray) === true)
        {
            return new Notes();
        }

        return new Notes($notesArray);
    }

    /**
     * Returns notes object
     *
     *  @ \RZP\Models\Payment\Notes;
     */
    public function getNotes()
    {
        return $this->getAttribute(self::NOTES);
    }

    /**
     * Returns notes object as json
     *
     *  @return string;
     */
    public function getNotesJson()
    {
        return $this->attributes[self::NOTES];
    }

    // -------------------------------------- End Getters --------------------------------------
}
