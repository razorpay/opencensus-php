import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import VirtualAccountsListFilter
  from 'merchant/components/VirtualAccounts/ListFilter';
import CreateVirtualAccount from './CreateVirtualAccount';
import { fetchConfig } from 'merchant/modules/config';
import { openModal } from 'rzp/modules/modals';
import {
  fetchVirtualAccounts as fetchAll,
} from 'merchant/modules/virtualaccounts';
import {
  virtualAccountId,
  accountDescription,
  amountPaid,
  status,
  createdAt,
} from 'rzp/ui/item/pair';

@connect(state => state.virtualaccounts, { fetchAll, fetchConfig, openModal })
export default class VirtualAccountsListContainer extends ListContainer {
  componentWillMount() {
    super.componentWillMount();
    this.props.fetchConfig();
  }

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

          <HeaderAction>
            <div class="btn-toolbar">
              <button class="btn btn-primary" onClick={this.showCreateVAModal}>
                <i class="icon icon-plus" />
                <span>Create Virtual Account</span>
              </button>
            </div>
          </HeaderAction>
        </header>

        <content>
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
                accountDescription,
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
        </content>
      </tabbed-container>
    );
  }
}
