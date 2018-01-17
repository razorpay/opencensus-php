import { NavLink } from 'react-router-dom';
import Time from 'rzp/ui/Time';
import Clipboard from 'rzp/ui/Clipboard';
import { titleCase } from 'rzp/utils/rzp-utils';
import Table from 'rzp/ui/Table/Index';
import { paymentId, amount, createdAt } from 'rzp/ui/item/pair';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

export default ({ invoice }) => {
  let status = invoice.status;
  let isNew = !invoice.id;
  let isDraft = status === 'draft';
  let isPaid = status === 'paid';
  let isPartiallyPaid = status === 'partially_paid';

  if (isNew || isDraft) {
    return null;
  }

  return (
    <div class="inv__info">
      <h4>Invoice - {titleCase(invoice.status)}</h4>

      <dl>
        {
          do {
            if (isPartiallyPaid) {
              if (invoice.payments) {
                <div>
                  <dt>Payments</dt>
                  <Table
                    class="table-noborder table-inv_payments"
                    rows={invoice.payments}
                    columns={[
                      {
                        value: item => {
                          return (
                            <div>
                              {paymentId.value(item)}
                              <div>{createdAt.value(item)}</div>
                            </div>
                          );
                        },
                      },
                      amount,
                    ]}
                    showHeaders={false}
                  />
                </div>;
              }
            } else if (isPaid) {
              <div>
                <dt>Payment Id</dt>
                <dd>
                  <NavLink to={`/payments/${invoice.payment_id}`}>
                    <code>{invoice.payment_id}</code>
                  </NavLink>
                </dd>

                <dt>Paid On</dt>
                <dd>
                  <Time
                    value={invoice.paid_at}
                    format="DD MMM YYYY, hh:mm:ss a"
                  />
                </dd>
              </div>;
            } else {
              <div>
                <dt>Payment Link</dt>
                <dd>
                  <Clipboard value={invoice.short_url} />
                </dd>
              </div>;
            }
          }
        }

        {invoice.email_status && (
          <div>
            <dt>Email Sent to</dt>
            <dd class="text-ellipsis">
              {invoice.customer_details.customer_email}
              <span
                style={{ marginLeft: '10px' }}
                class={`${notificationClassMap[invoice.email_status]}`}
              >
                {invoice.email_status ? `(${invoice.email_status})` : ''}
              </span>
            </dd>
          </div>
        )}

        {invoice.sms_status && (
          <div>
            <dt>SMS Sent to</dt>
            <dd>
              {invoice.customer_details.customer_contact}
              <span
                style={{ marginLeft: '10px' }}
                class={`${notificationClassMap[invoice.sms_status]}`}
              >
                {invoice.sms_status ? `(${invoice.sms_status})` : ''}
              </span>
            </dd>
          </div>
        )}
        {(isPaid || isPartiallyPaid) && (
          <div>
            <dt>Payment Link</dt>
            <dd>
              <Clipboard value={invoice.short_url} />
            </dd>
          </div>
        )}
      </dl>
    </div>
  );
};
