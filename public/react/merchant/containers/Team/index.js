import React, { Component } from 'react'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'
import Header from 'rzp/ui/Header'
import { fetchTeamDetails } from 'merchant/modules/team'
import * as NotificationsActions from 'rzp/modules/notifications'
import NewInvitation from './NewInvitation'
import Invitation from './Invitation'
import User from './User'

@connect(
  (state) => {
    return {
      team: state.team.team,
      merchant: state.session.user,
    }
  },
  {
    fetchTeamDetails,
    ...NotificationsActions
  }
)
export default class TeamContainer extends Component {
  componentWillMount() {
    this.props.fetchTeamDetails()
  }

  render() {
    let { invitations, users } = this.props.team
    let otherUsers = users.filter((user) => user.email !== this.props.merchant.email)

    return (
      <div class='react-root'>
        <Header title='Manage Team' showMode={false} />

        <div class='content-wrapper'>
          <div class='row'>
            <div class='col-md-8 col-md-offset-2 col-sm-12'>
              <div class='panel panel-default'>
                <div class='panel-heading'>
                  Invite users to your Organization Team

                  <small class='pull-right'>
                    <a href='https://docs.razorpay.com/v1/page/team-support' target='_blank'>
                      Team & Roles Documentation &nbsp;
                      <i class='fa fa-external-link'></i>
                    </a>
                  </small>
                </div>

                <div class='panel-body'>
                  <NewInvitation />

                  {
                    otherUsers.length ?
                      <div>
                        <div class='panel-heading'>Team Members</div>
                        <table class='table table-noborder'>
                          <tbody>
                            {
                              otherUsers.map((user) => (
                                <User
                                  key={user.id}
                                  user={user}
                                  form={`editUser_${user.id}`}
                                />
                              ))
                            }
                          </tbody>
                        </table>
                      </div> : null
                  }

                  {
                    invitations.length ?
                      <div>
                        <div class='panel-heading'>Pending Invitations</div>
                        <table class='table table-noborder'>
                          <tbody>
                            {
                              invitations.map((invite) => (
                                <Invitation
                                  key={invite.id}
                                  invite={invite}
                                  form={`editInvitation_${invite.id}`}
                                />
                              ))
                            }
                          </tbody>
                        </table>
                      </div> : null
                  }
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
