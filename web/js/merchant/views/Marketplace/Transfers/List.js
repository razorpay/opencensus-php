import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { withRouter } from 'common/deprecated/withRouter';
import ProductWrapper from 'common/ui/ProductWrapper';
import DataTable from 'common/ui/Table/DataTable';
import { transferId, recipient, amount, createdAt } from 'common/ui/item/pair';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { RouteTransfersStatusLabel } from 'merchant/components/StatusLabel';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ListContainer from 'merchant/containers/ListContainer';
import { RZPFeatures } from 'merchant/helpers/data';
import { fetchTransfers as fetchAll } from 'merchant/reducers/collection';
import { navItems } from 'merchant/views/Marketplace/NavItems';
import TransfersListFilter from 'merchant/views/Marketplace/Transfers/components/TransfersListFilter';

import SettlementStatus from './components/SettlementStatus';
import TransferSource from './components/TransferSource';

const source = {
  title: 'Source Id',
  value: (item) => <TransferSource source={item.source} initiatePoint="transfers-table" />,
};

const transferStatus = {
  title: 'Transfer Status',
  value: (item) => <RouteTransfersStatusLabel status={item.status || ''} />,
};

const settlementStatus = {
  title: 'Settlement Status',
  value: (item) => <SettlementStatus status={item.settlement_status} />,
};

@connect(
  (state) => ({
    ...state.transfers,
    user: state.session.user,
  }),
  { fetchAll },
)
class TransfersListContainer extends ListContainer {
  render() {
    const { isPlatformFeeTabEnabled, user, isPartnerPlatformFeeEnabled } = this.props;
    return (
      <ProductWrapper
        tabsData={navItems(user, isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled)}
        extra={
          <>
            <TakeATourButton feature={RZPFeatures.ROUTE} />

            <DocsLink url="ROUTE_TRANSFER_DOC_URL" />

            {user.isDirectTransferEnabled && (
              <NavLink class="btn btn-primary" to="/route/transfers/direct_transfer">
                <i class="i i-plus" />
                Create Direct Transfer
              </NavLink>
            )}
          </>
        }
      >
        <content>
          <div className="content-wrapper">
            <TestModeBanner />
            <TransfersListFilter
              form="transfersListFilter"
              count={this.state.count}
              onSubmit={this.search}
            />

            <DataTable
              title="Transfers"
              columns={[
                transferId,
                source,
                recipient,
                amount,
                createdAt,
                transferStatus,
                settlementStatus,
              ]}
              count={this.state.count}
              skip={this.state.skip}
              paginate={this.paginate}
              {...this.props}
            />
          </div>
        </content>
      </ProductWrapper>
    );
  }
}

export default withRouter(TransfersListContainer);
