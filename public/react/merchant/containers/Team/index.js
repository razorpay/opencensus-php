import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import AsyncButton from 'react-async-button';
import HeaderAction from 'rzp/ui/HeaderAction';
import { fetchTeamDetails } from 'rzp/modules/team';
import * as NotificationsActions from 'rzp/modules/notifications';
import NewInvitation from './NewInvitation';
import Invitation from './Invitation';
import User from './User';

@connect(
  state => {
    return {
      invitations: state.team.invitations,
      users: state.team.users,
      merchant: state.session.user,
    };
  },
  {
    fetchTeamDetails,
    ...NotificationsActions,
  }
)
export default class TeamContainer extends Component {
  componentWillMount() {
    this.props.fetchTeamDetails({ merchant_id: this.props.merchant.current });
  }

  render() {
    let invitations = this.props.invitations;
    let users = this.props.users;
    let otherUsers = users.filter(
      user => user.email !== this.props.merchant.email
    );

    return (
      <div>
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <a
              class="btn btn-link"
              href="https://docs.razorpay.com/v1/page/team-support"
              target="_blank"
            >
              Documentation &nbsp;
              <i class="icon icon-external-link" />
            </a>
          </div>
        </HeaderAction>

        <div class="content-wrapper content-sm">
          <NewInvitation />

          {otherUsers.length
            ? <div>
                <div class="panel-heading">Team Members</div>
                <table class="table table-noborder">
                  <tbody>
                    {otherUsers.map(user =>
                      <User
                        key={user.id}
                        user={user}
                        form={`editUser_${user.id}`}
                      />
                    )}
                  </tbody>
                </table>
              </div>
            : null}

          {invitations.length
            ? <div>
                <div class="panel-heading">Pending Invitations</div>
                <table class="table table-noborder">
                  <tbody>
                    {invitations.map(invite =>
                      <Invitation
                        key={invite.id}
                        invite={invite}
                        form={`editInvitation_${invite.id}`}
                      />
                    )}
                  </tbody>
                </table>
              </div>
            : null}
        </div>
      </div>
    );
  }
}
