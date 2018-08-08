import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import Time from 'rzp/ui/Time';
import { titleCase } from 'rzp/utils/rzp-utils';
import DetailRow from 'merchant/components/DetailRow';
import { openModal, closeModal } from 'rzp/modules/modals';

const businessTypeMap = {
  1: 'Proprietorship',
  2: 'Individual',
  3: 'Partnership',
  4: 'Private',
  5: 'Public',
  6: 'LLP',
  7: 'NGO',
  9: 'Trust',
  10: 'Society',
};

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
          value={titleCase(businessTypeMap[user.business_type])}
        />

        <DetailRow
          label="Registration Date"
          value={() => (
            <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />
          )}
        />

        <DetailRow
          label="Registered By"
          value={user.marketplace_merchant_name}
        />
      </div>
    );
  }
);
