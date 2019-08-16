import { connect } from 'react-redux';

import HeaderAction from 'rzp/ui/HeaderAction';
import DataTable from 'rzp/ui/Table/DataTable';
import ModalHeader from 'rzp/ui/ModalHeader';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';

import { openModal, closeModal } from 'rzp/modules/modals';
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

import ListContainer from 'merchant/containers/ListContainer';

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
  actions = ({ allowDelete }) => ({
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
        allowDelete={allowDelete}
        onRemove={() => {
          this.props.showNotification({
            type: 'success',
            message: 'Team member has been removed successfully',
          });
        }}
      />
    ),
  });

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

  render() {
    // check for allowUpdate happens inside actions component since it depends when item is invitation or user
    const allowDelete = showWhenUtil({
      additionalCondition: user => user.isAllowedEdit('team'),
    });

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
            <ShowWhen additionalCondition={user => user.isAllowedEdit('team')}>
              <button
                class="btn btn-primary pull-right"
                onClick={this.addNewMember}
              >
                Invite New User
              </button>
            </ShowWhen>
          </div>
        </HeaderAction>
        <div class="ManageTeam--list">
          <DataTable
            title="Team Members"
            columns={[
              name,
              contactPhone,
              userRole,
              this.actions({ allowDelete }),
            ]}
            {...this.props}
          />
        </div>
      </div>
    );
  }
}
