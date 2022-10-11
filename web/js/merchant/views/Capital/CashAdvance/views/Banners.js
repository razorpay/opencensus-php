import React from 'react';
import { connect } from 'react-redux';
import RemoteComponent from 'merchant/views/Capital/RemoteComponent';
import { fetchFunctionalWithdrawalConfigByMerchantID } from 'merchant/reducers/capital/withdrawals';

const Banners = ({ user, withdrawalConfiguration, fetchWithdrawalConfig }) => {
  if (user.isLocCliOfferEnabled) {
    return (
      <RemoteComponent
        project="cash-advance-banners"
        withdrawalConfiguration={withdrawalConfiguration}
        fetchWithdrawalConfig={fetchWithdrawalConfig}
      />
    );
  }

  return null;
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  withdrawalConfiguration: state.withdrawals.withdrawalConfiguration,
});

const mapDispatchToProps = {
  fetchWithdrawalConfig: fetchFunctionalWithdrawalConfigByMerchantID,
};

export default connect(mapStateToProps, mapDispatchToProps)(Banners);
