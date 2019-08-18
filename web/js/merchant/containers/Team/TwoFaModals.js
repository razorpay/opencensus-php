import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';

import ModalHeader from 'rzp/ui/ModalHeader';
import { showNotification } from 'rzp/modules/notifications';

import { OtpInput } from 'merchant/components/OtpInput';
import { Field, reduxForm } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import { required, phone } from 'rzp/utils/validators';

@connect(null, { showNotification })
class VerifyOtp extends Component {
  state = {};
  onConfirm = () => {
    return this.props
      .updateContactMobile({
        contact_mobile: this.props.contactMobile,
        otp: this.otpValue,
      })
      .then(response => {
        if (response.success) {
          this.props.onSuccessfullVerfication();
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
    return this.props.updateContactMobile({
      contact_mobile: this.props.contactMobile,
    });
  };

  updateOtpValue = otp => {
    this.otpValue = otp;
  };

  render() {
    const { contactMobile, closeModal, onChangeMobileNumber } = this.props;

    return (
      <div>
        <ModalHeader title="Verify Mobile Number" onCloseClick={closeModal} />
        <div class="modal-body">
          <p>
            An SMS with 6-digit OTP has been sent to {contactMobile}{' '}
            <a onClick={onChangeMobileNumber}>[Change]</a>
          </p>
          <p>OTP will expire in 5mins.</p>

          <OtpInput
            onComplete={this.updateOtpValue}
            onChange={this.updateOtpValue}
            wrong={this.state.wrongOtp}
          />
          <p>
            Didn’t receive an SMS?
            <AsyncButton
              class="btn btn-link"
              text="Send OTP"
              pendingText="Sending OTP..."
              onClick={this.onResend}
            />
          </p>
          <div class="Modal__actions">
            <AsyncButton
              class="btn btn-primary btn-block"
              onClick={this.onConfirm}
              text="Confirm"
              pendingText="Sending OTP..."
            />
            Confirm
          </div>
        </div>
      </div>
    );
  }
}

@connect(
  state => ({
    initialValues: {
      contact_mobile: (state.session.user.user || {}).contact_mobile,
    },
  }),
  { showNotification }
)
@reduxForm({
  form: 'askPhone',
})
class AskMobileNumber extends Component {
  sendOtp = data => {
    return (
      this.props
        .onConfirm(data)
        // there will be no then since it will fail from api, since we've not sent OTP
        .catch(({ errors }) => {
          const error = (errors || [])[0];

          if (error === 'OTP is required') {
            this.props.onOTPRequired(data.contact_mobile);
          } else {
            this.props.showNotification({
              type: 'error',
              message: error,
            });
          }
        })
    );
  };
  render() {
    const { closeModal, handleSubmit } = this.props;
    return (
      <div class="2fa-modal">
        <ModalHeader
          title="Setting up 2-step verification"
          onCloseClick={closeModal}
        />
        <div class="modal-body">
          <p>
            Let's setup a mobile number where you will receive an SMS with OTP
            everytime you log in.
          </p>
          <form style={{ marginBottom: '35px' }}>
            <div class="form-group">
              <label>Enter your phone number </label>
              <Field
                name="contact_mobile"
                component={InputField}
                class="form-control"
                placeholder="Phone Number"
                validate={[required(), phone('Invalid Mobile')]}
              />
            </div>
            <div class="form-group">
              <AsyncButton
                class="btn btn-primary btn-block"
                text="Send OTP"
                pendingText="Sending OTP..."
                onClick={handleSubmit(this.sendOtp)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

@connect(
  state => ({
    email: state.session.user.user.email,
  }),
  { showNotification }
)
@reduxForm({
  form: 'confirmPassword',
})
class PasswordVerification extends Component {
  handleRequestErrors = ({ errors }) => {
    const error = (errors || [])[0];
    if (error === 'User 2FA setup is required') {
      this.props.abort();
      this.props.onAllUsers2faSetupRequired();
    } else {
      this.props.showNotification({
        type: 'error',
        message: error,
      });
    }
  };

  onConfirm = ({ password }) => {
    return this.props
      .onConfirm(this.props.enable, password)
      .then(resposne => {
        const { second_factor_auth } = resposne.data;
        const message = `2-step verification successfully turned ${
          second_factor_auth ? 'on' : 'off'
        } for all your team members`;

        this.props.showNotification({
          type: 'success',
          message,
        });
        this.props.closeModal();
        this.props.actionCompleted(true);
      })
      .catch(this.handleRequestErrors);
  };

  render() {
    const { email, enable, handleSubmit, abort } = this.props;
    return (
      <div class="2fa-modal">
        <ModalHeader
          title={(enable ? 'Enable' : 'Disable') + '2-step verification'}
          onCloseClick={abort}
        />
        <div class="modal-body">
          <p>
            To confirm please enter the password for <strong>{email}</strong>
          </p>
          <form style={{ marginBottom: '35px' }}>
            <div class="form-group">
              <Field
                type="password"
                name="password"
                component={InputField}
                class="form-control"
                placeholder="Password"
                validate={[required()]}
                autoFocus
              />
            </div>
            <div class="Modal__actions">
              <AsyncButton
                class="btn btn-primary btn-block"
                text="Confirm"
                pendingText="Please Wait..."
                onClick={handleSubmit(this.onConfirm)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export { VerifyOtp, PasswordVerification, AskMobileNumber };
