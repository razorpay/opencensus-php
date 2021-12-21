<?php

namespace RZP\Models\Merchant\Detail;

class FileType
{
    const ALLOWED_EXTENSIONS = [
        'pdf',
        'png',
        'jpg',
        'jpeg',
        'zip',
        'xml',
        'csv',
    ];

    const ALLOWED_MIMES = [
       'image/jpeg',
       'image/png',
       'application/pdf',
       'application/x-pdf',
       'application/zip',
       'text/xml',
       'application/xml',
       'application/octet-stream',
       'text/csv',
       'text/plain',
    ];
}
