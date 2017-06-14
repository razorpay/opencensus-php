import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import RefundsListFilter from 'merchant/components/Refunds/RefundsListFilter';
import { fetchRefunds as fetchAll } from 'rzp/modules/collection';
import {
  refundId,
  paymentId,
  currency,
  amount,
  createdAt,
} from 'rzp/ui/Table/column';

@connect(state => state.refunds, { fetchAll })
export default class RefundsListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <RefundsListFilter
          form="refundListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Refunds"
          columns={[refundId, paymentId, currency, amount, createdAt]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
