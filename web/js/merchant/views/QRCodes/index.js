import { connect } from 'react-redux';
import { Switch, NavLink } from 'react-router-dom';
import React from 'react';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { RZPFeatures } from 'merchant/helpers/data';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { bindActionCreators } from 'redux';

import ComingSoon from './ComingSoon';
import QRCodesList from './QRCodes/List';
import PaymentsList from './Payments/List';
import QuickGuide, { getQRCodeQuickGuideIsClosed } from './QuickGuide';
import OnBoarding, { getIsQRCodesEnabled, getIsAllowedResetQRCodesOnBoarding } from './OnBoarding';

class QRCodeContainer extends React.Component {
  componentWillReceiveProps(nextProps) {
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
    const { isTestMode, user, productOnBoarding, mode } = this.props;

    if (user.isQRCodeComingSoonExpEnabled && !user.isQRCodeComingSoonEnabled(mode)) {
      return <ComingSoon onInterestClicked={() => this.forceUpdate()} />;
    }

    const { showOnboarding, isQuickGuideOpen } = productOnBoarding;

    if (showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <tabbed-container>
        {isQuickGuideOpen && <QuickGuide />}

        <header id="link-header">
          <NavLink exact to="/qr_codes">
            QR Codes
          </NavLink>
          <NavLink to="/qr_codes/payments">Payments</NavLink>
        </header>

        {isTestMode && <TestModeBanner />}

        <content>
          <Switch>
            <ShowWhenRoute
              path="/qr_codes/payments"
              component={PaymentsList}
              additionalCondition={(userCurrent) => userCurrent.isAllowedView('qr_codes')}
            />
            <ShowWhenRoute
              additionalCondition={(userCurrent) => userCurrent.isAllowedView('qr_codes')}
              path="/qr_codes"
              component={QRCodesList}
            />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    mode: state.session.mode,
    isTestMode: state.session.mode === 'test',
    user: state.session.user,
    qr_codes: state.qr_codes,
    productOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.QR_CODES),
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ handleProductQuickGuide }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(QRCodeContainer);
