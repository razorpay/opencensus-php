import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'rzp/utils/constants';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import TestModeBanner from 'merchant/containers/TestModeBanner';

import OnBoarding, { isAllowedPaymentPagesOnBoarding } from './OnBoarding';
import QuickGuide, { getRouteQuickGuideIsClosed } from './QuickGuide';

import PaymentPagesList from './Pages/List';

@connect(
  state => {
    return {
      user: state.session.user,
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
            <span
              class="badge bg-success hidden-xs"
              style={{ marginLeft: '5px' }}
            >
              new
            </span>
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
