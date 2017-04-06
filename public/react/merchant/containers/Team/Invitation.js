import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import * as NotificationsActions from 'merchant/modules/notifications'
import { roles, resendInvitation, updateInvitation, cancelInvitation, fetchTeamDetails } from 'merchant/modules/team'

@connect(
  null,
  {
    fetchTeamDetails,
    resendInvitation,
    cancelInvitation,
    updateInvitation,
    ...NotificationsActions,
  }
)
@reduxForm()
export default class EditInvitation extends Component {
  componentWillMount() {
    this.props.initialize({
      role: this.props.invite.role
    })
  }

  updateInvitation = (fieldProps) => {
    return this.props.updateInvitation(this.props.invite.id, fieldProps).then(() => {
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

  cancelInvitation = () => {
    return this.props.cancelInvitation(this.props.invite.id).then(() => {
      this.props.fetchTeamDetails()
      this.props.showNotification({
        type: 'success',
        message: 'Team member\'s invitation has been removed successfully'
      })
    }).catch((err) => {
      this.props.showNotification({
        type: 'error',
        message: err.errors
      })
    })
  }

  resendInvitation = () => {
    let invite = this.props.invite
    return this.props.resendInvitation(invite.id).then(() => {
      this.props.fetchTeamDetails()
      this.props.showNotification({
        type: 'success',
        message: `Invitation has been successfully resent to ${invite.email}`
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
      invite,
    } = this.props

    return (
      <tr>
        <td>{invite.email}</td>
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
              data-tip='Update role of the invited user'
              onClick={handleSubmit(this.updateInvitation)}
            />

            <AsyncButton
              class='btn btn-sm btn-danger'
              text='Cancel'
              data-tip='Cancels invitation'
              onClick={handleSubmit(this.cancelInvitation)}
            />

            <AsyncButton
              class='btn btn-sm btn-primary'
              text='Resend'
              data-tip='Resend invitation email'
              onClick={handleSubmit(this.resendInvitation)}
            />
          </div>
        </td>
      </tr>
    )
  }
}
