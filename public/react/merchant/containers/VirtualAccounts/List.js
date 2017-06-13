import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import VirtualAccountsListFilter
  from 'merchant/components/VirtualAccounts/ListFilter';
import { fetchVirtualAccounts as fetchAll } from 'rzp/modules/collection';
import {
  virtualAccountId,
  amountPaid,
  status,
  createdAt,
} from 'rzp/ui/Table/column';

@connect(state => state.virtualaccounts, { fetchAll })
export default class VirtualAccountsListContainer extends ListContainer {
  render() {
    debugger;
    return (
      <tabbed-container>
        <header id="#va-header">
          <NavLink to="/virtualaccounts">Virtual Accounts</NavLink>
        </header>

        <div class="content-wrapper">
          <VirtualAccountsListFilter
            form="virtualAccountsListFilter"
            count={this.state.count}
            onSubmit={this.search}
          />

          <DataTable
            title="Virtual Accounts"
            columns={[virtualAccountId, amountPaid, status, createdAt]}
            count={this.state.count}
            skip={this.state.skip}
            paginate={this.paginate}
            {...this.props}
          />
        </div>
      </tabbed-container>
    );
  }
}
