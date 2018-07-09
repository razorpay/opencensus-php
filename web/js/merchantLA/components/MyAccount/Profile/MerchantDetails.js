import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import Time from 'rzp/ui/Time';
import { titleCase } from 'rzp/utils/rzp-utils';
import DetailRow from 'merchant/components/DetailRow';
import { openModal, closeModal } from 'rzp/modules/modals';

export default connect(null, { openModal, closeModal })(
  ({ user, openModal, closeModal }) => {
    return (
      <div class="list-group details-row-container">
        <DetailRow label="Contact Name" value={titleCase(user.name)} />

        <DetailRow
          label="Contact Email"
          value={() => <a href={`mailto:${user.email}`}>{user.email}</a>}
        />

        <DetailRow
          label="Business Name"
          value={titleCase(user.business_name)}
        />

        <DetailRow
          label="Business Type"
          value={titleCase(user.business_type)}
        />

        <DetailRow
          label="Registration Date"
          value={() => (
            <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />
          )}
        />

        <DetailRow
          label="Registrated By"
          value={() => (
            <span>
              <b class="text--secondary">{user.parent_name}</b> (Merchant ID:{' '}
              {user.parent_id}){' '}
            </span>
          )}
        />
      </div>
    );
  }
);
