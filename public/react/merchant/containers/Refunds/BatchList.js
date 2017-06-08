import { Component } from 'react';
import { connect } from 'react-redux';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import BatchListFilter from 'merchant/components/Refunds/BatchListFilter';
import {
  fetchBatchUploads as fetchAll,
} from 'merchant/modules/refunds/batchuploads';
import { destroy } from 'rzp/modules/collection';
import {
  batchId,
  batchCount,
  status,
  batchDownload,
} from 'rzp/ui/Table/column';

@connect(
  state => {
    return {
      mode: state.session.mode,
      ...state.collection,
    };
  },
  { fetchAll, destroy }
)
export default class BatchListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <BatchListFilter
          form="batchListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Batch Uploads"
          columns={[
            batchId,
            batchCount,
            status,
            batchDownload(this.props.mode),
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
