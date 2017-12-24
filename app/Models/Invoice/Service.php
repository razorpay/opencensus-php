<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\LineItem;
use RZP\Models\FileStore;

class Service extends Base\Service
{
    protected $core;

    // This is dashboard's userId and userRole. Used to support access control
    // for one specific use case of sellerapp.
    // Ref: https://github.com/razorpay/api/issues/2397
    protected $userId   = null;
    protected $userRole = null;

    public function __construct()
    {
        parent::__construct();

        $this->setUser();

        $this->core = new Core();
    }

    public function create(array $input): array
    {
        $invoice = $this->core->create($input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function fetch(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole,
                                            $input);

        return $invoice->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        // Appends USER_ID in query input if userId available in headers via
        // dashboard given userRole is sellerapp so only invoices created by
        // that user is visible in fetched list.
        if (($this->userId !== null) and
            ($this->userRole === Constants::SELLERAPP_ROLE))
        {
            $input[Entity::USER_ID] = $this->userId;
        }

        $invoices = $this->repo->invoice
                               ->fetch($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->update($invoice, $input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function issue(string $id): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->issue($invoice, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function delete(string $id): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->delete($invoice);

        if ($invoice === null)
        {
            return [];
        }

        return $invoice->toArrayPublic();
    }

    public function addLineItems(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->addLineItems($invoice, $input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function updateLineItem(
        string $id,
        string $lineItemId,
        array $input): array
    {
        $invoice  = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $lineItem = $this->repo->line_item
                               ->findByPublicIdAndMorphEntity(
                                    $lineItemId,
                                    $invoice
                                );

        $invoice = $this->core->updateLineItem(
            $invoice,
            $lineItem,
            $input,
            $this->merchant
        );

        return $invoice->toArrayPublic();
    }

    public function removeLineItem(string $id, string $lineItemId): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $lineItem = $this->repo->line_item
                               ->findByPublicIdAndMorphEntity(
                                    $lineItemId,
                                    $invoice
                                );

        $invoice = $this->core->removeLineItem($invoice, $lineItem);

        return $invoice->toArrayPublic();
    }

    public function removeManyLineItems(string $id, array $input): array
    {
        $invoice  = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        (new LineItem\Validator)->validateInput('remove_many', $input);

        $lineItems = $this->repo->line_item
                               ->findManyByPublicIdsAndMorphEntity(
                                    $input[LineItem\Entity::IDS],
                                    $invoice
                                );

        $invoice = $this->core->removeManyLineItems($invoice, $lineItems);

        return $invoice->toArrayPublic();
    }

    public function sendNotification(string $id, string $medium): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $data = $this->core->sendNotification($invoice, $medium);

        return $data;
    }

    public function cancelInvoice(string $id): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->cancelInvoice($invoice);

        return $invoice->toArrayPublic();
    }

    public function expireInvoices(): array
    {
        return $this->core->expireInvoices();
    }

    public function sendNotificationsInBulk(): array
    {
        return (new Notifier())->sendNotificationsInBulk();
    }

    public function fetchStatus(string $id): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $data = $this->core->fetchStatus($invoice);

        return $data;
    }

    public function getInvoiceViewData(string $invoiceId): array
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        if (($routeName === 'invoice_view_test') or
            ($routeName === 'invoice_view_test_post'))
        {
            $mode = Mode::TEST;
        }
        else
        {
            $mode = Mode::LIVE;
        }

        \Database\DefaultConnection::set($mode);

        $this->app['rzp.mode'] = $mode;

        $invoice = $this->repo->invoice->findByPublicId($invoiceId);

        $invoice->getValidator()->validateInvoiceViewable();

        return (new ViewDataSerializer($invoice))->getWithSubscriptionIfApplicable();
    }

    /**
     * @param string       $id
     * @param bool|boolean $download
     *
     * @return string|null
     */
    public function getInvoicePdfSignedUrl(string $id, bool $download = false)
    {
        $invoice = $this->repo
                        ->invoice
                        ->findByPublicIdAndMerchantAndUser(
                            $id,
                            $this->merchant,
                            $this->userId,
                            $this->userRole);

        $pdf = $this->core->getFreshInvoicePdf($invoice);

        $downloadAs = $download ? $invoice->getPdfDisplayName() : null;

        return (new FileStore\Accessor)->getSignedUrlOfFile($pdf, $downloadAs);
    }

    public function issueInvoicesOfBatch(string $batchId, array $input): array
    {
        (new Validator)->validateInput(Validator::ISSUE_BATCH, $input);

        $batch = $this->repo
                      ->batch
                      ->findByPublicIdAndMerchant($batchId, $this->merchant);

        $response = $this->core->issueInvoicesOfBatch($batch, $input);

        return $response;
    }

    /**
     * Returns filtered list of batch ids which should be allowed
     * 'Issue all links' action.
     *
     * @param array $input
     *
     * @return array
     */
    public function getIssuableByBatchIds(array $input): array
    {
        (new Validator)->validateInput('invoiceStatsByBatches', $input);

        $batchIds = $input[Entity::BATCH_IDS];

        Batch\Entity::verifyIdAndSilentlyStripSignMultiple($batchIds);

        $results = $this->repo->invoice->getNonDraftInvoiceCountByBatchIds($batchIds);

        // Following batch ids have non draft invoices and we assume this
        // whole batch was already issued.

        $results = array_filter($results, function ($result)
                    {
                        return ($result['count'] > 0);
                    });

        $results = array_column($results, Entity::BATCH_ID);

        // Return the list which can be shown 'Issue all links' action

        $results = array_values(array_diff($batchIds, $results));

        Batch\Entity::getSignedIdMultiple($results);

        return $results;
    }

    /**
     * Sets userId and userRole members of this class by reading values from
     * request headers sent from dashboard.
     *
     * @return null
     */
    protected function setUser()
    {
        $dashboardHeaders = $this->app['basicauth']->getDashboardHeaders();

        $this->userId   = $dashboardHeaders['user_id'] ?? null;
        $this->userRole = $dashboardHeaders['user_role'] ?? null;
    }
}
