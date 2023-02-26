<?php

namespace RZP\Models\Merchant;

use Config;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\FileStore\Storage\AwsS3\Handler;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

class Logo extends Base\Core
{
    const ORIGINAL_SIZE  = 'original';
    const SMALL_SIZE     = 'small';
    const MEDIUM_SIZE    = 'medium';
    const LARGE_SIZE     = 'large';

    const JPG_EXTENSION  = 'jpg';
    const JPEG_EXTENSION = 'jpeg';

    public function setUpMerchantLogo($input)
    {
        $logoImage = $input['logo'];

        $extension = $logoImage->getClientOriginalExtension();

        $fileName = UniqueIdEntity::generateUniqueId()
                    .'.'
                    .$extension;

        $destinationPath = storage_path('files/logos');

        $mimeType = $logoImage->getMimeType();

        $merchantValidator = new Merchant\Validator;

        // Performs validation checks on an image.
        $merchantValidator->validateImage($mimeType, $extension);

        $imageDetails = [
            'file_name'  => $fileName,
            'extension'  => $extension,
            'mime_type'  => $mimeType,
            'size'       => $logoImage->getSize(),
            'width'      => \getimagesize($logoImage)[0],
            'height'     => \getimagesize($logoImage)[1],
            'file_path'  => $destinationPath . '/' . $fileName,
        ];

        $this->trace->info(
            TraceCode::LOGO_IMAGE_DETAILS,
            $imageDetails
        );

        // Performs validation checks on the logo.
        $merchantValidator->validateLogo($imageDetails);

        // Moves locally.
        $logoImage->move($destinationPath, $fileName);

        try
        {
            // Create local copies of different sizes of the logo.
            $this->resizeImage($imageDetails);

            // Store the logos in AWS
            $logoUrl = $this->saveToAws($imageDetails);
        }
        catch (Exception\BaseException $e)
        {
            $e->setData($imageDetails);

            throw $e;
        }
        finally
        {
            $this->deleteLogosLocally($imageDetails);
        }

        return $logoUrl;
    }

    protected function deleteLogosLocally($imageDetails)
    {
        $baseFilePath = $imageDetails['file_path'];
        $logoDimensions = $this->getLogoDimensionsArray();

        foreach ($logoDimensions as $size => $_)
        {
            $filePath = $this->getLogoFilePath($baseFilePath, $size);
            $this->deleteFile($filePath);
        }

        $this->deleteFile($baseFilePath);
    }

    protected function resizeImage($imageDetails)
    {
        $width = $imageDetails['width'];
        $height = $imageDetails['height'];
        $baseFilePath = $imageDetails['file_path'];

        // Different dimensions of the images that need to be created and stored.
        $logoDimensions = $this->getLogoDimensionsArray($width, $height);

        $src = $this->createImageObject($imageDetails);

        // Creates an image of different dimensions and stores them locally.
        foreach ($logoDimensions as $size => $dimension)
        {
            $newWidth = $dimension[0];
            $newHeight = $dimension[1];

            $extension = $imageDetails['extension'];

            // Appends the size to the file name in the file path.
            $filePath = $this->getLogoFilePath($baseFilePath, $size);

            // Creates the new image
            $tmp = \imagecreatetruecolor($newWidth, $newHeight);

            // Keeps the background transparent
            \imagealphablending($tmp, false );
            \imagesavealpha($tmp, true );

            \imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            if (($extension === self::JPG_EXTENSION) or ($extension === self::JPEG_EXTENSION))
            {
                \imagejpeg($tmp, $filePath, 100);
            }
            else
            {
                \imagepng($tmp, $filePath);
            }
        }

        // Delete the temporary files created.
        \imagedestroy($src);
        \imagedestroy($tmp);
    }

    protected function createImageObject($imageDetails)
    {
        $extension = $imageDetails['extension'];
        $baseFilePath = $imageDetails['file_path'];

        // The validation on image type happens before this is executed.

        if (($extension === self::JPG_EXTENSION) or ($extension === self::JPEG_EXTENSION))
        {
            return \imagecreatefromjpeg($baseFilePath);
        }
        else
        {
            return \imagecreatefrompng($baseFilePath);
        }
    }

    protected function getLogoDimensionsArray($width = null, $height = null)
    {
        return array(
                self::ORIGINAL_SIZE => [$width, $height],
                self::SMALL_SIZE    => [64, 64],
                self::MEDIUM_SIZE   => [128, 128],
                self::LARGE_SIZE    => [256, 256]
        );
    }

    protected function getLogoFilePath($baseFilePath, $size)
    {
        $extension_pos = strrpos($baseFilePath, '.');
        $filePath = substr($baseFilePath, 0, $extension_pos)
            .'_'
            .$size
            .substr($baseFilePath, $extension_pos);
        return $filePath;
    }

    protected function deleteFile($filePath)
    {
        if (file_exists($filePath))
        {
            $success = unlink($filePath); // nosemgrep : php.lang.security.unlink-use.unlink-use

            if ($success === false)
            {
                throw new Exception\RuntimeException(
                    'Failed to delete file: ' . $filePath);
            }
        }
    }

    protected function saveToAws($imageDetails)
    {
        $fileName = $imageDetails['file_name'];
        $awsFileName = 'logos/' . $fileName;

        $mimeType = $imageDetails['mime_type'];
        $baseFilePath = $imageDetails['file_path'];

        $config = Config::get('aws');

        $awsS3Mock = $config['mock'];

        if ($awsS3Mock === true)
        {
            $mockFileName = '/' . $awsFileName;
            return $mockFileName;
        }

        $s3 = Handler::getClient($config['logo_bucket_region']);

        $logoDimensions = $this->getLogoDimensionsArray();

        // Relative URL. No size appended to the file name.
        // This url will not actually exist in S3.
        // Since all the file names have sizes appended to them.
        $url = '/' . $awsFileName;

        $this->trace->info(
            TraceCode::AWS_S3_LOGO_UPLOAD,
            array_merge($imageDetails,[
                'logo_bucket_region' => $config['logo_bucket_region'],
                'logo_bucket'        => $config['logo_bucket']
            ])
        );

        foreach ($logoDimensions as $size => $_)
        {
            // Gets the location of the file w.r.t. the size.
            $filePath = $this->getLogoFilePath($baseFilePath, $size);

            // File name is the last part of the file path.
            $filePathArray = explode('/', $filePath);
            $awsFileName = 'logos/' . end($filePathArray);

            try
            {
                $s3Obj = [
                    'Bucket'        => $config['logo_bucket'],
                    'Key'           => $awsFileName,
                    'ContentType'   => $mimeType,
                    'SourceFile'    => $filePath,
                ];
                // The method which will upload to s3.
                $result = $s3->putObject($s3Obj);
            }
            catch (\Aws\S3\Exception\S3Exception $e)
            {
                throw new Exception\ServerErrorException(
                    'Failed to upload file: ' . $awsFileName,
                    ErrorCode::SERVER_ERROR_AWS_FAILURE, null, $e);
            }
            catch (\Exception $e)
            {
                throw new Exception\ServerErrorException(
                    'Failed to upload file: ' . $awsFileName,
                    ErrorCode::SERVER_ERROR_AWS_FAILURE, null, $e);
            }

            $this->trace->info(
                TraceCode::AWS_S3_LOGO_UPLOADED,
                $imageDetails);

            if ($size == self::ORIGINAL_SIZE) {
                $command = $s3->getCommand('GetObject', $s3Obj);

                $request = $s3->createPresignedRequest(
                    $command,
                    '+' . '15' . ' minutes'
                );

                $preSignedUrl = (string)$request->getUri();

                $this->app['merchantRiskClient']->enqueueProfanityCheckerRequest($this->merchant->getId(), 'image', 'merchant', $this->merchant->getId(), $preSignedUrl, DetailConstants:: MRS_PROFANITY_CHECKER_DEPTH, Constants::SOURCE_DASHBOARD);
            }
            //$s3Url = $result['ObjectURL'];
        }

        return $url;
    }
}
