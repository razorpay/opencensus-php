import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import TestModeBanner from 'merchant/containers/TestModeBanner';

import OnBoarding from './OnBoarding';
import QuickGuide from './QuickGuide';

import PaymentPagesList from 'merchant/views/PaymentPages/PaymentPages/List';

@connect(
  state => {
    return {
      paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(
        state,
        RZPFeatures.PP
      ),
    };
  },
  {
    handleProductQuickGuide,
  }
)
export default class PaymentPagesContainer extends React.Component {
  render() {
    const {
      isQuickGuideOpen,
      showOnboarding,
    } = this.props.paymentPageProductOnBoarding;

    if (showOnboarding) {
      return <OnBoarding />;
    }

    return (
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
            <Route path="/paymentpages" component={PaymentPagesList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
