import React from 'react';
import { connect } from 'react-redux';

import Withdrawals from 'merchant/views/Capital/CashAdvance/withdrawals';

const LlDashboard = (props) => {
  const { withdrawalConfigurationDetails } = props;

  if (Boolean(withdrawalConfigurationDetails.data)) {
    return (
      <div className="cash-advance-body">
        <tabbed-container>
          <h1 className="cash-advance-title">Cash Advance</h1>
          <Withdrawals />
        </tabbed-container>
      </div>
    );
  }

  return null;
};

const mapStateToProps = (state) => ({
  withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
});

export default connect(mapStateToProps)(LlDashboard);
