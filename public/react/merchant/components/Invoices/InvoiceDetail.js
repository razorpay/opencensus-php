import { NavLink } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import ShowWhen from 'merchant/components/ShowWhen';
import LineItemReadOnlyTable from './LineItemReadOnlyTable';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import DetailRow from 'merchant/components/DetailRow';
import NestedDetailRow from 'merchant/components/NestedDetailRow';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

export default props => {
  let { invoice, isLoading, statusMsg } = props;

  let status = invoice.status;
  let isDraft = status === 'draft';
  let isIssued = status === 'issued';
  let isPaid = status === 'paid';
  let isCancelled = status === 'cancelled';
  let isExpired = status === 'expired';

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="icon icon-link text-primary" />
              {' '}
              <strong>{invoice.id}</strong>

              <ShowWhen notMyRole="support finance">
                <div class="btn-toolbar pull-right">
                  {(isDraft || isIssued) &&
                    <button
                      class="btn btn-primary btn-sm"
                      onClick={props.onIssue}
                    >
                      Send Link
                    </button>}

                  {isIssued &&
                    <button
                      class="btn btn-default btn-sm"
                      onClick={props.onCancel}
                    >
                      Cancel Link
                    </button>}
                </div>
              </ShowWhen>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  <DetailRow
                    label="Amount"
                    value={() => <Amount value={invoice.amount} />}
                  />
                  <DetailRow
                    label="Amount Paid"
                    value={() => <Amount value={invoice.amount_paid} />}
                  />
                  <DetailRow label="Summary" value={invoice.description} />
                  <DetailRow
                    label="Invoice Date"
                    value={() => <Time value={invoice.date} />}
                  />

                  <DetailRow
                    label={isExpired ? 'Expired on' : 'Expires on'}
                    value={() => (
                      <Time
                        value={invoice.expire_by}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />
                    )}
                  />

                  <DetailRow label="Receipt" value={invoice.receipt} />
                  <DetailRow
                    label="Payment Link"
                    value={() => <CopyLink url={invoice.short_url} />}
                  />
                  <DetailRow
                    label="Status"
                    value={() => <InvoiceStatusLabel status={invoice.status} />}
                  />
                  <DetailRow
                    label="Payment Id"
                    value={() => {
                      if (!invoice.payment_id) {
                        return '--';
                      }
                      return (
                        <NavLink to={`/payments/${invoice.payment_id}`}>
                          <code>{invoice.payment_id}</code>
                        </NavLink>
                      );
                    }}
                  />
                  <DetailRow
                    label="Paid At"
                    value={() => (
                      <Time
                        value={invoice.paid_at}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />
                    )}
                  />
                  <DetailRow label="Terms & Conditions" value={invoice.terms} />
                  <NestedDetailRow label="Notes" value={invoice.notes} />
                </div>

                <div class="panel panel-default">
                  <div class="panel-heading panel-heading-sm">
                    Customer Details
                  </div>
                  <div class="list-group details-row-container">
                    <DetailRow
                      label="Name"
                      value={invoice.customer_details.customer_name}
                    />
                    <DetailRow
                      label="Email"
                      value={() => (
                        <span>
                          {invoice.customer_details.customer_email}
                          {invoice.email_status
                            ? <span
                                style={{ marginLeft: '10px' }}
                                class={`${notificationClassMap[invoice.email_status]}`}
                              >
                                ({invoice.email_status})
                              </span>
                            : null}
                        </span>
                      )}
                    />
                    <DetailRow
                      label="Phone"
                      value={() => (
                        <span>
                          {invoice.customer_details.customer_contact}
                          {invoice.sms_status
                            ? <span
                                style={{ marginLeft: '10px' }}
                                class={`${notificationClassMap[invoice.sms_status]}`}
                              >
                                ({invoice.sms_status})
                              </span>
                            : null}
                        </span>
                      )}
                    />
                  </div>
                </div>

                {invoice.line_items.length
                  ? <div class="panel panel-default">
                      <div class="panel-heading">
                        Item Details
                      </div>

                      <div class="panel-body">
                        <LineItemReadOnlyTable
                          line_items={invoice.line_items}
                        />
                      </div>
                    </div>
                  : ''}

                <div class="panel panel-default">
                  <div class="panel-heading panel-heading-sm">
                    Updates
                  </div>

                  <div class="list-group details-row-container">
                    <DetailRow
                      label="Last Updated At"
                      value={() => (
                        <Time
                          value={invoice.updated_at}
                          format="DD MMM YYYY, hh:mm:ss a"
                        />
                      )}
                    />
                    <DetailRow
                      label="Created At"
                      value={() => (
                        <Time
                          value={invoice.created_at}
                          format="DD MMM YYYY, hh:mm:ss a"
                        />
                      )}
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>}
    </div>
  );
};
