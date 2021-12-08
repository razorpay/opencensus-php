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
import Announcement from 'merchant/components/Announcements';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { CardsGoLiveBanner, PaperNachBanner } from './components/banners/';
import {
  CAW_CARDS_BANNER,
  SUBSCRIPTION_CARDS_BANNER,
  PAPER_NACH_CARD_BANNER_URL,
} from './constants';
import RTracking from 'react-tracking';
import analytics from './analytics';
import './index.styl';
import DashboardBanner from '../../../common/ui/DashboardBanner';

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
@RTracking(() => window.rzpQ.component('Subscriptions'))
class SubscriptionsController extends React.Component {
  componentDidMount() {
    this.initSubscriptions();
    this.fetchDataForOnboarding();
    this.props.getCheckoutInfo(this.props.user.current);
    analytics.init(this.props.tracking.trackEvent);
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
    const showPaperNachBanner = userInfo.methods?.nach ?? false;

    if (subscriptionProductOnBoarding.showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <div class={classList('Subscriptions-Container')}>
        <div className="banner-container">
          <DashboardBanner />
          {userInfo.isChargeAtWillEnabled && !showPaperNachBanner && (
            <Announcement
              title="UPI Mandate Update"
              canBeClosed={true}
              theme="primary"
              key={`as-presented-${userInfo.current}`}
              id="as-presnted-banner"
              message="You can now charge customers on UPI Autopay any time as per your business requirement and not just monthly basis"
            />
          )}
          {showPaperNachBanner && <PaperNachBanner url={PAPER_NACH_CARD_BANNER_URL} />}
          <CardsGoLiveBanner url={cardsGoLiveBannerUrl} />
        </div>

        <tabbed-container>
          {subscriptionProductOnBoarding.isQuickGuideOpen && <QuickGuide />}

          <header id="subscriptions-header" className="scrollable-tab-header">
            <ShowWhen additionalCondition={(user) => !user.isChargeAtWillEnabled}>
              <NavLink
                exact
                to="/subscriptions"
                onClick={() => analytics.track('subscription.subscriptions.click')}
              >
                Subscriptions
              </NavLink>
              <NavLink to="/plans" onClick={() => analytics.track('subscription.plans.click')}>
                Plans
              </NavLink>
              <ShowWhen additionalCondition={(user) => !user.isChargeAtWillEnabled}>
                <NavLink
                  to="/subscriptions/settings"
                  onClick={() => analytics.track('subscription.settings.click')}
                >
                  Settings
                </NavLink>
              </ShowWhen>
            </ShowWhen>

            <ShowWhen additionalCondition={(user) => user.isChargeAtWillEnabled}>
              <ShowWhen
                additionalCondition={(user) => user.isRegistrationLinkTokenAndPaymentsEnabled}
              >
                <NavLink
                  to="/recurring_payments"
                  onClick={() => analytics.track('subscription.recurring_payments.click')}
                >
                  Payments
                </NavLink>
                <NavLink to="/tokens" onClick={() => analytics.track('subscription.tokens.click')}>
                  Tokens
                </NavLink>
              </ShowWhen>
              <NavLink
                to="/registration_links"
                onClick={() => analytics.track('subscription.registration_links.click')}
              >
                Registration Links{' '}
                <span>
                  <i class="i i-info-circle" />
                  <Popover theme="dark">
                    <PopoverBody>Authorization links are now called Registration links</PopoverBody>
                  </Popover>
                </span>
              </NavLink>
              <ShowWhen additionalCondition={(user) => user.isRegistrationLinkBatchUploadEnabled}>
                <NavLink
                  exact
                  to="/subscriptions/batchuploads"
                  onClick={() => analytics.track('subscription.batchuploads.click')}
                >
                  Batch Upload
                </NavLink>
              </ShowWhen>
            </ShowWhen>
          </header>

          <TestModeBanner />

          <content>
            <ErrorBoundary resetOnProps>
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
            </ErrorBoundary>
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
