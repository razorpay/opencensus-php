<?php

namespace RZP\Models\Base\Traits;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Notes;

trait NotesTrait
{
	/**************************************************************
	 * Setter
	 **************************************************************
	 */
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

    /**************************************************************
     * Getters
     **************************************************************
     */

    /**
     * Makes sure that getNotes always returns an object
     */
    protected function getNotesAttribute($notes)
    {
        $notesArray = json_decode($notes, true);

        if ($notesArray === '')
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
}
