import { connect } from 'react-redux';
import HeaderAction from 'rzp/ui/HeaderAction';
import DataTable from 'rzp/ui/Table/DataTable';
import ShowWhen from 'merchant/components/ShowWhen';
import ModalHeader from 'rzp/ui/ModalHeader';
import { openModal, closeModal } from 'rzp/modules/modals';

import ListContainer from 'merchant/containers/ListContainer';
import { fetchTeam as fetchAll } from 'merchant/modules/collection';
import {
  removeUser,
  cancelInvitation,
  updateMember,
  updateInvitation,
  sendInvitation,
} from 'merchant/modules/team';
import { showNotification } from 'rzp/modules/notifications';

import { roles, agentRole, RBLRoles } from 'rzp/utils/constants';

import Actions from './Actions';
import Toggle2FA from './Toggle2FA';
import NewInvitation from './NewInvitation';

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

@connect(state => ({ ...state.team, user: state.session.user.user }), {
  fetchAll,
  removeUser,
  updateMember,
  cancelInvitation,
  updateInvitation,
  showNotification,
  sendInvitation,
  openModal,
  closeModal,
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
        updateMember={this.props.updateMember}
        updateInvitation={this.props.updateInvitation}
        openModal={this.props.openModal}
        closeModal={this.props.closeModal}
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
              extraFields={{ sender_name: this.props.user.name }}
              modalType="invite"
              onSuccess={this.props.closeModal}
              onFormSubmit={this.props.sendInvitation}
              successMsg={data =>
                'Invitation has been successfully sent to ' + data.email
              }
            />
          </div>
        </div>
      ),
    });
  };

  render() {
    return (
      <div class="content-wrapper content-sm">
        <Toggle2FA />
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
            <button
              class="btn btn-primary pull-right"
              onClick={this.addNewMember}
            >
              Invite New User
            </button>
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
