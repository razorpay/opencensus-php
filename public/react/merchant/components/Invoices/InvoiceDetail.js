import AsyncButton from 'react-async-button';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import LineItemReadOnlyTable from './LineItemReadOnlyTable';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import DetailRow from 'merchant/components/DetailRow';
import ListGroupToggler from 'rzp/ui/ListGroupToggler';

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
        : <div>
            <div class="panel panel-default">
              <div class="panel-heading">
                Invoice ID: <strong>{invoice.id}</strong>

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

              </div>

              <div class="panel-body">
                <div class="list-group details-row-container">
                  <DetailRow
                    label="Amount"
                    value={() => <Amount value={invoice.amount} />}
                  />
                  <DetailRow label="Summary" value={invoice.description} />
                  <DetailRow
                    label="Invoice Date"
                    value={() => <Time value={invoice.date} />}
                  />
                  <DetailRow label="Receipt" value={invoice.receipt} />
                  <DetailRow label="Payment Link" value={invoice.short_url} />
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
                        <a href={`#/app/payments/${invoice.payment_id}`}>
                          {invoice.payment_id}
                        </a>
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
                  {Object.keys(invoice.notes).length > 0
                    ? <ListGroupToggler label="Notes">
                        {Object.keys(invoice.notes).map(note => (
                          <DetailRow
                            key={note}
                            label={note}
                            value={invoice.notes[note]}
                          />
                        ))}
                      </ListGroupToggler>
                    : <DetailRow label="Notes" value="No Notes" />}
                </div>
              </div>
            </div>

            <div class="panel panel-default">
              <div class="panel-heading">Customer Details</div>
              <div class="panel-body">
                <div class="list-group details-row-container">
                  <DetailRow label="Name" value={invoice.customer.name} />
                  <DetailRow
                    label="Email"
                    value={() => (
                      <span>
                        {invoice.customer.email}
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
                        {invoice.customer.contact}
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
            </div>

            {invoice.line_items.length
              ? <div class="panel panel-default">
                  <div class="panel-heading">
                    Item Details
                  </div>

                  <div class="panel-body">
                    <LineItemReadOnlyTable line_items={invoice.line_items} />
                  </div>
                </div>
              : ''}

            <div class="panel panel-default">
              <div class="panel-heading">
                Updates
              </div>

              <div class="panel-body">
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
          </div>}
    </div>
  );
};
