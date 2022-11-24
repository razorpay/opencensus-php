<?php


namespace RZP\Models\Merchant\Webhook\AlertEmailRecon;


use Generator;
use RZP\Modules\Migrate\Record;
use RZP\Modules\Migrate\Response;
use RZP\Trace\TraceCode;

class MigrateTarget implements \RZP\Modules\Migrate\Target
{
    const WK_LIST_ROUTE   = '/twirp/rzp.stork.webhook.v1.WebhookAPI/List';
    const WK_UPDATE_ROUTE = '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update';

    /**
     * @inheritDoc
     */
    public function getParallelOpts(array $opts): Generator
    {
        //not required
        yield from [];
    }

    /**
     * @inheritDoc
     */
    public function iterate(array $opts): Generator
    {
        //not required
        yield from [];
    }

    /**
     * @inheritDoc
     */
    public function migrate(Record $sourceRecord, bool $dryRun): Response
    {
        /**
         * @var \RZP\Services\Stork
         */
        $stork = app('stork_service');
        $stork->init(app('rzp.mode'));

        $ownerId = $sourceRecord->key;
        $alertEmail = $sourceRecord->value;
        $paramsForListWebhook = ['offset' => 0, 'limit' => 100, 'owner_id' => $ownerId];

        // get all webhooks
        $webhooksListResp = $stork->request(self::WK_LIST_ROUTE, $paramsForListWebhook);
        $webhooksList = json_decode($webhooksListResp->body, true);

        $webhooks = $webhooksList['webhooks'];

        // for each webhook update the alert_email field.
        foreach($webhooks as $webhook)
        {
            if (isset($webhook['alert_email']))
            {
                $this->traceAlertEmailForWebhookAlreadyPresent($webhook);
                continue;
            }

            $webhook['alert_email'] = $alertEmail;

            if (isset($webhook['context']) === false || sizeof($webhook['context']) === 0)
            {
                $webhook['context'] = json_decode('{}');
            }

            $this->traceBeforeUpdateRequestMadeToStork($webhook);
            $resp = $stork->request(self::WK_UPDATE_ROUTE, ['webhook' => $webhook]);
            $this->traceAfterUpdateRequestMadeToStork($resp);
        }

        return new Response(Response::ACTION_UPDATED, $ownerId, $alertEmail);
    }

    /**
     * @inheritDoc
     */
    public function delete(Record $record)
    {
        //not required;
    }

    private function traceAlertEmailForWebhookAlreadyPresent(array $webhook)
    {
        app('trace')->info(TraceCode::MIGRATE_JOB_STORK_WK_ALERT_EMAIL_ALREADY_PRESENT, [
                'webhook_id'  => $webhook['id'],
                'alert_email' => $webhook['alert_email'],
        ]);
    }

    private function traceBeforeUpdateRequestMadeToStork(array $webhook)
    {
        app('trace')->info(TraceCode::MIGRATE_JOB_STORK_STORK_WK_ALERT_EMAIL_BEFORE_REQUEST, [
                'webhook_id' => $webhook['id'] ?? '',
                'alert_email' => $webhook['alert_email'] ?? '',
        ]);
    }

    private function traceAfterUpdateRequestMadeToStork($resp)
    {
        $resp = json_decode($resp->body, true);

        $webhook = $resp['webhook'] ?? [];
        app('trace')->info(TraceCode::MIGRATE_JOB_STORK_STORK_WK_ALERT_EMAIL_AFTER_REQUEST, [
                'webhook_id' => $webhook['id'] ?? '',
                'alert_email' => $webhook['alert_email'] ?? '',
        ]);
    }
}
