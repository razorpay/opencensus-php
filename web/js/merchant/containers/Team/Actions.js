import { Component } from 'react';
import AsyncButton from 'react-async-button';

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
    console.log(this.props.item.id);
  };

  render() {
    const item = this.props.item;
    return (
      <>
        <button class="btn btn-primary m-r" onClick={this.updateUser}>
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
