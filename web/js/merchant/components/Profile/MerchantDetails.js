import { Link } from 'react-router-dom';
import Time from 'rzp/ui/Time';
import { titleCase } from 'rzp/utils/rzp-utils';
import DetailRow from '../DetailRow';
import CheckIcon from 'rzp/ui/CheckIcon';
import ProgressBar from 'rzp/ui/ProgressBar';

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
        label="Registration Date"
        value={() => (
          <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />
        )}
      />

      <DetailRow
        label="Account Activation"
        value={() => (
          <span>
            <Link to={'/activation'}>
              {user.activated || user.locked || user.submitted
                ? 'View'
                : 'Fill'}{' '}
              Activation Form
            </Link>
          </span>
        )}
      />

      {!!user.activated && (
        <DetailRow
          label="Account Activated On"
          value={() => (
            <Time value={user.activated_at} format="MMM DD YYYY, hh:mm a" />
          )}
        />
      )}

      <DetailRow
        label="Activation Status"
        value={() =>
          user.activation_status ? (
            <ActivationStatusLabel status={user.activation_status} />
          ) : (
            <div className="activation-bar-content activation-status-secondary">
              <div className="activation-bar-text">
                {user.activation_progress}% Completed
              </div>
              <div className="activation-bar">
                <ProgressBar
                  type="success"
                  max={100}
                  value={user.activation_progress}
                />
              </div>
            </div>
          )
        }
      />
    </div>
  );
};
