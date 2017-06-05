import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import TetherComponent from 'react-tether';
import ShowWhen from 'merchant/components/ShowWhen';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import RefundsListFilter from 'merchant/components/Refunds/RefundsListFilter';
import { fetchRefunds as fetchAll } from 'rzp/modules/collection';
import {
  refundId,
  refundPayment,
  amount,
  createdAt,
} from 'rzp/ui/Table/Column';

@connect(state => state.collection, { fetchAll })
export default class RefundsListContainer extends ListContainer {
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

        <DataTable
          title="Refunds"
          columns={[refundId, refundPayment, amount, createdAt]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
