import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { transferId, recipient, amount, createdAt } from 'common/ui/item/pair';
import { RZPFeatures } from 'merchant/helpers/data';

import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';

import { fetchTransfers as fetchAll } from 'merchant/reducers/collection';

import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import TransfersListFilter from 'merchant/views/Marketplace/Transfers/components/TransfersListFilter';
import ListContainer from 'merchant/containers/ListContainer';
import TransferSource from './components/TransferSource';
import { RouteTransfersStatusLabel } from 'merchant/components/StatusLabel';
import SettlementStatus from './components/SettlementStatus';

const source = {
  title: 'Source Id',
  value: (item) => <TransferSource source={item.source} />,
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
    isDirectTransferEnabled: state.session.user.isDirectTransferEnabled,
  }),
  { fetchAll },
)
export default class TransfersListContainer extends ListContainer {
  render() {
    const { props } = this;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton feature={RZPFeatures.ROUTE} />

            <DocsLink url="https://razorpay.com/docs/route/" />

            {props.isDirectTransferEnabled && (
              <NavLink class="btn btn-primary" to="/route/transfers/direct_transfer">
                <i class="i i-plus" />
                Create Direct Transfer
              </NavLink>
            )}
          </div>
        </HeaderAction>

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
    );
  }
}
