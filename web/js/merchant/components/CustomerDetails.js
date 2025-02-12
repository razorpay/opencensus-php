import Definition from 'common/ui/Definition';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

const CustomerDetails = ({
  name,
  email,
  emailStatus,
  customerId,
  contact,
  smsStatus,
  isLoading,
}) => {
  return isLoading ? (
    <PlaceholderLoader />
  ) : (
    <Definition placeholder="--">
      {name}
      {email && (
        <span>
          {email}
          {emailStatus ? (
            <span style={{ marginLeft: '10px' }} className={`${notificationClassMap[emailStatus]}`}>
              ({emailStatus} mail)
            </span>
          ) : null}
        </span>
      )}

      {contact && (
        <span>
          {contact}
          {smsStatus ? (
            <span style={{ marginLeft: '10px' }} className={notificationClassMap[smsStatus]}>
              ({smsStatus} sms)
            </span>
          ) : null}
        </span>
      )}

      {customerId && <code>{customerId}</code>}
    </Definition>
  );
};

export default CustomerDetails;
