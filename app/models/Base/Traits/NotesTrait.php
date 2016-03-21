<?php

namespace Models\Base\Traits;

trait NotesTrait
{
	/**************************************************************
	 * Setter
	 **************************************************************
	 */
	public function setNotesAttribute($notes)
    {
        $this->attributes[self::NOTES] = json_encode($notes);
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