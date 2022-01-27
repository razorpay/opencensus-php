import React from 'react';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import Button from 'common/new-ui/Button';

// eslint-disable-next-line no-shadow
const NewID = ({ newEmail, user, closeModal }) => {
  return (
    <div className="e-self-serve">
      <div className="e-self-serve-heading">Check your email</div>
      <div className="image-container">
        <img
          alt="verify email"
          src="https://cdn.razorpay.com/static/assets/email-self-serve/EmailImage.svg"
        />
      </div>
      <p className="verification-msg">
        We have sent an email with a verification link to {newEmail}. Please use that link to verify
        and activate your new id
      </p>

      <div className="dialogue-container">
        <p className="dialogue-msg">Your existing email-id {user.user.email} will be deactivated</p>
      </div>

      <Button.Primary type="button" className="btn-block" onClick={closeModal}>
        Okay, got it
      </Button.Primary>
    </div>
  );
};

export default connect(
  (state) => {
    return {
      user: state.session.user,
    };
  },
  { closeModal },
)(NewID);
