import { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import AccountsList from 'merchant/components/Accounts/AccountsList';
import AccountCreation from 'merchant/containers/Accounts/New';
import AccountDetails from 'merchant/containers/Accounts/AccountDetails';
import AccountsListFilter
  from 'merchant/components/Accounts/AccountsListFilter';
import ListContainer from 'merchant/containers/ListContainer';
import * as AccountActions from 'merchant/modules/accounts';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

@connect(state => state.accounts, {
  ...AccountActions,
  ...ModalActions,
  showNotification,
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

  showAddAccountModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AccountCreation onSave={this.showAccountDetailsModal} />,
    });
  };

  showAccountDetailsModal = account => {
    this.props.openModal({
      size: 'large',
      component: (
        <AccountDetails
          accountId={account.id}
          onCloseClick={() => this.highlightRowAndClose(account)}
        />
      ),
    });
  };

  highlightRowAndClose = account => {
    this.props.highlightItemRow(account);
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
    let { loading, accounts, highlightRowId } = this.props;
    let status = this.state.status;

    return (
      <div class="react-root">
        <Header title="Marketplace Accounts" />

        <div class="content-wrapper">
          <div class="panel panel-default">
            <div class="panel-heading">
              All Accounts

              <div class="btn-toolbar pull-right">
                <button
                  class="btn btn-sm btn-default"
                  onClick={this.exportAccountsCSV}
                >
                  <i class="fa fa-download" />
                  <span>Export All (CSV)</span>
                </button>
                <button
                  class="btn btn-sm btn-primary"
                  onClick={this.showAddAccountModal}
                >
                  <i class="fa fa-plus" />
                  <span>Add Account</span>
                </button>
              </div>
            </div>

            <div class="panel-body">
              <AccountsListFilter
                form="accountsListFilter"
                count={this.state.count}
                onSubmit={this.search}
              />
            </div>

            <Alert type={status.type} message={status.message} />

            <AccountsList
              accounts={accounts}
              isLoading={loading}
              highlightRow={account => account.id === highlightRowId}
              onEdit={this.showAccountDetailsModal}
            />

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={accounts.length}
              onClick={this.fetchAll}
            />
          </div>
        </div>
      </div>
    );
  }
}
