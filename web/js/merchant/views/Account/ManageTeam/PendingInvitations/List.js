import { connect } from 'react-redux';

import DataTable from 'common/ui/Table/DataTable';
import { role } from 'common/ui/item/pair';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchInvitations as fetchAll } from 'merchant/reducers/collection';
import Actions from './Actions';

const email = {
  title: 'Email',
  value: (item) => item.email,
};

class InvitationsListContainer extends ListContainer {
  actions = {
    title: '',
    columnClass: 'text-right',
    value: (invitation) => (
      <Actions
        invitation={invitation}
        loggedInUserName={this.props.loggedInUser.name}
        pendingInvitationLength={this.props.items.length}
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
                    Pending Invitations (<small class="text-muted">{items.length}</small>)
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

const mapStateToProps = (state) => ({
  ...state.invitations,
  loggedInUser: state.session.user.user,
});

export default connect(mapStateToProps, {
  fetchAll,
})(InvitationsListContainer);
