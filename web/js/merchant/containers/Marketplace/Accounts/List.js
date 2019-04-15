import { connect } from 'react-redux';
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

import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';

@connect(state => state.accounts, {
  ...AccountActions,
  ...ModalActions,
  showNotification,
  luminateRow,
})
export default class AccountsListContainer extends ListContainer {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  onToggleDashboardAccess = (account, checked, cb) => {
    return this.context
      .confirm({
        header: `${checked ? 'Enable' : 'Disable'} Dashboard Access?`,
        message: () => (
          <div class="text-semi-muted">
            <p>
              {`Are you sure you want to ${
                checked ? 'Enable' : 'Disable'
              } dashboard access for this linked account`}
            </p>
          </div>
        ),
        affirmativeLabel: `${checked ? 'Enable' : 'Disable'}`,
        affirmativePendingLabel: `${checked ? 'Enabling' : 'Disabling'}`,
        abortLabel: 'Cancel',
        action: () => {
          return this.props
            .toggleDashboardAccess({
              dashboard_access: checked,
              accountId: account.id,
            })
            .then(resp => {
              cb(true);

              if (resp) {
                this.props.showNotification({
                  type: 'success',
                  message: `Dashboard access ${
                    checked ? 'Enabled' : 'Disabled'
                  } for merchant "${account.name}"`,
                });

                this.resetPagination();

                return resp;
              } else {
                throw 'Some network error has occurred';
              }
            })
            .catch(({ errors }) => {
              if (
                !errors ||
                (errors instanceof Array === true &&
                  (!errors.length || !errors[0]))
              ) {
                errors = 'Some network error has occurred';
              }

              this.props.showNotification({
                type: 'error',
                message: errors,
              });

              cb(false);

              throw errors;
            });
        },
      })
      .catch(() => {
        cb(false);
      }); // dummy catch to handle confirm abort rejection
  };

  onToggleAllowRefunds = (account, checked, cb) => {
    let header = `${checked ? 'Enable' : 'Disable'} Allow Refunds`,
      message = (
        <div class="text-semi-muted">
          <p>
            {`Are you sure you want to ${
              checked ? 'Enable' : 'Disable'
            } allow refunds for this linked account`}
          </p>
        </div>
      ),
      data = {
        reversals_access: checked,
        accountId: account.id,
      };

    if (!account.dashboard_access && checked) {
      header = 'Also enable Dashboard Access?';
      message = (
        <div class="text-semi-muted">
          <p>
            Enabling Refund to customer will also enable Dashboard access to the
            Linked Account.
          </p>
        </div>
      );
      data = {
        reversals_access: checked,
        accountId: account.id,
        dashboard_access: checked,
      };
    }

    return this.context
      .confirm({
        header: header,
        message: () => message,
        affirmativeLabel: `${checked ? 'Enable' : 'Disable'}`,
        affirmativePendingLabel: `${checked ? 'Enabling' : 'Disabling'}`,
        abortLabel: 'Cancel',
        action: () => {
          return this.props
            .toggleAllowRefunds(data)
            .then(resp => {
              cb(true);

              if (resp) {
                this.props.showNotification({
                  type: 'success',
                  message: `Dashboard access ${
                    checked ? 'Enabled' : 'Disabled'
                  } for merchant "${account.name}"`,
                });

                this.resetPagination();

                return resp;
              } else {
                throw 'Some network error has occurred';
              }
            })
            .catch(({ errors }) => {
              if (
                !errors ||
                (errors instanceof Array === true &&
                  (!errors.length || !errors[0]))
              ) {
                errors = 'Some network error has occurred';
              }

              this.props.showNotification({
                type: 'error',
                message: errors,
              });

              cb(false);

              throw errors;
            });
        },
      })
      .catch(() => {
        cb(false);
      }); // dummy catch to handle confirm abort rejection
  };

  fetchList({ id, ...rest }) {
    if (id && id.indexOf('acc_') > -1) {
      id = id.replace('acc_', '');
    }
    return this.props.fetchAccounts({ id, ...rest });
  }

  fetchAccounts = (skip, count) => {
    this.props.fetchAccounts({ skip, count });
  };

  onAccountCreation = account => {
    this.resetPagination();
    this.showAccountDetailsModal(account); // Open activation modal
  };

  resetPagination = account => {
    // Reset pagination and fetch results of updated pagination
    const paginationSkip = 0;
    this.setState({ skip: paginationSkip });
    this.fetchAccounts(paginationSkip, this.state.count);
  };

  showAddAccountModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AccountCreation onSave={this.onAccountCreation} />,
    });
  };

  showEditAccountModal = account => {
    this.props.openModal({
      size: 'small',
      component: (
        <AccountCreation onSave={this.resetPagination} accountData={account} />
      ),
    });
  };

  showAccountDetailsModal = account => {
    this.props.closeModal();
    this.setState({ showAccountDetailsFor: account.id });
  };

  highlightRowAndClose = accountId => {
    this.props.luminateRow(accountId);
    this.setState({ showAccountDetailsFor: null });
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
            <ShowWhen
              additionalCondition={user =>
                user.isOrgAllowedFunctionality('external_links')
              }
            >
              <a
                class="btn btn-link"
                href="https://docs.razorpay.com/v1/page/route"
                target="_blank"
              >
                Route APIs Documentation &nbsp;
                <i class="i i-external-link" />
              </a>
            </ShowWhen>

            <button class="btn btn-default" onClick={this.exportAccountsCSV}>
              <i class="i i-download" />
              <span>Export All (CSV)</span>
            </button>

            <ShowWhen
              additionalCondition={user => user.isAllowedEdit('accounts')}
            >
              <button
                class="btn btn-primary"
                onClick={this.showAddAccountModal}
              >
                <i class="i i-plus" />
                <span>Add Account</span>
              </button>
            </ShowWhen>
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
          showEditAccountModal={this.showEditAccountModal}
          onEdit={this.showAccountDetailsModal}
          onToggleDashboardAccess={
            showWhenUtil({
              additionalCondition: user => user.isAllowedEdit('accounts'),
            })
              ? this.onToggleDashboardAccess
              : undefined
          }
          onToggleAllowRefunds={
            showWhenUtil({
              additionalCondition: user => user.isAllowedEdit('accounts'),
            })
              ? this.onToggleAllowRefunds
              : undefined
          }
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={accounts.length}
          onClick={this.paginate}
        />
        {this.state.showAccountDetailsFor && (
          <AccountDetails
            accountId={this.state.showAccountDetailsFor}
            onClose={this.highlightRowAndClose}
            onSubmitSuccessCB={() => {
              this.props.showNotification({
                type: 'success',
                message: 'The account has been activated',
              });

              this.fetchAccounts(this.state.skip, this.state.count);
            }}
          />
        )}
      </div>
    );
  }
}
