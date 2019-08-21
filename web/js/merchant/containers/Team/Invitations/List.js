import { connect } from 'react-redux';

import DataTable from 'rzp/ui/Table/DataTable';
import { role } from 'rzp/ui/item/pair';
import ListContainer from 'merchant/containers/ListContainer';

import { fetchInvitations as fetchAll } from 'merchant/modules/collection';

import Actions from './Actions';

const email = {
  title: 'Email',
  value: item => item.email,
};

const actions = {
  title: '',
  columnClass: 'text-right',
  value: item => <Actions item={item} />,
};

@connect(state => ({ ...state.invitations }), { fetchAll })
export default class InvitationsListContainer extends ListContainer {
  render() {
    const { items, loading } = this.props;
    return (
      <DataTable
        title="Invitations"
        panelHeading={
          !loading && {
            title: (
              <>
                Pending Invitations (<small class="text-muted">
                  {items.length}
                </small>)
              </>
            ),
          }
        }
        columns={[email, role, actions]}
        {...this.props}
      />
    );
  }
}
