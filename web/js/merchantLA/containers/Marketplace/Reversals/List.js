import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import ReversalsListFilter from 'merchantLA/components/Marketplace/ReversalsListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchReversals as fetchAll } from 'merchantLA/modules/collection';

import {
  reversalId,
  transferId,
  amount,
  createdAt,
} from 'merchantLA/utils/item/pair';

@connect(state => state.reversals, { fetchAll })
export default class ReversalsListContainer extends ListContainer {
  render() {
    let { loading, items, error } = this.props;

    return (
      <div>
        <tabbed-container>
          <header id="marketplace-header">
            <NavLink to="/transfers">Transfers</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <div class="content-wrapper">
              <ReversalsListFilter
                form="reversalsListFilter"
                count={this.state.count}
                onSubmit={this.search}
              />

              <DataTable
                title="Reversals"
                columns={[reversalId, transferId, amount, createdAt]}
                count={this.state.count}
                skip={this.state.skip}
                paginate={this.paginate}
                {...this.props}
              />
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
