import { Component } from 'react';
import { connect } from 'react-redux';
import TransfersListFilter
  from 'merchant/components/Marketplace/TransfersListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchReversals as fetchAll } from 'rzp/modules/collection';

import {
  reversalId as idColumn,
  reversalTransfer,
  amount,
  createdAt,
} from 'rzp/ui/Table/Column';

@connect(state => state.collection, { fetchAll })
export default class ReversalsListContainer extends ListContainer {
  render() {
    let { loading, items, error } = this.props;

    return (
      <div class="content-wrapper">
        <TransfersListFilter
          form="listFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Reversals"
          columns={[idColumn, reversalTransfer, amount, createdAt]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
