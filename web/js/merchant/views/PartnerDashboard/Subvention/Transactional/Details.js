import React, { Component } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import CommissionTransactionalEntity, {
  CommissionEarningBreakUp,
} from 'merchant/views/PartnerDashboard/Commissions/Transactional/Details';

class SubventionTransactionalEntity extends Component {
  render() {
    return <CommissionTransactionalEntity renderDetails={renderDetails} {...this.props} />;
  }
}

function renderDetails(entity) {
  return (
    <CommissionEarningBreakUp
      currency={entity.currency}
      total={entity.fee}
      gst={entity.tax}
      base={entity.fee - entity.tax}
      org={entity.org}
      user={entity.user}
    />
  );
}

export default withRouter(SubventionTransactionalEntity);
