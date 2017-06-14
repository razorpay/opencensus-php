import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import VirtualAccountsListFilter
  from 'merchant/components/VirtualAccounts/ListFilter';
import CreateVirtualAccount from './CreateVirtualAccount';
import { openModal } from 'rzp/modules/modals';
import {
  fetchVirtualAccounts as fetchAll,
} from 'merchant/modules/virtualaccounts/list';
import {
  virtualAccountId,
  beneficiaryName,
  amountPaid,
  status,
  createdAt,
} from 'rzp/ui/Table/column';

@connect(state => state.virtualaccounts, { fetchAll, openModal })
export default class VirtualAccountsListContainer extends ListContainer {
  showCreateVAModal = () => {
    this.props.openModal({
      size: 'small',
      component: <CreateVirtualAccount />,
    });
  };

  render() {
    return (
      <tabbed-container>
        <header id="#va-header">
          <NavLink to="/virtualaccounts">Virtual Accounts</NavLink>

          <div class="btn-toolbar pull-right">
            <button class="btn btn-primary" onClick={this.showCreateVAModal}>
              <i class="icon icon-plus" />
              <span>Create Virtual Account</span>
            </button>
          </div>
        </header>

        <div class="content-wrapper">
          <VirtualAccountsListFilter
            form="virtualAccountsListFilter"
            count={this.state.count}
            onSubmit={this.search}
          />

          <DataTable
            title="Virtual Accounts"
            columns={[
              virtualAccountId,
              beneficiaryName,
              amountPaid,
              status,
              createdAt,
            ]}
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
