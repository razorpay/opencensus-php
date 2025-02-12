import React from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import UpdateContactMobile from 'common/ui/UpdateContactMobile';
import VerifyContactMobile from 'common/ui/VerifyContactMobile';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { checkPassword } from 'merchant/reducers/profile';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  triggerTwoFactorVerificationOtp,
  verifyTwoFactorOtp,
} from 'merchant_common/reducers/twoFactor';

import SetPasswordModal from './SetPasswordModal';
import TwoFaVerificationContext from './TwoFactorVerificationContext';
import TwoFactorVerificationOTP from './TwoFactorVerificationOTP';
import TwoFactorVerificationSetup from './TwoFactorVerificationSetup';

class TwoFaVerificationContextProvider extends React.Component {
  onOtpConfirm = (data) => {
    return this.props.verifyTwoFactorOtp({
      otp: data.otp,
    });
  };

  initiateVerifyOrUpdateMobile = (isNewAccountAndSettingsPage = false) => {
    const currentUser = this.props.currentUser;
    if (currentUser.contact_mobile) {
      this.props.openModal({
        size: 'small',
        component: (
          <VerifyContactMobile
            onComplete={this.onContactMobileUpdated}
            onClose={this.onClose}
            isNewAccountAndSettingsPage={isNewAccountAndSettingsPage}
          />
        ),
      });
    } else {
      this.props.openModal({
        size: 'small',
        component: (
          <UpdateContactMobile
            onComplete={this.onContactMobileUpdated}
            onClose={this.onClose}
            isNewAccountAndSettingsPage={isNewAccountAndSettingsPage}
          />
        ),
      });
    }
  };

  onContactMobileUpdated = () => {
    this.emitTwoFactorSetupSuccessEvent();
    // After contact mobile is updated
    // user is marked as two_fa_verified implicitly
    return this.onUserTwoFaVerifiedCallback();
  };
  onOtpResend = () => {
    return triggerTwoFactorVerificationOtp();
  };

  onClose = () => {
    if (this.onCloseCallback && typeof this.onCloseCallback === 'function') {
      this.onCloseCallback();
    }

    this.props.closeModal();
  };

  criticalFlow = async ({
    onUserTwoFaVerified,
    onFlowTermination,
    modes = ['test', 'live'],
    onBankAccountUpdateReq = false,
    onWrongOtpCallback = () => {},
    isNewAccountAndSettingsPage = false,
    enforceVerifyOtp = false,
  }) => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('critical_action.2fa_verification', {
        action: this.props.action,
      }),
    );
    this.onCloseCallback = onFlowTermination;
    this.onUserTwoFaVerifiedCallback = (...args) => {
      this.props.tracking.trackEvent(
        window.rzpQ.merchantActions().success('critical_actions.2fa_verification', {
          action: this.props.action,
        }),
      );
      return onUserTwoFaVerified(...args);
    };
    const { user, twoFactorVerified, modeOfApp } = this.props;

    if (onBankAccountUpdateReq) {
      analyticsTrack({
        objectName: '2fa setup popup',
        actionName: 'displayed',
        screen: 'my account',
        properties: {
          '2FaFlow': 'Bank Account update',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }

    let res;
    try {
      res = await this.props.checkPassword();
    } catch ({ errors }) {
      this.props.showNotification({
        type: 'error',
        message: errors,
      });
    }

    if (!res || !res.data || !('set_password' in res.data)) {
      return this.onCloseCallback();
    }
    const userHasPassword = res.data.set_password;
    if (modes.includes(modeOfApp)) {
      // 2fa mobile signup flow
      if (!userHasPassword && user.is2FAMobileSignupEnabled) {
        if (!twoFactorVerified || onBankAccountUpdateReq) {
          return this.completeTwoFactorVerificationSetup({
            onClickSetup: () =>
              this.verifyUserViaTwoFactorOtp({
                onSuccess: this.openSetPasswordModal,
                onWrongOtpCallback,
                isNewAccountAndSettingsPage,
              }),
          });
        } else {
          return this.openSetPasswordModal(isNewAccountAndSettingsPage);
        }
      }

      // normal flow
      if (!user.isTwoFactorSetupDone) {
        return this.completeTwoFactorVerificationSetup({
          onClickSetup: () => this.initiateVerifyOrUpdateMobile(isNewAccountAndSettingsPage),
        });
      } else if (!twoFactorVerified || onBankAccountUpdateReq || enforceVerifyOtp) {
        this.verifyUserViaTwoFactorOtp({
          onSuccess: this.onUserTwoFaVerifiedCallback,
          onWrongOtpCallback,
          isNewAccountAndSettingsPage,
        });
      } else {
        onUserTwoFaVerified();
      }
    } else {
      this.emitTwoFaSkippedEvent();
      onUserTwoFaVerified();
    }

    return '';
  };

  completeTwoFactorVerificationSetup = ({ onClickSetup }) => {
    this.props.openModal({
      size: 'medium',
      component: <TwoFactorVerificationSetup onClickSetup={onClickSetup} onClose={this.onClose} />,
    });
  };

  openSetPasswordModal = (isNewAccountAndSettingsPage) => {
    this.props.openModal({
      size: 'small',
      component: (
        <SetPasswordModal
          onClose={this.onClose}
          onComplete={() => this.onUserTwoFaVerifiedCallback({ skipVerifyPassword: true })}
          isNewAccountAndSettingsPage={isNewAccountAndSettingsPage}
        />
      ),
    });
  };

  verifyUserViaTwoFactorOtp = ({
    onSuccess,
    onWrongOtpCallback,
    isNewAccountAndSettingsPage = false,
  }) => {
    const { user } = this.props;

    triggerTwoFactorVerificationOtp().then(() => {
      this.props.openModal({
        size: 'small',
        component: (
          <TwoFactorVerificationOTP
            onConfirm={this.onOtpConfirm}
            onResend={this.onOtpResend}
            onClose={this.onClose}
            onSuccess={onSuccess}
            onWrongOtp={() => {
              this.emitWrongOtpEvent();
              onWrongOtpCallback();
            }}
            title="2-Step Verification"
            renderMessage={() => (
              <>
                <p className="m-b">
                  The action you are trying to perform needs 2-step verification. An SMS with
                  6-digit OTP has been sent to {user.user.contact_mobile}{' '}
                </p>
                <p className="m-t m-b">OTP will expire in 5 mins</p>
              </>
            )}
            isNewAccountAndSettingsPage={isNewAccountAndSettingsPage}
          />
        ),
      });
    });
  };

  render() {
    const { children } = this.props;

    const methods = {
      criticalFlow: this.criticalFlow,
    };

    return (
      <TwoFaVerificationContext.Provider value={methods}>
        {children}
      </TwoFaVerificationContext.Provider>
    );
  }

  emitTwoFaSkippedEvent = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().success('critical_actions.2fa_verification_skipped', {
        action: this.props.action,
      }),
    );
  };

  emitTwoFactorSetupSuccessEvent = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().success('critical_actions.2fa_setup_success', {
        action: this.props.action,
      }),
    );
  };

  emitWrongOtpEvent = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().failed('critical_actions.2fa_verification_wrong_otp', {
        action: this.props.action,
      }),
    );
  };
}

export default compose(
  rTracking({
    page: 'TwoFaVerificationContextProvider',
  }),
  connect(
    (state) => ({
      twoFactorVerified: state.twoFactor.data.twoFactorVerified,
      user: state.session.user,
      modeOfApp: state.session.mode,
      currentUser: state.session.user.user,
    }),
    {
      openModal,
      checkPassword,
      closeModal,
      verifyTwoFactorOtp,
      showNotification,
    },
  ),
)(TwoFaVerificationContextProvider);
