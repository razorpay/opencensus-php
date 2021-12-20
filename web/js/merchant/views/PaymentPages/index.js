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

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ZapierLaunchBanner from 'merchant/components/Announcements/ZapierBanner/ZapierBanner';
import { getItem } from 'common/utils/localStorage';
import DashboardBanner from '../../../common/ui/DashboardBanner';

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
        <div className="banner-container">
          <DashboardBanner />
          <ShowWhen
            additionalCondition={(currentUser) =>
              currentUser.isPartOfZapierIntegrationExperiment &&
              !getItem(`zapier-integration-banner-${user.current}`)
            }
          >
            <ZapierLaunchBanner
              fromWhere="payment-pages"
              bannerKey={`zapier-integration-banner-${user.current}`}
            />
          </ShowWhen>
        </div>
        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}
          <header id="link-header">
            <NavLink exact to="/paymentpages">
              Payment Pages
            </NavLink>
          </header>

          <TestModeBanner />

          <content>
            <ErrorBoundary resetOnProps>
              <Switch>
                <Route path="/paymentpages" exact component={PaymentPagesList} />
              </Switch>
            </ErrorBoundary>
          </content>
        </tabbed-container>
      </>
    );
  }
}
