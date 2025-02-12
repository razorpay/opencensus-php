import { Component } from 'react';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { compose } from 'redux';

import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

class CreateLogin extends Component {
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
        <ModalHeader title="Invite to Login" onCloseClick={this.props.closeModal} />

        <div className="modal-body">
          <p>
            The merchant <strong>{referral.name}</strong> (Merchant ID - <code>{referral.id}</code>
            ), will receive an email with the sign-in link. They will have complete access to their
            dashboard
          </p>
        </div>

        <div className="modal-footer">
          <button type="button" className="btn btn-default" onClick={this.props.closeModal}>
            No, Cancel
          </button>

          <AsyncButton
            type="submit"
            className="btn btn-primary"
            text="Yes, Invite"
            pendingText="Inviting Merchant..."
            onClick={this.save}
          />
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        ...state.session,
      };
    },
    { closeModal, ...NotificationsActions },
  ),
)(CreateLogin);
