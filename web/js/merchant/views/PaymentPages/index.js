import { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import { getCurrentProductOnBoardingDetails } from 'merchant/reducers/onboarding';

import TestModeBanner from 'merchant/components/TestModeBanner';
import OnBoarding from './OnBoarding';
import QuickGuide from './QuickGuide';
import ShowWhen from 'merchant/components/ShowWhen';

import PaymentPagesList from 'merchant/views/PaymentPages/PaymentPages/List';

import PaymentPageZapierBanner from '../../components/Announcements/PaymentPageZapier';

@connect((state) => {
  return {
    paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PP),
    user: state.session.user,
  };
})
export default class PaymentPagesContainer extends Component {
  render() {
    const { user } = this.props;
    const { isQuickGuideOpen, showOnboarding } = this.props.paymentPageProductOnBoarding;

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <>
        <ShowWhen
          additionalCondition={(currentUser) => currentUser.isPaymentPageZapierBannerEnabled}
        >
          <div className="banner-container">
            <PaymentPageZapierBanner bannerKey={`payment-pages-zapier-${user.current}`} />
          </div>
        </ShowWhen>
        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="link-header">
            <NavLink exact to="/paymentpages">
              Payment Pages
            </NavLink>
          </header>

          <TestModeBanner />

          <content>
            <Switch>
              <Route path="/paymentpages" exact component={PaymentPagesList} />
            </Switch>
          </content>
        </tabbed-container>
      </>
    );
  }
}
