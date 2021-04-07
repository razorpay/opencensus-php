import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';

const WelcomeModal = ({ onActivate, onClose, tracking, isFestive, isOnboardingV2Enabled }) => {
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

export default RTracking(() => {
  window.rzpQ.component('WelcomeModal');
})(WelcomeModal);
