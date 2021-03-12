import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import {
  triggerTwoFactorVerificationOtp,
  verifyTwoFactorOtp,
} from 'merchant_common/reducers/twoFactor';

import TwoFactorVerificationOTP from './TwoFactorVerificationOTP';
import TwoFaVerificationContext from './TwoFactorVerificationContext';
import TwoFactorVerificationSetup from './TwoFactorVerificationSetup';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

@connect(
  (state) => ({
    twoFactorVerified: state.twoFactor.data.twoFactorVerified,
    user: state.session.user,
    modeOfApp: state.session.mode,
  }),
  {
    openModal,
    closeModal,
    verifyTwoFactorOtp,
  },
)
@RTracking(() => window.rzpQ.component('TwoFaVerificationContextProvider'))
export default class TwoFaVerificationContextProvider extends React.Component {
  onOtpConfirm = (data) => {
    return this.props.verifyTwoFactorOtp({
      otp: data.otp,
    });
  };

  onContactMobileUpdated = ({ onUserTwoFaVerified }) => () => {
    this.emitTwoFactorSetupSuccessEvent();
    // After contact mobile is updated
    // user is marked as two_fa_verified implicitly
    return onUserTwoFaVerified();
  };

  onUserTwoFaVerified = ({ onUserTwoFaVerified }) => () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().success('critical_actions.2fa_verification', {
        action: this.props.action,
      }),
    );

    return onUserTwoFaVerified();
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
  criticalFlow = ({
    onUserTwoFaVerified,
    onFlowTermination,
    modes = ['test', 'live'],
    onBankAccountUpdateReq = false,
  }) => {
    this.onCloseCallback = onFlowTermination;

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

    if (
      (user.isCriticalRouteExperimentEnabled || onBankAccountUpdateReq) &&
      modes.includes(modeOfApp)
    ) {
      if (!user.isTwoFactorSetupDone) {
        return this.completeTwoFactorVerificationSetup({
          onContactMobileUpdated: this.onContactMobileUpdated({
            onUserTwoFaVerified,
          }),
        });
      } else if (!twoFactorVerified || onBankAccountUpdateReq) {
        this.verifyUserViaTwoFactorOtp({ onUserTwoFaVerified });
      } else {
        onUserTwoFaVerified();
      }
    } else {
      this.emitTwoFaSkippedEvent();
      onUserTwoFaVerified();
    }
  };

  completeTwoFactorVerificationSetup = ({ onContactMobileUpdated }) => {
    this.props.openModal({
      size: 'medium',
      component: (
        <TwoFactorVerificationSetup onComplete={onContactMobileUpdated} onClose={this.onClose} />
      ),
    });
  };

  verifyUserViaTwoFactorOtp = ({ onUserTwoFaVerified }) => {
    const { user } = this.props;
    triggerTwoFactorVerificationOtp().then(() => {
      this.props.openModal({
        size: 'small',
        component: (
          <TwoFactorVerificationOTP
            onConfirm={this.onOtpConfirm}
            onResend={this.onOtpResend}
            onClose={this.onClose}
            onSuccess={this.onUserTwoFaVerified({ onUserTwoFaVerified })}
            onWrongOtp={this.emitWrongOtpEvent}
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
