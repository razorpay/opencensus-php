import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import TetherComponent from 'react-tether';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import Header from 'rzp/ui/Header';
import BatchList from 'merchant/components/Refunds/BatchList';
import ListContainer from 'merchant/containers/ListContainer';
import BatchListFilter from 'merchant/components/Refunds/BatchListFilter';
import { fetchBatchUploads } from 'merchant/modules/refunds/batchuploads';

@connect(
  state => {
    return {
      mode: state.session.mode,
      ...state.batchuploads,
    };
  },
  { fetchBatchUploads }
)
export default class BatchListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchBatchUploads(params);
  }

  render() {
    let { loading, batchuploads, error, mode } = this.props;

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

            <NavLink
              class="btn btn-primary pull-right"
              to="/refunds/batchupload"
            >
              Click here to upload
            </NavLink>
          </div>
        </TetherComponent>

        <BatchListFilter
          form="batchListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        {error && <Alert type="error" message={error} />}

        <BatchList
          batchuploads={batchuploads}
          isLoading={loading}
          mode={mode}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={batchuploads.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
