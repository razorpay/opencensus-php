import { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { AsyncBtn } from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import ModalHeader from 'common/ui/ModalHeader';
import { isPhone } from 'common/utils/validators';
import { OtpInput } from 'merchant/components/OtpInput';
import { updateSelfContact } from 'merchant/reducers/team';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { verifyTwoFactorOtp } from 'merchant_common/reducers/twoFactor';

class UpdateSelfContactMobile extends Component {
  state = {
    values: {
      contact_mobile: this.props.contact_mobile,
    },
  };

  onSubmit = () => {
    return this.props
      .updateSelfContact(this.state.values)
      .then(() => {
        this.props.openModal({
          size: 'small',
          component: (
            <VerifyOtp
              contactMobile={this.state.values.contact_mobile}
              onSuccess={this.props.onSuccess}
              onClose={this.props.onClose}
            />
          ),
        });
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];

        this.props.showNotification({
          type: 'error',
          message: error,
        });
      });
  };

  onChange = ({ target }) => {
    this.setState((currentState) => ({
      values: {
        ...currentState.values,
        [target.name]: target.value,
      },
    }));
  };

  isFormValid = () => {
    const { values } = this.state;
    if (values.contact_mobile && isPhone(values.contact_mobile)) {
      return true;
    }
    return false;
  };

  onCloseClick = () => {
    this.props.onClose && this.props.onClose();
    this.props.closeModal();
  };

  render() {
    return (
      <div className="2fa-modal">
        <ModalHeader title="Setting up 2-step verification" onCloseClick={this.onCloseClick} />
        <div className="modal-body">
          <p>
            Let's setup a mobile number where you will receive an SMS with OTP everytime you log in.
          </p>
          <Form onChange={this.onChange}>
            <Input
              name="contact_mobile"
              type="text"
              className="Input--vTop is-focused"
              autoFocus
              label="Enter your phone number"
              defaultValue={this.props.contact_mobile}
              required
            />

            <AsyncBtn.Primary
              pendingState="Updating"
              type="submit"
              className="Button--full-width"
              onClick={this.onSubmit}
              disabled={!this.isFormValid()}
            >
              Update
            </AsyncBtn.Primary>
          </Form>
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      contact_mobile: (state.session.user.user || {}).contact_mobile,
    }),
    {
      closeModal,
      openModal,
      updateSelfContact,
      showNotification,
    },
  ),
)(UpdateSelfContactMobile);

class VerifyOtpComponent extends Component {
  state = {};

  onConfirm = () => {
    return this.props
      .verifyTwoFactorOtp({
        otp: this.otpValue,
      })
      .then((response) => {
        if (response.success) {
          this.props.onSuccess && this.props.onSuccess();
        }
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];
        if (error === 'Verification failed because of incorrect OTP.') {
          this.setState({
            wrongOtp: true,
          });
        } else {
          this.props.showNotification({
            type: 'error',
            message: error,
          });
        }
      });
  };

  onResend = () => {
    return this.props.updateSelfContact({
      contact_mobile: this.props.contactMobile,
    });
  };

  updateOtpValue = (otp) => {
    this.otpValue = otp;
  };

  onChangeMobileNumber = () => {
    this.props.openModal({
      size: 'small',
      component: <UpdateSelfContactMobile onSuccess={this.props.onSuccess} />,
    });
  };

  onCloseClick = () => {
    this.props.onClose && this.props.onClose();
    this.props.closeModal();
  };

  render() {
    const { contactMobile } = this.props;
    return (
      <div>
        <ModalHeader title="Verify Mobile Number" onCloseClick={this.onCloseClick} />
        <div className="modal-body">
          <p className="m-b">
            An SMS with 6-digit OTP has been sent to {contactMobile}{' '}
            <a onClick={this.onChangeMobileNumber}>[Change]</a>
          </p>

          <p className="m-t m-b">OTP will expire in 5mins.</p>

          <OtpInput
            onComplete={this.updateOtpValue}
            onChange={this.updateOtpValue}
            wrong={this.state.wrongOtp}
          />
          <p className="m-t m-b">
            Didn’t receive an SMS?{' '}
            <AsyncBtn.Transparent
              pendingState="Sending OTP..."
              onClick={this.onResend}
              className="m-l"
              showLoader={false}
            >
              Send OTP
            </AsyncBtn.Transparent>
          </p>
          <div className="Modal__actions">
            <AsyncBtn.Primary
              pendingState="Updating"
              type="submit"
              className="Button--full-width"
              onClick={this.onConfirm}
            >
              Update
            </AsyncBtn.Primary>
          </div>
        </div>
      </div>
    );
  }
}
const VerifyOtp = connect(null, {
  verifyTwoFactorOtp,
  showNotification,
  openModal,
  closeModal,
})(VerifyOtpComponent);
