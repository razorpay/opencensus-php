import { Component } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import DataTable from 'rzp/ui/Table/DataTable';
import TetherComponent from 'react-tether';
import ListContainer from 'merchant/containers/ListContainer';
import BatchListFilter from 'merchant/components/Refunds/BatchListFilter';
import {
  fetchBatchUploads as fetchAll,
} from 'merchant/modules/refunds/batchuploads';
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
      ...state.batchuploads,
    };
  },
  { fetchAll }
)
export default class BatchListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <TetherComponent
          target="#transactions-header"
          attachment="top right"
          targetAttachment="top right"
          offset="-8px 0"
        >
          <div />{/* required by react-tether */}
          <div class="btn-toolbar pull-right">
            <a
              class="btn btn-link"
              href="https://docs.razorpay.com/v1/page/batch-refunds"
              target="_blank"
            >
              Documentation &nbsp;
              <i class="icon icon-external-link" />
            </a>

            <Link class="btn btn-primary pull-right" to="/refunds/batchupload">
              Click here to upload
            </Link>
          </div>
        </TetherComponent>

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
