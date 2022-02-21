import React, { Component } from 'react';
import { connect } from 'react-redux';

import HeaderAction from 'common/ui/HeaderAction';
import Alert from 'common/ui/Forms/Alert';

import Rewards from 'merchant/views/CheckoutRewards/Rewards';
import { Route, Switch, NavLink } from 'react-router-dom';
import RTracking from 'react-tracking';

import TestModeBanner from 'merchant/components/TestModeBanner';
import { RZPFeatures } from 'merchant/helpers/data';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import CheckoutRewardsAnnouncement from 'merchant/components/Announcements/CheckoutRewards';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import OnBoarding, { getIsRewardsEnabled, getIsAllowedResetRewardsOnBoarding } from './OnBoarding';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ShowWhen from 'merchant/components/ShowWhen';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import DashboardBanner from '../../../common/ui/DashboardBanner';

@connect(
  (state) => {
    return {
      rewards: state.rewards,
      user: state.session.user,
      rewardsProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.REWARDS),
    };
  },
  {
    handleProductQuickGuide,
  },
)
@RTracking(() => window.rzpQ.component('CheckoutRewardsIndex'))
export default class CheckoutRewardsIndex extends Component {
  componentDidMount() {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().success('CheckoutRewards_rendered'),
    );
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.rewards.loading !== this.props.rewards.loading) {
      this.initRewardsOnboarding(nextProps);
    }
  }

  initRewardsOnboarding = (props = this.props) => {
    if (props.rewardsProductOnBoarding.isTour) {
      return;
    }

    const data = {
      user: props.user,
      merchantId: props.user.current,
      rewards: props.rewards,
    };

    const isRewardsEnabled = getIsRewardsEnabled(data);

    let showOnboarding = !isRewardsEnabled;

    if (isRewardsEnabled) {
      showOnboarding = getIsAllowedResetRewardsOnBoarding(data.rewards);
    }

    const rewardsProductOnBoarding = {
      ...props.rewardsProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: false,
    };

    this.props.handleProductQuickGuide(rewardsProductOnBoarding);
  };

  documentationClicked = () => {
    analyticsTrack({
      objectName: 'Documentation',
      actionName: 'clicked',
      screen: 'Checkout Rewards',
      properties: {
        location: 'rewards',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  };

  render() {
    const { user } = this.props;
    const { showOnboarding } = this.props.rewardsProductOnBoarding;

    if ((!user.isRewardsPageEnabled || showOnboarding) && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <div className="checkout-rewards-main-container">
        <div className="banner-container">
          <DashboardBanner />
          <CheckoutRewardsAnnouncement userId={user.current} />
        </div>
        <tabbed-container>
          <header id="link-header">
            <NavLink exact to="/checkout-rewards">
              Checkout Rewards
            </NavLink>
          </header>

          <TestModeBanner />

          <ErrorBoundary resetOnProps>
            <Switch>
              <Route path="/checkout-rewards">
                <content>
                  <div className="content-wrapper">
                    <HeaderAction>
                      <div className="btn-toolbar pull-right">
                        <a
                          href="https://razorpay.com/checkout-rewards-merchant-terms/"
                          target="_blank"
                          className="btn btn-link"
                          rel="noreferrer noopener"
                        >
                          Merchant Terms
                        </a>

                        <ShowWhen additionalCondition={(currentUser) => !currentUser.isOrgAxis}>
                          <TakeATourButton feature={RZPFeatures.REWARDS} />
                        </ShowWhen>
                        <DocsLink
                          url="https://razorpay.com/docs/payment-gateway/checkout-rewards/"
                          onClick={this.documentationClicked}
                        />
                      </div>
                    </HeaderAction>
                    <Alert type={status.type} message={status.message} />
                    <Rewards {...this.props} />
                  </div>
                </content>
              </Route>
            </Switch>
          </ErrorBoundary>
        </tabbed-container>
      </div>
    );
  }
}
