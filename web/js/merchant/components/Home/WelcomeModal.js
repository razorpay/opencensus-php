import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import rTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import * as LocalStorageService from 'common/utils/localStorage';

const RECOMMANDED_PRODUCT_LIST = [
  'payment_gateway',
  'payment_page',
  'payment_link',
  'payment_button',
  'smart_collect',
  'route',
  'subscriptions',
];

const WelcomeModal = ({
  onActivate,
  onClose,
  tracking,
  isFestive,
  isOnboardingV2Enabled,
  isProductRecommendationEnabled,
}) => {
  const getLandingProduct =
    LocalStorageService.getItem('merchant_landing_page') ||
    LocalStorageService.getItem('default_product_page');

  const isRecommendProduct =
    RECOMMANDED_PRODUCT_LIST.includes(getLandingProduct) && isProductRecommendationEnabled;

  const handleActivationClick = () => {
    onActivate();
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'Activate Account CTA Clicked',
      screen: 'home page',
      properties: {
        ...getCommonSegmentProperties(),
      },
    });
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('act.form_fill', {
        clickSource: 'First_Login_Popup',
      }),
    );
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('act.popup', {
        clickSource: 'activate',
        ...(isFestive && { ID: 'NOV20-PGFESTIVEMODAL' }),
      }),
    );
    tracking.trackEvent(
      window.rzpQ.onbr().success('login.first_login_modal', {
        action: 'Activate_Account',
      }),
    );
  };

  const handleTryOutClick = () => {
    onClose();
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'Try Dashboard CTA Clicked',
      screen: 'home page',
      properties: {
        ...getCommonSegmentProperties(),
      },
    });
    tracking.trackEvent(
      window.rzpQ.onbr().success('login.first_login_modal', {
        action: 'Try_Dashboard',
      }),
    );
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('act.popup', {
        clickSource: 'Go To Dashboard',
        ...(isFestive && { ID: 'NOV20-PGFESTIVEMODAL' }),
      }),
    );
  };

  useEffect(() => {
    if (isFestive)
      tracking.trackEvent(
        window.rzpQ.onbr().success('merchant_dashboard.display_welcomemodal', {
          ID: 'NOV20-PGFESTIVEMODAL',
        }),
      );
  }, []);

  return (
    <div className="welcome-modal-content">
      {isFestive ? (
        <React.Fragment>
          <h1 className="welcome-title">Congratulations on</h1>
          <h1 className="welcome-title welcome-subtitle">making the move to Razorpay.</h1>
          <p class="welcome-para">We're excited to have you &amp; even more excited to help</p>
          <p class="welcome-para">you grow your business this festive season.</p>
          <br />
          <p class="welcome-para">
            You can now{' '}
            <span className="gold-highlight">accept payments for ₹2,00,000 for free.</span>
          </p>
          <p class="welcome-para">Activate your account to continue.</p>
        </React.Fragment>
      ) : isRecommendProduct ? (
        <React.Fragment>
          <h4 style={{ fontSize: '16px' }}>Welcome to Razorpay</h4>
          <h1 className="welcome-title prd-title">
            You are just one step away from accepting payments
          </h1>
          <p className="product-desc">
            Activate your account and find the right product for your business needs
          </p>
          <div className="slideshow_wrapper">
            <div className="product-recommendation">
              <div className="payment-product">
                <img
                  src="https://cdn.razorpay.com/static/assets/product-recommendation/payment-geteway.svg"
                  className="prd-icon active"
                />
                <div className="active-product">
                  <div className="title">Payment Gateway</div>
                  <div className="subtitle">Accept payments through your website or app</div>
                </div>
              </div>
              <div className="payment-product">
                <img
                  src="https://cdn.razorpay.com/static/assets/product-recommendation/payment-page.svg"
                  className="prd-icon active"
                />
                <div className="active-product">
                  <div className="title">Payment Pages</div>
                  <div className="subtitle">Create no-code online pages to accept payments</div>
                </div>
              </div>
              <div className="payment-product">
                <img
                  src="https://cdn.razorpay.com/static/assets/product-recommendation/payment-button.svg"
                  className="prd-icon active"
                />
                <div className="active-product">
                  <div className="title">Payment Buttons</div>
                  <div className="subtitle">Add payment button directly on your website</div>
                </div>
              </div>
              <div className="payment-product">
                <img
                  src="https://cdn.razorpay.com/static/assets/product-recommendation/payment-link.svg"
                  className="prd-icon active"
                />
                <div className="active-product">
                  <div className="title">Payment Link</div>
                  <div className="subtitle">Accept payments through SMS, whatsapp, email</div>
                </div>
              </div>
            </div>
          </div>
        </React.Fragment>
      ) : (
        <React.Fragment>
          <h1 className="welcome-title">Welcome to your</h1>
          <h1 className="welcome-title welcome-subtitle">Razorpay Dashboard</h1>
          <p>
            You are just one step away from accepting payments from your customers. All we need is
            your basic details to get you started.
          </p>
          <br />
          <p>Activate your account to start accepting payments from customers.</p>
        </React.Fragment>
      )}
      <div className="welcome-modal-actions">
        <Link
          to={isOnboardingV2Enabled && isMobileDevice() ? '/onboarding/steps' : '/activation'}
          onClick={handleActivationClick}
          className="btn btn-primary"
        >
          Activate your account
        </Link>
        <span className="btn-link m-l cursor-pointer" onClick={handleTryOutClick}>
          Try out the Dashboard
        </span>
      </div>
    </div>
  );
};

export default rTracking(() => {
  window.rzpQ.component('WelcomeModal');
})(WelcomeModal);
