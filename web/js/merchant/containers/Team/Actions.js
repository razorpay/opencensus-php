import { Component } from 'react';
import AsyncButton from 'react-async-button';

import ModalHeader from 'rzp/ui/ModalHeader';
import { pickProps } from 'rzp/utils/rzp-utils';

import NewInvitation from './NewInvitation';

export default class MerchantUserActions extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  removeUser = () => {
    const { item, cancelInvitation, removeUser } = this.props;
    const isInvitation = item.hasOwnProperty('user_id');

    this.context.confirm({
      header: isInvitation ? 'Cancel Invitation?' : 'Remove User?',
      message: getRemoveConfirmMessage(item, isInvitation),

      affirmativeLabel: 'Yes, ' + (isInvitation ? 'Cancel' : 'Remove'),
      affirmativePendingLabel: isInvitation ? 'Cancelling' : 'Removing',

      action: () => {
        const deleteMethod = isInvitation ? cancelInvitation : removeUser;

        return deleteMethod(this.props.item.id).then(response => {
          if (response.success) {
            this.props.onRemove();
          }
        });
      },
    });
  };

  updateUser = () => {
    const item = this.props.item;
    const defaults = pickProps(item, ['contact_mobile', 'role']);

    return this.props.openModal({
      size: 'small',
      component: (
        <div>
          <ModalHeader
            title="Update Member Details"
            onCloseClick={this.props.closeModal}
          />
          <div class="modal-body">
            <NewInvitation
              modalType="update_member"
              extraFields={{ id: item.id }}
              onFormSubmit={this.props.updateMember}
              onSuccess={this.props.closeModal}
              successMsg={() => 'User has been successfully updated'}
              defaults={{ ...defaults }}
            />
          </div>
        </div>
      ),
    });
  };

  updateInvitation = () => {
    const item = this.props.item;
    const defaults = pickProps(item, ['role']);

    return this.props.openModal({
      size: 'small',
      component: (
        <div>
          <ModalHeader
            title="Update Invitation Details"
            onCloseClick={this.props.closeModal}
          />
          <div class="modal-body">
            <NewInvitation
              modalType="update_invitation"
              onFormSubmit={this.props.updateInvitation}
              onSuccess={this.props.closeModal}
              successMsg={() => 'Invitation update successfully'}
              extraFields={{ id: item.id }}
              defaults={{ ...defaults }}
            />
          </div>
        </div>
      ),
    });
  };

  render() {
    const item = this.props.item;
    return (
      <>
        <button
          class="btn btn-primary m-r"
          onClick={
            item.hasOwnProperty('user_id')
              ? this.updateInvitation
              : this.updateUser
          }
        >
          Update
        </button>

        <AsyncButton
          class="btn btn-default"
          text="Remove"
          onClick={this.removeUser}
        />
      </>
    );
  }
}

function getRemoveConfirmMessage(item, isInvitation) {
  if (isInvitation) {
    messagePart2 = (
      <>
        cancel invitation to <strong>{item.name || item.email}</strong>
      </>
    );
  } else {
    messagePart2 = (
      <>
        remove <strong>{item.name || item.email}</strong> as member of your team
      </>
    );
  }
  return <>Are you sure you want to {messagePart2}</>;
  var messagePart2;
}
