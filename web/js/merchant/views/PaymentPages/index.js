import { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Routes } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import DashboardBanner from 'common/ui/DashboardBanner';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { RZPFeatures } from 'merchant/helpers/data';
import { getCurrentProductOnBoardingDetails } from 'merchant/reducers/onboarding';
import lazy from 'merchant/routes/LazyLoader';
import PaymentPagesList from 'merchant/views/PaymentPages/PaymentPages/List';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';
import ProductsCatalogList from 'merchant/views/PaymentPages/Products';

import OnBoarding from './OnBoarding';
import QuickGuide from './QuickGuide';

const MonetizationChargesBanner = lazy(() =>
  import(
    /* webpackChunkName: 'PaymentPagesMonetizationChargesBanner' */ 'merchant/components/Announcements/MonetizationCharges'
  ),
);

class PaymentPagesContainer extends Component {
  componentDidMount() {
    analyticsTrack({
      objectName: 'NC App Page Dashboard',
      actionName: 'Render Success',
      screen: 'Payment Pages',
      toCleverTap: true,
      properties: {
        event_name: 'nc_app_.render.success',
        source: getDeviceSource(),
        page: 'Payment Pages',
        email_id: this.props?.user?.email,
        url: window.location.href,
        browser: window.razorpayAnalytics?.utils?.getBrowserDetails(),
        activation_status: this.props?.user?.activation_status,
        device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
        exp_name: 'NoCode Monetization',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }

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
          <SuspenseWithLoader>
            <MonetizationChargesBanner
              userId={user?.current}
              screen="paymentAndStorefrontPages"
              user={user}
            />
          </SuspenseWithLoader>
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

export default connect((state) => {
  return {
    paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PP),
    user: state.session.user,
  };
})(PaymentPagesContainer);
