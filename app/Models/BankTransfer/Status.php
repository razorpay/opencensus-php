<?php

namespace RZP\Models\BankTransfer;

// For details about introduction of States for BT. Please check
// https://docs.google.com/document/d/1pKb0sl7_2VeQ1cZ3moSsgmc8063mfMjRZo-CHlyn0sE/edit?usp=sharing
class Status
{
    const CREATED       = 'created';
    const PROCESSED     = 'processed';
    const FAILED        = 'failed';
}
