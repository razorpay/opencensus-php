<?php

namespace RZP\Models\Admin\File;

use Mail;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Admin;
use RZP\Constants\Timezone;
use RZP\Mail\Admin\FileUpload\Dispute as DisputeFileMailer;

class Core extends Base\Core
{
    const FILE = 'file';
    const TYPE = 'type';

    public function uploadFile(Admin\Entity $admin, string $type, array $fileInput)
    {
        $this->trace->info(
            TraceCode::ADMIN_FILES_UPLOAD,
            [
                'admin' => $admin->getId(),
            ]);

        $validator = new Validator;

        $validator->validateInput('upload', $fileInput);

        $file = $fileInput[self::FILE];

        $validator->validateType(self::TYPE, $type);

        $ufhService = $this->app['ufh.service'];

        $storageName = $this->getStorageFileName($type, $admin);

        $fileDetails = $ufhService->uploadFileAndGetUrl(
                                                    $file,
                                                    $storageName,
                                                    $type,
                                                    $admin);

        $attachment = $ufhService->getSignedUrl($fileDetails['file_id']);

        $this->trace->info(
            TraceCode::ADMIN_FILES_UPLOAD,
            [
                'file_id' => $fileDetails['file_id'],
            ]);

        $data = [
            'orgName' => $admin->org->getBusinessName(),
            'file'    => $attachment,
        ];

        Mail::queue(new DisputeFileMailer($data));

        $this->deleteLocalFile($file, $type);
    }

    /**
     * Relative path in the assigned bucket along with custom file name
     *
     * @param  string $type
     * @param  Admin\Entity $admin
     *
     * @return string
     */
    protected function getStorageFileName(string $type, Admin\Entity $admin): string
    {
        $bankCustomCode = $admin->org->getCustomCode();

        $fileName = $this->getCustomFileName($type);

        return $bankCustomCode . '/' . $fileName;
    }

    /**
     * Storing all files with a custom name that has type and upload time in it for
     * better consistency and ease of use by our teams dealing with the files
     *
     * @param  string $type
     *
     * @return string
     */
    protected function getCustomFileName(string $type)
    {
        $currentTimeSuffix = Carbon::now(Timezone::IST)->format('Y-m-d H:i:s');

        return $type . '_' . $currentTimeSuffix;
    }

    /**
     * @param UploadedFile $file
     * @param string       $type
     */
    protected function deleteLocalFile(UploadedFile $file, string $type)
    {
        $name = $this->getCustomFileName($type);

        $ext = $file->getClientOriginalExtension();

        $filePath = storage_path('files/filestore') . '/' . $name . '.' . $ext;

        if (($filePath !== null) and (file_exists($filePath) === true))
        {
            $success = unlink($filePath); // nosemgrep : php.lang.security.unlink-use.unlink-use

            if ($success === false)
            {
                $this->trace->error(TraceCode::ADMIN_FILE_DELETE_ERROR, ['file_path' => $filePath]);
            }
        }
    }
}
