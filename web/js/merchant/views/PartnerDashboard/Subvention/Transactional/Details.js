import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import CommissionTransactionalEntity, {
  CommissionEarningBreakUp,
} from 'merchant/views/PartnerDashboard/Commissions/Transactional/Details';

@withRouter
export default class SubventionTransactionalEntity extends Component {
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
