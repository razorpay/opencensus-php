import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';

import HeaderAction from 'rzp/ui/HeaderAction';
import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';

import { removeUser, cancelInvitation, unlock } from 'merchant/modules/team';

import ListContainer from 'merchant/containers/ListContainer';

import Actions from './Actions';
import InvitationsList from './Invitations/List';
import MembersList from './Members/List';

export default class ManageTeamContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  unlock = member => () => {
    return this.context.confirm({
      header: 'Unblock the account?',
      message: (
        <>
          <p>
            Account of <strong>{member.name || member.email}</strong> has been
            blocked due to multiple wrong OTP attempts.
          </p>
          <p>Are you sure you want to unblock this account?</p>
        </>
      ),
      affirmativeLabel: 'Yes, Unblock',
      affirmativePendingLabel: 'Unblocking...',
      abortLabel: "No, Don't",
      action: () => {
        return this.props
          .unlock(member.id)
          .then(response => {
            if (response) {
              this.props.showNotification({
                type: 'success',
                message: 'User is successfully unblocked',
              });
            }
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
    });
  };

  actions = {
    title: 'Actions',
    columnClass: 'text-right',
    value: item => (
      <Actions
        item={item}
        removeUser={this.props.removeUser}
        cancelInvitation={this.props.cancelInvitation}
        updateMember={this.props.updateMember}
        updateInvitation={this.props.updateInvitation}
        openModal={this.props.openModal}
        closeModal={this.props.closeModal}
        allowDelete={allowDelete}
        onRemove={() => {
          this.props.showNotification({
            type: 'success',
            message: 'Team member has been removed successfully',
          });
        }}
      />
    ),
  };

  addNewMember = () => {
    const visibleFields = {
      email: true,
      role: true,
    };

    const defaults = {
      sender_name: this.props.user.name,
    };

    this.props.openModal({
      size: 'small',
      component: (
        <div>
          <ModalHeader
            title="Invite New Member"
            onCloseClick={this.props.closeModal}
            onSuccess={this.props.closeModal}
          />
          <div class="modal-body">
            <NewInvitation
              visibleFields={visibleFields}
              defaults={defaults}
              onSuccess={this.props.closeModal}
              onFormSubmit={this.props.sendInvitation}
              successMsg={data =>
                'Invitation has been successfully sent to ' + data.email
              }
              ctaText="Send Invitation"
            />
          </div>
        </div>
      ),
    });
  };

  nameColumn = {
    title: 'Member',
    value: user => (
      <div>
        {user.name && <p>{user.name}</p>}
        <p className="text-muted no-margin">{user.email}</p>
        {user.account_locked && (
          <ShowWhen
            myRole="owner admin"
            additionalCondition={user => user.isMerchantRestricted}
          >
            <span class="status-label label-pale-warning">
              <i class="i i-info-circle text-warning" /> Account blocked due to
              multiple wrong login attempts{' '}
              <AsyncButton
                text="Unlock"
                onClick={this.unlock(user)}
                class="btn-link text-warning"
              />
            </span>
          </ShowWhen>
        )}
      </div>
    ),
  };

  render() {
    return (
      <div class="content-wrapper content-sm">
        <Toggle2FA />
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <DocsLink url="https://razorpay.com/docs/team-support/" />
          </div>
        </HeaderAction>
        <div class="ManageTeam--list">
          <InvitationsList {...this.props} />

          <div class="m-t" />

          <MembersList {...this.props} />
        </div>
      </div>
    );
  }
}
