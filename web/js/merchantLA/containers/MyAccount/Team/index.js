/* eslint-disable */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Navigate } from 'react-router-dom';
import HeaderAction from 'common/ui/HeaderAction';
import { fetchTeamDetails } from 'merchantLA/reducers/team';
import { DocLink } from 'merchant/components/DocsLink';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import NewInvitation from './NewInvitation';
import Invitation from './Invitation';
import User from './User';

@connect(
  (state) => {
    return {
      invitations: state.team.invitations,
      users: state.team.users,
      merchant: state.session.user,
    };
  },
  {
    fetchTeamDetails,
    ...NotificationsActions,
  },
)
export default class TeamContainer extends Component {
  UNSAFE_componentWillMount() {
    if (this.props.merchant.userRole === 'linked_account_owner') {
      this.props.fetchTeamDetails({ merchant_id: this.props.merchant.current });
    }
  }

  render() {
    let invitations = this.props.invitations;
    let users = this.props.users;
    let otherUsers = users.filter((user) => user.email !== this.props.merchant.email);

    return this.props.merchant.userRole === 'linked_account_owner' ? (
      <div>
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <DocLink
              class="btn btn-link"
              href="https://razorpay.com/docs/team-support/"
              target="_blank"
            >
              Documentation &nbsp;
              <i class="icon icon-external-link" />
            </DocLink>
          </div>
        </HeaderAction>
        <div class="content-wrapper content-sm">
          <NewInvitation />

          {otherUsers.length ? (
            <div>
              <div class="panel-heading">
                <b>Team Members</b>
              </div>
              <table class="table table-noborder" style={{ margin: '0 12px' }}>
                <tbody>
                  {otherUsers.map((user) => (
                    <User key={user.id} user={user} form={`editUser_${user.id}`} />
                  ))}
                </tbody>
              </table>
            </div>
          ) : null}

          {!!otherUsers.length && <div class="section-divide" />}

          {invitations.length ? (
            <div>
              <div class="panel-heading">
                <b>Pending Invitations</b>
              </div>
              <table class="table table-noborder" style={{ margin: '0 12px' }}>
                <tbody>
                  {invitations.map((invite) => (
                    <Invitation
                      key={invite.id}
                      invite={invite}
                      form={`editInvitation_${invite.id}`}
                    />
                  ))}
                </tbody>
              </table>
            </div>
          ) : null}
        </div>
      </div>
    ) : (
      <Navigate to="/profile" replace />
    );
  }
}
