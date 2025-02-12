import React, { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { removeUser, fetchTeamDetails } from 'merchantLA/reducers/team';
import { compose } from 'redux';
class EditUser extends Component {
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
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    return (
      <tr>
        <td>{this.props.user.email}</td>
        <td>{this.props.user.name}</td>
        <td>
          <div className="btn-toolbar">
            <AsyncButton
              className="btn btn-sm btn-danger"
              text="Remove"
              data-tip="Removes user from your team"
              onClick={this.removeUser}
            />
          </div>
        </td>
      </tr>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        merchantId: state.session.user.current,
      };
    },
    {
      fetchTeamDetails,
      removeUser,
      ...NotificationsActions,
    },
  ),
)(EditUser);
