import { Component } from 'react';
import { connect } from 'react-redux';
import TransfersListFilter
  from 'merchant/components/Marketplace/TransfersListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchTransfers as fetchAll, destroy } from 'rzp/modules/collection';
import {
  transferId,
  transferSource,
  transferRecipient,
  amount,
  createdAt,
} from 'rzp/ui/Table/Column';

@connect(state => state.collection, { fetchAll, destroy })
export default class TransfersListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <TransfersListFilter
          form="transfersListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Transfers"
          columns={[
            transferId,
            transferSource,
            transferRecipient,
            amount,
            createdAt,
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
