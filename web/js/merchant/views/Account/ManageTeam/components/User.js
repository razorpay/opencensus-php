import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { roles, agentRole, RBLRoles } from 'merchant/helpers/data';
import { without } from 'common/utils/rzp-utils';
import {
  updateUser,
  removeUser,
  fetchTeamDetails,
} from 'merchant/reducers/team';
import rolesList from 'merchant/helpers/permissions/roles-list';

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
  UNSAFE_componentWillMount() {
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

      if (session.user.role === rolesList.RBL_SUPERVISOR) {
        isAllowedUpdate = false; // No roles apart from rbl_agent to be allowed to be managed by rbl_supervisor
        isAllowedRemove = false;

        if (user.role === rolesList.RBL_AGENT) {
          allRoles = { agent: RBLRoles.rbl_agent };
          isAllowedRemove = true;
        }
      }
    }

    let ROLES =
      user.role === rolesList.OWNER
        ? allRoles
        : without(allRoles, rolesList.OWNER);
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
