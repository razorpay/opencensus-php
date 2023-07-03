import { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import { getCurrentProductOnBoardingDetails } from 'merchant/reducers/onboarding';

import OnBoarding from './OnBoarding';
import QuickGuide from './QuickGuide';

import PaymentPagesList from 'merchant/views/PaymentPages/PaymentPages/List';
import ProductsCatalogList from 'merchant/views/PaymentPages/Products';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';

import { BATCH_PAYMENT_PAGES_BASE_URL } from './PaymentPages/constants';

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
        </div>
        {isQuickGuideOpen && <QuickGuide className="QuickGuide-v2" />}
        <ErrorBoundary resetOnProps>
          <Switch>
            <Route path="/paymentpages" exact component={PaymentPagesList} />
            {user.isPaymentPageStorefrontEnabled && (
              <Route path="/paymentpages/products" exact component={ProductsCatalogList} />
            )}
            {user.isPaymentPageFileUploadEnabled && (
              <Route
                path={BATCH_PAYMENT_PAGES_BASE_URL}
                exact
                render={(routeProps) => <PaymentPagesList {...routeProps} isBatchPaymentPages />}
              />
            )}
          </Switch>
        </ErrorBoundary>
      </>
    );
  }
}
