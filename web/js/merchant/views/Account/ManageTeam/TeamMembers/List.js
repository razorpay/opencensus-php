import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';

import DataTable from 'common/ui/Table/DataTable';
import { role } from 'common/ui/item/pair';
import ListContainer from 'merchant/containers/ListContainer';

import { fetchTeam as fetchAll } from 'merchant/reducers/team';
import { unlockMember } from 'merchant/reducers/team';

import Actions from './Actions';

const contactPhone = {
  title: 'Phone Number',
  value: user => user.contact_mobile || '--',
};

const actions = {
  title: '',
  columnClass: 'text-right',
  value: member => <Actions member={member} />,
};

@connect(state => ({ ...state.team }), { fetchAll, unlockMember })
export default class MembersListContainer extends ListContainer {
  member = {
    title: 'Member',
    value: member => (
      <>
        {member.name && <p>{member.name}</p>}
        <p class="text-muted no-margin">{member.email}</p>
        {member.account_locked && (
          <AccountLocked
            unlockMember={this.props.unlockMember}
            memberId={member.id}
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
        columns={[this.member, contactPhone, role, actions]}
        {...this.props}
      />
    );
  }
}

function AccountLocked({ unlockMember, memberId }) {
  const unlock = () => {
    return unlockMember(memberId);
  };

  return (
    <span class="status-label label-pale-warning m-t">
      <i class="i i-info-circle text-warning" /> Account blocked due to multiple
      wrong login attempts{' '}
      <AsyncButton
        text="Unlock"
        pendingText="Unlocking"
        onClick={unlock}
        class="btn-link text-warning"
      />
    </span>
  );
}
