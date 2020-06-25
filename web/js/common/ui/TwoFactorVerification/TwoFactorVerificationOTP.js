import ModalHeader from 'common/ui/ModalHeader';
import OtpInput from 'common/new-ui/Input/OtpInput';
import { AsyncBtn } from 'common/new-ui/Button';

export default class TwoFactorVerificationOTP extends React.Component {
  state = {};

  updateOtpValue = otp => {
    this.otpValue = otp;
  };

  onCloseClick = () => {
    this.props.onClose && this.props.onClose();
  };

  onConfirm = () => {
    return this.props
      .onConfirm(this.otpValue)
      .then(() => {
        this.props.onClose();
        this.props.onSuccess();
      })
      .catch(() => {
        this.setState({ wrongOtp: true });
      });
  };

  render() {
    const { contactMobile } = this.props;
    return (
      <div>
        <ModalHeader
          title="2 Step Verification"
          onCloseClick={this.onCloseClick}
        />
        <div class="modal-body">
          <p class="m-b">
            The action you are trying to perform needs 2-step verification. An
            SMS with 6-digit OTP has been sent to {contactMobile}{' '}
          </p>

          <p class="m-t m-b">OTP will expire in 5 mins.</p>
          <OtpInput
            onComplete={this.updateOtpValue}
            onChange={this.updateOtpValue}
            wrong={this.state.wrongOtp}
          />
          <p class="m-t m-b">
            Didn’t receive an SMS?{' '}
            <AsyncBtn.Transparent
              pendingState="Sending OTP..."
              onClick={this.props.onResend}
              class="m-l"
              showLoader={false}
            >
              Resend
            </AsyncBtn.Transparent>
          </p>
          <div class="Modal__actions">
            <AsyncBtn.Primary
              pendingState="Verifying OTP..."
              type="submit"
              class="Button--full-width"
              onClick={this.onConfirm}
            >
              Confirm
            </AsyncBtn.Primary>
          </div>
        </div>
      </div>
    );
  }
}
