import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import * as NotificationsActions from 'merchant/modules/notifications'
import { roles, updateUser, removeUser, fetchTeamDetails } from 'merchant/modules/team'

@connect(
  null,
  {
    fetchTeamDetails,
    updateUser,
    removeUser,
    ...NotificationsActions,
  }
)
@reduxForm()
export default class EditInvitation extends Component {
  componentWillMount() {
    this.props.initialize({
      role: this.props.user.pivot.role
    })
  }

  updateUser = (fieldProps) => {
    return this.props.updateUser(this.props.user.id, fieldProps).then(() => {
      this.props.fetchTeamDetails()
      this.props.showNotification({
        type: 'success',
        message: 'Team member\'s role has been changed successfully'
      })
    }).catch((err) => {
      this.props.showNotification({
        type: 'error',
        message: err.errors
      })
    })
  }

  removeUser = () => {
    return this.props.removeUser(this.props.user.id).then(() => {
      this.props.fetchTeamDetails()
      this.props.showNotification({
        type: 'success',
        message: 'Team member has been removed successfully'
      })
    }).catch((err) => {
      this.props.showNotification({
        type: 'error',
        message: err.errors
      })
    })
  }

  render() {
    const {
      handleSubmit,
      user,
    } = this.props

    return (
      <tr>
        <td>{user.email}</td>
        <td>
          <Field
            name='role'
            component='select'
            class='form-control'
          >
            {
              Object.keys(roles).map((role) => <option key={role} value={role}>{roles[role].label}</option>)
            }
          </Field>
        </td>

        <td>
          <div class='btn-toolbar'>
            <AsyncButton
              class='btn btn-sm btn-success'
              text='Update'
              onClick={handleSubmit(this.updateUser)}
            />

            <AsyncButton
              class='btn btn-sm btn-danger'
              text='Remove'
              onClick={handleSubmit(this.removeUser)}
            />
          </div>
        </td>
      </tr>
    )
  }
}
