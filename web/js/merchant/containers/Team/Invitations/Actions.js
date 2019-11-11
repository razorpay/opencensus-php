import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';

import {
  cancelInvitation,
  updateInvitation,
  resendInvitation,
} from 'merchant/reducers/invitation';
import { openModal, closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import { pickProps } from 'rzp/utils/rzp-utils';

import ModalHeader from 'rzp/ui/ModalHeader';

import NewInvitation from '../NewInvitation';

@connect(null, {
  cancelInvitation,
  updateInvitation,
  resendInvitation,
  showNotification,
  openModal,
  closeModal,
})
export default class InvitationsActions extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  update = () => {
    const { invitation, updateInvitation, closeModal, ...props } = this.props;
    const visibleFields = {
      role: true,
    };

    const defaults = pickProps(invitation, ['id', 'role']);

    props.openModal({
      size: 'small',
      component: (
        <>
          <ModalHeader title="Update Invitation" onCloseClick={closeModal} />
          <div class="modal-body">
            <NewInvitation
              visibleFields={visibleFields}
              defaults={{ ...defaults }}
              ctaText="Update Invitation"
              successMsg="Invitation is updated successfully"
              closeModal={this.props.closeModal}
              onFormSubmit={this.props.updateInvitation}
            />
          </div>
        </>
      ),
    });
  };

  cancel = () => {
    const { invitation, cancelInvitation } = this.props;
    this.context.confirm({
      header: 'Cancel Invitation',
      message: (
        <>
          Are you sure you want to cancel invitation sent to{' '}
          <strong>{invitation.email}</strong>?
        </>
      ),
      affirmativeLabel: 'Yes, Cancel',
      affirmativePendingLabel: 'Cancelling...',
      abortLabel: "No, Don't Cancel",
      action: () => {
        return cancelInvitation(invitation.id)
          .then(response => {
            if (response) {
              this.props.showNotification({
                type: 'success',
                message: 'Invitation successfully cancelled',
              });
            }
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
    });
  };

  resend = () => {
    const { invitation, loggedInUserName } = this.props;
    return this.props
      .resendInvitation({
        id: invitation.id,
        sender_name: loggedInUserName,
      })
      .then(response => {
        if (response) {
          this.props.showNotification({
            type: 'success',
            message: 'Invitation resent successfully',
          });
        }
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    return (
      <>
        <AsyncButton
          class="btn btn-primary m-r"
          onClick={this.resend}
          text="Resend"
          pendingText="Resending..."
        />
        <button className="btn btn-primary m-r" onClick={this.update}>
          Update
        </button>

        <AsyncButton
          class="btn btn-default"
          text="Cancel"
          pendingText="Cancelling"
          onClick={this.cancel}
        />
      </>
    );
  }
}
