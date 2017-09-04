<?php

namespace RZP\Models\Gateway\File;

class FailureCode
{
    const ERROR_SENDING_MAIL          = 'error_sending_mail';
    const ERROR_CREATING_FILE         = 'error_creating_file';
    const ERROR_GENERATING_FILE_DATA  = 'error_generating_file_data';
    const NO_DATA_FOR_FILE_GENERATION = 'no_data_for_file_generation';
}
