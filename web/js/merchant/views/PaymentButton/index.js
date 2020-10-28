import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import TestModeBanner from 'merchant/components/TestModeBanner';
import PaymentButtonList from 'merchant/views/PaymentButton/PaymentButton/List';
import SubscriptionButtonList from 'merchant/views/PaymentButton/SubscriptionButton/List';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import OnBoarding, {
  getIsPaymentButtonsEnabled,
  getIsAllowedResetPaymentButtonsOnBoarding,
} from './OnBoarding';
import QuickGuide, { getPaymentButtonsQuickGuideIsClosed } from './QuickGuide';

@connect(
  (state) => {
    return {
      user: state.session.user,
      mode: state.session.mode,
      paymentbuttons: state.paymentbuttons,
      paymentButtonsProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PB),
    };
  },
  { handleProductQuickGuide },
)
export default class PaymentButtonsContainer extends React.Component {
  componentWillReceiveProps(nextProps) {
    if (nextProps.paymentbuttons.loading !== this.props.paymentbuttons.loading) {
      this.initPaymentButtonsOnboarding(nextProps);
    }
  }

  componentWillUnmount() {
    const { paymentButtonsProductOnBoarding } = this.props;

    if (paymentButtonsProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...paymentButtonsProductOnBoarding,
        showOnboarding: false,
        isTour: false,
      });
    }
  }

  initPaymentButtonsOnboarding = (props = this.props) => {
    if (props.paymentButtonsProductOnBoarding.isTour) {
      return;
    }

    const data = {
      user: props.user,
      merchantId: props.user.current,
      paymentbuttons: props.paymentbuttons,
    };

    const isPaymentButtonsEnabled = getIsPaymentButtonsEnabled(data);

    let showOnboarding = !isPaymentButtonsEnabled;

    if (isPaymentButtonsEnabled) {
      showOnboarding = getIsAllowedResetPaymentButtonsOnBoarding(data.paymentbuttons);
    }

    this.props.handleProductQuickGuide({
      ...props.paymentButtonsProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getPaymentButtonsQuickGuideIsClosed(props),
    });
  };

  render() {
    const { showOnboarding, isQuickGuideOpen } = this.props.paymentButtonsProductOnBoarding;

    if (showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <tabbed-container>
        {isQuickGuideOpen && <QuickGuide mid={this.props.user.current} mode={this.props.mode} />}

        <header id="link-header">
          {this.props.user.isPaymentButtonEnabledByRazorX && (
            <NavLink exact to="/paymentbuttons">
              Payment Buttons
            </NavLink>
          )}

          {this.props.user.isSubscriptionButtonEnabledByRazorX && (
            <NavLink exact to="/subscription_buttons">
              Subscription Buttons <span class="badge bg-success m-r">new</span>
            </NavLink>
          )}
        </header>

        <TestModeBanner />

        <content>
          <Switch>
            <Route path="/paymentbuttons" component={PaymentButtonList} />
            <Route path="/subscription_buttons" component={SubscriptionButtonList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
