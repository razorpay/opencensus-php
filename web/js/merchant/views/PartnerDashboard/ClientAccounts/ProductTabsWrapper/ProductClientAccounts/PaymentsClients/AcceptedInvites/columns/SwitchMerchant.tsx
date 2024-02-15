import React from 'react';
import { Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { switchMerchant } from 'merchant/reducers/session';
import { showNotification } from 'merchant_common/reducers/notifications';

const SwitchMerchant = ({ submerchant, switchMerchant, showNotification }) => {
  const handleSwitchMerchant = (merchantId) => () => {
    switchMerchant(merchantId)
      .then(() => {
        window.location.reload();
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };
  return (
    <Button
      size="small"
      variant="secondary"
      onClick={handleSwitchMerchant(submerchant.id.replace('acc_', ''))}
    >
      Switch
    </Button>
  );
};
export default connect(null, (dispatch) =>
  bindActionCreators({ switchMerchant, showNotification }, dispatch),
)(SwitchMerchant);
