import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import DataTable from 'common/ui/Table/DataTable';
import { role } from 'common/ui/item/pair';
import { isJKOfflineMerchant } from 'merchant/components/Sidebar/helpers';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchInvitations as fetchAll } from 'merchant/reducers/collection';

import Actions from './Actions';
import MerchantInvitationJKOrg from '../components/MerchantInvitationJK';

const email = {
  title: 'Email',
  value: (item) => item.email,
};

class InvitationsListContainer extends ListContainer {
  actions = {
    title: '',
    columnClass: 'text-right',
    value: (invitation, isJkOrg) => (
      <Actions
        invitation={invitation}
        loggedInUserName={this.props.loggedInUser.name}
        pendingInvitationLength={this.props.items.length}
        isJkOrg={isJkOrg}
      />
    ),
  };

  render() {
    const { items, loading, org, isMobile, user } = this.props;

    // Show update UI if jk offline org and mobile
    const isJkOrg = isJKOfflineMerchant(org, user) && isMobile;

    if (isJkOrg) {
      return <MerchantInvitationJKOrg items={items} actions={this.actions} />;
    }

    return (
      <DataTable
        title="Invitations"
        panelHeading={
          !loading && items.length
            ? {
                title: (
                  <>
                    Pending Invitations (<small className="text-muted">{items.length}</small>)
                  </>
                ),
              }
            : undefined
        }
        columns={[
          { ...email, width: '4fr' },
          { ...role, width: '1fr' },
          { ...this.actions, width: '1fr' },
        ]}
        progressLoader={true}
        {...this.props}
      />
    );
  }
}

const mapStateToProps = (state) => ({
  ...state.invitations,
  loggedInUser: state.session.user.user,
  user: state.session.user,
  org: state.session.org,
  isMobile: state.app.isMobileResolution,
});

export default connect(mapStateToProps, {
  fetchAll,
})(withRouter(InvitationsListContainer));
