import ShowWhen from 'merchant/components/ShowWhen';
import { connect } from 'react-redux';
import React from "react";

const EmptyDailyList = (props) => {
  const { items, isAddMerchantView, user } = props;
  /**
   * Default limit for rzp would be 3 and for other
   * orgs it would be 0.
   */
  let limit = user.isOrgRZP ? 3 : 0;
  if (items.limit) {
    limit = items.limit;
  }
  const minAccCount = <>(&gt;{limit})</>;

  return (
    <div className="empty-daily-list">
      <div className="empty-table-message">
        <ShowWhen additionalCondition={() => isAddMerchantView}>
          <h3>Unlock your earnings view</h3>
          {/* We would hide limit text ie- (>0) if the limit is 0 */}
          <p className="m-t">
            Add more accounts {limit !== 0 ? minAccCount : ''} to unlock the details view of
            processed earnings
          </p>
          <p>
            <button className="btn btn-link" onClick={props.handleAddMerchant}>
              + Add New Account
            </button>
          </p>
        </ShowWhen>
        <ShowWhen additionalCondition={() => !isAddMerchantView}>
          <h4> No Data Found!</h4>
        </ShowWhen>
      </div>
    </div>
  );
};

export default connect((state) => ({ user: state.session.user }), null)(EmptyDailyList);
