import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { transferId, recipient, amount, createdAt } from 'common/ui/item/pair';
import { source as sourceId } from 'common/ui/item/id';
import { RZPFeatures } from 'merchant/helpers/data';

import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';

import { fetchTransfers as fetchAll } from 'merchant/reducers/collection';

import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import TransfersListFilter from 'merchant/views/Marketplace/Transfers/components/TransfersListFilter';

import ListContainer from 'merchant/containers/ListContainer';
import TransferSource from './components/TransferSource';

const source = {
  title: 'Source',
  value: (item) => <TransferSource source={item.source} />,
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
          columns={[transferId, source, recipient, amount, createdAt]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
