import React from 'react';
import { closeModal } from 'merchant_common/reducers/modals';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { compose } from 'redux';
import AsyncButton from 'react-async-button';

// eslint-disable-next-line no-shadow
const SameTeam = ({ newEmail, closeModal, history }) => {
  return (
    <div className="e-self-serve">
      <div className="e-self-serve-heading">
        Upgrade user role
        <button type="button" className="close" onClick={closeModal}>
          <i className="i i-close" />
        </button>
      </div>
      <div className="image-container">
        <img
          alt="image here"
          src="https://cdn.razorpay.com/static/assets/email-self-serve/userImage.svg"
        />
      </div>
      <p className="verification-msg">
        {newEmail} already exists in your team. Please upgrade them to owner role from the manage
        team page to make this the primary login email
      </p>
      <AsyncButton
        type="button"
        className="btn btn-primary btn-block"
        onClick={() => {
          history.push('/team');
          closeModal();
        }}
        text="Go to manage team"
      />
    </div>
  );
};

export default compose(
  connect(
    (state) => {
      return {
        user: state.session.user,
      };
    },
    { closeModal },
  ),
  withRouter,
)(SameTeam);
