/* eslint-disable react/no-unsafe */
import React from 'react';
import { connect } from 'react-redux';
import { Route, Routes } from 'react-router-dom';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { RZPFeatures } from 'merchant/helpers/data';
import { BlockCustomerFeeBearerOnboarding } from 'merchant/components/BlockOnBoarding';
import { bindActionCreators } from 'redux';
import { RouteGuard } from 'merchant/components/ShowWhen';

import QRCodesList from './QRCodes/List';
import PaymentsList from './Payments/List';
import QuickGuide, { getQRCodeQuickGuideIsClosed } from './QuickGuide';
import OnBoarding, { getIsQRCodesEnabled, getIsAllowedResetQRCodesOnBoarding } from './OnBoarding';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import { Alert, Box } from '@razorpay/blade/components';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';

class QRCodeContainer extends React.Component {
  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.qr_codes.loading !== this.props.qr_codes.loading) {
      this.initOnboarding(nextProps);
    }
  }

  componentWillUnmount() {
    const { productOnBoarding } = this.props;

    if (productOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...productOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: false,
        isTour: false,
      });
    }
  }

  initOnboarding = (props = this.props) => {
    if (props.productOnBoarding.isTour) {
      return;
    }

    const data = {
      user: props.user,
      merchantId: props.user.current,
      qr_codes: props.qr_codes,
    };

    const isQRCodesEnabled = getIsQRCodesEnabled(data);

    let showOnboarding = !isQRCodesEnabled;

    if (isQRCodesEnabled) {
      showOnboarding = getIsAllowedResetQRCodesOnBoarding(data.qr_codes);
    }

    const productOnBoarding = {
      ...props.productOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getQRCodeQuickGuideIsClosed(props),
    };

    this.props.handleProductQuickGuide(productOnBoarding);
  };

  render() {
    const { productOnBoarding, user } = this.props;

    const feeBearer = user.merchant.fee_bearer;
    const isCustomerFeeBearer = feeBearer === FEE_BEARER_TYPES.CUSTOMER;
    const isQRDisabled = !user.isQRCodeProductEnabled;

    const { showOnboarding, isQuickGuideOpen } = productOnBoarding;

    // in case of customer fee bearer if qr is disabled for the user, he should not be able to access onboarding to turn on the QR
    if (isCustomerFeeBearer && isQRDisabled) {
      return (
        <div className="text-justified">
          <BlockCustomerFeeBearerOnboarding feature="QR Codes" />
        </div>
      );
    }

    if (showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <>
        <div className="banner-container">
          <DashboardBanner />
        </div>

        {isQuickGuideOpen && <QuickGuide className="QuickGuide-v2" />}
        {isCustomerFeeBearer && (
          <Box padding="spacing.6" paddingBottom="spacing.0">
            <Alert
              contrast="low"
              description="This product is not supported for merchants accepting payments as per the convenience fee model. Any payments accepted via QR will be auto refunded."
              intent="notice"
              title="QR Code is not available for you"
              isDismissible={false}
              isFullWidth
            />
          </Box>
        )}
        <ErrorBoundary resetOnProps>
          <Routes>
            <Route
              path="payments/*"
              element={
                <RouteGuard
                  additionalCondition={(currentUser) => currentUser.isAllowedView('qr_codes')}
                >
                  <PaymentsList />
                </RouteGuard>
              }
            />
            <Route
              index
              element={
                <RouteGuard
                  additionalCondition={(currentUser) => currentUser.isAllowedView('qr_codes')}
                >
                  <QRCodesList />
                </RouteGuard>
              }
            />
          </Routes>
        </ErrorBoundary>
      </>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    mode: state.session.mode,
    user: state.session.user,
    qr_codes: state.qr_codes,
    productOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.QR_CODES),
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ handleProductQuickGuide }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(QRCodeContainer);
