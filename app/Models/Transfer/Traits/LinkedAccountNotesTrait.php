<?php

namespace RZP\Models\Transfer\Traits;

trait LinkedAccountNotesTrait
{
    /**
     * Linked account notes which are passed on to payment/refunds
     * are stored in transfer/reversal notes with comma seprated values.
     * @param $laNotes
     */
    public function setLinkedAccountNotesAttribute($laNotes)
    {
        $notes = $this->getNotes();

        if (empty($laNotes) === false)
        {
            $laNotes = implode(',', $laNotes);

            $notes[self::LINKED_ACCOUNT_NOTES] = $laNotes;

            $this->setNotes($notes->toArray());
        }
    }

    /**
     * While reterving the linked account notes we serialize the notes for transfer/reversals.
     *
     * @param array $attributes
     */
    public function setPublicLinkedAccountNotesAttribute(array & $attributes)
    {
        $notes = $this->getNotes();

        $laNotesList = $notes[self::LINKED_ACCOUNT_NOTES] ?? [];

        if ((empty($notes) === false) and (empty($laNotesList) === false))
        {
            $attributes[self::LINKED_ACCOUNT_NOTES] = explode(',', $laNotesList);
            unset($attributes[self::NOTES][self::LINKED_ACCOUNT_NOTES]);
        }
        else
        {
            $attributes[self::LINKED_ACCOUNT_NOTES] = [];
        }
    }

    /**
     * This function will fetch the linked account notes keys from notes
     * and return the list.
     */
    public function getLinkedAccountNotes()
    {
        $notes = $this->getNotes();

        $laNotesList = $notes[self::LINKED_ACCOUNT_NOTES] ?? [];

        if ((empty($notes) === false) and (empty($laNotesList) === false))
        {
            return explode(',', $laNotesList);
        }

        return $laNotesList;
    }
}
