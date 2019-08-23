import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';

import { removeMember, updateMember } from 'merchant/modules/team';
import { openModal, closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

@connect(null, {
  removeMember,
  updateMember,
  openModal,
  closeModal,
  showNotification,
})
export default class MembersActions extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  update = () => {
    // code to update member from team
  };

  remove = () => {
    const { member, removeMember, showNotification } = this.props;
    this.context.confirm({
      header: 'Remove User?',
      message: (
        <>
          Are you sure you want to remove{' '}
          <strong>{member.name || member.email}</strong> as a member of your
          team
        </>
      ),

      affirmativeLabel: 'Yes, Remove',
      affirmativePendingLabel: 'Removing...',

      abortLabel: "No, Don't Remove",
      action: () => {
        return removeMember(member.id)
          .then(response => {
            if (response) {
              showNotification({
                type: 'success',
                message: 'Member remove successfully from the team',
              });
            }
          })
          .catch(({ errors }) => {
            showNotification({
              type: error,
              message: errors,
            });
          });
      },
    });
  };

  render() {
    return (
      <>
        <button class="btn btn-primary m-r" onClick={this.update}>
          Update
        </button>

        <AsyncButton
          class="btn btn-default"
          text="Remove"
          pendingText="Removing..."
          onClick={this.remove}
        />
      </>
    );
  }
}
