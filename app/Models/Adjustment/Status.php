<?php

namespace RZP\Models\Adjustment;
// For details on why status is being introduced for Adjustments
// Refer - https://docs.google.com/document/d/1pKb0sl7_2VeQ1cZ3moSsgmc8063mfMjRZo-CHlyn0sE/edit
class Status
{
    const CREATED   = 'created';
    const FAILED    = 'failed';
    const PROCESSED = 'processed';
}
