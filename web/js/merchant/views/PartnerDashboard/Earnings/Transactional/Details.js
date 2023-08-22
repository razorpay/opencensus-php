import { Component } from 'react';
import { withRouter } from 'react-router-dom';

import { paiseToRupees } from 'common/utils/rzp-utils';
import CommissionTransactionalDetails, {
  CommissionEarningBreakUp,
} from 'merchant/views/PartnerDashboard/Commissions/Transactional/Details';
import { COMMISSION_TYPE } from 'merchant/views/PartnerDashboard/constants';

@withRouter
export default class EarningTransactionalDetails extends Component {
  render() {
    return <CommissionTransactionalDetails renderDetails={renderDetails} {...this.props} />;
  }
}

function renderDetails(entity) {
  // dividing by 100 because the amount is being returned in paise from backend
  const totalAmount = paiseToRupees(
    entity.sourceType === COMMISSION_TYPE.REFUND ? entity.debit : entity.credit,
  );
  const tax = paiseToRupees(entity.tax);
  return (
    <CommissionEarningBreakUp
      currency={entity.currency}
      total={totalAmount}
      gst={tax}
      base={totalAmount - tax}
      org={entity.org}
      user={entity.user}
      sourceType={entity.sourceType}
    />
  );
}
