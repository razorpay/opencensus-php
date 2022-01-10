import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import {
  triggerTwoFactorVerificationOtp,
  verifyTwoFactorOtp,
} from 'merchant_common/reducers/twoFactor';
import { checkPassword } from 'merchant/reducers/profile';
import TwoFactorVerificationOTP from './TwoFactorVerificationOTP';
import TwoFaVerificationContext from './TwoFactorVerificationContext';
import TwoFactorVerificationSetup from './TwoFactorVerificationSetup';
import SetPasswordModal from './SetPasswordModal';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { showNotification } from 'merchant_common/reducers/notifications';
import VerifyContactMobile from 'common/ui/VerifyContactMobile';
import UpdateContactMobile from 'common/ui/UpdateContactMobile';

@connect(
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
)
@RTracking(() => window.rzpQ.component('TwoFaVerificationContextProvider'))
export default class TwoFaVerificationContextProvider extends React.Component {
  onOtpConfirm = (data) => {
    return this.props.verifyTwoFactorOtp({
      otp: data.otp,
    });
  };

  initiateVerifyOrUpdateMobile = () => {
    const currentUser = this.props.currentUser;
    if (currentUser.contact_mobile) {
      this.props.openModal({
        size: 'small',
        component: (
          <VerifyContactMobile onComplete={this.onContactMobileUpdated} onClose={this.onClose} />
        ),
      });
    } else {
      this.props.openModal({
        size: 'small',
        component: (
          <UpdateContactMobile onComplete={this.onContactMobileUpdated} onClose={this.onClose} />
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

  @RTracking((props) => {
    return props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('critical_action.2fa_verification', {
        action: props.action,
      }),
    );
  })
  criticalFlow = async ({
    onUserTwoFaVerified,
    onFlowTermination,
    modes = ['test', 'live'],
    onBankAccountUpdateReq = false,
    onWrongOtpCallback = () => {},
  }) => {
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

    const res = await this.props.checkPassword().catch((err) => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });
    if (!res || !res.data || !('set_password' in res.data)) {
      return this.onCloseCallback();
    }
    const userHasPassword = res.data.set_password;
    if (
      (user.isCriticalRouteExperimentEnabled || onBankAccountUpdateReq) &&
      modes.includes(modeOfApp)
    ) {
      // 2fa mobile signup flow
      if (!userHasPassword && user.is2FAMobileSignupEnabled) {
        if (!twoFactorVerified || onBankAccountUpdateReq) {
          return this.completeTwoFactorVerificationSetup({
            onClickSetup: () =>
              this.verifyUserViaTwoFactorOtp({
                onSuccess: this.openSetPasswordModal,
                onWrongOtpCallback,
              }),
          });
        } else {
          return this.openSetPasswordModal();
        }
      }

      // normal flow
      if (!user.isTwoFactorSetupDone) {
        return this.completeTwoFactorVerificationSetup({
          onClickSetup: this.initiateVerifyOrUpdateMobile,
        });
      } else if (!twoFactorVerified || onBankAccountUpdateReq) {
        this.verifyUserViaTwoFactorOtp({
          onSuccess: this.onUserTwoFaVerifiedCallback,
          onWrongOtpCallback,
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

  openSetPasswordModal = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <SetPasswordModal
          onClose={this.onClose}
          onComplete={() => this.onUserTwoFaVerifiedCallback({ skipVerifyPassword: true })}
        />
      ),
    });
  };

  verifyUserViaTwoFactorOtp = ({ onSuccess, onWrongOtpCallback }) => {
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
                <p class="m-b">
                  The action you are trying to perform needs 2-step verification. An SMS with
                  6-digit OTP has been sent to {user.user.contact_mobile}{' '}
                </p>
                <p class="m-t m-b">OTP will expire in 5 mins</p>
              </>
            )}
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
