import React from 'react';
// eslint-disable-next-line import/no-extraneous-dependencies
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { compose } from 'redux';
import Button from 'common/new-ui/Button';

const OwnerUpdatedModal = ({ user, newEmail }) => {
  return (
    <div className="e-self-serve">
      <div className="e-self-serve-heading">Email updated successfully</div>
      <div className="image-container">
        <img
          alt="user Image"
          src="https://cdn.razorpay.com/static/assets/email-self-serve/userImage.svg"
        />
      </div>
      <p className="verification-msg">Log in with account credentials associated with {newEmail}</p>
      <div className="dialogue-container">
        <p className="dialogue-msg">
          Your exisitng email-id {user.user.email} has been deactivated
        </p>
      </div>
      <Button.Primary type="button" onClick={() => window.location.reload()} className="btn-block">
        Okay Got it
      </Button.Primary>
    </div>
  );
};

export default compose(
  connect((state) => {
    return {
      user: state.session.user,
    };
  }),
  withRouter,
)(OwnerUpdatedModal);
