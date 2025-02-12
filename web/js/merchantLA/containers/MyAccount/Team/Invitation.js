import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { resendInvitation, cancelInvitation, fetchTeamDetails } from 'merchantLA/reducers/team';
import { compose } from 'redux';
class EditInvitation extends Component {
  cancelInvitation = () => {
    return this.props
      .cancelInvitation(this.props.invite.id)
      .then(() => {
        this.fetchTeamDetails();
        this.props.showNotification({
          type: 'success',
          message: "Team member's invitation has been removed successfully",
        });
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  resendInvitation = () => {
    let invite = this.props.invite;
    let data = {
      sender_name: this.props.user.user.name,
    };
    return this.props
      .resendInvitation(invite.id, data)
      .then(() => {
        this.fetchTeamDetails();
        this.props.showNotification({
          type: 'success',
          message: `Invitation has been successfully resent to ${invite.email}`,
        });
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  fetchTeamDetails = () => {
    this.props.fetchTeamDetails({ merchant_id: this.props.user.current });
  };

  render() {
    return (
      <tr>
        <td>{this.props.invite.email}</td>
        <td>
          <div className="btn-toolbar">
            <AsyncButton
              className="btn btn-sm btn-danger"
              text="Cancel"
              data-tip="Cancels invitation"
              onClick={this.cancelInvitation}
            />

            <AsyncButton
              className="btn btn-sm btn-primary"
              text="Resend"
              data-tip="Resend invitation email"
              onClick={this.resendInvitation}
            />
          </div>
        </td>
      </tr>
    );
  }
}

export default compose(
  connect((state) => state.session, {
    fetchTeamDetails,
    resendInvitation,
    cancelInvitation,
    ...NotificationsActions,
  }),
)(EditInvitation);
