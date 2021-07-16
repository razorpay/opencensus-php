<?php


namespace RZP\Models\Dispute\Evidence;


class Constants
{
    const ACTION       = 'action';
    const DOCUMENT_IDS = 'document_ids';

    const IGNORE_FIELDS_FOR_UPDATE_REQUEST = [
        Entity::SUBMITTED_AT
    ];
}