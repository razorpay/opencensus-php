import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import {
  getPHProductOnboarding,
  isHandleAvailableForMerchant,
} from 'merchant/views/PaymentHandle/utils';
import Loading from 'merchant/views/PaymentHandle/views/Shimmer';
import List from 'merchant/views/PaymentHandle/views/List';
import Onboarding from 'merchant/views/PaymentHandle/views/Onboarding';
import { PaymentHandleIndexPropTypes } from 'merchant/views/PaymentHandle/typings';
import { fetchPaymentHandle, createPaymentHandle } from 'merchant/reducers/paymentHandle';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';

const PaymentHandle = ({
  user,
  handleInfo,
  showNotification,
  fetchPaymentHandle,
  createPaymentHandle,
}: PaymentHandleIndexPropTypes): JSX.Element => {
  const [isOnboardingVisible, setOnboardingVisible] = useState(!getPHProductOnboarding(user));
  const [isApiFailed, setApiFailed] = useState(false);

  const fetchInfo = () => {
    try {
      fetchPaymentHandle().catch((err) => {
        if (!isHandleAvailableForMerchant(err.errors)) {
          setOnboardingVisible(true);
          createPaymentHandle();
        } else {
          setApiFailed(true);
          showNotification({
            type: 'error',
            hidePrevious: true,
            message: 'Something went wrong, Our team will get back to you shortly.',
          });
        }
      });
    } catch {
      setApiFailed(true);
      showNotification({
        type: 'error',
        hidePrevious: true,
        message: 'Something went wrong, Our team will get back to you shortly.',
      });
    }
  };

  useEffect(() => {
    fetchInfo();
  }, []);

  if (handleInfo.loading || isApiFailed) {
    return <Loading showError={isApiFailed} />;
  }

  return isOnboardingVisible ? (
    <Onboarding setOnboardingVisible={setOnboardingVisible} />
  ) : (
    <List />
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
    handleInfo: state.paymentHandle.handleInfo,
  }),
  {
    fetchPaymentHandle,
    createPaymentHandle,
    showNotification: showNotificationProp,
  },
)(PaymentHandle);
