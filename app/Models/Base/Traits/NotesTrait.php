<?php

namespace RZP\Models\Base\Traits;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Notes;
use Razorpay\Spine\DataTypes\Dictionary;

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

        $notes = $notes ?: [];

        $this->attributes[self::NOTES] = (new Dictionary($notes))->toJson();
    }

    public function setNotes(array $notes)
    {
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
            $notesArray = [];
        }

        return new Dictionary($notesArray);
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
