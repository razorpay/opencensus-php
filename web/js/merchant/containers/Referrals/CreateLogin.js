import { Component } from 'react';
import { connect } from 'react-redux';
import * as NotificationsActions from 'rzp/modules/notifications';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import { closeModal } from 'rzp/modules/modals';

@connect(
  state => {
    return {
      ...state.session,
    };
  },
  { closeModal, ...NotificationsActions }
)
export default class CreateLogin extends Component {
  save = () => {
    const { id } = this.props.referral;
    return this.props
      .onSave(id)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Merchant invitation sent successfully',
        });
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { referral } = this.props;

    return (
      <div>
        <ModalHeader
          title="Invite to Login"
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <p>
            The merchant <strong>{referral.name}</strong> (Merchant ID -{' '}
            <code>{referral.id}</code>), will receive an email with the sign-in
            link. They will have complete access to their dashboard
          </p>
        </div>

        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-default"
            onClick={this.props.closeModal}
          >
            No, Cancel
          </button>

          <AsyncButton
            type="submit"
            class="btn btn-primary"
            text="Yes, Invite"
            pendingText="Inviting Merchant..."
            onClick={this.save}
          />
        </div>
      </div>
    );
  }
}
