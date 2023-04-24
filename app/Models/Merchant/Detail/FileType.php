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
        '3g2',
        '3gp',
        'avi',
        'flv',
        'h264',
        'm4v',
        'mkv',
        'mov',
        'mp4',
        'mpg',
        'mpeg',
        'rm',
        'wmv',
        'jfif',
        'heic',
        'heif'
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
        'video/3gpp2',
        'video/3gpp',
        'video/x-msvideo',
        'video/x-flv',
        'video/mp4',
        'video/m4v',
        'video/x-matroska',
        'video/quicktime',
        'video/mp4',
        'video/mpeg',
        'video/mpeg',
        'application/vnd.rn-realmedia',
        'video/x-ms-wmv',
        'image/jfif',
        'image/heic',
        'image/heif'
    ];
}
