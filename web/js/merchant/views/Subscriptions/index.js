import React from 'react';
import { connect } from 'react-redux';
import { Switch, NavLink, Route } from 'react-router-dom';

import { classList } from 'common/utils/rzp-utils';

import { RZPFeatures } from 'merchant/helpers/data';

import Popover, { PopoverBody } from 'common/ui/Popover';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import { fetchPlans } from 'merchant/reducers/plans';
import { fetchSubscriptions, getCheckoutInfo } from 'merchant/reducers/subscriptions';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import PlansList from 'merchant/views/Subscriptions/Plans/List';
import TestModeBanner from 'merchant/components/TestModeBanner';
import SubscriptionsList from 'merchant/views/Subscriptions/Subscriptions/List';

import TokensList from 'merchant/views/Subscriptions/Tokens/List';
import RegistrationLinksList from 'merchant/views/Subscriptions/RegistrationLinks/List';
import HostedEmanadateBatches from 'merchant/views/Subscriptions/Batch/List';
import RecurringPayments from 'merchant/views/Subscriptions/RecurringPayments/List';
import OnBoarding, {
  getIsAllowedResetSubscriptionBoarding,
} from 'merchant/views/Subscriptions/OnBoarding';
import QuickGuide, {
  getSubscriptionQuickGuideIsClosed,
} from 'merchant/views/Subscriptions/QuickGuide';
import SubscriptionSettings from 'merchant/views/Subscriptions/Settings';
import SubscriptionOffersLaunchBanner from 'merchant/components/Announcements/SubscriptionOffers';
import EmandateBanner from 'merchant/components/Announcements/EmandateSubscription';
import CardPaymentsBlockedBanner from './components/CardPaymentsBlocked/Banner';
import { CardsGoLiveBanner } from './components/banners/';
import { CAW_CARDS_BANNER, SUBSCRIPTION_CARDS_BANNER } from './constants';

@connect(
  (state) => ({
    mode: state.session.mode,
    user: state.session.user,
    plans: state.plans,
    subscriptions: state.subscriptions,
    subscriptionProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.SUBSCRIPTIONS,
    ),
    payments: state.payments,
  }),
  {
    fetchPlans,
    getCheckoutInfo,
    fetchSubscriptions,
    handleProductQuickGuide,
  },
)
class SubscriptionsController extends React.Component {
  componentDidMount() {
    this.initSubscriptions();
    this.fetchDataForOnboarding();
    this.props.getCheckoutInfo(this.props.user.current);
  }

  componentWillUnmount() {
    const { subscriptionProductOnBoarding } = this.props;

    if (subscriptionProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...subscriptionProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: false,
        isTour: false,
      });
    }
  }

  componentWillReceiveProps(nextProps) {
    if (
      nextProps.subscriptions.loading !== this.props.subscriptions.loading ||
      nextProps.subscriptions.loading != this.props.subscriptions.loading
    ) {
      this.initSubscriptions(nextProps);
    }
  }

  fetchDataForOnboarding = () => {
    if (this.props.user.isChargeAtWillEnabled) return;

    const { subscriptions, plans, location } = this.props;

    const isPlanRoute = location.pathname.includes('plan');

    if (subscriptions.items.length || plans.items.length) {
      return;
    }

    if (isPlanRoute) {
      this.props.fetchSubscriptions({ count: 25 });

      return;
    }

    this.props.fetchPlans({ count: 25 });
  };

  initSubscriptions = (props = this.props) => {
    if (props.user.isChargeAtWillEnabled || props.subscriptionProductOnBoarding.isTour) {
      return;
    }

    const { subscriptionProductOnBoarding } = props;
    const { isSubscriptionsEnabled } = props.user;

    let showOnboarding = !isSubscriptionsEnabled;

    if (isSubscriptionsEnabled) {
      showOnboarding = getIsAllowedResetSubscriptionBoarding({
        plans: props.plans,
        subscriptions: props.subscriptions,
      });
    } else {
      this.props.handleProductQuickGuide({
        ...subscriptionProductOnBoarding,
        showOnboarding,
      });

      return;
    }

    this.props.handleProductQuickGuide({
      ...subscriptionProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getSubscriptionQuickGuideIsClosed(props),
    });
  };

  render() {
    const { subscriptionProductOnBoarding, user: userInfo } = this.props;
    const cardsGoLiveBannerUrl = userInfo.isChargeAtWillEnabled
      ? CAW_CARDS_BANNER
      : SUBSCRIPTION_CARDS_BANNER;

    if (subscriptionProductOnBoarding.showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <div class={classList('Subscriptions-Container')}>
        {!userInfo.isChargeAtWillEnabled && <SubscriptionOffersLaunchBanner />}
        <CardsGoLiveBanner url={cardsGoLiveBannerUrl} />
        {userInfo.isEmandateOnSubscriptionEnabled && <EmandateBanner />}
        {userInfo.isCardRecurringPaymentsBlocked && (
          <CardPaymentsBlockedBanner isCAW={userInfo.isChargeAtWillEnabled} />
        )}

        <tabbed-container>
          {subscriptionProductOnBoarding.isQuickGuideOpen && <QuickGuide />}

          <header id="subscriptions-header">
            <ShowWhen additionalCondition={(user) => !user.isChargeAtWillEnabled}>
              <NavLink exact to="/subscriptions">
                Subscriptions
              </NavLink>
              <NavLink to="/plans">Plans</NavLink>
              <ShowWhen additionalCondition={(user) => !user.isChargeAtWillEnabled}>
                <NavLink to="/subscriptions/settings">Settings</NavLink>
              </ShowWhen>
            </ShowWhen>

            <ShowWhen additionalCondition={(user) => user.isChargeAtWillEnabled}>
              <ShowWhen
                additionalCondition={(user) => user.isRegistrationLinkTokenAndPaymentsEnabled}
              >
                <NavLink to="/recurring_payments">Payments</NavLink>
                <NavLink to="/tokens">Tokens</NavLink>
              </ShowWhen>
              <NavLink to="/registration_links">
                Registration Links{' '}
                <span>
                  <i class="i i-info-circle" />
                  <Popover theme="dark">
                    <PopoverBody>Authorization links are now called Registration links</PopoverBody>
                  </Popover>
                </span>
              </NavLink>
              <ShowWhen additionalCondition={(user) => user.isRegistrationLinkBatchUploadEnabled}>
                <NavLink exact to="/subscriptions/batchuploads">
                  Batch Upload
                </NavLink>
              </ShowWhen>
            </ShowWhen>
          </header>

          <TestModeBanner />

          <content>
            <Switch>
              <ShowWhenRoute
                path="/subscriptions/batchuploads"
                component={HostedEmanadateBatches}
                additionalCondition={(user) => user.isChargeAtWillEnabled}
              />

              <ShowWhenRoute
                path="/subscriptions/settings"
                component={SubscriptionSettings}
                additionalCondition={(user) => !user.isChargeAtWillEnabled}
              />

              <ShowWhenRoute
                path="/subscriptions"
                component={SubscriptionsList}
                additionalCondition={(user) => !user.isChargeAtWillEnabled}
              />
              <ShowWhenRoute
                path="/plans"
                component={ClonedPlanList}
                additionalCondition={(user) => !user.isChargeAtWillEnabled}
              />

              <Route path="/registration_links" component={RegistrationLinksList} />

              <Route
                path="/recurring_payments"
                component={RecurringPayments}
                additionalCondition={(user) => user.isRegistrationLinkTokenAndPaymentsEnabled}
              />

              <Route
                path="/tokens"
                component={TokensList}
                additionalCondition={(user) => user.isRegistrationLinkTokenAndPaymentsEnabled}
              />
            </Switch>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

function ClonedPlanList(props) {
  return <PlansList docUrl="https://razorpay.com/docs/subscriptions/" {...props} />;
}

export default SubscriptionsController;
