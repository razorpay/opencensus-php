/* eslint-disable react/no-unsafe */
import React from 'react';
import { connect } from 'react-redux';
import { NavLink, Outlet } from 'react-router-dom';

import { classList } from 'common/utils/rzp-utils';

import { RZPFeatures } from 'merchant/helpers/data';

import Popover, { PopoverBody } from 'common/ui/Popover';

import ShowWhen from 'merchant/components/ShowWhen';

import { fetchPlans } from 'merchant/reducers/plans';
import { fetchSubscriptions, getCheckoutInfo } from 'merchant/reducers/subscriptions';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import TestModeBanner from 'merchant/components/TestModeBanner';
import OnBoarding, {
  getIsAllowedResetSubscriptionBoarding,
} from 'merchant/views/Subscriptions/OnBoarding';
import QuickGuide, {
  getSubscriptionQuickGuideIsClosed,
} from 'merchant/views/Subscriptions/QuickGuide';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { PaperNachBanner, UpdatePaymentMethodBanner } from './components/banners/';
import { PAPER_NACH_CARD_BANNER_URL, UPDATE_PAYMENT_METHOD_URL } from './constants';
import rTracking from 'react-tracking';
import analytics from './analytics';
import './index.styl';
import DashboardBanner from 'common/ui/DashboardBanner';
import { withI18Service } from 'common/i18';
import { compose } from 'redux';

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

  UNSAFE_componentWillReceiveProps(nextProps) {
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

    console.log(location.pathname);

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
    const {
      subscriptionProductOnBoarding,
      user: userInfo,
      i18: { isConfigTagEnabled },
    } = this.props;
    const showPaperNachBanner = userInfo.methods?.nach ?? false;

    if (subscriptionProductOnBoarding.showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <div className={classList('Subscriptions-Container')}>
        <div className="banner-container">
          <DashboardBanner />
          {!userInfo.isChargeAtWillEnabled && (
            <UpdatePaymentMethodBanner url={UPDATE_PAYMENT_METHOD_URL} />
          )}
          {showPaperNachBanner && <PaperNachBanner url={PAPER_NACH_CARD_BANNER_URL} />}
        </div>

        <tabbed-container>
          {subscriptionProductOnBoarding.isQuickGuideOpen && <QuickGuide />}

          <header id="subscriptions-header" className="scrollable-tab-header">
            <ShowWhen additionalCondition={(user) => !user.isChargeAtWillEnabled}>
              <NavLink
                end
                to="/subscriptions"
                onClick={() => analytics.track('subscription.subscriptions.click')}
              >
                Subscriptions
              </NavLink>
              <NavLink to="/plans" onClick={() => analytics.track('subscription.plans.click')}>
                Plans
              </NavLink>
              <ShowWhen
                additionalCondition={(user) =>
                  !user.isChargeAtWillEnabled &&
                  !isConfigTagEnabled('subscriptions.subscription_settings')
                }
              >
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
                  <i className="i i-info-circle" />
                  <Popover theme="dark">
                    <PopoverBody>Authorization links are now called Registration links</PopoverBody>
                  </Popover>
                </span>
              </NavLink>
              <ShowWhen additionalCondition={(user) => user.isRegistrationLinkBatchUploadEnabled}>
                <NavLink
                  end
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
              <Outlet />
            </ErrorBoundary>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

export default compose(
  withI18Service,
  connect(
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
  ),
  rTracking(() => window.rzpQ.component('Subscriptions')),
)(SubscriptionsController);
