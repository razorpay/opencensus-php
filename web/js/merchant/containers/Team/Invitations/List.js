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

@connect(
  state => ({ ...state.invitations, loggedInUser: state.session.user.user }),
  { fetchAll }
)
export default class InvitationsListContainer extends ListContainer {
  actions = {
    title: '',
    columnClass: 'text-right',
    value: invitation => (
      <Actions
        invitation={invitation}
        loggedInUserName={this.props.loggedInUser.name}
      />
    ),
  };

  render() {
    const { items, loading } = this.props;
    return (
      <DataTable
        title="Invitations"
        panelHeading={
          !loading && items.length
            ? {
                title: (
                  <>
                    Pending Invitations (<small class="text-muted">
                      {items.length}
                    </small>)
                  </>
                ),
              }
            : undefined
        }
        columns={[email, role, this.actions]}
        {...this.props}
      />
    );
  }
}
