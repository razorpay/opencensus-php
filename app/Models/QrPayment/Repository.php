<?php

namespace RZP\Models\QrPayment;

use Exception;
use RZP\Base\Common;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\QrCode\NonVirtualAccountQrCode as QrV2;

class Repository extends Base\Repository
{
    protected $entity = 'qr_payment';

    public function isEsSyncNeeded(string $action, array $dirty = null, PublicEntity $qrPayment = null): bool
    {
        // Sync in ES only for payments on QRv2 created via API, DASHBOARD
        if ($qrPayment->qrCode->getRequestSource() === QrV2\RequestSource::CHECKOUT)
        {
            return false;
        }

        return parent::isEsSyncNeeded($action, $dirty, $qrPayment);
    }

    public function findByProviderReferenceIdAndGatewayAndAmount(string $providerReferenceId, string $gateway, int $amount)
    {
        $mode = ($mode ?? $this->app['rzp.mode']) ?? Mode::LIVE;

        return $this->newQueryWithConnection($mode)
                    ->useWritePdo()
                    ->where(Entity::PROVIDER_REFERENCE_ID, '=', $providerReferenceId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where(Entity::AMOUNT, '=', $amount)
                    ->first();
    }

    protected function serializeForIndexing(PublicEntity $entity): array
    {
        try
        {
            $errorMessage = null;

            $payment = $entity->payment;

            $qrCodeEntity = $entity->qrCode;

            $fields = $this->esRepo->getIndexedFields();

            $serialized = $entity->setVisible($fields)->toArray();

            if (empty($payment) === false)
            {
                $serialized[PaymentEntity::STATUS] = $payment->getStatus();

                $serialized[EsRepository::NOTES_NEW] = $payment->getNotes()->toArray();

                if (empty($serialized[EsRepository::NOTES_NEW]) === false)
                {
                    $serialized[EsRepository::NOTES_NEW] = array_map(
                        function ($key, $value) {
                            return compact('key', 'value');
                        },
                        array_keys($serialized[EsRepository::NOTES_NEW]),
                        $serialized[EsRepository::NOTES_NEW]
                    );
                }

                unset($serialized[PaymentEntity::NOTES]);
            }

            if ($qrCodeEntity !== null)
            {
                $serialized[Entity::MERCHANT_ID] = $qrCodeEntity->getMerchantId();

                if ($qrCodeEntity->customer !== null)
                {
                    $serialized[EsRepository::CUSTOMER_EMAIL] = $qrCodeEntity->customer->getEmail();
                }
            }
        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();

            $this->trace->traceException(
                                        $ex,
                                        Trace::CRITICAL,
                                        TraceCode::QR_V2_PAYMENT_SYNC_FAILED,
                                        $entity->toArray());
        }
        finally
        {
            (new Metric())->pushQrV2PaymentsESSyncMetrics($errorMessage);
        }

        $this->trace->info(TraceCode::QR_PAYMENT_ES_DEBUG, $serialized);

        return $serialized;
    }

    public function getPaymentIdsForQrPaymentIds(array $qrPaymentIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $qrPaymentIds)
                    ->get()
                    ->pluck(Entity::PAYMENT_ID)
                    ->toArray();
    }

    public function getLatestExpectedPaymentIdForQrCodeId(string $qrCodeId)
    {
        QrV2\Entity::verifyIdAndSilentlyStripSign($qrCodeId);

        return $this->newQueryOnSlave()
                    ->where(Entity::QR_CODE_ID, '=', $qrCodeId)
                    ->where(Entity::EXPECTED, '=', 1)
                    ->latest()
                    ->pluck(Entity::PAYMENT_ID)
                    ->first();
    }
}
