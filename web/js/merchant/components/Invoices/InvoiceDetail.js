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

export default props => {
  let { invoice, isLoading, statusMsg } = props;

  let status = invoice.status;
  let isDraft = status === 'draft';
  let isIssued = status === 'issued';
  let isPaid = status === 'paid';
  let isCancelled = status === 'cancelled';
  let isExpired = status === 'expired';
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
            <i class="icon icon-link text-primary icon--formal" />{' '}
            <strong>{invoice.id}</strong>
            <ShowWhen notMyRole="support finance">
              <div class="btn-toolbar pull-right">
                {(isDraft || isIssued) && (
                  <button
                    class="btn btn-primary btn-sm"
                    onClick={props.onIssue}
                  >
                    {isSmsOrEmailSent ? 'Send Again' : 'Send Link'}
                  </button>
                )}

                {isIssued && (
                  <button
                    class="btn btn-default btn-sm"
                    onClick={props.onCancel}
                  >
                    Cancel Link
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
                  label="Amount"
                  value={() => (
                    <Amount
                      value={invoice.amount}
                      currency={invoice.currency}
                    />
                  )}
                />
                <EntityDetailRow
                  label="Status"
                  value={() => <InvoiceStatusLabel status={invoice.status} />}
                />
                <EntityDetailRow
                  label="Amount Paid"
                  value={() => (
                    <Amount
                      value={invoice.amount_paid}
                      currency={invoice.currency}
                    />
                  )}
                />

                <EntityDetailRow
                  label="Payment Id"
                  value={() => {
                    if (!invoice.payment_id) {
                      return '--';
                    }
                    return (
                      <Link to={`/payments/${invoice.payment_id}`}>
                        <code>{invoice.payment_id}</code>
                      </Link>
                    );
                  }}
                />

                <EntityDetailRow
                  label="Payment Link"
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
                <EntityDetailRow
                  label="Summary"
                  value={invoice.description || '--'}
                />
                <EntityDetailRow label="Receipt" value={invoice.receipt} />
                <EntityDetailRow label="Customer Details">
                  {getCustomerDetail(invoice)}
                </EntityDetailRow>
                <EntityDetailRow
                  label="Created At"
                  value={() => <Time value={invoice.date} />}
                />
                <EntityDetailRow
                  label="Paid At"
                  value={() => (
                    <Time
                      value={invoice.paid_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />
                <EntityDetailRow
                  label={isExpired ? 'Expired on' : 'Expires on'}
                  value={() => (
                    <Time
                      value={invoice.expire_by}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />

                <NestedEntityDetailRow label="Notes" value={invoice.notes} />

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
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
