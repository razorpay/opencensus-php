import { connect } from 'react-redux';

import DataTable from 'rzp/ui/Table/DataTable';
import { role } from 'rzp/ui/item/pair';
import ListContainer from 'merchant/containers/ListContainer';

import { fetchInvitations as fetchAll } from 'merchant/modules/collection';

const email = {
  title: 'Email',
  value: item => item.email,
};

@connect(state => ({ ...state.invitations }), { fetchAll })
export default class InvitationsListContainer extends ListContainer {
  render() {
    return (
      <DataTable title="Invitations" columns={[email, role]} {...this.props} />
    );
  }
}
