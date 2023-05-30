<?php

namespace RZP\Models\QrCode;

use Response;
use Mockery\Mock;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Mail\System\Trace;
use RZP\Services\UfhService;
use RZP\Constants\HyperTrace;
use RZP\Models\QrCode\Constants;
use Illuminate\Http\UploadedFile;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Constants\Entity as EntityConstants;
use RZP\Services\Mock\UfhService as MockUfhService;


class Core extends Base\Core
{
    protected $virtualAccount;

    /**
     * Base qrcode url from which qr code link is generated.
     *
     * @var string
     */
    protected $baseQrCodeUrl;

    /**
     * Url shortener service
     *
     */
    protected $elfin;

    protected $generator;

    /**
     * Core constructor.
     *
     * @param null $virtualAccount
     */
    public function __construct($virtualAccount = null)
    {
        parent::__construct();

        if ($virtualAccount !== null)
        {
            $this->virtualAccount = $virtualAccount;

            $this->merchant = $virtualAccount->merchant;
        }

        $this->generator = new Generator;

        $this->baseQrCodeUrl = $this->app['config']->get('app.url') . '/v1';

        $this->elfin = $this->app['elfin'];
    }

    public function fetchQrCodePathFromFileStore(Entity $qrCode, Merchant\Entity $merchant)
    {
        $qrCodeImage = $qrCode->qrCodeFile();

        return (new FileStore\Accessor)
            ->id($qrCodeImage->getId())
            ->merchantId($merchant->getId())
            ->getFile();
    }

    protected function getUfhService()
    {
        $this->ufhService = $this->app['ufh.service'];

        $this->trace->info(
            TraceCode::QR_CODE_UFH_SERVICE_FETCHED
        );

        return $this->ufhService;
    }

    public function fetchQrCodePathFromUfh(Entity $qrCode)
    {
        // This shouldn't be needed as the Service layer does set it inside fetchQrCodePath()
        // Keeping this anyway to avoid breaking other flows that directly call this function (fetchQrCodePathFromUfh())
        $this->app['basicauth']->setMerchantById($qrCode->merchant->getId());

        $ufhQueryParams = [
            'entity_id'   => $qrCode->getId(),
            'entity_type' => $qrCode->getEntityName(),
        ];

        $ufhService = $this->getUfhService();

        $response = $ufhService->fetchFiles($ufhQueryParams, $qrCode->merchant->getId());

        $this->trace->info(
            TraceCode::QR_CODE_IMAGE_UFH_FETCH_FILE_RESPONSE,
            $response
        );

        $ufhServiceMock = $this->app['config']->get('applications.ufh.mock');

        if ($response['count'] === 0)
        {
            //The qr code image files which are created without using UFH service do not have a ufh file id associated
            //with them, so we need to use FileStore for old files.
            $response = $this->fetchQrCodePathFromFileStore($qrCode, $qrCode->merchant);

            $this->trace->info(
                TraceCode::QR_CODE_IMAGE_FILE_FETCH_FROM_LOCAL
            );

            return Response::download($response, Constants::QR_CODE_FILE_NAME);
        }

        $fileId = $response['items'][0]['id'];

        $response = $ufhService->getSignedUrl($fileId);

        $this->trace->info(
            TraceCode::QR_CODE_IMAGE_SIGNED_URL_FETCH_RESPONSE,
            $response
        );

        $filename    = Constants::QR_CODE_FILE_NAME;
        $tempImage   = tempnam(sys_get_temp_dir(), $filename);

        if($ufhServiceMock === false)
        {
            $copySuccess = copy($response['signed_url'], $tempImage);
        }
        else
        {
            $copySuccess = copy(Constants::QR_CODE_TEMP_IMAGE_URL, $tempImage);
        }

        return Response::download($tempImage, $filename);
    }

    public function edit(Entity $qrCode, array $input)
    {
        $qrCode->edit($input);

        $this->repo->saveOrFail($qrCode);

        return $qrCode;
    }

    public function tokenizeExistingQrCodeMpans(Entity $qrCode)
    {
        $qrStringTokenized = Entity::getQrStringWithTokenizedMpans($qrCode->getOriginalQrString());

        $editInput = [
            Entity::QR_STRING       => $qrStringTokenized,
            Entity::MPANS_TOKENIZED => true,
        ];

        $this->edit($qrCode, $editInput);
    }

    public function buildQrCode(array $input, $order = null)
    {
        $qrCode = (new Entity)->build($input);

        if($this->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
        {
            $customer = $this->getCustomerIfGiven($input);

            $qrCode->customer()->associate($customer);
        }

        $qrCode->merchant()->associate($this->merchant);

        $qrCode->source()->associate($this->virtualAccount);

        //
        // The only case when reference will not be equal to ID is
        // when the QR code is generated by the client/merchant system
        // and not RZP system. If the QR code is generated by the
        // merchant, we don't care about generating the QR string and
        // hence the image  and hence the URL, because the QR code
        // has already been generated by the merchant.
        // Note: The reference will not be equal to ID here because
        //       the merchant would have used their own reference
        //       while generating the QR code.
        //
        // This happens when the terminal's expected attribute is
        // set to true, in which case, whatever reference we get in
        // the notification, we treat the notification as expected.
        // If the terminal's expected attribute is set to false, we
        // treat the notification as unexpected and it would never
        // reach this flow because the unexpected notifications are
        // created against one shared QR code.
        //
        if ($qrCode->isGeneratedByMerchant() === false)
        {
            $qrCode->generateQrString();

            $this->setShortUrl($qrCode);
        }

        $this->repo->transaction(function() use ($qrCode)
        {
            $this->repo->saveOrFail($qrCode);

            $this->generateQrCodeFile($qrCode);
        });

        return $qrCode;
    }

    protected function generateQrCodeFile($qrCode)
    {
        $this->trace->info(TraceCode::QR_CODE_IMAGE_FILE_GENERATE, $qrCode->toArrayPublic());

        //
        // There are cases when we don't generate a QR string for QR codes.
        // Refer to the function `generate` for more information around this.
        //
        if (empty($qrCode->getQrString()) === true)
        {
            return;
        }

        $localFilePath = Tracer::inspan(['name' => HyperTrace::QR_CODE_GENERATE_IMAGE], function () use ($qrCode)
        {
            if ($qrCode->getProvider() === Type::UPI_QR)
            {
                if ($this->merchant->org->isFeatureEnabled(\RZP\Models\Feature\Constants::ORG_CUSTOM_UPI_LOGO) === true)
                {
                    return (new NonVirtualAccountQrCode\Generator())->generateUpiQrCodeImage($qrCode);
                } else
                {
                    return $this->generator->generateUpiQrCodeImage($qrCode);
                }
            } else
            {
                return $this->generator->generateBharatQrCodeImage($qrCode);
            }
        });

        Tracer::inspan(['name' => HyperTrace::QR_CODE_UFH_UPLOAD], function () use ($localFilePath, $qrCode)
        {
            $this->saveQrCodeImageUsingUfh($localFilePath, $qrCode);
        });

    }

    private function saveQrCodeImageUsingUfh($localFilePath, $qrCode)
    {
        $uploadedFile = new UploadedFile(
            $localFilePath,
            $qrCode->getId() . '.jpeg',
            Entity::MIME_TYPE,
            null,
            true
        );

        //the ufh service takes the merchant id from ba, so setting the ba merchant as the qr code merchant
        $this->app['basicauth']->setMerchantById($this->merchant->getId());

        try
        {
            $filenameWithoutExt = str_before($uploadedFile->getClientOriginalName(), '.' . $uploadedFile->getClientOriginalExtension());

            $uploadFilename = 'qrcodes/' . $filenameWithoutExt;

            $ufhService  = $this->getUfhService();
            $ufhResponse = $ufhService->uploadFileAndGetUrl(
                $uploadedFile,
                $uploadFilename,
                FileStore\Type::QR_CODE_IMAGE,
                $qrCode
            );

            $this->trace->info(
                TraceCode::QR_CODE_IMAGE_UFH_FILE_UPLOAD_RESPONSE,
                $ufhResponse
            );
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::QR_CODE_IMAGE_UFH_FILE_UPLOAD_FAILED,
                [
                    'Error message' => $ex->getMessage(),
                ]
            );
        }
    }

    public function setShortUrl($qrCode)
    {
        $longUrl = $this->getQrCodeLink($qrCode);

        $retryAttempts = Constants::MAX_RETRY_ATTEMPTS_FOR_QR_CODE_URL_SHORTEN_GIMLI_FAILURES;

        while ($retryAttempts--)
        {
            $shortenedUrl = $this->elfin->shorten($longUrl);

            if (empty($shortenedUrl) === false)
            {
                break;
            }

            $this->trace->info(TraceCode::GIMLI_REQUEST_FAILED_FOR_QR_CODE_URL_SHORTEN,
                [
                    'qr_code_id' => $qrCode->getId(),
                    'attempt' => (Constants::MAX_RETRY_ATTEMPTS_FOR_QR_CODE_URL_SHORTEN_GIMLI_FAILURES - $retryAttempts),
                ]);
        }

        $this->trace->info(
            TraceCode::QR_CODE_URL,
            [
                'qr_code_id' => $qrCode->getId(),
                'short_url'  => $shortenedUrl,
                'long_url'   => $longUrl,
            ]);

        $qrCode->setShortUrl($shortenedUrl);
    }

    public function getQrCodeLink($qrCode): string
    {
        $qrCodePublicId = $qrCode->getPublicId();

        if ($this->mode === Mode::LIVE)
        {
            $shortMode = Constants::SHORT_MODE_LIVE;
        }
        else
        {
            $shortMode = Constants::SHORT_MODE_TEST;
        }

        $qrCodeLink = $this->baseQrCodeUrl . '/' . $shortMode . '/qrcode/' . $qrCodePublicId;

        return $qrCodeLink;
    }

    protected function getCustomerIfGiven(array $input)
    {
        $customer = null;

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

            $customer = $this->repo
                ->customer
                ->findByPublicIdAndMerchant($customerId, $this->merchant);
        }

        return $customer;
    }
}
