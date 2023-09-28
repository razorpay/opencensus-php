import React from 'react';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
// eslint-disable-next-line import/no-extraneous-dependencies
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateOwner } from 'merchant/reducers/team';

import Button from 'common/new-ui/Button';

import OwnerUpdated from './OwnerUpdatedModal';

// eslint-disable-next-line
const DifferentTeam = ({ newEmail, setContactEmail, openModal, closeModal, user, updateOwner }) => {
  const onProceedClick = () => {
    openModal({
      size: 'small',
      component: <OwnerUpdated newEmail={newEmail} />,
    });
    return updateOwner(newEmail, false, setContactEmail)
      .then((res) => {
        if (res.data && res.success) {
          openModal({
            size: 'small',
            component: <OwnerUpdated newEmail={newEmail} />,
          });
        }
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err.errors[0] || 'some error occured',
        });
      });
  };
  return (
    <div className="e-self-serve">
      <div className="e-self-serve-heading">
        Confirm Update
        <button type="button" className="close" onClick={closeModal}>
          <i className="i i-close" />
        </button>
      </div>
      <p className="verification-msg">
        {newEmail} will become your login id and {user.user.email} will be deactivated
      </p>
      <Button.Primary type="button" className="btn-block" onClick={onProceedClick}>
        Proceed
      </Button.Primary>
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
    { openModal, closeModal, showNotification, updateOwner },
  ),
  withRouter,
)(DifferentTeam);
