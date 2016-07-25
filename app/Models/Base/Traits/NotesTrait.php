<?php

namespace RZP\Models\Base\Traits;

use RZP\Exception;
use RZP\Error\ErrorCode;

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

        $this->attributes[self::NOTES] = utf8_json_encode($notes);
    }

    /**************************************************************
     * Getters
     **************************************************************
     */

    /**
     * Makes sure that getNotes always returns an array
     */
    protected function getNotesAttribute($notes)
    {
        $notesArray = json_decode($notes, true);

        if ($notesArray === '')
        {
            return [];
        }

        return $notesArray;
    }

    public function setNotes($notes)
    {
        $this->setAttribute(self::NOTES, $notes);
    }

    public function getNotes()
    {
        return $this->getAttribute(self::NOTES);
    }

    public function getNotesJson()
    {
        return $this->attributes[self::NOTES];
    }
}