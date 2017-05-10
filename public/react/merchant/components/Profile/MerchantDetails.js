import Time from 'rzp/ui/Time';
import { titleCase } from 'rzp/utils/rzp-utils';
import DetailRow from '../DetailRow';
import CheckIcon from 'rzp/ui/CheckIcon';

export default ({ user }) => {
  return (
    <div class="panel-detail-container">
      <div class="list-group">
        <DetailRow label="Merchant Name" value={titleCase(user.name)} />

        <DetailRow
          label="Merchant Email"
          value={() => <a href={`mailto:${user.email}`}>{user.email}</a>}
        />

        <DetailRow
          label="Activation Status"
          data-tip={user.activated === 1 ? 'Activated' : 'Not Activated'}
          value={() => <CheckIcon value={user.activated == 1} />}
        />

        <DetailRow
          label="Registration Date"
          value={() => (
            <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />
          )}
        />

        <DetailRow
          label="Activation Form Progress"
          value={`${user.activation_progress}%`}
        />

      </div>
    </div>
  );
};
