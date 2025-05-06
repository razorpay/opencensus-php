import React, { useState } from 'react';
import { Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import { switchMerchant } from 'merchant/reducers/session';
import { showNotification } from 'merchant_common/reducers/notifications';
import { PARTNER_TYPE } from 'merchant/views/PartnerDashboard/constants';

const SwitchMerchant = ({ submerchant, switchMerchant, showNotification, isDisabled, user }) => {
  const [isLoading, setLoading] = useState(false);
  const btnText = user.isPartner(PARTNER_TYPE.AGGREGATOR) ? 'Switch to merchant account' : 'Switch';

  const handleSwitchMerchant = (merchantId) => () => {
    setLoading(true);
    switchMerchant(merchantId)
      .then(() => {
        setLoading(false);
        window.location.reload();
      })
      .catch(({ errors }) => {
        setLoading(false);
        showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };
  return (
    <Button
      size="small"
      isLoading={isLoading}
      variant="secondary"
      onClick={handleSwitchMerchant(submerchant.id.replace('acc_', ''))}
      isDisabled={isDisabled}
    >
      {btnText}
    </Button>
  );
};

export default compose(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({ switchMerchant, showNotification }, dispatch),
  ),
)(SwitchMerchant);
