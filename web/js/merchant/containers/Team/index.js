import { connect } from 'react-redux';

import HeaderAction from 'rzp/ui/HeaderAction';
import DataTable from 'rzp/ui/Table/DataTable';
import ShowWhen from 'merchant/components/ShowWhen';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchTeam as fetchAll } from 'merchant/modules/collection';
import { removeUser, cancelInvitation } from 'merchant/modules/team';
import { showNotification } from 'rzp/modules/notifications';

import { roles, agentRole, RBLRoles } from 'rzp/utils/constants';

import Actions from './Actions';

const allRoles = {
  ...roles,
  ...agentRole,
  ...RBLRoles,
};

const name = {
  title: 'Member',
  value: user => (
    <div>
      <p>{user.name}</p>
      <p className="text-muted">{user.email}</p>
    </div>
  ),
};

const contactPhone = {
  title: 'Phone Number',
  value: user => <p>{user.contact_mobile || '--'}</p>,
};

const userRole = {
  title: 'Role',
  value: user => <p>{(allRoles[user.role] || {}).label}</p>,
};

@connect(state => ({ ...state.team }), {
  fetchAll,
  removeUser,
  cancelInvitation,
  showNotification,
})
export default class ManageTeamContainer extends ListContainer {
  actions = {
    title: '',
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
            columns={[name, contactPhone, userRole, this.actions]}
            {...this.props}
          />
        </div>
      </div>
    );
  }
}
