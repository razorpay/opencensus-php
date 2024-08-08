/* eslint-disable no-shadow */
import React from 'react';
import { connect } from 'react-redux';
import { Route, Routes } from 'react-router-dom';

import { withI18Service } from 'common/i18';
import { RZPFeatures } from 'merchant/helpers/data';

import PaymentLinksList from 'merchant/views/PaymentLinks/PaymentLinks/List';
import BatchUploadList from 'merchant/views/PaymentLinks/BatchUpload/List';
import SwitchToPaymentLinksV2 from 'merchant/components/Announcements/SwitchToPaymentLinksV2';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import { isMobileDevice } from 'merchant/components/Home/data';
import { MobilePopup, UseAppFooter } from 'merchant/components/MobilePopup';
import { getCommonAnalyticsProperties, getMobileOperatingSystem } from 'common/utils/rzp-utils';
import { getItem } from 'common/utils/localStorage';
import { DocLink } from 'merchant/components/DocsLink';
import OnBoarding, {
  getIsPaymentLinksEnabled,
  getIsAllowedResetPaymentLinksOnBoarding,
} from './OnBoarding';

import QuickGuide, { getPaymentLinksQuickGuideIsClosed } from './QuickGuide';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import DashboardBanner from 'common/ui/DashboardBanner';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';

const MonetizationChargesBanner = lazy(() =>
  import(
    /* webpackChunkName: 'PaymentLinksMonetizationChargesBanner' */ 'merchant/components/Announcements/MonetizationCharges'
  ),
);

let url = 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app';
if (getMobileOperatingSystem() == 'iOS') {
  url = 'https://apps.apple.com/in/app/razorpay-payments-dashboard/id1497250144';
}

// eslint-disable-next-line react/no-unsafe
@connect(
  (state) => {
    return {
      user: state.session.user,
      paymentlinks: state.paymentlinks,
      paymentLinksProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PL),
    };
  },
  { handleProductQuickGuide, openModal, closeModal },
)
class PaymentLinksContainer extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      showPopup: false,
      showFooter: false,
      url,
    };
  }

  componentDidMount() {
    const mwebPopupLS = !!getItem('payment links_mweb_popup'); // Check if popup is already shown to user once.
    const mwebPopupSS = !!window.sessionStorage.getItem('transactions_mweb_popup'); // Check if popup is shown in session on another scrren.
    let showPopup = isMobileDevice();
    if (mwebPopupLS) {
      showPopup = false;
    } else if (mwebPopupSS) {
      showPopup = false;
    }
    // eslint-disable-next-line react/no-did-mount-set-state
    this.setState({ showPopup });
    analyticsTrack({
      objectName: 'NC App Page Dashboard',
      actionName: 'Render Success',
      screen: 'Payment Links',
      toCleverTap: true,
      properties: {
        event_name: 'nc_app_.render.success',
        source: getDeviceSource(),
        page: 'Payment Links',
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

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.paymentlinks.loading !== this.props.paymentlinks.loading) {
      this.initPaymentLinksOnboarding(nextProps);
    }
  }

  componentWillUnmount() {
    const { paymentLinksProductOnBoarding } = this.props;

    if (paymentLinksProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...paymentLinksProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: false,
        isTour: false,
      });
    }
  }

  initPaymentLinksOnboarding = (props = this.props) => {
    if (props.paymentLinksProductOnBoarding.isTour) {
      return;
    }

    const data = {
      user: props.user,
      merchantId: props.user.current,
      paymentlinks: props.paymentlinks,
    };

    const isPaymentLinksEnabled = getIsPaymentLinksEnabled(data);

    let showOnboarding = !isPaymentLinksEnabled;

    if (isPaymentLinksEnabled) {
      showOnboarding = getIsAllowedResetPaymentLinksOnBoarding(data.paymentlinks);
    }

    const paymentLinksProductOnBoarding = {
      ...props.paymentLinksProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getPaymentLinksQuickGuideIsClosed(props),
    };

    this.props.handleProductQuickGuide(paymentLinksProductOnBoarding);
  };

  closePopup = () => {
    this.setState({ showFooter: true, showPopup: false });
  };

  closeFooter = () => {
    this.setState({ showFooter: false, showPopup: false });
  };

  showMobilePopup = () => {
    const { url } = this.state;

    setTimeout(
      () =>
        this.props.openModal({
          size: 'xlarge',
          component: (
            <MobilePopup
              title="Send Payment Links Faster with the Mobile App"
              subtitle="Switch to the app for better ways to track payments, issue refunds, and more."
              screen="Payment Links"
              url={url}
              notNowClicked={this.closePopup}
              closeModal={this.props.closeModal}
            />
          ),
          className: 'mobile-app-popup',
        }),
      1000,
    );
  };

  render() {
    const {
      user,
      i18: { isConfigTagEnabled },
    } = this.props;
    const { isQuickGuideOpen, showOnboarding } = this.props.paymentLinksProductOnBoarding;

    const { activation_status, role } = window.rzp_user;

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <React.Fragment>
        <div className="banner-container">
          <DashboardBanner />
          <ShowWhen additionalCondition={(user) => user.missedOrderPLBanner}>
            <AnnouncementBanner
              title="Introducing Retry Links"
              theme="primary"
              card_id="missed-order-payment-links"
              canBeClosed={true}
              bannerKey={`missed-order-payment-links-${user.current}`}
            >
              Convert customers who drop-off because of a failed payment. Available from Feb 9,2022{' '}
              <DocLink
                class="btn btn-link"
                href="https://razorpay.com/docs/payments/payment-links/announcements/retry-link/"
                target="_blank"
                rel="noopener noreferrer"
              >
                Know more
              </DocLink>
            </AnnouncementBanner>
          </ShowWhen>

          <ShowWhen additionalCondition={(user) => user.isPLSwitchEnabled}>
            <SwitchToPaymentLinksV2 source="payment-links-list" />
          </ShowWhen>
          <SuspenseWithLoader>
            <MonetizationChargesBanner userId={user?.current} screen="paymentLinks" user={user} />
          </SuspenseWithLoader>
        </div>

        {isQuickGuideOpen && <QuickGuide className="QuickGuide-v2" />}

        <ErrorBoundary resetOnProps>
          <Routes>
            <Route
              path="batchuploads/*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('payment_links_batch_uploads') &&
                    user.isPLBatchUploadEnabled &&
                    (!user.isSellerAppRole || user.isPaymentLinkBatchEnabledForSellerAppRole)
                  }
                >
                  <BatchUploadList />
                </RouteGuard>
              }
            />

            <Route
              index
              element={
                <RouteGuard>
                  <PaymentLinksList />
                </RouteGuard>
              }
            />
          </Routes>
        </ErrorBoundary>

        {this.state.showPopup &&
          !isConfigTagEnabled('account.mobile_app') &&
          activation_status === 'activated' &&
          (role === 'owner' || role === 'admin' || role === 'manager' || role === 'operations') &&
          this.showMobilePopup()}
        {this.state.showFooter &&
          activation_status === 'activated' &&
          (role === 'owner' || role === 'admin' || role === 'manager' || role === 'operations') && (
            <UseAppFooter
              screen="Payment Links"
              url={this.state.url}
              closeFooter={this.closeFooter}
            />
          )}
      </React.Fragment>
    );
  }
}

export default withI18Service(PaymentLinksContainer);
