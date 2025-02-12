import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import Time from 'common/ui/Time';
import { titleCase } from 'common/utils/rzp-utils';
import DetailRow from 'merchant/components/DetailRow';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

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
  ({ user, openModal, closeModal, changeDisplayName }) => {
    return (
      <div className="list-group details-row-container">
        <DetailRow label="Contact Name" value={titleCase(user.name)} />

        {changeDisplayName && (
          <DetailRow
            label={() => (
              <div>
                <span>Display Name</span>
                <small className="help-content">
                  <i className="i i-info-outline" />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div>
                        This is the display name that you and your team will see
                        on the Razorpay dashboard.
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
              </div>
            )}
            value={() => (
              <span>
                {user.display_name}
                <a
                  className="p-l"
                  title="Edit Display Name"
                  onClick={changeDisplayName}
                >
                  <i className="i i-edit" />
                </a>
              </span>
            )}
          />
        )}

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
