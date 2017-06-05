import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import TetherComponent from 'react-tether';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import RefundsList from 'merchant/components/Refunds/RefundsList';
import ListContainer from 'merchant/containers/ListContainer';
import RefundsListFilter from 'merchant/components/Refunds/RefundsListFilter';
import { fetchRefunds as fetchAll } from 'rzp/modules/collection';

@connect(state => state.refunds, { fetchAll })
export default class RefundsListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchAll(params);
  }

  render() {
    let { loading, items, error } = this.props;

    return (
      <div class="content-wrapper">
        <TetherComponent
          target="#transactions-header"
          attachment="top right"
          targetAttachment="top right"
          offset="-8px 0"
        >
          <div />{/* required by react-tether */}
          <ShowWhen
            featureEnabled="Batchrefunds"
            myRole="owner manager operations admin finance"
          >
            <NavLink
              class="btn btn-primary pull-right"
              to="/refunds/batchupload"
            >
              Batch Refunds
            </NavLink>
          </ShowWhen>
        </TetherComponent>

        <RefundsListFilter
          form="refundListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        {error && <Alert type="error" message={error} />}

        <RefundsList refunds={items} isLoading={loading} />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={items.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
