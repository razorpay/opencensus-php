import React from 'react';
import { connect } from 'react-redux';
import RemoteComponent from '../../RemoteComponent';
import { fetchFunctionalWithdrawalConfigByMerchantID } from 'merchant/reducers/capital/withdrawals';

const Settings = ({ withdrawalConfiguration, fetchWithdrawalConfig }) => {
  return (
    <RemoteComponent
      project="cash-advance-settings"
      withdrawalConfiguration={withdrawalConfiguration}
      fetchWithdrawalConfig={fetchWithdrawalConfig}
    />
  );
};

const mapStateToProps = (state) => ({
  withdrawalConfiguration: state.withdrawals.withdrawalConfiguration,
});

const mapDispatchToProps = {
  fetchWithdrawalConfig: fetchFunctionalWithdrawalConfigByMerchantID,
};

export default connect(mapStateToProps, mapDispatchToProps)(Settings);
