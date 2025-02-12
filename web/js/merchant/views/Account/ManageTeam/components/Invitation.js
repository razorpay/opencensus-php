import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { roles, agentRole, RBLRoles, RegistrationLinkRoles } from 'merchant/helpers/data';
import {
  resendInvitation,
  updateInvitation,
  cancelInvitation,
  fetchTeamDetails,
} from 'merchant/reducers/team';

import rolesList from 'merchant/helpers/permissions/roles-list';
import { compose } from 'redux';

class EditInvitation extends Component {
  UNSAFE_componentWillMount() {
    this.props.initialize({
      role: this.props.invite.role,
    });
  }

  updateInvitation = (fieldProps) => {
    return this.props
      .updateInvitation(this.props.invite.id, fieldProps)
      .then(() => {
        this.fetchTeamDetails();
        this.props.showNotification({
          type: 'success',
          message: "Team member's role has been changed successfully",
        });
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

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
    const { handleSubmit, invite, user } = this.props;

    let isAllowedEdit = true;
    let allRoles = roles;

    if (user.isAgentRole) {
      allRoles = { ...allRoles, ...agentRole };
    } else if (user.isRBLRoleEnabled) {
      allRoles = { ...allRoles, ...RBLRoles };

      if (user.role === rolesList.RBL_SUPERVISOR) {
        allRoles = { ...allRoles, ...RBLRoles };

        isAllowedEdit = false; // No roles apart from rbl_agent to be allowed to be managed by rbl_supervisor

        if (invite.role === rolesList.RBL_AGENT) {
          isAllowedEdit = true;
          allRoles = { rbl_agent: RBLRoles.rbl_agent };
        }
      }
    }

    if (user.isRegistrationLinkRoleEnabled) {
      allRoles = { ...allRoles, ...RegistrationLinkRoles };
    }

    let ROLES = allRoles;

    return (
      <tr>
        <td>{invite.email}</td>
        <td>
          <Field name="role" component="select" className="form-control" disabled={!isAllowedEdit}>
            {Object.keys(ROLES).map(
              (
                role, // Only limited roles allowed to be managed in pending invitation
              ) => (
                <option key={role} value={role}>
                  {ROLES[role].label}
                </option>
              ),
            )}
          </Field>
        </td>

        <td>
          {isAllowedEdit && (
            <div className="btn-toolbar">
              <AsyncButton
                className="btn btn-sm btn-success"
                text="Update"
                data-tip="Update role of the invited user"
                onClick={handleSubmit(this.updateInvitation)}
              />

              <AsyncButton
                className="btn btn-sm btn-danger"
                text="Cancel"
                data-tip="Cancels invitation"
                onClick={handleSubmit(this.cancelInvitation)}
              />

              <AsyncButton
                className="btn btn-sm btn-primary"
                text="Resend"
                data-tip="Resend invitation email"
                onClick={handleSubmit(this.resendInvitation)}
              />
            </div>
          )}
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
    updateInvitation,
    ...NotificationsActions,
  }),
  reduxForm({}),
)(EditInvitation);
