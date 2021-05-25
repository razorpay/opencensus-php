import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import TestModeBanner from 'merchant/components/TestModeBanner';

import OnBoarding from './OnBoarding';
import QuickGuide from './QuickGuide';

import PaymentPagesList from 'merchant/views/PaymentPages/PaymentPages/List';

import PaymentPageAnalyticsBanner from 'merchant/components/Announcements/PaymentPageAnalytics';

@connect(
  (state) => {
    return {
      paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PP),
      user: state.session.user,
    };
  },
  {
    handleProductQuickGuide,
  },
)
export default class PaymentPagesContainer extends React.Component {
  render() {
    const { isQuickGuideOpen, showOnboarding } = this.props.paymentPageProductOnBoarding;

    if (showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <>
        <PaymentPageAnalyticsBanner
          bannerKey={`payment-pages-analytics-${this.props.user.current}`}
        />

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
