import { connect } from 'react-redux';

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
  state => ({
    twoFactorVerified: state.twoFactor.data.twoFactorVerified,
    user: state.session.user,
  }),
  {
    openModal,
    closeModal,
    verifyTwoFactorOtp,
    updateContactMobile,
    updateUser,
  }
)
export default class TwoFaVerificationContextProvider extends React.Component {
  onOtpConfirm = data => {
    return this.props.verifyTwoFactorOtp(
      {
        otp: data.otp,
      },
      this.props.ajax
    );
  };

  onContactMobileSubmit = data => {
    return this.props.updateContactMobile(data, this.props.merchantFetch);
  };

  onContactMobileUpdated = ({ onUserTwoFaVerified }) => userData => {
    this.props.updateUser(userData);
    // After contact mobile is updated
    // user is marked as two_fa_verified implicitly
    return onUserTwoFaVerified();
  };

  onOtpResend = () => {
    return triggerTwoFactorVerificationOtp(this.props.merchantFetch);
  };

  criticalFlow = ({ onUserTwoFaVerified }) => {
    const { user, twoFactorVerified } = this.props;

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
            onSuccess={onUserTwoFaVerified}
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
}
