import { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Routes } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import { getCurrentProductOnBoardingDetails } from 'merchant/reducers/onboarding';

import OnBoarding from './OnBoarding';
import QuickGuide from './QuickGuide';

import PaymentPagesList from 'merchant/views/PaymentPages/PaymentPages/List';
import ProductsCatalogList from 'merchant/views/PaymentPages/Products';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';

import { RouteGuard } from 'merchant/components/ShowWhen';

import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';

@connect((state) => {
  return {
    paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PP),
    user: state.session.user,
  };
})
export default class PaymentPagesContainer extends Component {
  render() {
    const { user, location } = this.props;
    const { isQuickGuideOpen, showOnboarding } = this.props.paymentPageProductOnBoarding;
    const isBatchPaymentPages = location?.pathname === BATCH_PAYMENT_PAGES_BASE_URL;

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <>
        <div className="banner-container">
          <DashboardBanner />
        </div>
        {isQuickGuideOpen && (
          <QuickGuide className="QuickGuide-v2" isBatchPaymentPages={isBatchPaymentPages} />
        )}
        <ErrorBoundary resetOnProps>
          <Routes>
            <Route
              index
              element={
                <RouteGuard>
                  <PaymentPagesList />
                </RouteGuard>
              }
            />
            {user.isPaymentPageStorefrontEnabled && (
              <Route
                path="products"
                element={
                  <RouteGuard>
                    <ProductsCatalogList />
                  </RouteGuard>
                }
              />
            )}
            {user.isPaymentPageFileUploadEnabled && (
              <Route
                path="batchpaymentpages"
                element={
                  <RouteGuard>
                    <PaymentPagesList isBatchPaymentPages />
                  </RouteGuard>
                }
              />
            )}
          </Routes>
        </ErrorBoundary>
      </>
    );
  }
}
