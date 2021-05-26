import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';

import DataTable from 'common/ui/Table/DataTable';
import { role } from 'common/ui/item/pair';
import ListContainer from 'merchant/containers/ListContainer';

import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchTeam as fetchAll } from 'merchant/reducers/team';
import { unlockMember, unverifyContact } from 'merchant/reducers/team';

import Actions from './Actions';

const actions = {
  title: '',
  columnClass: 'text-right',
  value: (member) => <Actions member={member} />,
};

@connect(
  (state) => ({
    currentUser: state.session.user.user,
    ...state.team,
  }),
  {
    fetchAll,
    unlockMember,
    unverifyContact,
    showNotification,
  },
)
export default class MembersListContainer extends ListContainer {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  member = {
    title: 'Member',
    value: (member) => (
      <>
        {member.name && <p>{member.name}</p>}
        <p class="text-muted no-margin">{member.email}</p>
        {member.account_locked && (
          <AccountLocked
            unlockMember={this.props.unlockMember}
            memberId={member.id}
            showNotification={this.props.showNotification}
          />
        )}
      </>
    ),
  };

  contactPhone = {
    title: 'Phone Number',
    value: (member) => (
      <>
        <p>{member.contact_mobile || '--'}</p>
        {!member.org_enforced_second_factor_auth &&
          member.id !== this.props.currentUser.id &&
          !!member.contact_mobile &&
          member.contact_mobile_verified && (
            <RaiseContactMobileLost
              memberId={member.id}
              memberEmail={member.email}
              unverifyContact={this.props.unverifyContact}
              showNotification={this.props.showNotification}
              confirm={this.context.confirm}
            />
          )}
      </>
    ),
  };

  render() {
    const { items, loading } = this.props;
    return (
      <DataTable
        title="Members"
        panelHeading={
          !loading && {
            title: (
              <>
                Team Members (<small class="text-muted">{items.length}</small>)
              </>
            ),
          }
        }
        columns={[this.member, this.contactPhone, role, actions]}
        {...this.props}
      />
    );
  }
}

function AccountLocked({ unlockMember, memberId, showNotification }) {
  const unlock = () => {
    return unlockMember(memberId)
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Team member account is successfully unlocked',
        });
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: (errors || [])[0],
        });
      });
  };

  return (
    <span class="status-label label-pale-warning m-t">
      <i class="i i-info-circle text-warning" /> Account blocked due to multiple wrong login
      attempts{' '}
      <AsyncButton
        text="Unlock"
        pendingText="Unlocking"
        onClick={unlock}
        class="btn-link text-warning"
      />
    </span>
  );
}

function RaiseContactMobileLost({
  memberId,
  memberEmail,
  unverifyContact,
  showNotification,
  confirm,
}) {
  const unverify = () => {
    return unverifyContact(memberId)
      .then(() => {
        showNotification({
          type: 'success',
          message: '2FA is successfully invalidated for user account',
        });
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: (errors || [])[0],
        });
      });
  };

  const unverifyAfterConfirm = () => {
    confirm({
      header: 'Invalidate 2FA',
      affirmativeLabel: 'Confirm',
      message: <>This will invalidate 2FA for the user {memberEmail}. Click confirm to continue</>,
      action: unverify,
    });
  };

  return (
    <span class="small">
      <AsyncButton
        onClick={unverifyAfterConfirm}
        class="btn-link no-padding"
        pendingText="Invalidating..."
      >
        Invalidate 2FA
      </AsyncButton>
    </span>
  );
}
