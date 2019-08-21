import { connect } from 'react-redux';

import DataTable from 'rzp/ui/Table/DataTable';
import { role } from 'rzp/ui/item/pair';
import ListContainer from 'merchant/containers/ListContainer';

import { fetchTeam as fetchAll } from 'merchant/modules/collection';

import Actions from './Actions';

const member = {
  title: 'Member',
  value: item => (
    <>
      {item.name && <p>{item.name}</p>}
      <p className="text-muted no-margin">{item.email}</p>
    </>
  ),
};

const contactPhone = {
  title: 'Phone Number',
  value: user => user.contact_mobile || '--',
};

const actions = {
  title: '',
  value: user => <Actions user={user} />,
};

@connect(state => ({ ...state.team }), { fetchAll })
export default class MembersListContainer extends ListContainer {
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
        columns={[member, contactPhone, role, actions]}
        {...this.props}
      />
    );
  }
}
