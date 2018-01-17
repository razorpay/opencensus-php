import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import AccountsList from 'merchant/components/Marketplace/Accounts/AccountsList';
import AccountCreation from 'merchant/containers/Marketplace/Accounts/New';
import AccountDetails from 'merchant/containers/Marketplace/Accounts/Details';
import AccountsListFilter from 'merchant/components/Marketplace/Accounts/AccountsListFilter';
import ListContainer from 'merchant/containers/ListContainer';
import * as AccountActions from 'merchant/modules/marketplace/accounts';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { luminateRow } from 'merchant/modules/app';

@connect(state => state.accounts, {
  ...AccountActions,
  ...ModalActions,
  showNotification,
  luminateRow,
})
export default class AccountsListContainer extends ListContainer {
  fetchEntityList({ id, ...params }) {
    if (id) {
      id = id.replace('acc_', '');
      return Promise.resolve(
        this.showAccountDetailsModal({
          id,
        })
      );
    } else {
      return this.props.fetchAccounts(params);
    }
  }

  fetchAccounts = (skip, count) => {
    this.props.fetchAccounts({ skip, count });
  };

  onAccountCreation = account => {
    // Reset pagination and fetch results of updated pagination
    const paginationSkip = 0;
    this.setState({ skip: paginationSkip });
    this.fetchAccounts(paginationSkip, this.state.count);

    // Open activation modal
    this.showAccountDetailsModal(account);
  };

  showAddAccountModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AccountCreation onSave={this.onAccountCreation} />,
    });
  };

  showAccountDetailsModal = account => {
    this.props.openModal({
      size: 'large',
      component: (
        <AccountDetails
          accountId={account.id}
          fetchAccounts={this.fetchAccounts}
          count={this.state.count}
          skip={this.state.skip}
          onCloseClick={() => this.highlightRowAndClose(account)}
        />
      ),
    });
  };

  highlightRowAndClose = account => {
    this.props.luminateRow(account.id);
    this.props.closeModal();
  };

  exportAccountsCSV = () => {
    this.props.showNotification({
      type: 'info',
      message: 'Your file will download shortly',
      hidePrevious: true,
    });

    return this.props
      .exportAccountsCSV()
      .then(response => {
        window.location.href = response.data.url;
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
          hidePrevious: true,
        });
      });
  };

  render() {
    let { loading, accounts } = this.props;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <a
              class="btn btn-link"
              href="https://docs.razorpay.com/v1/page/route"
              target="_blank"
            >
              Route APIs Documentation &nbsp;
              <i class="icon icon-external-link" />
            </a>

            <button class="btn btn-default" onClick={this.exportAccountsCSV}>
              <i class="icon icon-download" />
              <span>Export All (CSV)</span>
            </button>
            <button class="btn btn-primary" onClick={this.showAddAccountModal}>
              <i class="icon icon-plus" />
              <span>Add Account</span>
            </button>
          </div>
        </HeaderAction>

        <AccountsListFilter
          form="accountsListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <Alert type={status.type} message={status.message} />

        <AccountsList
          accounts={accounts}
          isLoading={loading}
          onEdit={this.showAccountDetailsModal}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={accounts.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
