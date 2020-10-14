import { connect } from 'react-redux';

import ModalHeader from 'common/ui/ModalHeader';
import OtpInput from 'common/new-ui/Input/OtpInput';
import { AsyncBtn } from 'common/new-ui/Button';

import { closeModal } from 'merchant_common/reducers/modals';

@connect(null, { closeModal })
export default class TwoFactorVerificationOTP extends React.Component {
  static defaultProps = {
    onWrongOtp: () => {},
    onSuccess: () => {},
  };

  state = {};

  updateOtpValue = (otp) => {
    this.otpValue = otp;
  };

  onCloseClick = () => {
    this.props.onClose && this.props.onClose();
    this.props.closeModal();
  };

  onConfirm = () => {
    return this.props
      .onConfirm({
        otp: this.otpValue,
      })
      .then(() => {
        this.onCloseClick();
        this.props.onSuccess();
      })
      .catch(({ errors }) => {
        this.props.onWrongOtp({ errors });
        this.setState({ wrongOtp: true });
      });
  };

  render() {
    return (
      <div>
        <ModalHeader title={this.props.title} onCloseClick={this.onCloseClick} />
        <div class="modal-body">
          {this.props.renderMessage()}
          <OtpInput
            onComplete={this.updateOtpValue}
            onChange={this.updateOtpValue}
            wrong={this.state.wrongOtp}
          />
          <p class="m-t m-b">
            Didn’t receive an OTP?{' '}
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
