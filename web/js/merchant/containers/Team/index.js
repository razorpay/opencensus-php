import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';

import HeaderAction from 'rzp/ui/HeaderAction';
import DataTable from 'rzp/ui/Table/DataTable';
import ShowWhen from 'merchant/components/ShowWhen';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchTeam as fetchAll } from 'merchant/modules/collection';
import { removeUser, cancelInvitation, unlock } from 'merchant/modules/team';
import { showNotification } from 'rzp/modules/notifications';

import { roles, agentRole, RBLRoles } from 'rzp/utils/constants';

import Actions from './Actions';

const allRoles = {
  ...roles,
  ...agentRole,
  ...RBLRoles,
};

const contactPhone = {
  title: 'Phone Number',
  value: user => user.contact_mobile || '--',
};

const userRole = {
  title: 'Role',
  value: user => (allRoles[user.role] || {}).label,
};

@connect(state => ({ ...state.team }), {
  fetchAll,
  removeUser,
  cancelInvitation,
  showNotification,
  unlock,
})
export default class ManageTeamContainer extends ListContainer {
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
        onRemove={() => {
          this.props.showNotification({
            type: 'success',
            message: 'Team member has been removed successfully',
          });
        }}
      />
    ),
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
        <div class="ManageTeam--list">
          <DataTable
            title="Team Members"
            columns={[this.nameColumn, contactPhone, userRole, this.actions]}
            {...this.props}
          />
        </div>
      </div>
    );
  }
}
