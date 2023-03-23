import React from 'react';
import { connect } from 'react-redux';
import { Switch } from 'react-router-dom';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { RZPFeatures } from 'merchant/helpers/data';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { bindActionCreators } from 'redux';

import QRCodesList from './QRCodes/List';
import PaymentsList from './Payments/List';
import QuickGuide, { getQRCodeQuickGuideIsClosed } from './QuickGuide';
import OnBoarding, { getIsQRCodesEnabled, getIsAllowedResetQRCodesOnBoarding } from './OnBoarding';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';

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
    const { productOnBoarding } = this.props;

    const { showOnboarding, isQuickGuideOpen } = productOnBoarding;

    if (showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <>
        <div className="banner-container">
          <DashboardBanner />
        </div>

        {isQuickGuideOpen && <QuickGuide className="QuickGuide-v2" />}

        <ErrorBoundary resetOnProps>
          <Switch>
            <ShowWhenRoute
              path="/qr_codes/payments"
              component={PaymentsList}
              additionalCondition={(currentUser) => currentUser.isAllowedView('qr_codes')}
            />
            <ShowWhenRoute
              additionalCondition={(currentUser) => currentUser.isAllowedView('qr_codes')}
              path="/qr_codes"
              component={QRCodesList}
            />
          </Switch>
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
