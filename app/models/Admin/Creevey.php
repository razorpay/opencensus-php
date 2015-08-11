<?php
namespace Models\Admin;

use AWS;
use Config;
use Requests;
use Slack;
use VIPSoft\Unzip\Unzip;

class Creevey
{
    protected static $HEADERS = [
        'Content-Type' => 'application/json'
    ];

    protected static $OPTIONS = [
        'timeout'   => 120,
        'useragent' => 'Razorpay/Dashboard'
    ];

    public function fire($job, array $data)
    {
        $this->merchantId = $data[0];
        $urls = $data[1];
        $this->name = $data[2];

        $config = Config::get('creevey');
        $baseUrl = $config['root'];

        if ($config['mock'])
        {
            $job->delete();
            return;
        }

        // Now we make the post request
        $postData = json_encode([
            'url'   =>  $urls,
            'token' =>  $config['token'],
            'id'    =>  $this->merchantId
        ]);

        try
        {
            $response = Requests::post($baseUrl,
                self::$HEADERS,
                $postData,
                self::$OPTIONS
            );

            if ($response->success)
            {
                // Now we save the file somewhere
                $images = $this->extract($response->body);
                $this->uploadToS3($images);

                $url = action('AdminController@getMerchantScreenshot', $this->merchantId);

                $link = "Screenshots Captured ({$this->name}): <$url|View>";

                Slack::to('#sales')->from('creevey')->withIcon(':camera:')->send($link);
            }

            else
            {
                $this->handleError($response->status_code);
            }
        }
        catch (\Exception $e)
        {
            $this->handleError($e->getMessage());
        }

        finally
        {
            $job->delete();
        }
    }

    public function handleError($status)
    {
        // Instead of throwing an exception, post on slack
        $error = "Invalid response from creevey: $status";
        Slack::to('#tech_bots')->from('creevey')->withIcon(':shit:')
            ->send($error);
    }

    /**
     * Extracts the response
     * @return array list of extracted files
     */
    public function extract($body)
    {
        $this->dir = $this->tempdir();

        $zipFilePath = $this->dir.'/screenshots.zip';
        file_put_contents($zipFilePath, $body);

        $unzipper = new Unzip();
        return $unzipper->extract($zipFilePath, $this->dir);
    }

    public function uploadToS3($images)
    {
        $s3 =  AWS::get('s3');
        foreach ($images as $filename)
        {
            $fullPath = $this->dir . "/$filename";
            $s3Obj = [
                'Bucket'        => $_ENV['AWS_ACTIVATION_BUCKET'],
                'Key'           => $this->merchantId."/screenshots/$filename",
                'ContentType'   => "image/jpeg",
                'SourceFile'    => $fullPath,
            ];

            $s3->putObject($s3Obj);
        }
    }

    function tempdir($dir = false, $prefix = 'php') {
        $tempfile = tempnam(storage_path('files'), '');
        if (file_exists($tempfile))
        {
            unlink($tempfile);
        }
        mkdir($tempfile);
        return $tempfile;
    }
}
