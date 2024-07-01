import React from 'react';
import { connect } from 'react-redux';

import Time from 'common/ui/Time';
import { titleCase } from 'common/utils/rzp-utils';
import DetailRow from 'merchant/components/DetailRow';
import { BUSINESS_TYPE_MAP } from 'merchant/views/Account/constants';

const BusinessDetails = ({ user, isFlowRevamped = true }): JSX.Element => {
  return (
    <div
      data-testid="business-details-section"
      className={`${isFlowRevamped ? 'list-group details-row-container' : ''}`}
    >
      <DetailRow label="Business Name" value={user.business_name} />
      <DetailRow label="Business Type" value={titleCase(BUSINESS_TYPE_MAP[user.business_type])} />
      <DetailRow
        label="Registration Date"
        value={() => <Time value={user.created_at} format="MMM DD YYYY, hh:mm:ss a" />}
      />
      <DetailRow label="Registered By" value={user.marketplace_merchant_name} />
    </div>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps, null)(BusinessDetails);
