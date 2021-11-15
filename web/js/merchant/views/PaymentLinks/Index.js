/* eslint-disable no-shadow */
import React from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import PaymentLinksList from 'merchant/views/PaymentLinks/PaymentLinks/List';
import BatchUploadList from 'merchant/views/PaymentLinks/BatchUpload/List';
import PaymentButtonLaunchBanner from 'merchant/components/Announcements/PaymentButtonLaunch';
import SwitchToPaymentLinksV2 from 'merchant/components/Announcements/SwitchToPaymentLinksV2';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import { isMobileDevice } from 'merchant/components/Home/data';
import { MobilePopup, UseAppFooter } from 'merchant/components/MobilePopup';
import { getMobileOperatingSystem } from 'common/utils/rzp-utils';
import { getItem, setItem } from 'common/utils/localStorage';

import OnBoarding, {
  getIsPaymentLinksEnabled,
  getIsAllowedResetPaymentLinksOnBoarding,
} from './OnBoarding';

import QuickGuide, { getPaymentLinksQuickGuideIsClosed } from './QuickGuide';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

let url = 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app';
if (getMobileOperatingSystem() == 'iOS') {
  url = 'https://apps.apple.com/in/app/razorpay-payments-dashboard/id1497250144';
}

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
  }

  componentWillReceiveProps(nextProps) {
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
    const { user } = this.props;
    const { isQuickGuideOpen, showOnboarding } = this.props.paymentLinksProductOnBoarding;

    const { activation_status, role } = window.rzp_user;

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }
    return (
      <React.Fragment>
        <div className="banner-container">
          <ShowWhen
            additionalCondition={(user) =>
              user.isPartOfAiSensyBannerExperiment &&
              !getItem(`payment-links-on-whatsapp-banner-${user.current}`)
            }
          >
            <AnnouncementBanner
              title="Payment links on Whatsapp"
              theme="primary"
              card_id="payment-links-on-whatsapp-banner"
              canBeClosed={true}
              onClose={() => {
                setItem(`payment-links-on-whatsapp-banner-${user.current}`, 1);
              }}
            >
              Now automatically send payment links from your Whatsapp handle with our partner app -
              AiSensy{' '}
              <a
                href="https://m.aisensy.com/razorpay-whatsapp-integration/"
                target="_blank"
                rel="noopener noreferrer"
                className="Button--primary Button scheduled-btn-act btn-border"
              >
                Get Started
              </a>{' '}
              <a
                href="https://www.youtube.com/watch?v=cuNLNF6Pi8I"
                target="_blank"
                rel="noopener noreferrer"
              >
                Watch video
              </a>
            </AnnouncementBanner>
          </ShowWhen>
          <ShowWhen
            additionalCondition={(user) =>
              !user.isPLSwitchEnabled && !user.isPartOfAiSensyBannerExperiment
            }
          >
            <PaymentButtonLaunchBanner productName="PaymentLinks" />
          </ShowWhen>

          <ShowWhen additionalCondition={(user) => user.isPLSwitchEnabled}>
            <SwitchToPaymentLinksV2 source="payment-links-list" />
          </ShowWhen>
        </div>

        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="link-header">
            <NavLink exact to="/paymentlinks">
              Payment Links
            </NavLink>
            <ShowWhen
              additionalCondition={(user) =>
                user.isAllowedView('payment_links_batch_uploads') &&
                user.isPLBatchUploadEnabled &&
                (!user.isSellerAppRole || user.isPaymentLinkBatchEnabledForSellerAppRole)
              }
            >
              <NavLink exact to="/paymentlinks/batchuploads">
                Batch Uploads
              </NavLink>
            </ShowWhen>
          </header>

          <TestModeBanner />

          <content>
            <ErrorBoundary resetOnProps>
              <Switch>
                <ShowWhenRoute
                  path="/paymentlinks/batchuploads"
                  component={BatchUploadList}
                  additionalCondition={(user) =>
                    user.isAllowedView('payment_links_batch_uploads') &&
                    user.isPLBatchUploadEnabled &&
                    (!user.isSellerAppRole || user.isPaymentLinkBatchEnabledForSellerAppRole)
                  }
                />
                <Route path="/paymentlinks" component={PaymentLinksList} />
              </Switch>
            </ErrorBoundary>
          </content>

          {this.state.showPopup &&
            activation_status === 'activated' &&
            (role === 'owner' || role === 'admin' || role === 'manager' || role === 'operations') &&
            this.showMobilePopup()}
        </tabbed-container>
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

export default PaymentLinksContainer;
