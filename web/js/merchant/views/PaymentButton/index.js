import React from 'react';
import { connect } from 'react-redux';
import { Route, Switch } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import PaymentButtonList from 'merchant/views/PaymentButton/PaymentButton/List';
import SubscriptionButtonList from 'merchant/views/PaymentButton/SubscriptionButton/List';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import { updateFeatures } from 'merchant/reducers/config';
import { fetchUser } from 'merchant/reducers/session';

import OnBoarding, {
  getIsPaymentButtonsEnabled,
  getIsAllowedResetPaymentButtonsOnBoarding,
} from './OnBoarding';
import QuickGuide, { getPaymentButtonsQuickGuideIsClosed } from './QuickGuide';
import CardPaymentsBlockedBanner from 'merchant/views/Subscriptions/components/CardPaymentsBlocked/Banner';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

@connect(
  (state) => {
    return {
      user: state.session.user,
      mode: state.session.mode,
      paymentbuttons: state.paymentbuttons,
      paymentButtonsProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PB),
    };
  },
  {
    handleProductQuickGuide,
    fetchUser,
    updateFeatures,
  },
)
export default class PaymentButtonsContainer extends React.Component {
  UNSAFE_componentWillReceiveProps(nextProps) {
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

    // Note: For those merchant who have seen the onboarding but couldn't enable Subscription feature, it would automatically make an update call
    if (!showOnboarding && !this.props.user.isSubscriptionsEnabled) {
      this.props
        .updateFeatures(
          {
            features: {
              [RZPFeatures.SUBSCRIPTIONS]: 1,
            },
          },
          this.props.user.current,
        )
        .then(() => {
          return this.props.fetchUser();
        });
    }
  };

  render() {
    const { user } = this.props;
    const { showOnboarding, isQuickGuideOpen } = this.props.paymentButtonsProductOnBoarding;

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <>
        <div className="banner-container">
          <DashboardBanner />
          {user.isSubscriptionButtonEnabled && user.isCardRecurringPaymentsBlocked && (
            <CardPaymentsBlockedBanner />
          )}
        </div>

        {isQuickGuideOpen && (
          <QuickGuide
            mid={this.props.user.current}
            mode={this.props.mode}
            className="QuickGuide-v2"
          />
        )}

        <ErrorBoundary resetOnProps>
          <Switch>
            <Route path="/paymentbuttons" component={PaymentButtonList} />
            {!user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.SubscriptionPaymentButton) && (
              <Route path="/subscription_buttons" component={SubscriptionButtonList} />
            )}
          </Switch>
        </ErrorBoundary>
      </>
    );
  }
}
