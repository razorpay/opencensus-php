import Definition from 'common/ui/Definition';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

const CustomerDetails = ({ invoice }) => (
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

export default CustomerDetails;
