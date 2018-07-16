import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Definition from 'rzp/ui/Definition';
import Spinner from 'rzp/ui/Spinner';
import Banner from 'rzp/ui/Banner';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import ShowWhen from 'merchant/components/ShowWhen';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { Link } from 'react-router-dom';
import DataTable from 'rzp/ui/Table/DataTable';
import { paymentId, amount, paidOn } from 'rzp/ui/item/pair';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Button, { AsyncBtn } from 'component/Button';

import {
  EditExpiry,
  EditNotes,
  EditReceipt,
} from 'merchant/containers/PaymentLinks/Edit/index';

import {
  trackDetailViewEdits,
  trackTogglePartialPayment,
} from 'merchant/containers/PaymentLinks/Links/ga';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

const getCustomerDetail = invoice => (
  <Definition placeholder="--">
    {invoice.customer_details.customer_name}
    {invoice.customer_details.customer_email && (
      <span>
        {invoice.customer_details.customer_email}
        {invoice.email_status ? (
          <span
            style={{ marginLeft: '10px' }}
            class={`${notificationClassMap[invoice.email_status]}`}
          >
            ({invoice.email_status} mail)
          </span>
        ) : null}
      </span>
    )}
    {invoice.customer_details.customer_contact && (
      <span>
        {invoice.customer_details.customer_contact}
        {invoice.sms_status ? (
          <span
            style={{ marginLeft: '10px' }}
            class={`${notificationClassMap[invoice.sms_status]}`}
          >
            ({invoice.sms_status} sms)
          </span>
        ) : null}
      </span>
    )}
    {invoice.customer_id && <code>{invoice.customer_id}</code>}
  </Definition>
);

const getPaymentDetail = invoice => (
  <Definition placeholder="--">
    <Amount value={invoice.amount_paid} currency={invoice.currency} />
    {invoice.partial_payment &&
    invoice.payments &&
    invoice.payments.items.length ? (
      <ContentToggler>
        <span>View Payment Details</span>
        <div
          className="full-width-item sub-entity-list"
          style={{ fontSize: 14 }}
        >
          <DataTable
            title="Payments"
            progressLoader={true}
            columns={[paymentId, paidOn, amount]}
            items={invoice.payments.items}
            noStripe={true}
          />
        </div>
      </ContentToggler>
    ) : (
      <React.Fragment>
        {invoice.payment_id && (
          <Link to={`/payments/${invoice.payment_id}`}>
            <code>{invoice.payment_id}</code>
          </Link>
        )}
        {invoice.paid_at && (
          <div>
            Paid on{' '}
            <Time value={invoice.paid_at} format="DD MMM YYYY, hh:mm a" />
          </div>
        )}
      </React.Fragment>
    )}
  </Definition>
);

export default props => {
  let { invoice, isLoading, statusMsg, editPaymentLink } = props;

  let status = invoice.status;
  const isDraft = status === 'draft';
  const isIssued = status === 'issued';
  const isPaid = status === 'paid';
  const isPartiallyPaid = status === 'partially_paid';
  const isCancelled = status === 'cancelled';
  const isExpired = status === 'expired';

  let isSmsOrEmailSent =
    invoice.sms_status === 'sent' || invoice.email_status === 'sent';

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-link text-primary icon--formal" />{' '}
            <strong>{invoice.id}</strong>
            <ShowWhen notMyRole="support finance">
              <div class="btn-toolbar pull-right">
                {invoice.customer_id &&
                  (isDraft || isIssued || isPartiallyPaid) && (
                    <button
                      class="btn btn-primary btn-sm"
                      onClick={props.onIssue}
                    >
                      {isSmsOrEmailSent ? 'Resend Link' : 'Send Link'}
                    </button>
                  )}
              </div>
            </ShowWhen>
          </div>

          <div class="SliderPanel__Body">
            {invoice.type === 'invoice' && (
              <Banner cta="View Invoice" ctaUrl={'/invoices/' + invoice.id}>
                <span>
                  Following is the summary of the invoice. See invoice to view
                  all details.
                </span>
              </Banner>
            )}
            <div class="panel-body">
              <div class="list-group details-row-container">
                <EntityDetailRow
                  label="Payment For"
                  pairClass="description"
                  value={invoice.description || '--'}
                />

                <EntityDetailRow
                  label="Status"
                  value={() => (
                    <div>
                      <InvoiceStatusLabel status={invoice.status} />
                      <ShowWhen notMyRole="support finance">
                        {isIssued && (
                          <Button.Transparent
                            class="Button--Link"
                            style={{ marginLeft: 12 }}
                            onClick={props.onCancel}
                          >
                            Cancel Link
                          </Button.Transparent>
                        )}
                      </ShowWhen>
                    </div>
                  )}
                />

                <React.Fragment>
                  {do {
                    const isPartialPayment = invoice.partial_payment;

                    <EntityDetailRow
                      label="Partial Payment"
                      value={() => (
                        <div>
                          {isPartialPayment ? 'Enabled' : 'Disabled'}
                          {isIssued && (
                            <AsyncBtn.Transparent
                              onClick={() => {
                                const toEnablePartialPayment = +!isPartialPayment;
                                editPaymentLink({
                                  partial_payment: toEnablePartialPayment,
                                });

                                trackTogglePartialPayment(
                                  invoice.id,
                                  'Toggle Partial Payment',
                                  toEnablePartialPayment
                                );
                              }}
                              class="Button--Link"
                              style={{ marginLeft: 12 }}
                              pendingState={
                                isPartialPayment ? 'Disabling' : 'Enabling'
                              }
                            >
                              {isPartialPayment ? 'Disable' : 'Enable'}
                            </AsyncBtn.Transparent>
                          )}
                        </div>
                      )}
                    />;
                  }}
                </React.Fragment>

                <EntityDetailRow
                  label="Amount"
                  value={() => (
                    <Amount
                      value={invoice.amount}
                      currency={invoice.currency}
                    />
                  )}
                />
                <EntityDetailRow label="Amount Paid">
                  {getPaymentDetail(invoice)}
                </EntityDetailRow>

                <EntityDetailRow
                  label="Link Url"
                  value={() => (
                    <CopyLink
                      url={invoice.short_url}
                      onCopy={() => {
                        if (invoice.type === 'link') {
                          window.rzpAnalytics({
                            eventCategory: 'Dashboard - Payment Links',
                            eventAction: 'Copy - Payment Link',
                            eventLabel: `payment_link_id=${invoice.id}`,
                          });
                        }
                      }}
                    />
                  )}
                />
                <EntityDetailRow label="Customer Details">
                  {getCustomerDetail(invoice)}
                </EntityDetailRow>

                <EntityDetailRow
                  label="Receipt No."
                  value={
                    isIssued
                      ? () => (
                          <EditReceipt
                            value={invoice.receipt}
                            entityId={invoice.id}
                            editFn={editPaymentLink}
                            trackerFn={trackDetailViewEdits}
                          />
                        )
                      : invoice.receipt || '--'
                  }
                />

                <EntityDetailRow label="Created By">
                  {!!invoice.user ? (
                    <Definition>
                      {invoice.user.name}
                      {invoice.user.email}
                    </Definition>
                  ) : (
                    'API'
                  )}
                </EntityDetailRow>

                <EntityDetailRow
                  label="Created At"
                  value={() => <Time value={invoice.date} />}
                />
                <EntityDetailRow
                  label={isExpired ? 'Expired On' : 'Expires On'}
                  value={
                    isIssued
                      ? () => (
                          <EditExpiry
                            value={invoice.expire_by}
                            editFn={editPaymentLink}
                            entityId={invoice.id}
                            trackerFn={trackDetailViewEdits}
                          />
                        )
                      : () =>
                          invoice.expire_by ? (
                            <Time
                              value={invoice.expire_by}
                              format="DD MMM YYYY, hh:mm a"
                            />
                          ) : (
                            'No Expiry'
                          )
                  }
                />

                <EntityDetailRow
                  label="Notes"
                  value={() => (
                    <EditNotes
                      value={invoice.notes}
                      editFn={editPaymentLink}
                      entityId={invoice.id}
                      trackerFn={trackDetailViewEdits}
                    />
                  )}
                />
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
