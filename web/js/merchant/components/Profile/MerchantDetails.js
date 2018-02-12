import Time from 'rzp/ui/Time';
import { titleCase } from 'rzp/utils/rzp-utils';
import DetailRow from '../DetailRow';
import CheckIcon from 'rzp/ui/CheckIcon';

import { ActivationStatusLabel } from 'merchant/components/StatusLabel';

export default ({ user }) => {
  return (
    <div class="list-group details-row-container">
      <DetailRow label="Merchant Name" value={titleCase(user.name)} />

      <DetailRow
        label="Merchant Email"
        value={() => <a href={`mailto:${user.email}`}>{user.email}</a>}
      />

      <DetailRow
        label="Activation Form Progress"
        value={`${user.activation_progress}%`}
      />

      <DetailRow
        label="Activation Status"
        value={() =>
          user.activation_status ? (
            <ActivationStatusLabel status={user.activation_status} />
          ) : (
            '--'
          )}
      />

      <DetailRow
        label="Registration Date"
        value={() => (
          <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />
        )}
      />
    </div>
  );
};
