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
import {
  Box,
  Text,
  Button,
  ShareIcon,
  ChevronRightIcon,
  Link as BladeLink,
  useTheme,
} from '@razorpay/blade/components';
import View from '@razorpay/blade-old/src/atoms/View';
import { getIsTestMode } from 'merchant/views/PaymentHandle/utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { PAYMENT_HANDLE_URL } from 'merchant/views/PaymentHandle/constants';
import { fetchPaymentHandle } from 'merchant/reducers/paymentHandle';
import PaymentHandleModal from 'merchant/containers/Home/ProductOnboardingCard/partials/PaymentHandleModal';
import { Link } from 'react-router-dom';
import { useBreakpoint } from '@razorpay/blade/utils';

const DropDownSlug = ({
  handleInfo,
  mode,
  history,
  openModal,
  closeModal,
  showNotification,
  fetchPaymentHandle,
  isConnectedNavigation,
}) => {
  const isTestMode = getIsTestMode(mode);
  const handleData = handleInfo?.data || {};
  const isSlugNotAvailable = Object.keys(handleData).length === 0 && !isTestMode;
  const isSlugAvailable = Object.keys(handleData).length > 0 && !isTestMode;
  const routeToPH = () => history.push(PAYMENT_HANDLE_URL);

  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';

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

  if (isConnectedNavigation) {
    //TODO: Layout shift issue on first time open post initial release
    //TODO: Check slug disappereing issue on click post initial release
    if (isSlugAvailable) {
      return (
        <Box
          marginX={isMobile ? 'spacing.0' : 'spacing.4'}
          padding="spacing.3"
          borderRadius="medium"
          borderColor="surface.border.gray.muted"
          borderWidth="thinner"
          marginY="spacing.3"
          display="flex"
          alignItems="center"
          justifyContent="space-between"
        >
          <Link to={PAYMENT_HANDLE_URL}>
            <Box display="flex" alignItems="center">
              <Text variant="body" weight="regular" size="medium" color="surface.text.gray.subtle">
                razorpay.me/
                <Text
                  variant="body"
                  weight="semibold"
                  size="medium"
                  color="surface.text.gray.subtle"
                  display="inline-block"
                >
                  {paymentHandleConfig?.paymentHandleSlug}
                </Text>
              </Text>

              <ChevronRightIcon size="medium" color="interactive.icon.primary.normal" />
            </Box>
          </Link>
          <Box>
            <Button
              size="xsmall"
              variant="tertiary"
              icon={ShareIcon}
              onClick={(event) => openPHShareModal(event)}
            />
          </Box>
        </Box>
      );
    }
    return null;
  }
  return isSlugAvailable ? (
    <DropDownSlugContainer onClick={routeToPH}>
      <LegendWrapper>
        <NewContainer>NEW</NewContainer>
      </LegendWrapper>
      <CenterAlignContainer>
        {' '}
        <AlignTogether>
          <Text size="small" color="surface.text.gray.subtle">
            razorpay.me/
          </Text>
          <Text weight="semibold" size="small" color="surface.text.gray.normal">
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
