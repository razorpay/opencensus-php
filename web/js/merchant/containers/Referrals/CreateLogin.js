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
    const { email, id, name } = this.props.referral;
    return this.props
      .onSave({ email, id })
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Login created for the merchant ' + name,
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
            The merchant <b>{referral.name}</b> with merchant id{' '}
            <code>{referral.id}</code>will receive an email containing a link to
            set password for newly created login account. There account will
            have complete access to their merchant dashboard
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
            pendingText="Creating Login..."
            onClick={this.save}
          />
        </div>
      </div>
    );
  }
}
