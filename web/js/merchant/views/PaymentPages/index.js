import { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import { getCurrentProductOnBoardingDetails } from 'merchant/reducers/onboarding';

import OnBoarding from './OnBoarding';
import QuickGuide from './QuickGuide';

import PaymentPagesList from 'merchant/views/PaymentPages/PaymentPages/List';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import CustomDomainBanner from 'merchant/components/Announcements/PaymentPageCustomDomain';
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
          <CustomDomainBanner userId={user.current} email={user.email} />
        </div>

        {isQuickGuideOpen && <QuickGuide className="QuickGuide-v2" />}

        <ErrorBoundary resetOnProps>
          <Switch>
            <Route path="/paymentpages" exact component={PaymentPagesList} />
          </Switch>
        </ErrorBoundary>
      </>
    );
  }
}
