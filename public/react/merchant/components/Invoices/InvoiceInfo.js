import { NavLink } from 'react-router-dom';
import Time from 'rzp/ui/Time';
import Clipboard from 'rzp/ui/Clipboard';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

export default ({ invoice }) => {
  let status = invoice.status;
  let isNew = !invoice.id;
  let isDraft = status === 'draft';
  let isPaid = status === 'paid';

  if (isNew || isDraft) {
    return null;
  }

  return (
    <div class="inv__info">
      <h4>Invoice {invoice.status}</h4>
      <dl>
        {isPaid
          ? <div>
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
            </div>
          : <div>
              <dt>Payment Link</dt>
              <dd>
                <Clipboard value={invoice.short_url} />
              </dd>
            </div>}
        {invoice.email_status &&
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
          </div>}

        {invoice.sms_status &&
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
          </div>}
        {isPaid &&
          <div>
            <dt>Payment Link</dt>
            <dd>
              <Clipboard value={invoice.short_url} />
            </dd>
          </div>}
      </dl>
    </div>
  );
};
