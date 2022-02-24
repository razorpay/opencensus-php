import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import TwoFactorVerificationOTP from 'common/ui/TwoFactorVerification/TwoFactorVerificationOTP';
import UpdateContactMobile from 'common/ui/UpdateContactMobile';

import {
  triggerOtpOnMobileForVerification,
  verifyContactMobile,
} from 'merchant_common/reducers/twoFactor';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateUser } from 'merchant_common/reducers/user';

@connect(
  (state) => ({
    contactMobile: state.session.user.user.contact_mobile,
  }),
  {
    openModal,
    closeModal,
    updateUser,
    showNotification,
    verifyContactMobile,
  },
)
export default class VerifyContactMobile extends React.Component {
  state = {};

  onCloseClick = () => {
    this.props.onClose && this.props.onClose();
    this.props.closeModal();
  };

  onOtpSubmit = ({ otp }) => {
    return this.props
      .verifyContactMobile({
        token: this.state.token,
        otp,
      })
      .then(() => {
        this.props.updateUser({ contact_mobile_verified: true });
      });
  };

  onChangeClick = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <UpdateContactMobile onComplete={this.props.onComplete} onClose={this.onCloseClick} />
      ),
    });
  };

  UNSAFE_componentWillMount() {
    this.triggerOtp();
  }

  triggerOtp = () => {
    return triggerOtpOnMobileForVerification()
      .then(({ data }) => {
        this.setState({
          token: data.token,
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    return (
      <TwoFactorVerificationOTP
        onConfirm={this.onOtpSubmit}
        onResend={this.triggerOtp}
        onSuccess={this.props.onComplete}
        onClose={this.onCloseClick}
        title="Verify your Mobile"
        renderMessage={() => (
          <>
            <p class="m-b">
              An SMS with 6-digit OTP has been sent to {this.props.contactMobile}&nbsp;
              <Button.Transparent onClick={this.onChangeClick}>Change</Button.Transparent>
            </p>

            <p class="m-t m-b">OTP will expire in 5 mins.</p>
          </>
        )}
      />
    );
  }
}
