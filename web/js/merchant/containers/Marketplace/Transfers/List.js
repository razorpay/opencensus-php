import { connect } from 'react-redux';

import {
  transferId,
  source,
  recipient,
  amount,
  createdAt,
} from 'common/ui/item/pair';
import { RZPFeatures } from 'merchant/helpers/data';

import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';

import { fetchTransfers as fetchAll } from 'merchant/reducers/collection';

import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import TransfersListFilter from 'merchant/components/Marketplace/TransfersListFilter';

import ListContainer from 'merchant/containers/ListContainer';

@connect(state => state.transfers, { fetchAll })
export default class TransfersListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton feature={RZPFeatures.ROUTE} />

            <DocsLink url="https://razorpay.com/docs/route/" />
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
