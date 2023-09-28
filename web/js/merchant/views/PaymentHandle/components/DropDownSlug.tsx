import React, { useEffect, useState } from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import {
  NewContainer,
  AlignTogether,
  LegendWrapper,
  ExportWrapper,
  ShareContainer,
  CenterAlignContainer,
  DropDownSlugContainer,
} from 'merchant/views/PaymentHandle/style';
import { Text } from '@razorpay/blade/components';
import View from '@razorpay/blade-old/src/atoms/View';
import { getIsTestMode } from 'merchant/views/PaymentHandle/utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { PAYMENT_HANDLE_URL } from 'merchant/views/PaymentHandle/constants';
import { fetchPaymentHandle } from 'merchant/reducers/paymentHandle';
import PaymentHandleModal from 'merchant/containers/Home/ProductOnboardingCard/partials/PaymentHandleModal';

const DropDownSlug = ({
  handleInfo,
  mode,
  history,
  openModal,
  closeModal,
  showNotification,
  fetchPaymentHandle,
}) => {
  const isTestMode = getIsTestMode(mode);
  const handleData = handleInfo?.data || {};
  const isSlugNotAvailable = Object.keys(handleData).length === 0 && !isTestMode;
  const isSlugAvailable = Object.keys(handleData).length > 0 && !isTestMode;
  const routeToPH = () => history.push(PAYMENT_HANDLE_URL);

  const initialState = {
    paymentHandleSlug: handleData?.slug ?? '@',
    paymentHandleUrl: handleData?.url ?? `https://razorpay.me/@`,
  };

  const [paymentHandleConfig, setPaymentHandleConfig] = useState(initialState);

  useEffect(() => {
    const fetchHandle = async () => {
      await fetchPaymentHandle().then((response) => {
        if (response) {
          setPaymentHandleConfig({
            paymentHandleSlug: response.data?.slug ?? '@',
            paymentHandleUrl: response.data?.url ?? `https://razorpay.me/@`,
          });
        }
      });
    };
    if (isSlugNotAvailable) {
      fetchHandle();
    }
  }, []);

  const openPHShareModal = (event) => {
    openModal({
      component: (
        <PaymentHandleModal
          product="PH"
          closeModal={closeModal}
          showNotification={showNotification}
          paymentHandleData={paymentHandleConfig}
        />
      ),
      size: 'medium',
      className: 'PaymentHandle--Modal',
    });
    event.stopPropagation();
  };

  return isSlugAvailable ? (
    <DropDownSlugContainer onClick={routeToPH}>
      <LegendWrapper>
        <NewContainer>NEW</NewContainer>
      </LegendWrapper>
      <CenterAlignContainer>
        {' '}
        <AlignTogether>
          <Text type="subtle" contrast="low" size="small">
            razorpay.me/
          </Text>
          <Text weight="bold" type="normal" contrast="low" size="small">
            {paymentHandleConfig?.paymentHandleSlug}
          </Text>
          <ShareContainer onClick={(event) => openPHShareModal(event)}>
            <ExportWrapper className="i-export" />
          </ShareContainer>
        </AlignTogether>
        <View>
          <i className="i i-chevron-right" />
        </View>
      </CenterAlignContainer>
    </DropDownSlugContainer>
  ) : null;
};

export default compose<any>(
  withRouter,
  connect(
    (state) => ({
      mode: state.session.mode,
      handleInfo: state.paymentHandle.handleInfo,
    }),
    {
      openModal: fnOpenModal,
      closeModal: fnCloseModal,
      showNotification,
      fetchPaymentHandle,
    },
  ),
)(DropDownSlug);
