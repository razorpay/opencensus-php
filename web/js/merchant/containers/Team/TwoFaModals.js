import React from 'react';
import ModalHeader from 'rzp/ui/ModalHeader';
import { OtpInput } from 'merchant/components/OtpInput';

const VerifyMobileNumber = ({
  mobile,
  closeModal,
  onOtpEnter,
  onConfirm,
  changeMobile,
}) => {
  return (
    <div>
      <ModalHeader title="Verify Mobile Number" onCloseClick={closeModal} />
      <div class="modal-body">
        <p>
          An SMS with 6-digit OTP has been sent to {mobile}
          <a onClick={changeMobile}>[Change]</a>
        </p>
        <p>OTP will expire in 5mins. </p>

        <OtpInput
          onComplete={otp => {
            onOtpEnter(otp);
          }}
          wrong={false}
        />
        <p>Didn’t receive an SMS? Sending.</p>
        <div class="Modal__actions">
          <button class="btn btn-primary btn-block" onClick={onConfirm}>
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

export { VerifyMobileNumber, MissingNumbers };
