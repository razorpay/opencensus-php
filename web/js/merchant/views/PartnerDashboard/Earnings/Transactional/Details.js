import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import CommissionTransactionalDetails, {
  CommissionEarningBreakUp,
} from 'merchant/views/PartnerDashboard/Commissions/Transactional/Details';

@withRouter
export default class EarningTransactionalDetails extends Component {
  render() {
    return <CommissionTransactionalDetails renderDetails={renderDetails} {...this.props} />;
  }
}

function renderDetails(entity) {
  return (
    <CommissionEarningBreakUp
      currency={entity.currency}
      total={entity.credit}
      gst={entity.tax}
      base={entity.credit - entity.tax}
      org={entity.org}
      user={entity.user}
    />
  );
}
