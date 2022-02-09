<?php

namespace RZP\Services\AutoGenerateApiDocs;

class Constants
{
    const FILES_DIR = './_docs/';

    const TOTAL_API_DETAILS_FILES = 10;

    const API_SUMMARY                          = 'api_summary';

    const API_REQUEST_DESCRIPTION              = 'api_request_description';

    const API_RESPONSE_DESCRIPTION             = 'api_response_description';

    const API_DOCUMENTATION_DETAILS            = 'api_documentation_details';

    const COMBINED_API_DETAILS_FILE_NAME       = 'combined.json';

    const OPEN_API_SPEC_FILE_NAME              = 'open_api_spec.json';

    const CONTENT_TYPE_APPLICATION_JSON        = 'application/json';

    const INPUT_SET_API_LIMIT                  = 3;

    const COMBINED_API_DETAILS_FILE_PATH = Constants::FILES_DIR.Constants::COMBINED_API_DETAILS_FILE_NAME;
}
