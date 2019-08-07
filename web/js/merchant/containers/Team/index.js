import React, { Component } from 'react';
import { connect } from 'react-redux';
import HeaderAction from 'rzp/ui/HeaderAction';
import { fetchTeamDetails } from 'merchant/modules/team';
import * as NotificationsActions from 'rzp/modules/notifications';
import NewInvitation from './NewInvitation';
import Invitation from './Invitation';
import User from './User';
import ShowWhen from 'merchant/components/ShowWhen';
import Toggle2FA from './Toggle2FA';
import ModalHeader from 'rzp/ui/ModalHeader';
import { openModal, closeModal } from 'rzp/modules/modals';
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
    openModal,
    closeModal,
  }
)
export default class TeamContainer extends Component {
  componentWillMount() {
    this.props.fetchTeamDetails({ merchant_id: this.props.merchant.current });
  }
  addNewMember = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <div>
          <ModalHeader
            title="Add Team Member"
            onCloseClick={this.props.closeModal}
          />
          <div class="modal-body">
            <NewInvitation />
          </div>
        </div>
      ),
    });
  };
  render() {
    let invitations = this.props.invitations;
    let users = this.props.users;
    let otherUsers = users.filter(
      user => user.email !== this.props.merchant.user.email
    );
    return (
      <div>
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <ShowWhen
              additionalCondition={user =>
                user.isOrgAllowedFunctionality('external_links')
              }
            >
              <a
                class="btn btn-link"
                href="https://razorpay.com/docs/team-support/"
                target="_blank"
              >
                Documentation &nbsp;
                <i class="icon icon-external-link" />
              </a>
            </ShowWhen>
          </div>
        </HeaderAction>
        <div class="content-wrapper content-sm content-sm-900">
          <Toggle2FA />
          {/* <NewInvitation /> */}
          {otherUsers.length ? (
            <div>
              <div class="panel-heading">
                <b>Team Members</b>
              </div>
              {/* <DataTable
              title="Disputes"
              columns={[
                email,
              ]}
              {...this.props}
            /> */}
              <table class="table table-noborder" style={{ margin: '0 12px' }}>
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
          ) : null}

          {!!otherUsers.length && <div class="section-divide" />}

          {invitations.length ? (
            <div>
              <div class="panel-heading">
                <b>Pending Invitations</b>
              </div>
              <table class="table table-noborder" style={{ margin: '0 12px' }}>
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
          ) : null}
        </div>
      </div>
    );
  }
}
