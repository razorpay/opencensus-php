import { connect } from 'react-redux';

import DataTable from 'rzp/ui/Table/DataTable';
import { role } from 'rzp/ui/item/pair';
import ListContainer from 'merchant/containers/ListContainer';

import { fetchTeam as fetchAll } from 'merchant/modules/collection';

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

@connect(state => ({ ...state.team }), { fetchAll })
export default class MembersListContainer extends ListContainer {
  render() {
    return (
      <DataTable
        title="Members"
        columns={[member, contactPhone, role]}
        {...this.props}
      />
    );
  }
}
