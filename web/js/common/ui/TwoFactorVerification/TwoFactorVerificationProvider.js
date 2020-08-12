import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import {
  triggerTwoFactorVerificationOtp,
  verifyTwoFactorOtp,
} from 'merchant_common/reducers/twoFactor';
import { updateContactMobile, updateUser } from 'merchant_common/reducers/user';

import UpdateContactMobile from 'common/ui/UpdateContactMobile';

import TwoFactorVerificationOTP from './TwoFactorVerificationOTP';
import TwoFaVerificationContext from './TwoFactorVerificationContext';

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
    updateContactMobile,
    updateUser,
  }
)
@RTracking(() => window.rzpQ.component('TwoFaVerificationContextProvider'))
export default class TwoFaVerificationContextProvider extends React.Component {
  onOtpConfirm = (data) => {
    return this.props.verifyTwoFactorOtp(
      {
        otp: data.otp,
      },
      this.props.ajax
    );
  };

  onContactMobileSubmit = (data) => {
    return this.props.updateContactMobile(data, this.props.merchantFetch);
  };

  onContactMobileUpdated = ({ onUserTwoFaVerified }) => (userData) => {
    this.emitTwoFactorSetupSuccessEvent();
    this.props.updateUser(userData);
    // After contact mobile is updated
    // user is marked as two_fa_verified implicitly
    return onUserTwoFaVerified();
  };

  onUserTwoFaVerified = ({ onUserTwoFaVerified }) => () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().success('critical_actions.2fa_verification', {
        action: this.props.action,
      })
    );

    onUserTwoFaVerified();
  };

  onWrongOtp = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().failed('critical_actions.2fa_verification_wrong_otp', {
        action: this.props.action,
      })
    );
  };

  onOtpResend = () => {
    return triggerTwoFactorVerificationOtp(this.props.merchantFetch);
  };

  @RTracking((props) => {
    return props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('critical_action.2fa_verification', {
        action: props.action,
      })
    );
  })
  criticalFlow = ({ onUserTwoFaVerified, modes = ['test', 'live'] }) => {
    const { user, twoFactorVerified, modeOfApp } = this.props;

    if (user.isCriticalRouteExperimentEnabled && modes.includes(modeOfApp)) {
      if (!user.isTwoFactorSetupDone) {
        return this.updateAndVerifiyContactMobile({
          onContactMobileUpdated: this.onContactMobileUpdated({
            onUserTwoFaVerified,
          }),
        });
      } else if (!twoFactorVerified) {
        this.verifyUserViaTwoFactorOtp({ onUserTwoFaVerified });
      } else {
        onUserTwoFaVerified();
      }
    } else {
      this.emitTwoFaSkippedEvent();
      onUserTwoFaVerified();
    }
  };

  updateAndVerifiyContactMobile = ({ onContactMobileUpdated }) => {
    const { user } = this.props;
    this.props.openModal({
      size: 'small',
      component: (
        <UpdateContactMobile
          contactMobile={user.contact_mobile}
          onComplete={onContactMobileUpdated}
          onSubmit={this.onContactMobileSubmit}
          onOtpConfirm={this.onOtpConfirm}
        />
      ),
    });
  };

  verifyUserViaTwoFactorOtp = ({ onUserTwoFaVerified }) => {
    const { merchantFetch, user } = this.props;
    triggerTwoFactorVerificationOtp(merchantFetch).then(() => {
      this.props.openModal({
        size: 'small',
        component: (
          <TwoFactorVerificationOTP
            contactMobile={user.contact_mobile}
            onConfirm={this.onOtpConfirm}
            onResend={this.onOtpResend}
            onClose={this.props.closeModal}
            onSuccess={this.onUserTwoFaVerified({ onUserTwoFaVerified })}
            onWrongOtp={this.onWrongOtp}
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
      })
    );
  };

  emitTwoFactorSetupSuccessEvent = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().success('critical_actions.2fa_setup_success', {
        action: this.props.action,
      })
    );
  };
}
