<?php

namespace Models\Base\Traits;

use EE\Exception;
use EE\Error\ErrorCode;

trait NotesTrait
{
	/**************************************************************
	 * Setter
	 **************************************************************
	 */
	public function setNotesAttribute($notes)
    {
        if ($notes === '')
        {
            $code = ErrorCode::BAD_REQUEST_NOTES_SHOULD_BE_ARRAY;

            throw new Exception\BadRequestException($code, 'notes');
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
    public function getNotesAttribute($notes)
    {
        $notesArray = json_decode($notes, true);

        if ($notesArray === '')
        {
            return [];
        }

        return $notesArray;
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