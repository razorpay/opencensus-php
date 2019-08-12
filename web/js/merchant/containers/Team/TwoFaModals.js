import React, { Component } from 'react';
import ModalHeader from 'rzp/ui/ModalHeader';
import { OtpInput } from 'merchant/components/OtpInput';
import { Field, reduxForm } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import { required, phone } from 'rzp/utils/validators';
import AsyncButton from 'react-async-button';

const VerifyOtp = ({
  otp,
  mobile,
  closeModal,
  onConfirm,
  verified,
  onOtpEnter,
  onChangeMobileNumber,
}) => {
  let otpValue = '';
  const inCorrectOtp = undefined === verified ? false : !verified;
  return (
    <div>
      <ModalHeader title="Verify Mobile Number" onCloseClick={closeModal} />
      <div class="modal-body">
        <p>
          An SMS with 6-digit OTP has been sent to {mobile}
          <a onClick={onChangeMobileNumber}>[Change]</a>
        </p>
        <p>OTP will expire in 5mins. </p>

        <OtpInput
          otp={otp}
          onComplete={otp => {
            otpValue = otp;
            onOtpEnter && onOtpEnter(otpValue);
          }}
          wrong={inCorrectOtp}
        />
        <p>Didn’t receive an SMS? Sending.</p>
        <div class="Modal__actions">
          <button
            class="btn btn-primary btn-block"
            onClick={() => onConfirm(otpValue)}
          >
            Confirm
          </button>
        </div>
      </div>
    </div>
  );
};

const MissingNumbers = ({ count, closeModal }) => {
  return (
    <div class="2fa-modal">
      <ModalHeader title="2-step verification" onCloseClick={closeModal} />
      <div class="modal-body">
        <p>
          To enable 2-step verification all your team members should have phone
          numbers associated to their account.
        </p>
        <div class="error-msg">
          <div style={{ flex: 1 }}>
            <i
              class="i i-error pull-left wrong-msg"
              style={{ fontSize: '18px' }}
            ></i>
          </div>
          <div style={{ flex: 9 }}>
            <p>
              There are <strong>{count} team members</strong> who don't have
              phone numbers linked.
            </p>
          </div>
        </div>
        <div class="Modal__actions no-side-padding">
          <button class="btn btn-primary btn-block" onClick={closeModal}>
            Close
          </button>
        </div>
      </div>
    </div>
  );
};
const EnableAgreement = ({ closeModal, onAgree }) => {
  return (
    <div class="2fa-modal">
      <ModalHeader
        title="Setting up 2-step verification"
        onCloseClick={closeModal}
      />
      <div class="modal-body">
        <p>
          To enforce 2-step verification to your team, your account needs to be
          2-step enabled
        </p>
        <div class="Modal__actions no-side-padding">
          <button class="btn btn-primary btn-block" onClick={onAgree}>
            Agree and proceed
          </button>
        </div>
      </div>
    </div>
  );
};
const DisableAgreement = ({ closeModal, onAgree }) => {
  return (
    <div class="2fa-modal">
      <ModalHeader
        title="Disable 2-step verification"
        onCloseClick={closeModal}
      />
      <div class="modal-body">
        <p>
          Are you sure you want to disable 2-step verification to all your team
          members?
        </p>
        <div
          class="Modal__actions no-side-padding"
          style={{ display: 'flex', justifyContent: 'space-between' }}
        >
          <button
            class="btn btn-default"
            onClick={closeModal}
            style={{ width: '131px' }}
          >
            No, Don’t!
          </button>
          <button
            class="btn btn-primary"
            onClick={onAgree}
            style={{ width: '131px' }}
          >
            Yes, disable it
          </button>
        </div>
      </div>
    </div>
  );
};

@reduxForm({
  form: 'askPhone',
  initialValues: {
    contact_mobile: '',
  },
})
class AskMobileNumber extends Component {
  render() {
    const sendOtp = ({ contact_mobile }) => {
      return onComplete(contact_mobile);
    };
    const { closeModal, onComplete, handleSubmit, mobile } = this.props;
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
          <form
            onSubmit={handleSubmit(sendOtp)}
            style={{ marginBottom: '35px' }}
          >
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
                onClick={handleSubmit(sendOtp)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
@reduxForm({
  form: 'confimrPassword',
  initialValues: {
    password: '',
  },
})
class PasswordVerification extends Component {
  state = {
    disableAbort: false, //If form-submit is in progress
  };
  render() {
    const { closeModal, title, email, handleSubmit, onConfirm } = this.props;
    const confimrPassword = handleSubmit(({ password }) => onConfirm(password));
    return (
      <div class="2fa-modal">
        <ModalHeader
          title={title}
          onCloseClick={() => {
            !this.state.disableAbort && closeModal();
          }}
        />
        <div class="modal-body">
          <p>
            To confirm please enter the password for <strong>{email}</strong>
          </p>
          <form onSubmit={confimrPassword} style={{ marginBottom: '35px' }}>
            <div class="form-group">
              <Field
                type="password"
                name="password"
                component={InputField}
                class="form-control"
                placeholder="Password"
                validate={[required()]}
                autoFocus={true}
              />
            </div>
            <div
              class="Modal__actions no-side-padding"
              style={{ display: 'flex', justifyContent: 'space-between' }}
            >
              <button
                disabled={this.state.disableAbort}
                class="btn btn-default"
                onClick={closeModal}
                style={{ width: '131px' }}
              >
                Cancel
              </button>
              <AsyncButton
                style={{ width: '131px' }}
                class="btn btn-primary btn-block"
                text="Confirm"
                pendingText="Please Wait..."
                onClick={() => {
                  this.setState(() => {
                    return { disableAbort: true };
                  });
                  return confimrPassword();
                }}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export {
  VerifyOtp,
  MissingNumbers,
  EnableAgreement,
  DisableAgreement,
  PasswordVerification,
  AskMobileNumber,
};
