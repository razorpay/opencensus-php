import { connect } from 'react-redux';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import {
  triggerTwoFactorVerificationOtp,
  verifyTwoFactorOtp,
} from 'merchant_common/reducers/twoFactor';
import { updateContactMobile } from 'merchant_common/reducers/user';

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
  }
)
export default class TwoFaVerificationContextProvider extends React.Component {
  onConfirm = data => {
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

  criticalFlow = ({ onUserTwoFaVerified }) => {
    const { user, twoFactorVerified } = this.props;

    if (!user.isTwoFactorSetupDone) {
      this.updateAndVerifiyContactMobile({
        onContactMobileUpdated: () => {
          // TODO: After contact mobile updated
          // user will be marked as 2FA verified
          // Once backend supports above change,
          // user need not do OTP veriification again
          this.verifyUserViaTwoFactorOtp({ onUserTwoFaVerified });
        },
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
          onSuccess={onContactMobileUpdated}
          onSubmit={this.onContactMobileSubmit}
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
            onConfirm={this.onConfirm}
            onResend={this.props.triggerTwoFaVerificationOtp}
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
