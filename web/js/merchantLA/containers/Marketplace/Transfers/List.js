import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import TransfersListFilter from 'merchantLA/components/Marketplace/TransfersListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchTransfers as fetchAll } from 'merchantLA/modules/collection';
import {
  transferId,
  recipient,
  amount,
  createdAt,
} from 'merchantLA/utils/item/pair';

@connect(state => state.transfers, { fetchAll })
export default class TransfersListContainer extends ListContainer {
  render() {
    return (
      <div>
        <tabbed-container>
          <header id="marketplace-header">
            <NavLink to="/transfers">Transfers</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <div class="content-wrapper">
              <TransfersListFilter
                form="transfersListFilter"
                count={this.state.count}
                onSubmit={this.search}
              />

              <DataTable
                title="Transfers"
                columns={[transferId, recipient, amount, createdAt]}
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
