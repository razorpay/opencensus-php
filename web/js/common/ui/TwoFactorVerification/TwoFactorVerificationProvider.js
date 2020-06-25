import { connect } from 'react-redux';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import {
  triggerTwoFactorVerificationOtp,
  verifyTwoFactorOtp,
} from 'merchant_common/reducers/twoFactor';

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
  }
)
export default class TwoFaVerificationContextProvider extends React.Component {
  onConfirm = otp => {
    return this.props.verifyTwoFactorOtp({ otp }, this.props.ajax);
  };

  criticalFlow = ({ onUserTwoFaVerified }) => {
    const { user, twoFactorVerified, merchantFetch } = this.props;

    if (!twoFactorVerified) {
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
    } else {
      onUserTwoFaVerified();
    }
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
