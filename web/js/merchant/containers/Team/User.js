import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import * as NotificationsActions from 'rzp/modules/notifications';
import { roles, agentRole, RBLRoles } from 'rzp/utils/constants';
import { without } from 'rzp/utils/rzp-utils';
import {
  updateUser,
  removeUser,
  fetchTeamDetails,
} from 'merchant/modules/team';

@connect(
  state => {
    return {
      merchantId: state.session.user.current,
      session: state.session,
    };
  },
  {
    fetchTeamDetails,
    updateUser,
    removeUser,
    ...NotificationsActions,
  }
)
@reduxForm({})
export default class EditUser extends Component {
  componentWillMount() {
    this.props.initialize({
      role: this.props.user.role,
    });
  }

  updateUser = fieldProps => {
    return this.props
      .updateUser(this.props.user.id, fieldProps)
      .then(() => {
        this.props.fetchTeamDetails({
          merchant_id: this.props.merchantId,
        });
        this.props.showNotification({
          type: 'success',
          message: "Team member's role has been changed successfully",
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  removeUser = () => {
    return this.props
      .removeUser(this.props.user.id)
      .then(() => {
        this.props.fetchTeamDetails({
          merchant_id: this.props.merchantId,
        });
        this.props.showNotification({
          type: 'success',
          message: 'Team member has been removed successfully',
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit, user, session } = this.props;

    let isAllowedUpdate = true,
      isAllowedRemove = true;
    let allRoles = roles;

    if (session.user.isAgentRole) {
      allRoles = { ...allRoles, ...agentRole };
    } else if (session.user.isRBLRoleEnabled) {
      allRoles = { ...allRoles, ...RBLRoles };

      if (session.user.role === 'rbl_supervisor') {
        isAllowedUpdate = false; // No roles apart from rbl_agent to be allowed to be managed by rbl_supervisor
        isAllowedRemove = false;

        if (user.role === 'rbl_agent') {
          allRoles = { agent: RBLRoles.rbl_agent };
          isAllowedRemove = true;
        }
      }
    }

    let ROLES = user.role === 'owner' ? allRoles : without(allRoles, 'owner');
    return (
      <tr>
        <td>{user.email}</td>
        <td>{user.mobile_number}</td>
        <td>{user.name}</td>
        <td>
          <Field
            name="role"
            component="select"
            class="form-control"
            disabled={!(isAllowedUpdate || isAllowedRemove)}
          >
            {Object.keys(ROLES).map(role => (
              <option key={role} value={role}>
                {ROLES[role].label}
              </option>
            ))}
          </Field>
        </td>

        <td>
          <div class="btn-toolbar">
            {isAllowedUpdate && (
              <AsyncButton
                class="btn btn-sm btn-success"
                text="Update"
                data-tip="Updates the user's role"
                onClick={handleSubmit(this.updateUser)}
              />
            )}
            {isAllowedRemove && (
              <AsyncButton
                class="btn btn-sm btn-danger"
                text="Remove"
                data-tip="Removes user from your team"
                onClick={handleSubmit(this.removeUser)}
              />
            )}
          </div>
        </td>
      </tr>
    );
  }
}
