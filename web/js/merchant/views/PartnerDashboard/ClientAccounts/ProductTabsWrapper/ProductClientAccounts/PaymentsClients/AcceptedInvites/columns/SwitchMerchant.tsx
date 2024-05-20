import React, { useState } from 'react';
import { Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { switchMerchant } from 'merchant/reducers/session';
import { showNotification } from 'merchant_common/reducers/notifications';

const SwitchMerchant = ({ submerchant, switchMerchant, showNotification }) => {
  const [isLoading, setLoading] = useState(false);
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
    >
      Switch
    </Button>
  );
};
export default connect(null, (dispatch) =>
  bindActionCreators({ switchMerchant, showNotification }, dispatch),
)(SwitchMerchant);
