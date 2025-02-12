import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';

import ModalHeader from 'common/ui/ModalHeader';
import { showNotification } from 'merchant_common/reducers/notifications';

import { Field, reduxForm } from 'redux-form';
import InputField from 'common/ui/Forms/InputField';
import { required, phone, mobile } from 'common/utils/validators';
import { OtpInput } from 'merchant/components/OtpInput';
import { compose } from 'redux';

// This file has similar components to
// Account/Profile/components/UpdateSelfContactMobile.js
// refactor ASAP to remove redundancy

class VerifyOtpComponent extends Component {
  state = {};
  onConfirm = () => {
    return this.props
      .onSubmit({
        contact_mobile: this.props.contactMobile,
        otp: this.otpValue,
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
    return this.props.onResend({
      contact_mobile: this.props.contactMobile,
    });
  };

  updateOtpValue = (otp) => {
    this.otpValue = otp;
  };

  render() {
    const { contactMobile, closeModal, onChangeMobileNumber } = this.props;

    return (
      <div>
        <ModalHeader title="Verify Mobile Number" onCloseClick={closeModal} />
        <div className={`modal-body ${this.props.customClass ? this.props.customClass : ''}`}>
          <p>
            {this.props.customMessage
              ? this.props.customMessage
              : `An SMS with 6-digit OTP has been sent to ${contactMobile} `}
            <a onClick={onChangeMobileNumber}>Change</a>
          </p>
          <p>OTP will expire in 5 mins.</p>

          <OtpInput
            onComplete={this.updateOtpValue}
            onChange={this.updateOtpValue}
            wrong={this.state.wrongOtp}
          />
          <p>
            Didn’t receive an SMS?
            <AsyncButton
              className="btn btn-link"
              text="Send OTP"
              pendingText="Sending OTP..."
              onClick={this.onResend}
            />
          </p>
          <div className="Modal__actions">
            <AsyncButton
              className="btn btn-primary btn-block"
              onClick={this.onConfirm}
              text="Confirm"
              pendingText="Sending OTP..."
            />
          </div>
        </div>
      </div>
    );
  }
}

class AskMobileNumberComponent extends Component {
  onSubmit = (data) => {
    return this.props.onSubmit(data);
  };

  UNSAFE_componentWillMount() {
    //handling the use case where the number should be empty
    if (this.props.blank) {
      this.props.change('contact_mobile', '');
    }
  }

  render() {
    const { closeModal, handleSubmit } = this.props;
    return (
      <div className="2fa-modal">
        <ModalHeader
          title={this.props.customTitle ? this.props.customTitle : `Setting up 2-step verification`}
          onCloseClick={closeModal}
        />
        <div className="modal-body">
          <p>
            {this.props.customMessage
              ? this.props.customMessage
              : `Let's setup a mobile number where you will receive an SMS with OTP everytime you log in.`}
          </p>
          <form style={{ marginBottom: '35px' }}>
            <div className="form-group">
              <label>Enter your phone number </label>
              <Field
                name="contact_mobile"
                component={InputField}
                className="form-control"
                placeholder="Phone Number"
                validate={[
                  required(),
                  ...(this.props.mobileValidation
                    ? mobile('Enter a valid mobile number')
                    : phone('Invalid Mobile')),
                ]}
              />
            </div>
            <div className="form-group">
              <AsyncButton
                className="btn btn-primary btn-block"
                text="Send OTP"
                pendingText="Sending OTP..."
                onClick={handleSubmit(this.onSubmit)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

const VerifyOtp = compose(connect(null, { showNotification }))(VerifyOtpComponent);

const AskMobileNumber = compose(
  connect(
    (state) => ({
      initialValues: {
        contact_mobile: (state.session.user.user || {}).contact_mobile,
      },
    }),
    { showNotification },
  ),
  reduxForm({
    form: 'askPhone',
  }),
)(AskMobileNumberComponent);

export { AskMobileNumber, VerifyOtp };
