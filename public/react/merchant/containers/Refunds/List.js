import { Component } from 'react';
import { connect } from 'react-redux';
import TetherComponent from 'react-tether';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import Header from 'rzp/ui/Header';
import ShowWhen from 'merchant/components/ShowWhen';
import RefundsList from 'merchant/components/Refunds/RefundsList';
import ListContainer from 'merchant/containers/ListContainer';
import RefundsListFilter from 'merchant/components/Refunds/RefundsListFilter';
import { fetchRefunds } from 'merchant/modules/refunds/list';

@connect(state => state.refunds, { fetchRefunds })
export default class RefundsListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchRefunds(params);
  }

  render() {
    let { loading, refunds, error } = this.props;

    return (
      <div class="content-wrapper">
        <TetherComponent
          target="#transactions-header"
          attachment="top right"
          targetAttachment="top right"
          offset="-8px 20px"
        >
          <div />{/* required by react-tether */}
          <ShowWhen
            featureEnabled="Batchrefunds"
            myRole="owner manager operations admin finance"
          >
            <a
              class="btn btn-primary pull-right"
              href="#/app/refunds/batchupload"
            >
              Batch Refunds
            </a>
          </ShowWhen>
        </TetherComponent>

        <RefundsListFilter
          form="refundListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        {error && <Alert type="error" message={error} />}

        <RefundsList refunds={refunds} isLoading={loading} />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={refunds.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
