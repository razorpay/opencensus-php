import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import AsyncButton from 'react-async-button';
import Header from 'rzp/ui/Header';
import { fetchTeamDetails } from 'merchant/modules/team';
import * as NotificationsActions from 'rzp/modules/notifications';
import NewInvitation from './NewInvitation';
import Invitation from './Invitation';
import User from './User';

@connect(
  state => {
    return {
      team: state.team.team,
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
    this.props.fetchTeamDetails();
  }

  render() {
    let { invitations, users } = this.props.team;
    let otherUsers = users.filter(
      user => user.email !== this.props.merchant.email
    );

    return (
      <tabbed-container>
        <header>
          <NavLink to="/app/team">Manage Team</NavLink>

          <a
            class="pull-right"
            href="https://docs.razorpay.com/v1/page/team-support"
            target="_blank"
          >
            Team & Roles Documentation &nbsp;
            <i class="fa fa-external-link" />
          </a>
        </header>

        <div class="content-wrapper content-sm">
          <NewInvitation />

          {otherUsers.length
            ? <div>
                <div class="panel-heading">Team Members</div>
                <table class="table table-noborder">
                  <tbody>
                    {otherUsers.map(user => (
                      <User
                        key={user.id}
                        user={user}
                        form={`editUser_${user.id}`}
                      />
                    ))}
                  </tbody>
                </table>
              </div>
            : null}

          {invitations.length
            ? <div>
                <div class="panel-heading">Pending Invitations</div>
                <table class="table table-noborder">
                  <tbody>
                    {invitations.map(invite => (
                      <Invitation
                        key={invite.id}
                        invite={invite}
                        form={`editInvitation_${invite.id}`}
                      />
                    ))}
                  </tbody>
                </table>
              </div>
            : null}
        </div>
      </tabbed-container>
    );
  }
}
