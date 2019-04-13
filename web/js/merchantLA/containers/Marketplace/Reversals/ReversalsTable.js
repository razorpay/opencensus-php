import { connect } from 'react-redux';
import ReversalsListFilter from 'merchantLA/components/Marketplace/ReversalsListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchReversals as fetchAll } from 'merchantLA/modules/collection';

import {
  reversalId,
  transferId,
  customerRefundId,
  amount,
  createdAt,
} from 'merchantLA/utils/item/pair';

@connect(
  state => ({
    ...state.reversals,
    user: state.session.user,
  }),
  { fetchAll }
)
export default class ReversalsTable extends ListContainer {
  render() {
    const isRefundsAllowed = this.props.user.features.includes(
      'allow_refunds_from_la'
    );
    return (
      <div class="content-wrapper">
        <ReversalsListFilter
          form="reversalsListFilter"
          count={this.state.count}
          onSubmit={this.search}
          user={this.props.user}
        />

        <DataTable
          title="Reversals"
          columns={
            isRefundsAllowed
              ? [reversalId, customerRefundId, transferId, amount, createdAt]
              : [reversalId, transferId, amount, createdAt]
          }
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
