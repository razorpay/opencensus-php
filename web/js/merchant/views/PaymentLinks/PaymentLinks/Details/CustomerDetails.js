import Definition from 'common/ui/Definition';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

const CustomerDetails = ({ paymentlink }) => (
  <Definition placeholder="--">
    {paymentlink.customer_details.customer_name}
    {paymentlink.customer_details.customer_email && (
      <span>
        {paymentlink.customer_details.customer_email}
        {paymentlink.email_status ? (
          <span
            style={{ marginLeft: '10px' }}
            class={`${notificationClassMap[paymentlink.email_status]}`}
          >
            ({paymentlink.email_status} mail)
          </span>
        ) : null}
      </span>
    )}
    {paymentlink.customer_details.customer_contact && (
      <span>
        {paymentlink.customer_details.customer_contact}
        {paymentlink.sms_status ? (
          <span
            style={{ marginLeft: '10px' }}
            class={`${notificationClassMap[paymentlink.sms_status]}`}
          >
            ({paymentlink.sms_status} sms)
          </span>
        ) : null}
      </span>
    )}
    {paymentlink.customer_id && <code>{paymentlink.customer_id}</code>}
  </Definition>
);

export default CustomerDetails;
